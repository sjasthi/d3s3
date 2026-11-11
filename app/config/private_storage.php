<?php
/**
 * Files that must not be served directly by Apache.
 * Callers check the logged-in user before calling sendPrivateFile().
 */

function privateStorageRoot(): string
{
	$root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
	if (!is_dir($root)) {
		mkdir($root, 0755, true);
	}
	$ht = $root . DIRECTORY_SEPARATOR . '.htaccess';
	if (!is_file($ht)) {
		file_put_contents($ht, "Require all denied\nDeny from all\n");
	}
	return $root;
}

function storePrivateFile(array $file, string $subdir, array $allowedExt, int $maxBytes): array
{
	if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
		throw new RuntimeException('The file did not upload.');
	}
	$size = (int)($file['size'] ?? 0);
	if ($size <= 0 || $size > $maxBytes) {
		throw new RuntimeException('The file is empty or larger than the allowed size.');
	}
	$original = basename((string)($file['name'] ?? 'upload'));
	$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
	if (!in_array($ext, $allowedExt, true)) {
		throw new RuntimeException('That file type is not allowed.');
	}
	$dir = privateStorageRoot() . DIRECTORY_SEPARATOR . $subdir;
	if (!is_dir($dir)) {
		mkdir($dir, 0755, true);
	}
	$stored = bin2hex(random_bytes(16)) . '.' . $ext;
	$dest = $dir . DIRECTORY_SEPARATOR . $stored;
	if (!move_uploaded_file($file['tmp_name'], $dest)) {
		throw new RuntimeException('The file could not be stored.');
	}
	$mime = function_exists('mime_content_type') ? (mime_content_type($dest) ?: 'application/octet-stream') : 'application/octet-stream';
	return [
		'stored_name'   => $subdir . '/' . $stored,
		'original_name' => $original,
		'mime'          => $mime,
		'size'          => $size,
	];
}

function sendPrivateFile(string $relative, string $downloadName, string $mime): void
{
	$root = realpath(privateStorageRoot());
	$full = privateStorageRoot() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
	$path = realpath($full);
	if ($root === false || $path === false || !str_starts_with($path, $root) || !is_file($path)) {
		http_response_code(404);
		echo 'File not found.';
		exit;
	}
	header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
	header('Content-Length: ' . (string)filesize($path));
	header('Content-Disposition: inline; filename="' . str_replace('"', '', $downloadName) . '"');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: private, no-store');
	readfile($path);
	exit;
}

function deletePrivateFile(?string $relative): void
{
	if ($relative === null || $relative === '') {
		return;
	}
	$root = realpath(privateStorageRoot());
	$full = privateStorageRoot() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
	$path = realpath($full);
	if ($root && $path && str_starts_with($path, $root) && is_file($path)) {
		unlink($path);
	}
}
