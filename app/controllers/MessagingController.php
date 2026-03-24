<?php
/**
 * app/controllers/MessagingController.php
 *
 * Internal messaging between users.
 * All authenticated users have RW access (no additional role guard required
 * beyond session authentication, which is enforced by app/middleware/auth.php).
 *
 * Multi-recipient support: one row per recipient is inserted with a shared
 * thread_id (32-char hex).  Reply All pre-selects all thread participants.
 */

require_once __DIR__ . '/../config/database.php';

class MessagingController
{
	public function index(): void
	{
		$action = $_GET['action'] ?? 'inbox';

		if ($action === 'compose') {
			$this->compose();
		} elseif ($action === 'view' && isset($_GET['id'])) {
			$this->view((int)$_GET['id']);
		} elseif ($action === 'sent') {
			$this->sent();
		} else {
			$this->inbox();
		}
	}

	// ── Inbox ────────────────────────────────────────────────

	private function inbox(): void
	{
		$pdo    = getDBConnection();
		$userId = (int)$_SESSION['user_id'];

		$flashSuccess = null;
		if (isset($_SESSION['messages_success'])) {
			$flashSuccess = $_SESSION['messages_success'];
			unset($_SESSION['messages_success']);
		}

		$flashError = null;
		if (isset($_SESSION['messages_error'])) {
			$flashError = $_SESSION['messages_error'];
			unset($_SESSION['messages_error']);
		}

		$stmt = $pdo->prepare(
			'SELECT m.*, u.first_name AS sender_first, u.last_name AS sender_last
			   FROM messages m
			   JOIN users u ON m.sender_user_id = u.user_id
			  WHERE m.recipient_user_id = ?
			  ORDER BY m.sent_at DESC'
		);
		$stmt->execute([$userId]);
		$messages = $stmt->fetchAll();

		// Count unread
		$stmt = $pdo->prepare(
			'SELECT COUNT(*) FROM messages WHERE recipient_user_id = ? AND is_read = 0'
		);
		$stmt->execute([$userId]);
		$unreadCount = (int)$stmt->fetchColumn();

		$view = 'inbox';
		require __DIR__ . '/../views/messages.php';
	}

	// ── Sent ─────────────────────────────────────────────────

	private function sent(): void
	{
		$pdo    = getDBConnection();
		$userId = (int)$_SESSION['user_id'];

		$flashSuccess = null;
		if (isset($_SESSION['messages_success'])) {
			$flashSuccess = $_SESSION['messages_success'];
			unset($_SESSION['messages_success']);
		}

		// Group by thread so multi-recipient sends appear as one row
		$stmt = $pdo->prepare(
			'SELECT MIN(m.message_id) AS message_id,
			        m.thread_id,
			        GROUP_CONCAT(u.first_name, \' \', u.last_name
			                     ORDER BY u.last_name SEPARATOR \', \') AS recipients_list,
			        COUNT(*) AS recipient_count,
			        MIN(m.subject) AS subject,
			        MIN(m.sent_at) AS sent_at
			   FROM messages m
			   JOIN users u ON m.recipient_user_id = u.user_id
			  WHERE m.sender_user_id = ?
			  GROUP BY m.thread_id
			  ORDER BY sent_at DESC'
		);
		$stmt->execute([$userId]);
		$messages = $stmt->fetchAll();

		$flashError  = null;
		$unreadCount = 0;
		$view        = 'sent';
		require __DIR__ . '/../views/messages.php';
	}

	// ── Compose ──────────────────────────────────────────────

	private function compose(): void
	{
		$pdo    = getDBConnection();
		$userId = (int)$_SESSION['user_id'];

		$formError = null;

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$formError = $this->processSend();
			// On success processSend() exits via redirect
		}

		// Fetch active users (excluding self) for recipient list
		$stmt = $pdo->prepare(
			'SELECT user_id, first_name, last_name, role
			   FROM users
			  WHERE is_active = 1 AND user_id != ?
			  ORDER BY last_name, first_name'
		);
		$stmt->execute([$userId]);
		$recipients = $stmt->fetchAll();

		// Pre-selected recipient IDs (reply / reply-all / POST error repopulate)
		$preselectedIds = [];
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// Repopulate after validation error
			$preselectedIds = array_map('intval', $_POST['recipient_user_ids'] ?? []);
		} elseif (isset($_GET['reply_all_thread'])) {
			// Reply All: every participant in the thread except self
			$stmt = $pdo->prepare(
				'SELECT DISTINCT u.user_id
				   FROM users u
				  WHERE u.is_active = 1
				    AND u.user_id != ?
				    AND u.user_id IN (
				        SELECT sender_user_id    FROM messages WHERE thread_id = ?
				        UNION
				        SELECT recipient_user_id FROM messages WHERE thread_id = ?
				    )'
			);
			$stmt->execute([$userId, $_GET['reply_all_thread'], $_GET['reply_all_thread']]);
			$preselectedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
		} elseif (isset($_GET['reply_to'])) {
			$preselectedIds = [(int)$_GET['reply_to']];
		}

