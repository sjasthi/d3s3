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
 *
 * Split-panel support: GET ?panel=right returns only the right-panel HTML
 * fragment (no full page wrapper) for AJAX injection.
 */

require_once __DIR__ . '/../config/database.php';

class MessagingController
{
	public function index(): void
	{
		$action  = $_GET['action'] ?? 'inbox';
		$isPanel = ($_GET['panel'] ?? '') === 'right';
		$isAjax  = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
		           && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

		// ── POST: archive / unarchive (always exits via JSON) ────────────
		if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
		    in_array($action, ['archive', 'unarchive'], true)) {
			$this->processArchive($action === 'unarchive');
		}
		
        // POST: delete message
        if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
            $action === 'delete') {
            $this->processDelete();
        }

		// ── POST: send message (always runs before panel logic) ──────────
		$formError = null;
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$formError = $this->processSend($isAjax);
			// processSend() exits on success (JSON or redirect).
			// If we reach here, it returned a validation error string.
			$action = 'compose';
		}

		$pdo    = getDBConnection();
		$userId = (int)$_SESSION['user_id'];

		// ── Flash messages ───────────────────────────────────────────────
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

		// ── Left panel data (skipped for panel-only AJAX requests) ───────
		$listTab      = 'inbox';
		$listMessages = [];
		$unreadCount  = 0;

		if (!$isPanel) {
			$listTab = match($action) {
				'sent'     => 'sent',
				'archived' => 'archived',
				default    => 'inbox',
			};

			try {
				if ($listTab === 'sent') {
					$stmt = $pdo->prepare(
						'SELECT MIN(m.message_id) AS message_id,
						        m.thread_id,
						        GROUP_CONCAT(u.first_name, \' \', u.last_name
						                     ORDER BY u.last_name SEPARATOR \', \') AS recipients_list,
						        COUNT(*) AS recipient_count,
						        MIN(m.subject) AS subject,
						        MIN(m.sent_at)  AS sent_at
						   FROM messages m
						   JOIN users u ON m.recipient_user_id = u.user_id
						  WHERE m.sender_user_id = ? AND m.sender_deleted = 0
						  GROUP BY m.thread_id
						  ORDER BY sent_at DESC'
					);
				} elseif ($listTab === 'archived') {
					$stmt = $pdo->prepare(
						'SELECT m.*, u.first_name AS sender_first, u.last_name AS sender_last
						   FROM messages m
						   JOIN users u ON m.sender_user_id = u.user_id
						  WHERE m.recipient_user_id = ? AND m.recipient_archived = 1 AND m.recipient_deleted = 0
						  ORDER BY m.sent_at DESC'
					);
				} else {
					$stmt = $pdo->prepare(
						'SELECT m.*, u.first_name AS sender_first, u.last_name AS sender_last
						   FROM messages m
						   JOIN users u ON m.sender_user_id = u.user_id
						  WHERE m.recipient_user_id = ? AND m.recipient_archived = 0 AND m.recipient_deleted = 0
						  ORDER BY m.sent_at DESC'
					);
				}
				$stmt->execute([$userId]);
				$listMessages = $stmt->fetchAll();

				$stmt = $pdo->prepare(
					
                SELECT COUNT(*) FROM messages WHERE recipient_user_id = ? AND is_read = 0 AND recipient_archived = 0 AND recipient_deleted = 0

				);
				$stmt->execute([$userId]);
				$unreadCount = (int)$stmt->fetchColumn();
			} catch (Throwable $e) {
				// Leave $listMessages as [] — table or column may not exist yet
				$flashError = $flashError ?? 'Could not load messages. Please run pending database migrations.';
			}
		}

		// ── Recipients list (always fetched — Tom Select needs it on every page) ──
		$stmt = $pdo->prepare(
			'SELECT user_id, first_name, last_name, role
			   FROM users
			  WHERE is_active = 1 AND user_id != ?
			  ORDER BY last_name, first_name'
		);
		$stmt->execute([$userId]);
		$recipients = $stmt->fetchAll();

		// ── Right panel data ─────────────────────────────────────────────
		$rightView        = 'welcome';
		$message          = null;
		$threadRecipients = [];
		$preselectedIds   = [];
		$prefillSubject   = '';

		if ($action === 'view' && isset($_GET['id'])) {
			$msgId = (int)$_GET['id'];
			$stmt  = $pdo->prepare(
				'SELECT m.*,
				        s.first_name AS sender_first, s.last_name AS sender_last
				   FROM messages m
				   JOIN users s ON m.sender_user_id = s.user_id
				 
                  WHERE m.message_id = ?
                    AND (
                        (m.sender_user_id = ? AND m.sender_deleted = 0)
                        OR
                        (m.recipient_user_id = ? AND m.recipient_deleted = 0)
                    )'
            );
            $stmt->execute([$msgId, $userId, $userId]);

			$message = $stmt->fetch();

			if (!$message ||
			    ((int)$message['sender_user_id'] !== $userId &&
			     (int)$message['recipient_user_id'] !== $userId)
			) {
				$flashError = 'Message not found or access denied.';
			} else {
				if ((int)$message['recipient_user_id'] === $userId && !$message['is_read']) {
					$pdo->prepare('UPDATE messages SET is_read = 1 WHERE message_id = ?')
					    ->execute([$msgId]);
					$message['is_read'] = 1;
				}
				if (!empty($message['thread_id'])) {
					$stmt = $pdo->prepare(
						'SELECT DISTINCT u.user_id, u.first_name, u.last_name
						   FROM messages m
						   JOIN users u ON m.recipient_user_id = u.user_id
						  WHERE m.thread_id = ?
						  ORDER BY u.last_name, u.first_name'
					);
					$stmt->execute([$message['thread_id']]);
					$threadRecipients = $stmt->fetchAll();
				}
				$rightView = 'view';
			}

		} elseif ($action === 'compose') {
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$preselectedIds = array_map('intval', $_POST['recipient_user_ids'] ?? []);
			} elseif (isset($_GET['reply_all_thread'])) {
				$stmt = $pdo->prepare(
					'SELECT DISTINCT u.user_id
					   FROM users u
					  WHERE u.is_active = 1 AND u.user_id != ?
					    AND u.user_id IN (
					        SELECT sender_user_id    FROM messages WHERE thread_id = ?
					        UNION
					        SELECT recipient_user_id FROM messages WHERE thread_id = ?
					    )'
				);
				$stmt->execute([$userId, $_GET['reply_all_thread'], $_GET['reply_all_thread']]);
				$preselectedIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
			} elseif (isset($_GET['reply_to'])) {
				$preselectedIds = [(int)$_GET['reply_to']];
			}

			$prefillSubject = ($_SERVER['REQUEST_METHOD'] !== 'POST')
				? substr($_GET['reply_subject'] ?? '', 0, 200)
				: '';

			$rightView = 'compose';
		}

		$panelOnly = $isPanel;
		require __DIR__ . '/../views/messages.php';
	}

	// ── Processor ────────────────────────────────────────────────────────

	private function processSend(bool $isAjax = false): ?string
	{
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'error' => 'Invalid request token.']); exit; }
			return 'Invalid request token.';
		}

		$rawIds       = $_POST['recipient_user_ids'] ?? [];
		$recipientIds = array_values(array_unique(array_filter(array_map('intval', $rawIds))));
		$recipientIds = array_values(array_filter(
			$recipientIds, fn($id) => $id !== (int)$_SESSION['user_id']
		));

		$subject = trim($_POST['subject'] ?? '');
		$body    = trim($_POST['body']    ?? '');

		$err = null;
		if (empty($recipientIds))          $err = 'Please select at least one recipient.';
		elseif (count($recipientIds) > 20) $err = 'You may send to a maximum of 20 recipients at once.';
		elseif ($subject === '')           $err = 'Subject is required.';
		elseif ($body === '')              $err = 'Message body is required.';
		elseif (strlen($subject) > 200)    $err = 'Subject may not exceed 200 characters.';
		elseif (strlen($body) > 10000)     $err = 'Message body may not exceed 10,000 characters.';

		if ($err !== null) {
			if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'error' => $err]); exit; }
			return $err;
		}

		$pdo          = getDBConnection();
		$placeholders = implode(',', array_fill(0, count($recipientIds), '?'));
		$stmt         = $pdo->prepare(
			"SELECT COUNT(*) FROM users WHERE user_id IN ($placeholders) AND is_active = 1"
		);
		$stmt->execute($recipientIds);
		if ((int)$stmt->fetchColumn() !== count($recipientIds)) {
			$err = 'One or more selected recipients could not be found.';
			if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'error' => $err]); exit; }
			return $err;
		}

		$threadId = bin2hex(random_bytes(16));
		$stmt     = $pdo->prepare(
			'INSERT INTO messages (thread_id, sender_user_id, recipient_user_id, subject, body)
			 VALUES (?, ?, ?, ?, ?)'
		);
		foreach ($recipientIds as $rid) {
			$stmt->execute([$threadId, $_SESSION['user_id'], $rid, $subject, $body]);
		}

		$n = count($recipientIds);
		if ($isAjax) {
			header('Content-Type: application/json');
			echo json_encode(['success' => true, 'sent_count' => $n]);
			exit;
		}

		$_SESSION['messages_success'] = 'Message sent to ' . $n . ' recipient' . ($n !== 1 ? 's' : '') . '.';
		header('Location: messages.php?action=sent');
		exit;
	}

	private function processArchive(bool $unarchive): void
	{
		header('Content-Type: application/json');
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'error' => 'Invalid request token.']);
			exit;
		}
		$msgId  = (int)($_POST['message_id'] ?? 0);
		$userId = (int)$_SESSION['user_id'];
		if ($msgId < 1) {
			echo json_encode(['success' => false, 'error' => 'Invalid message.']);
			exit;
		}
		try {
			$pdo  = getDBConnection();
			$stmt = $pdo->prepare(
				'UPDATE messages SET recipient_archived = ? WHERE message_id = ? AND recipient_user_id = ?'
			);
			$stmt->execute([$unarchive ? 0 : 1, $msgId, $userId]);
			echo json_encode(['success' => true]);
		} catch (Throwable $e) {
			echo json_encode(['success' => false, 'error' => 'Database error.']);
		}
		exit;
	}
	
    private function processDelete(): void
    {
        header('Content-Type: application/json');

        if (!hash_equals(
            $_SESSION['csrf_token'] ?? '',
            $_POST['csrf_token'] ?? ''
        )) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid request token.'
            ]);
            exit;
        }

        $msgId = (int)($_POST['message_id'] ?? 0);
        $userId = (int)$_SESSION['user_id'];

        if ($msgId < 1) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid message.'
            ]);
            exit;
        }

        try {
            $pdo = getDBConnection();

            $stmt = $pdo->prepare(
    'UPDATE messages
     SET sender_deleted = 1
     WHERE sender_user_id = ?
       AND thread_id = (
           SELECT thread_id FROM (
               SELECT thread_id
               FROM messages
               WHERE message_id = ?
                 AND sender_user_id = ?
           ) AS selected_message
       )
       AND sender_deleted = 0'
);
$stmt->execute([$userId, $msgId, $userId]);

            $senderUpdated = $stmt->rowCount();

            $stmt = $pdo->prepare(
                'UPDATE messages
                 SET recipient_deleted = 1
                 WHERE message_id = ?
                 AND recipient_user_id = ?'
            );
            $stmt->execute([$msgId, $userId]);
            $recipientUpdated = $stmt->rowCount();

            echo json_encode([
                'success' => ($senderUpdated + $recipientUpdated) > 0,
                'error' => ($senderUpdated + $recipientUpdated) > 0
                    ? null
                    : 'Message not found or already deleted.'
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error.'
            ]);
        }

        exit;
    }

}
