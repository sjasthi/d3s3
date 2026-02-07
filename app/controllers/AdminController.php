<?php
/**
 * app/controllers/AdminController.php
 *
 * Handles routing and rendering for all admin-related views.
 */

require_once __DIR__ . '/../config/database.php';

class AdminController
{
    public function dashboard(): void
    {
        require __DIR__ . '/../views/admin/dashboard.php';
    }

    // ── User Management ─────────────────────────────────────────

    /**
     * GET  – render the employee list or the edit form.
     * POST – validate and persist an employee edit (PRG on success).
     */
    public function users(): void
    {
        $pdo = getDBConnection();

        // ── Role guard: ADMIN or SUPER_ADMIN only ──────────────
        $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch();

        if (!in_array($currentUser['role'] ?? '', ['ADMIN', 'SUPER_ADMIN'])) {
            $_SESSION['dashboard_notice'] = 'You do not have permission to access this page.';
            header('Location: dashboard.php');
            exit;
        }

        // ── One-time flash ──────────────────────────────────────
        $flashSuccess = null;
        if (isset($_SESSION['users_success'])) {
            $flashSuccess = $_SESSION['users_success'];
            unset($_SESSION['users_success']);
        }

        // ── Determine view mode ─────────────────────────────────
        $action   = $_GET['action'] ?? 'list';
        $editId   = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $formError = null;
        $editUser = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formError = $this->processEditUser();
            // Success exits via PRG; reaching here means $formError is the error string.
            $action = 'edit';
            $editUser = [
                'user_id'    => (int)($_POST['user_id']        ?? 0),
                'first_name' => trim($_POST['first_name']      ?? ''),
                'last_name'  => trim($_POST['last_name']       ?? ''),
                'email'      => trim($_POST['email']           ?? ''),
                'username'   => trim($_POST['username']        ?? ''),
                'role'       =>      $_POST['role']            ?? '',
                'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            ];
        } elseif ($action === 'edit' && $editId !== null) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
            $stmt->execute([$editId]);
            $editUser = $stmt->fetch();
            if (!$editUser) {
                $action = 'list';
            }
        }

        // ── Fetch all users for the list table ──────────────
        $users = $pdo->query(
            'SELECT user_id, first_name, last_name, email, username, role, is_active, last_login_at
             FROM users ORDER BY last_name, first_name'
        )->fetchAll();

        require __DIR__ . '/../views/admin/users.php';
    }

    /**
     * Validate and persist an employee edit.
     * Returns an error string on failure, or exits via redirect on success.
     */
    private function processEditUser(): ?string
    {
        // ── CSRF guard ──────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $userId      = (int)($_POST['user_id']          ?? 0);
        $firstName   = trim($_POST['first_name']        ?? '');
        $lastName    = trim($_POST['last_name']         ?? '');
        $email       = trim($_POST['email']             ?? '');
        $username    = trim($_POST['username']          ?? '');
        $role        =      $_POST['role']              ?? '';
        $isActive    = isset($_POST['is_active']) ? 1 : 0;
        $newPassword =      $_POST['new_password']      ?? '';
        $confirmPwd  =      $_POST['confirm_password']  ?? '';

        if ($userId === 0) {
            return 'Invalid user.';
        }

        // ── Basic validation ────────────────────────────────────
        if ($firstName === '' || $lastName === '' || $email === '' || $username === '') {
            return 'All fields are required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{3,60}$/', $username)) {
            return 'Username must be 3–60 characters (letters, numbers, underscores, or hyphens only).';
        }

        $validRoles = ['SUPER_ADMIN', 'ADMIN', 'DATA_ENTRY_OPERATOR'];
        if (!in_array($role, $validRoles, true)) {
            return 'Invalid role selected.';
        }

        $pdo = getDBConnection();

        // ── Uniqueness checks (exclude current employee) ────────
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND user_id != ? LIMIT 1');
        $stmt->execute([$email, $employeeId]);
        if ($stmt->fetch()) {
            return 'An account with this email already exists.';
        }

        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ? AND user_id != ? LIMIT 1');
        $stmt->execute([$username, $employeeId]);
        if ($stmt->fetch()) {
            return 'This username is already taken.';
        }

        // ── Build UPDATE ────────────────────────────────────────
        $sets   = ['first_name = ?', 'last_name = ?', 'email = ?',
                   'username = ?', 'role = ?', 'is_active = ?'];
        $params = [$firstName, $lastName, $email, $username, $role, $isActive];

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                return 'New password must be at least 8 characters.';
            }
            if ($newPassword !== $confirmPwd) {
                return 'Passwords do not match.';
            }
            $sets[]   = 'password_hash = ?';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $params[] = $userId;
        $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE user_id = ?')
            ->execute($params);

        // ── PRG ─────────────────────────────────────────────────
        $_SESSION['users_success'] = 'User updated successfully.';
        header('Location: users.php');
        exit;
    }
}
