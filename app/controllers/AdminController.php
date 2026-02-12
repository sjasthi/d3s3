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
            // TODO: Uncomment the registration-code block below once .env is
            //       writable by the web server (chmod 664 .env or similar).
            //       This lets a Super Admin change the registration code from
            //       the User Management page without FTP access.
            //
            // $formAction = $_POST['form_action'] ?? 'edit_user';
            //
            // if ($formAction === 'update_registration_code') {
            //     $regCodeError = $this->processUpdateRegistrationCode($currentUser['role']);
            // } else {

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

            // } // end of else block for registration-code feature
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

        // ── Registration code (read-only display) ────────────
        $registrationCode = getenv('REGISTRATION_CODE') ?: '';

        // TODO: Uncomment when the registration-code EDIT UI is enabled (see TODO above).
        // $regCodeError = $regCodeError ?? null;

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

        // ── Uniqueness checks (exclude current user) ─────────────
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

    // ── Reports & Data Export ─────────────────────────────────

    /**
     * GET  – render the reports page with exportable tables.
     * POST – stream a CSV download of the selected table.
     */
    public function reports(): void
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

        // ── Exportable tables whitelist ────────────────────────
        $exportables = [
            'users'            => ['label' => 'Users',            'icon' => 'fas fa-users',          'exclude' => ['password_hash']],
            'patients'         => ['label' => 'Patients',         'icon' => 'fas fa-user-injured',   'exclude' => ['password_hash']],
            'case_sheets'      => ['label' => 'Case Sheets',      'icon' => 'fas fa-file-medical',   'exclude' => []],
            'events'           => ['label' => 'Events',           'icon' => 'fas fa-calendar-alt',   'exclude' => []],
            'messages'         => ['label' => 'Messages',         'icon' => 'fas fa-envelope',       'exclude' => []],
            'patient_feedback' => ['label' => 'Patient Feedback', 'icon' => 'fas fa-comment-dots',   'exclude' => []],
            'assets'           => ['label' => 'Assets',           'icon' => 'fas fa-boxes',          'exclude' => []],
        ];

        // ── One-time flash ──────────────────────────────────────
        $flashSuccess = null;
        if (isset($_SESSION['reports_success'])) {
            $flashSuccess = $_SESSION['reports_success'];
            unset($_SESSION['reports_success']);
        }

        $formError = null;

        // ── Handle POST (export or import) ───────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formAction = $_POST['form_action'] ?? 'export';
            if ($formAction === 'import') {
                $formError = $this->processImportCsv($exportables);
                // On success, processImportCsv() redirects via PRG.
            } else {
                $formError = $this->processExportCsv($exportables);
                // On success, processExportCsv() streams CSV and exits.
            }
            // If we reach here, there was a validation error.
        }

        require __DIR__ . '/../views/admin/reports.php';
    }

    /**
     * Validate the requested table and stream a CSV download.
     * Returns an error string on failure, or exits after streaming on success.
     */
    private function processExportCsv(array $exportables): ?string
    {
        // ── CSRF guard ──────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $table = $_POST['table'] ?? '';

        if (!array_key_exists($table, $exportables)) {
            return 'Invalid table selected.';
        }

        $pdo = getDBConnection();
        $excludeCols = $exportables[$table]['exclude'];

        // Fetch all rows (table name is from whitelist, safe to use directly)
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll();

        if (empty($rows)) {
            return 'No data found in the ' . $exportables[$table]['label'] . ' table.';
        }

        // Determine columns (exclude sensitive ones)
        $allColumns = array_keys($rows[0]);
        $columns = array_diff($allColumns, $excludeCols);

        // Stream CSV
        $filename = $table . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");
        // Header row
        fputcsv($out, array_values($columns));
        // Data rows
        foreach ($rows as $row) {
            $filtered = [];
            foreach ($columns as $col) {
                $filtered[] = $row[$col];
            }
            fputcsv($out, $filtered);
        }
        fclose($out);
        exit;
    }

    /**
     * Validate an uploaded CSV and import rows into the selected table.
     * Returns an error string on failure, or exits via PRG redirect on success.
     */
    private function processImportCsv(array $exportables): ?string
    {
        // ── CSRF guard ──────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $table = $_POST['table'] ?? '';

        if (!array_key_exists($table, $exportables)) {
            return 'Invalid table selected.';
        }

        // ── Validate file upload ────────────────────────────────
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds the maximum upload size.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds the maximum upload size.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error (no temp directory).',
                UPLOAD_ERR_CANT_WRITE => 'Server configuration error (cannot write to disk).',
            ];
            $code = $_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE;
            return $uploadErrors[$code] ?? 'File upload failed.';
        }

        $file = $_FILES['csv_file'];

        // Check file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return 'Only CSV files are accepted.';
        }

        // ── Parse CSV ───────────────────────────────────────────
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            return 'Could not read the uploaded file.';
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read header row
        $csvHeaders = fgetcsv($handle);
        if ($csvHeaders === false || empty($csvHeaders)) {
            fclose($handle);
            return 'CSV file is empty or has no header row.';
        }

        // Trim whitespace from headers
        $csvHeaders = array_map('trim', $csvHeaders);

        // ── Get valid columns from the database ─────────────────
        $pdo = getDBConnection();
        $descRows = $pdo->query("DESCRIBE `{$table}`")->fetchAll();
        $validColumns = array_column($descRows, 'Field');

        // Columns to always exclude from import
        $excludeCols = array_merge(
            $exportables[$table]['exclude'],  // password_hash for users/patients
            ['patient_code']                  // trigger-generated
        );

        // Map CSV column index → database column name (only valid ones)
        $columnMap = [];  // index => column_name
        $skippedHeaders = [];
        foreach ($csvHeaders as $i => $header) {
            if (in_array($header, $excludeCols, true)) {
                $skippedHeaders[] = $header;
                continue;
            }
            if (in_array($header, $validColumns, true)) {
                $columnMap[$i] = $header;
            } else {
                $skippedHeaders[] = $header;
            }
        }

        if (empty($columnMap)) {
            fclose($handle);
            return 'No valid columns found in the CSV header. Column names must match database field names.';
        }

        // ── Back up existing table data before import ───────────
        $backupError = $this->backupTableToCsv($pdo, $table, $exportables[$table]['exclude']);
        if ($backupError !== null) {
            fclose($handle);
            return $backupError;
        }

        // ── Build INSERT IGNORE statement ───────────────────────
        $colNames = array_values($columnMap);
        $colList = implode(', ', array_map(fn($c) => "`{$c}`", $colNames));
        $placeholders = implode(', ', array_fill(0, count($colNames), '?'));
        $sql = "INSERT IGNORE INTO `{$table}` ({$colList}) VALUES ({$placeholders})";
        $stmt = $pdo->prepare($sql);

        // ── Import rows in a transaction ────────────────────────
        $pdo->beginTransaction();
        $totalRows = 0;
        $inserted = 0;
        $lineNum = 1; // header was line 1

        try {
            while (($csvRow = fgetcsv($handle)) !== false) {
                $lineNum++;

                // Skip completely empty rows
                if (count($csvRow) === 1 && ($csvRow[0] === null || trim($csvRow[0]) === '')) {
                    continue;
                }

                $totalRows++;
                $values = [];
                foreach ($columnMap as $csvIndex => $colName) {
                    $val = $csvRow[$csvIndex] ?? null;
                    // Convert empty strings to null for nullable columns
                    $values[] = ($val === '' || $val === null) ? null : $val;
                }

                $stmt->execute($values);
                $inserted += $stmt->rowCount();
            }

            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            fclose($handle);
            return 'Import failed on line ' . $lineNum . ': ' . $e->getMessage();
        }

        fclose($handle);

        $skipped = $totalRows - $inserted;
        $label = $exportables[$table]['label'];
        $msg = "Imported {$inserted} row(s) into {$label}. A backup was saved before import.";
        if ($skipped > 0) {
            $msg .= " {$skipped} duplicate(s) skipped.";
        }
        if (!empty($skippedHeaders)) {
            $msg .= ' Ignored columns: ' . implode(', ', $skippedHeaders) . '.';
        }

        $_SESSION['reports_success'] = $msg;
        header('Location: reports.php');
        exit;
    }

    /**
     * Save all current rows of a table to a timestamped CSV in backups/.
     * Returns an error string on failure, or null on success.
     */
    private function backupTableToCsv(PDO $pdo, string $table, array $excludeCols): ?string
    {
        $backupDir = __DIR__ . '/../../backups';
        if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true)) {
            return 'Could not create backups directory.';
        }

        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll();

        // Nothing to back up — that's fine, proceed with import
        if (empty($rows)) {
            return null;
        }

        $allColumns = array_keys($rows[0]);
        $columns = array_values(array_diff($allColumns, $excludeCols));

        $timestamp = date('Y-m-d_His');
        $filename = "{$table}_backup_{$timestamp}.csv";
        $filepath = $backupDir . '/' . $filename;

        $out = fopen($filepath, 'w');
        if ($out === false) {
            return 'Could not write backup file. Check directory permissions.';
        }

        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $columns);
        foreach ($rows as $row) {
            $filtered = [];
            foreach ($columns as $col) {
                $filtered[] = $row[$col];
            }
            fputcsv($out, $filtered);
        }
        fclose($out);

        return null;
    }

    /**
     * Validate and update the REGISTRATION_CODE in .env.
     * Returns an error string on failure, or exits via redirect on success.
     */
    private function processUpdateRegistrationCode(string $currentRole): ?string
    {
        if ($currentRole !== 'SUPER_ADMIN') {
            return 'Only Super Admins can change the registration code.';
        }

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $newCode = trim($_POST['registration_code'] ?? '');
        if ($newCode === '') {
            return 'Registration code cannot be empty.';
        }

        if (!preg_match('/^[a-zA-Z0-9_@!#$%&*+=?-]{4,100}$/', $newCode)) {
            return 'Code must be 4–100 characters (letters, numbers, and common symbols).';
        }

        // ── Rewrite the .env file ─────────────────────────────
        $envPath = __DIR__ . '/../../.env';
        if (!is_writable($envPath)) {
            return 'Cannot write to .env file. Check file permissions.';
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        $found = false;
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*REGISTRATION_CODE\s*=/', $line)) {
                $lines[$i] = 'REGISTRATION_CODE=' . $newCode;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $lines[] = 'REGISTRATION_CODE=' . $newCode;
        }

        file_put_contents($envPath, implode("\n", $lines) . "\n", LOCK_EX);
        putenv('REGISTRATION_CODE=' . $newCode);

        $_SESSION['users_success'] = 'Registration code updated successfully.';
        header('Location: users.php');
        exit;
    }
}
