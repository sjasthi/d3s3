<?php
/**
 * app/controllers/AssetController.php
 *
 * Handles all asset-management operations:
 *   index()          – list assets with filters; create / edit / delete via POST (PRG)
 *   download()       – stream a locally stored file (auth-gated for staff & patients)
 *   processSendToPatient()  – POST: create a patient_assets record
 *   processRemoveSend()     – POST: delete a patient_assets record
 *
 * Storage model:
 *   LOCAL  – file uploaded via PHP and stored under uploads/assets/; served by download()
 *   URL    – external link (video, audio, hosted doc); no local file
 *   OTHER  – catch-all; resource_url may be supplied
 *
 * Roles that can send assets to a patient's portal:
 *   Any role with both assets READ and patient_data READ access.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class AssetController
{
    // ── Constants ─────────────────────────────────────────────────────────────

    private const UPLOAD_BASE    = 'uploads/assets/';   // relative to project root
    private const MAX_FILE_BYTES = 20 * 1024 * 1024;    // 20 MB

    /** Allowed MIME types for uploaded files → canonical extension */
    private const ALLOWED_MIME = [
        'application/pdf'                                                          => 'pdf',
        'image/jpeg'                                                               => 'jpg',
        'image/png'                                                                => 'png',
        'image/gif'                                                                => 'gif',
        'image/webp'                                                               => 'webp',
        'application/msword'                                                       => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel'                                                 => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'        => 'xlsx',
    ];

    /** MIME → inline (true) or force-download (false) */
    private const INLINE_MIME = [
        'application/pdf' => true,
        'image/jpeg'      => true,
        'image/png'       => true,
        'image/gif'       => true,
        'image/webp'      => true,
    ];

    // ── Auth helpers ──────────────────────────────────────────────────────────

    private function requireRead(): void
    {
        if (empty($_SESSION['user_id']) || !can($_SESSION['user_role'] ?? '', 'assets')) {
            $_SESSION['dashboard_notice'] = 'You do not have permission to access Assets.';
            header('Location: dashboard.php');
            exit;
        }
    }

    private function requireWrite(): void
    {
        if (empty($_SESSION['user_id']) || !can($_SESSION['user_role'] ?? '', 'assets', 'W')) {
            $_SESSION['assets_error'] = 'You do not have permission to modify assets.';
            header('Location: assets.php');
            exit;
        }
    }

    // ── Main page (list + CRUD) ───────────────────────────────────────────────

    public function index(): void
    {
        $this->requireRead();

        $pdo         = getDBConnection();
        $currentRole = $_SESSION['user_role'] ?? '';
        $canWrite    = can($currentRole, 'assets', 'W');

        // Roles that may send assets to a patient's portal
        $canSendToPatient = can($currentRole, 'assets') && can($currentRole, 'patient_data');

        // ── Flash messages ────────────────────────────────────────
        $flashSuccess = null;
        $flashError   = null;
        if (isset($_SESSION['assets_success'])) {
            $flashSuccess = $_SESSION['assets_success'];
            unset($_SESSION['assets_success']);
        }
        if (isset($_SESSION['assets_error'])) {
            $flashError = $_SESSION['assets_error'];
            unset($_SESSION['assets_error']);
        }

        // ── Routing / action ──────────────────────────────────────
        $action    = $_GET['action'] ?? 'list';
        $editId    = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $formError = null;
        $editAsset = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formAction = $_POST['form_action'] ?? '';

            if ($formAction === 'send_to_patient') {
                $this->processSendToPatient();   // exits via redirect

            } elseif ($formAction === 'remove_send') {
                $this->processRemoveSend();      // exits via redirect

            } elseif ($formAction === 'delete') {
                $this->requireWrite();
                $formError = $this->processDelete($pdo);

            } elseif ($formAction === 'create') {
                $this->requireWrite();
                $formError = $this->processCreate($pdo);
                if ($formError !== null) {
                    $action    = 'create';
                    $editAsset = $this->buildFormState(0);
                }

            } elseif ($formAction === 'edit') {
                $this->requireWrite();
                $formError = $this->processEdit($pdo);
                if ($formError !== null) {
                    $action    = 'edit';
                    $editAsset = $this->buildFormState((int)($_POST['asset_id'] ?? 0));
                }
            }

        } elseif ($action === 'edit' && $editId !== null) {
            if (!$canWrite) {
                $action = 'list';
            } else {
                $stmt = $pdo->prepare('SELECT * FROM assets WHERE asset_id = ?');
                $stmt->execute([$editId]);
                $editAsset = $stmt->fetch();
                if (!$editAsset) {
                    $action = 'list';
                }
            }

        } elseif ($action === 'create') {
            if (!$canWrite) {
                $action = 'list';
            } else {
                $editAsset = [
                    'asset_id'        => 0,
                    'title'           => '',
                    'description'     => '',
                    'asset_type'      => 'PDF',
                    'category'        => '',
                    'storage_type'    => 'LOCAL',
                    'resource_url'    => '',
                    'local_file_path' => '',
                    'file_name'       => '',
                    'file_size_bytes' => null,
                    'is_public'       => 1,
                    'is_active'       => 1,
                    'notes'           => '',
                ];
            }
        }

        // ── Filters ───────────────────────────────────────────────
        $filterType     = $_GET['type']     ?? '';
        $filterCategory = $_GET['category'] ?? '';
        $filterPublic   = $_GET['public']   ?? '';
        $filterSearch   = trim($_GET['search'] ?? '');

        $validTypes = ['VIDEO', 'PDF', 'IMAGE', 'DOCUMENT', 'AUDIO', 'FORM', 'OTHER'];

        $conditions = [];
        $params     = [];

        if ($filterType !== '' && in_array($filterType, $validTypes, true)) {
            $conditions[] = 'a.asset_type = ?';
            $params[]     = $filterType;
        }
        if ($filterCategory !== '') {
            $conditions[] = 'a.category = ?';
            $params[]     = $filterCategory;
        }
        if ($filterPublic === '1') {
            $conditions[] = 'a.is_public = 1';
        } elseif ($filterPublic === '0') {
            $conditions[] = 'a.is_public = 0';
        }
        if ($filterSearch !== '') {
            $esc          = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $filterSearch) . '%';
            $conditions[] = "(a.title LIKE ? ESCAPE '\\\\' OR a.description LIKE ? ESCAPE '\\\\' OR a.category LIKE ? ESCAPE '\\\\')";
            array_push($params, $esc, $esc, $esc);
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $pdo->prepare(
            "SELECT a.*,
                    u.first_name, u.last_name,
                    COUNT(DISTINCT pa.patient_id) AS send_count
               FROM assets a
               LEFT JOIN users u         ON u.user_id  = a.uploaded_by_user_id
               LEFT JOIN patient_assets pa ON pa.asset_id = a.asset_id
              {$where}
              GROUP BY a.asset_id
              ORDER BY a.created_at DESC"
        );
        $stmt->execute($params);
        $assetsList = $stmt->fetchAll();

        // Distinct categories for the filter dropdown
        $categories = $pdo->query(
            "SELECT DISTINCT category FROM assets
              WHERE category IS NOT NULL AND category <> ''
              ORDER BY category"
        )->fetchAll(PDO::FETCH_COLUMN);

        require __DIR__ . '/../views/admin/assets.php';
    }

    // ── File download / serve ─────────────────────────────────────────────────

    public function download(): void
    {
        $isStaff   = !empty($_SESSION['user_id']);
        $isPatient = !empty($_SESSION['patient_account_id']);

        if (!$isStaff && !$isPatient) {
            http_response_code(403);
            exit('Access denied.');
        }

        $assetId = (int)($_GET['id'] ?? 0);
        if ($assetId <= 0) {
            http_response_code(404);
            exit;
        }

        $pdo  = getDBConnection();
        $stmt = $pdo->prepare('SELECT * FROM assets WHERE asset_id = ? AND is_active = 1');
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();

        if (!$asset) {
            http_response_code(404);
            exit('Asset not found.');
        }

        // Staff: must have assets read permission
        if ($isStaff && !can($_SESSION['user_role'] ?? '', 'assets')) {
            http_response_code(403);
            exit('Access denied.');
        }

        // Patient auth: asset must be public OR explicitly sent to this patient
        if ($isPatient && !$isStaff) {
            if (!$asset['is_public']) {
                $stmt2 = $pdo->prepare(
                    'SELECT 1 FROM patient_assets WHERE asset_id = ? AND patient_id = ?'
                );
                $stmt2->execute([$assetId, (int)$_SESSION['patient_id']]);
                if (!$stmt2->fetch()) {
                    http_response_code(403);
                    exit('Access denied.');
                }
            }
        }

        if ($asset['storage_type'] !== 'LOCAL' || empty($asset['local_file_path'])) {
            http_response_code(400);
            exit('This asset does not have a locally stored file.');
        }

        $filePath = __DIR__ . '/../../' . self::UPLOAD_BASE . $asset['local_file_path'];
        if (!is_file($filePath)) {
            http_response_code(404);
            exit('File not found on server.');
        }

        // Detect actual MIME type for safe headers
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        $inline   = self::INLINE_MIME[$mimeType] ?? false;
        $filename = $asset['file_name'] ?: basename($filePath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment')
               . '; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . (string)filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($filePath);
        exit;
    }

    // ── Private: create ───────────────────────────────────────────────────────

    private function processCreate(PDO $pdo): ?string
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assetType   = $_POST['asset_type'] ?? '';
        $category    = trim($_POST['category'] ?? '');
        $storageType = $_POST['storage_type'] ?? '';
        $resourceUrl = trim($_POST['resource_url'] ?? '');
        $fileName    = trim($_POST['file_name'] ?? '');
        $fileSize    = $_POST['file_size_bytes'] ?? null;
        $isPublic    = isset($_POST['is_public']) ? 1 : 0;
        $isActive    = isset($_POST['is_active']) ? 1 : 0;
        $notes       = trim($_POST['notes'] ?? '');

        if ($title === '') {
            return 'Title is required.';
        }

        $validTypes = ['VIDEO', 'PDF', 'IMAGE', 'DOCUMENT', 'AUDIO', 'FORM', 'OTHER'];
        if (!in_array($assetType, $validTypes, true)) {
            return 'Invalid asset type selected.';
        }

        $validStorage = ['URL', 'LOCAL', 'OTHER'];
        if (!in_array($storageType, $validStorage, true)) {
            return 'Invalid storage type selected.';
        }

        if ($resourceUrl !== '' && !filter_var($resourceUrl, FILTER_VALIDATE_URL)) {
            return 'Please enter a valid URL (include https://).';
        }

        $localFilePath = null;

        // Handle file upload for LOCAL storage
        if ($storageType === 'LOCAL'
            && isset($_FILES['asset_file'])
            && $_FILES['asset_file']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $uploadResult = $this->handleFileUpload($_FILES['asset_file']);
            if (is_string($uploadResult)) {
                return $uploadResult;
            }
            $localFilePath = $uploadResult['path'];
            $fileName      = $uploadResult['name'];
            $fileSize      = $uploadResult['size'];
        }

        if ($storageType === 'LOCAL' && $localFilePath === null && $resourceUrl === '') {
            return 'Please upload a file for LOCAL storage, or switch to URL storage type.';
        }

        $fileSize = ($fileSize !== null && $fileSize !== '') ? (int)$fileSize : null;

        $stmt = $pdo->prepare(
            'INSERT INTO assets
                (title, description, asset_type, category, storage_type,
                 resource_url, local_file_path, file_name, file_size_bytes,
                 is_public, is_active, notes, uploaded_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $title, $description ?: null, $assetType, $category ?: null, $storageType,
            $resourceUrl ?: null, $localFilePath, $fileName ?: null, $fileSize,
            $isPublic, $isActive, $notes ?: null, $_SESSION['user_id'],
        ]);

        $_SESSION['assets_success'] = 'Asset "' . $title . '" created successfully.';
        header('Location: assets.php');
        exit;
    }

    // ── Private: edit ─────────────────────────────────────────────────────────

    private function processEdit(PDO $pdo): ?string
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $assetId     = (int)($_POST['asset_id'] ?? 0);
        if ($assetId <= 0) {
            return 'Invalid asset.';
        }

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assetType   = $_POST['asset_type'] ?? '';
        $category    = trim($_POST['category'] ?? '');
        $storageType = $_POST['storage_type'] ?? '';
        $resourceUrl = trim($_POST['resource_url'] ?? '');
        $fileName    = trim($_POST['file_name'] ?? '');
        $fileSize    = $_POST['file_size_bytes'] ?? null;
        $isPublic    = isset($_POST['is_public']) ? 1 : 0;
        $isActive    = isset($_POST['is_active']) ? 1 : 0;
        $notes       = trim($_POST['notes'] ?? '');

        if ($title === '') {
            return 'Title is required.';
        }

        $validTypes = ['VIDEO', 'PDF', 'IMAGE', 'DOCUMENT', 'AUDIO', 'FORM', 'OTHER'];
        if (!in_array($assetType, $validTypes, true)) {
            return 'Invalid asset type selected.';
        }

        $validStorage = ['URL', 'LOCAL', 'OTHER'];
        if (!in_array($storageType, $validStorage, true)) {
            return 'Invalid storage type selected.';
        }

        if ($resourceUrl !== '' && !filter_var($resourceUrl, FILTER_VALIDATE_URL)) {
            return 'Please enter a valid URL (include https://).';
        }

        // Fetch existing to carry forward file metadata if no new upload
        $stmt = $pdo->prepare(
            'SELECT local_file_path, file_name, file_size_bytes FROM assets WHERE asset_id = ?'
        );
        $stmt->execute([$assetId]);
        $existing = $stmt->fetch();
        if (!$existing) {
            return 'Asset not found.';
        }

        $localFilePath = $existing['local_file_path'];
        if ($fileName === '') {
            $fileName = $existing['file_name'] ?? '';
        }
        if (($fileSize === '' || $fileSize === null) && $existing['file_size_bytes'] !== null) {
            $fileSize = $existing['file_size_bytes'];
        }

        // Handle replacement file upload
        if ($storageType === 'LOCAL'
            && isset($_FILES['asset_file'])
            && $_FILES['asset_file']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $uploadResult = $this->handleFileUpload($_FILES['asset_file']);
            if (is_string($uploadResult)) {
                return $uploadResult;
            }
            // Remove old file
            if ($localFilePath) {
                $oldPath = __DIR__ . '/../../' . self::UPLOAD_BASE . $localFilePath;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $localFilePath = $uploadResult['path'];
            $fileName      = $uploadResult['name'];
            $fileSize      = $uploadResult['size'];
        }

        $fileSize = ($fileSize !== null && $fileSize !== '') ? (int)$fileSize : null;

        $stmt = $pdo->prepare(
            'UPDATE assets
                SET title = ?, description = ?, asset_type = ?, category = ?,
                    storage_type = ?, resource_url = ?, local_file_path = ?,
                    file_name = ?, file_size_bytes = ?,
                    is_public = ?, is_active = ?, notes = ?
              WHERE asset_id = ?'
        );
        $stmt->execute([
            $title, $description ?: null, $assetType, $category ?: null, $storageType,
            $resourceUrl ?: null, $localFilePath, $fileName ?: null, $fileSize,
            $isPublic, $isActive, $notes ?: null, $assetId,
        ]);

        $_SESSION['assets_success'] = 'Asset updated successfully.';
        header('Location: assets.php');
        exit;
    }

    // ── Private: delete ───────────────────────────────────────────────────────

    private function processDelete(PDO $pdo): ?string
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            return 'Invalid request.';
        }

        $assetId = (int)($_POST['asset_id'] ?? 0);
        if ($assetId <= 0) {
            return 'Invalid asset.';
        }

        $stmt = $pdo->prepare('SELECT local_file_path FROM assets WHERE asset_id = ?');
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();

        // Remove local file if present
        if ($asset && $asset['local_file_path']) {
            $filePath = __DIR__ . '/../../' . self::UPLOAD_BASE . $asset['local_file_path'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        $pdo->prepare('DELETE FROM assets WHERE asset_id = ?')->execute([$assetId]);

        $_SESSION['assets_success'] = 'Asset deleted.';
        header('Location: assets.php');
        exit;
    }

    // ── Send asset to patient ─────────────────────────────────────────────────

    private function processSendToPatient(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $role = $_SESSION['user_role'] ?? '';
        if (!can($role, 'assets') || !can($role, 'patient_data')) {
            $_SESSION['assets_error'] = 'You do not have permission to send assets to patients.';
            header('Location: assets.php');
            exit;
        }

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $_SESSION['assets_error'] = 'Invalid request.';
            header('Location: assets.php');
            exit;
        }

        $assetId   = (int)($_POST['asset_id']   ?? 0);
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $note      = trim($_POST['note'] ?? '');

        if ($assetId <= 0 || $patientId <= 0) {
            $_SESSION['assets_error'] = 'Please select both an asset and a patient.';
            header('Location: assets.php');
            exit;
        }

        $pdo = getDBConnection();

        $stmt = $pdo->prepare('SELECT title FROM assets WHERE asset_id = ? AND is_active = 1');
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();
        if (!$asset) {
            $_SESSION['assets_error'] = 'Asset not found.';
            header('Location: assets.php');
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT patient_id, first_name, last_name FROM patients WHERE patient_id = ? AND is_active = 1'
        );
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        if (!$patient) {
            $_SESSION['assets_error'] = 'Patient not found.';
            header('Location: assets.php');
            exit;
        }

        try {
            $pdo->prepare(
                'INSERT INTO patient_assets (asset_id, patient_id, sent_by_user_id, note)
                 VALUES (?, ?, ?, ?)'
            )->execute([$assetId, $patientId, (int)$_SESSION['user_id'], $note ?: null]);

            $patientName = trim($patient['first_name'] . ' ' . $patient['last_name']);
            $_SESSION['assets_success'] = '"' . $asset['title'] . '" sent to ' . $patientName . '.';
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'uq_patient_asset')) {
                $_SESSION['assets_error'] = 'This asset has already been sent to that patient.';
            } else {
                $_SESSION['assets_error'] = 'Could not send asset. Please try again.';
            }
        }

        header('Location: assets.php');
        exit;
    }

    // ── Remove patient-asset send ─────────────────────────────────────────────

    private function processRemoveSend(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        if (!can($_SESSION['user_role'] ?? '', 'assets', 'W')) {
            $_SESSION['assets_error'] = 'Permission denied.';
            header('Location: assets.php');
            exit;
        }

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $_SESSION['assets_error'] = 'Invalid request.';
            header('Location: assets.php');
            exit;
        }

        $patientAssetId = (int)($_POST['patient_asset_id'] ?? 0);
        if ($patientAssetId <= 0) {
            $_SESSION['assets_error'] = 'Invalid record.';
            header('Location: assets.php');
            exit;
        }

        $pdo = getDBConnection();
        $pdo->prepare('DELETE FROM patient_assets WHERE patient_asset_id = ?')
            ->execute([$patientAssetId]);

        $_SESSION['assets_success'] = 'Asset removed from patient portal.';
        header('Location: assets.php');
        exit;
    }

    // ── File upload handler ───────────────────────────────────────────────────

    /** Returns associative array [path, name, size] on success, or error string on failure. */
    private function handleFileUpload(array $file): string|array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload size limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload size limit.',
                UPLOAD_ERR_PARTIAL    => 'File upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is unavailable.',
                UPLOAD_ERR_CANT_WRITE => 'Server could not write the file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
            ];
            return $errorMessages[$file['error']] ?? 'Unknown upload error (code ' . $file['error'] . ').';
        }

        if ($file['size'] > self::MAX_FILE_BYTES) {
            return 'File is too large. Maximum allowed size is 20 MB.';
        }

        // Use finfo for reliable MIME detection (ignore browser-supplied type)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIME[$mimeType])) {
            return 'File type not allowed. Accepted types: PDF, images (JPEG/PNG/GIF/WebP), Word documents, Excel spreadsheets.';
        }

        $ext     = self::ALLOWED_MIME[$mimeType];
        $uuid    = bin2hex(random_bytes(16));
        $subdir  = date('Y/m');
        $relPath = $subdir . '/' . $uuid . '.' . $ext;
        $fullDir = __DIR__ . '/../../' . self::UPLOAD_BASE . $subdir;

        if (!is_dir($fullDir) && !mkdir($fullDir, 0755, true)) {
            return 'Could not create upload directory. Please contact the administrator.';
        }

        if (!move_uploaded_file($file['tmp_name'], $fullDir . '/' . $uuid . '.' . $ext)) {
            return 'Could not save the uploaded file. Please try again.';
        }

        return [
            'path' => $relPath,
            'name' => $file['name'],
            'size' => $file['size'],
        ];
    }

    // ── Rebuild form state after validation failure ───────────────────────────

    private function buildFormState(int $assetId): array
    {
        return [
            'asset_id'        => $assetId,
            'title'           => trim($_POST['title'] ?? ''),
            'description'     => trim($_POST['description'] ?? ''),
            'asset_type'      => $_POST['asset_type'] ?? 'PDF',
            'category'        => trim($_POST['category'] ?? ''),
            'storage_type'    => $_POST['storage_type'] ?? 'LOCAL',
            'resource_url'    => trim($_POST['resource_url'] ?? ''),
            'local_file_path' => '',
            'file_name'       => trim($_POST['file_name'] ?? ''),
            'file_size_bytes' => $_POST['file_size_bytes'] ?? null,
            'is_public'       => isset($_POST['is_public']) ? 1 : 0,
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
            'notes'           => trim($_POST['notes'] ?? ''),
        ];
    }
}