		// Pre-filled subject (passed via GET for reply/reply-all)
		$prefillSubject = '';
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$prefillSubject = substr($_GET['reply_subject'] ?? '', 0, 200);
		}

		$view = 'compose';
		require __DIR__ . '/../views/messages.php';
	}

	// ── View single message ──────────────────────────────────

	private function view(int $id): void
	{
		$pdo    = getDBConnection();
		$userId = (int)$_SESSION['user_id'];

		$stmt = $pdo->prepare(
			'SELECT m.*,
			        s.first_name AS sender_first, s.last_name AS sender_last
			   FROM messages m
			   JOIN users s ON m.sender_user_id = s.user_id
			  WHERE m.message_id = ?'
		);
		$stmt->execute([$id]);
		$message = $stmt->fetch();

		if (!$message) {
			$_SESSION['messages_error'] = 'Message not found.';
			header('Location: messages.php');
			exit;
		}

		// Only sender or recipient may read the message
		if ((int)$message['sender_user_id'] !== $userId && (int)$message['recipient_user_id'] !== $userId) {
			$_SESSION['messages_error'] = 'You do not have permission to view that message.';
			header('Location: messages.php');
			exit;
		}

		// Mark as read if current user is the recipient
		if ((int)$message['recipient_user_id'] === $userId && !$message['is_read']) {
			$pdo->prepare('UPDATE messages SET is_read = 1 WHERE message_id = ?')
			    ->execute([$id]);
			$message['is_read'] = 1;
		}

		// All recipients in this thread (for "To:" display and Reply All)
		$threadRecipients = [];
		if (!empty($message['thread_id'])) {
			$stmt = $pdo->prepare(
				'SELECT u.user_id, u.first_name, u.last_name
				   FROM messages m
				   JOIN users u ON m.recipient_user_id = u.user_id
				  WHERE m.thread_id = ?
				  ORDER BY u.last_name, u.first_name'
			);
			$stmt->execute([$message['thread_id']]);
			$threadRecipients = $stmt->fetchAll();
		}

		$view = 'view';
		require __DIR__ . '/../views/messages.php';
	}

	// ── Processor ────────────────────────────────────────────

	private function processSend(): ?string
	{
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			return 'Invalid request token.';
		}

		$rawIds      = $_POST['recipient_user_ids'] ?? [];
		$recipientIds = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
		// Remove self silently
		$recipientIds = array_values(array_filter($recipientIds, fn($id) => $id !== (int)$_SESSION['user_id']));

		$subject = trim($_POST['subject'] ?? '');
		$body    = trim($_POST['body']    ?? '');

		if (empty($recipientIds)) {
			return 'Please select at least one recipient.';
		}
		if (count($recipientIds) > 20) {
			return 'You may send to a maximum of 20 recipients at once.';
		}
		if ($subject === '') {
			return 'Subject is required.';
		}
		if ($body === '') {
			return 'Message body is required.';
		}
		if (strlen($subject) > 200) {
			return 'Subject may not exceed 200 characters.';
		}
		if (strlen($body) > 10000) {
			return 'Message body may not exceed 10,000 characters.';
		}

		// Verify all recipients exist and are active
		$pdo          = getDBConnection();
		$placeholders = implode(',', array_fill(0, count($recipientIds), '?'));
		$stmt         = $pdo->prepare(
			"SELECT COUNT(*) FROM users WHERE user_id IN ($placeholders) AND is_active = 1"
		);
		$stmt->execute($recipientIds);
		if ((int)$stmt->fetchColumn() !== count($recipientIds)) {
			return 'One or more selected recipients could not be found.';
		}

		// One thread_id shared by all rows in this send
		$threadId = bin2hex(random_bytes(16));

		$stmt = $pdo->prepare(
			'INSERT INTO messages (thread_id, sender_user_id, recipient_user_id, subject, body)
			 VALUES (?, ?, ?, ?, ?)'
		);
		foreach ($recipientIds as $rid) {
			$stmt->execute([$threadId, $_SESSION['user_id'], $rid, $subject, $body]);
		}

		$n = count($recipientIds);
		$_SESSION['messages_success'] = 'Message sent to ' . $n . ' recipient' . ($n !== 1 ? 's' : '') . '.';
		header('Location: messages.php?action=sent');
		exit;
	}
}
