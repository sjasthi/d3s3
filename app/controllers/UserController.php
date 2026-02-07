<?php

/**
 * app/controllers/UserController.php
 *
 * Handles login and employee-account creation.
 */

require_once __DIR__ . '/../config/database.php';

class UserController
{
    // ── Login ───────────────────────────────────────────────────

    /**
     * GET  – render the login form.
     * POST – validate credentials; start a session on success.
     */
    public function login(): void
    {
        $loginError   = null;
        $loginSuccess = null;

        // Consume a one-time success flash (set after account creation)
        if (isset($_SESSION['create_account_success'])) {
            $loginSuccess = $_SESSION['create_account_success'];
            unset($_SESSION['create_account_success']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $loginError = $this->processLogin();
            // If we reach here the login failed; fall through to render the form.
        }

        require __DIR__ . '/../views/login.php';
    }

    // ── Profile ────────────────────────────────────────────────

    /**
     * GET  – render the profile page.
     * POST – validate and persist profile updates (PRG on success).
     */
    public function profile(): void
    {
        $pdo = getDBConnection();

        // ── One-time flash ──────────────────────────────────────
        $flashSuccess = null;
        if (isset($_SESSION['profile_success'])) {
            $flashSuccess = $_SESSION['profile_success'];
            unset($_SESSION['profile_success']);
        }

        $formError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formError = $this->processProfileUpdate();
        }

        $stmt = $pdo->prepare(
            'SELECT user_id, first_name, last_name, email, username, role, is_active, last_login_at
             FROM users WHERE user_id = ? LIMIT 1'
        );
        $stmt->execute([$_SESSION['user_id']]);
        $profile = $stmt->fetch();

        require __DIR__ . '/../views/profile.php';
    }

    /**
     * Validate the POST payload and authenticate against the users table.
     * Returns an error string on failure, or exits via redirect on success.
     */
    private function processLogin(): ?string
    {
        // ── CSRF guard ──────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $email    = trim($_POST['email']   ?? '');
        $password =      $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            return 'Email and password are required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }

        // ── Credential check ────────────────────────────────────
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare('SELECT user_id, password_hash, is_active, role, first_name, last_name FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row  = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return 'Invalid email or password.';
        }

        if (!$row['is_active']) {
            return 'This account is no longer active. Please contact the administrator.';
        }

        // ── Start authenticated session ─────────────────────────
        session_regenerate_id(true);
        $_SESSION['user_id']   = $row['user_id'];
        $_SESSION['email']     = $email;
        $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
        $_SESSION['user_role'] = $row['role'];

        $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE user_id = ?')
            ->execute([$row['user_id']]);

        $destination = in_array($row['role'], ['ADMIN', 'SUPER_ADMIN']) ? 'admin.php' : 'dashboard.php';
        header('Location: ' . $destination);
        exit;
    }

    // ── Create user account ─────────────────────────────────

    /**
     * GET  – render the create-account form.
     * POST – validate and insert the new user row.
     */
    public function createAccount(): void
    {
        $formError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formError = $this->processCreateAccount();
            // null means the method already redirected via PRG.
        }

        require __DIR__ . '/../views/admin/emp_register.php';
    }

    /**
     * Validate the POST payload and insert a new user row.
     * Returns an error string on failure, or exits via redirect on success.
     */
    private function processCreateAccount(): ?string
    {
        // ── CSRF guard ──────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $firstName  = trim($_POST['first_name']       ?? '');
        $lastName   = trim($_POST['last_name']        ?? '');
        $email      = trim($_POST['email']            ?? '');
        $username   = trim($_POST['username']         ?? '');
        $password   =      $_POST['password']         ?? '';
        $confirmPwd =      $_POST['confirm_password'] ?? '';

        // ── Basic validation ────────────────────────────────────
        if ($firstName === '' || $lastName === '' || $email === '' || $username === '' || $password === '') {
            return 'All fields are required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{3,60}$/', $username)) {
            return 'Username must be 3–60 characters (letters, numbers, underscores, or hyphens only).';
        }

        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }

        if ($password !== $confirmPwd) {
            return 'Passwords do not match.';
        }

        // ── Uniqueness checks ───────────────────────────────────
        $pdo = getDBConnection();

        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return 'An account with this email already exists.';
        }

        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return 'This username is already taken.';
        }

        // ── Insert (role defaults to DATA_ENTRY_OPERATOR in the schema) ─
        $pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, username, password_hash)
                VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $firstName,
            $lastName,
            $email,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
        ]);

        // PRG: flash success and redirect to the login page
        $_SESSION['create_account_success'] = 'Account created successfully. Please sign in.';
        header('Location: login.php');
        exit;
    }

    /**
     * Validate and persist profile changes for the logged-in user.
     * Returns an error string on failure, or exits via redirect on success.
     */
    private function processProfileUpdate(): ?string
    {
        // ── CSRF guard ──────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId === 0) {
            return 'Invalid user.';
        }

        $firstName  = trim($_POST['first_name'] ?? '');
        $lastName   = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $username   = trim($_POST['username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPwd  = $_POST['confirm_password'] ?? '';

        if ($firstName === '' || $lastName === '' || $email === '' || $username === '') {
            return 'All fields are required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{3,60}$/', $username)) {
            return 'Username must be 3–60 characters (letters, numbers, underscores, or hyphens only).';
        }

        $pdo = getDBConnection();

        // ── Uniqueness checks (exclude current user) ────────
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND user_id != ? LIMIT 1');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            return 'An account with this email already exists.';
        }

        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ? AND user_id != ? LIMIT 1');
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            return 'This username is already taken.';
        }

        $sets = ['first_name = ?', 'last_name = ?', 'email = ?', 'username = ?'];
        $params = [$firstName, $lastName, $email, $username];

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                return 'New password must be at least 8 characters.';
            }
            if ($newPassword !== $confirmPwd) {
                return 'Passwords do not match.';
            }
            $sets[] = 'password_hash = ?';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $params[] = $userId;
        $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE user_id = ?')
            ->execute($params);

        // Update session display fields
        $_SESSION['email'] = $email;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;

        $_SESSION['profile_success'] = 'Profile updated successfully.';
        header('Location: profile.php');
        exit;
    }
}
