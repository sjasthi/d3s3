<?php
/**
 * app/controllers/PermissionsController.php
 *
 * Handles the Permissions Management page: view and update the role/resource
 * access matrix stored in `role_permissions`.  Admin-only feature.
 *
 * Access flow:
 *   1. requireAdmin() – non-admins redirected immediately.
 *   2. Password gate  – admin must authenticate before the matrix is shown.
 *                       Session key `perms_auth_time` is valid for 30 minutes.
 *   3. Read-only view – matrix displayed; nothing editable until JS edit mode.
 *   4. Save           – password re-verified in the confirmation modal.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class PermissionsController
{
    /** Roles that are locked and can never be edited via the UI. */
    private const ADMIN_ROLES = ['SUPER_ADMIN', 'ADMIN'];

    /** All editable roles, in display order. */
    private const EDITABLE_ROLES = [
        'DOCTOR',
        'TRIAGE_NURSE',
        'NURSE',
        'PARAMEDIC',
        'GRIEVANCE_OFFICER',
        'EDUCATION_TEAM',
        'DATA_ENTRY_OPERATOR',
    ];

    /** All resources, in display order. */
    private const RESOURCES = [
        'assets',
        'case_sheets',
        'events',
        'patient_data',
        'users',
        'feedback',
        'messages',
        'tasks',
    ];

    /** Human-friendly resource labels. */
    private const RESOURCE_LABELS = [
        'assets'       => 'Assets',
        'case_sheets'  => 'Case Sheets',
        'events'       => 'Events',
        'patient_data' => 'Patient Data',
        'users'        => 'Users',
        'feedback'     => 'Feedback',
        'messages'     => 'Messages',
        'tasks'        => 'Tasks',
    ];

    /** Human-friendly role labels. */
    private const ROLE_LABELS = [
        'SUPER_ADMIN'          => 'Super Admin',
        'ADMIN'                => 'Admin',
        'DOCTOR'               => 'Doctor',
        'TRIAGE_NURSE'         => 'Triage Nurse',
        'NURSE'                => 'Nurse',
        'PARAMEDIC'            => 'Paramedic',
        'GRIEVANCE_OFFICER'    => 'Grievance Officer',
        'EDUCATION_TEAM'       => 'Education Team',
        'DATA_ENTRY_OPERATOR'  => 'Data Entry Operator',
    ];

    /**
     * Idle timeout in seconds.
     * The gate re-appears if the user has been away from permissions.php
     * for longer than this duration.  A JS heartbeat keeps it alive while
     * they are actively on the page.
     */
    private const IDLE_TTL = 300; // 5 minutes

    // ── Entry point ──────────────────────────────────────────────────────────

    public function index(): void
    {
        // Admin check is always first, regardless of action.
        $this->requireAdmin();

        $action = $_GET['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];

        if ($action === 'gate-auth' && $method === 'POST') {
            $this->processGateAuth();
            return;
        }

        // Heartbeat must be checked before the idle-timeout gate so it can
        // refresh perms_last_seen while the user is still on the page.
        if ($action === 'heartbeat' && $method === 'POST') {
            $this->heartbeat();
            return;
        }

        if ($action === 'save' && $method === 'POST') {
            $this->save();
            return;
        }

        // GET: idle-timeout check.  Use perms_last_seen (refreshed by every
        // page load and every heartbeat) rather than a fixed auth timestamp.
        $lastSeen = (int)($_SESSION['perms_last_seen'] ?? 0);
        if (!$lastSeen || (time() - $lastSeen) > self::IDLE_TTL) {
            unset($_SESSION['perms_auth_time'], $_SESSION['perms_last_seen']);
            $this->showGate();
        } else {
            $this->listing();
        }
    }

    // ── Password gate ────────────────────────────────────────────────────────

    private function showGate(?string $gateError = null): void
    {
        require __DIR__ . '/../views/admin/permissions_gate.php';
    }

    // ── Heartbeat ─────────────────────────────────────────────────────────────

    /**
     * Called by the client-side JS every 60 seconds while the admin is
     * actively on permissions.php.  Refreshes perms_last_seen so the idle
     * timeout does not fire.  Returns JSON – never renders a view.
     */
    private function heartbeat(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // CSRF check (cheap protection against cross-site pings).
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            echo json_encode(['ok' => false, 'reason' => 'csrf']);
            exit;
        }

        $lastSeen = (int)($_SESSION['perms_last_seen'] ?? 0);
        if ($lastSeen && (time() - $lastSeen) <= self::IDLE_TTL) {
            $_SESSION['perms_last_seen'] = time();
            echo json_encode(['ok' => true]);
        } else {
            // Session already expired server-side; clear it so the next GET
            // shows the gate cleanly.
            unset($_SESSION['perms_auth_time'], $_SESSION['perms_last_seen']);
            echo json_encode(['ok' => false, 'reason' => 'expired']);
        }
        exit;
    }

    private function processGateAuth(): void
    {
        // CSRF validation.
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->showGate('Invalid request token. Please try again.');
            return;
        }

        $password = $_POST['gate_password'] ?? '';
        if ($password === '') {
            $this->showGate('Please enter your password.');
            return;
        }

        $pdo  = getDBConnection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->showGate('Incorrect password. Access denied.');
            return;
        }

        // Set both the auth timestamp (for audit / record-keeping) and the
        // idle-timeout timestamp (the one actually checked on subsequent GETs).
        $_SESSION['perms_auth_time'] = time();
        $_SESSION['perms_last_seen'] = time();
        header('Location: permissions.php');
        exit;
    }

    // ── Matrix display ────────────────────────────────────────────────────────

    private function listing(?string $formError = null): void
    {
        // Refresh the idle timer on every page load.
        $_SESSION['perms_last_seen'] = time();

        $pdo = getDBConnection();

        // Load current matrix from DB (indexed by role → resource → permission).
        $matrix = $this->loadMatrix($pdo);

        $roles          = self::EDITABLE_ROLES;
        $adminRoles     = self::ADMIN_ROLES;
        $resources      = self::RESOURCES;
        $roleLabels     = self::ROLE_LABELS;
        $resourceLabels = self::RESOURCE_LABELS;

        require __DIR__ . '/../views/admin/permissions.php';
    }

    // ── Save ─────────────────────────────────────────────────────────────────

    private function save(): void
    {
        // 1. CSRF validation.
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->listing('Invalid request token. Please try again.');
            return;
        }

        $pdo = getDBConnection();

        // 2. Password verification.
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($confirmPassword === '') {
            $this->listing('Please enter your password to confirm changes.');
            return;
        }

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($confirmPassword, $user['password_hash'])) {
            $this->listing('Incorrect password. No changes were saved.');
            return;
        }

        // 3. Load current matrix.
        $current = $this->loadMatrix($pdo);

        // 4. Collect and validate submitted values.
        $submitted  = $_POST['perm'] ?? [];
        $validPerms = ['R', 'RW', 'N'];
        $changes    = [];

        foreach (self::EDITABLE_ROLES as $role) {
            foreach (self::RESOURCES as $resource) {
                $newPerm = $submitted[$role][$resource] ?? null;

                // Reject missing or invalid values silently (keep current).
                if (!in_array($newPerm, $validPerms, true)) {
                    continue;
                }

                $oldPerm = $current[$role][$resource] ?? 'N';

                if ($newPerm !== $oldPerm) {
                    $changes[] = [
                        'role'     => $role,
                        'resource' => $resource,
                        'old'      => $oldPerm,
                        'new'      => $newPerm,
                    ];
                }
            }
        }

        if (empty($changes)) {
            $_SESSION['permissions_success'] = 'No changes were detected – permissions remain unchanged.';
            header('Location: admin.php?page=panel');
            exit;
        }

        // 5. Persist changes and write audit log.
        $ipRaw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $ip    = $ipRaw !== null ? trim(explode(',', $ipRaw)[0]) : null;
        if ($ip !== null && strlen($ip) > 45) {
            $ip = substr($ip, 0, 45);
        }

        $updateStmt = $pdo->prepare(
            'UPDATE role_permissions SET permission = ? WHERE role = ? AND resource = ?'
        );
        $logStmt = $pdo->prepare(
            'INSERT INTO permission_change_log
               (changed_by, role, resource, old_perm, new_perm, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $pdo->beginTransaction();
        try {
            foreach ($changes as $c) {
                $updateStmt->execute([$c['new'], $c['role'], $c['resource']]);
                $logStmt->execute([
                    $_SESSION['user_id'],
                    $c['role'],
                    $c['resource'],
                    $c['old'],
                    $c['new'],
                    $ip,
                ]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->listing('A database error occurred. Changes were not saved.');
            return;
        }

        $count = count($changes);
        $_SESSION['permissions_success'] = "Permissions updated successfully. {$count} change" . ($count === 1 ? '' : 's') . " saved.";
        header('Location: admin.php?page=panel');
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Redirect non-admins to dashboard. */
    private function requireAdmin(): void
    {
        $role = $_SESSION['user_role'] ?? '';
        if (!in_array($role, self::ADMIN_ROLES, true)) {
            $_SESSION['dashboard_notice'] = 'You do not have permission to access that page.';
            header('Location: dashboard.php');
            exit;
        }
    }

    /** Load the full matrix from `role_permissions` keyed by [role][resource]. */
    private function loadMatrix(PDO $pdo): array
    {
        $rows = $pdo->query(
            'SELECT role, resource, permission FROM role_permissions'
        )->fetchAll(PDO::FETCH_ASSOC);

        $matrix = [];
        foreach ($rows as $row) {
            $matrix[$row['role']][$row['resource']] = $row['permission'];
        }
        return $matrix;
    }
}
