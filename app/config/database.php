<?php
/**
 * config/database.php
 *
 * Returns a singleton PDO connection.  Credentials are read once
 * from the project .env file at the repository root.
 */

function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // ── load .env ───────────────────────────────────────────────
    $envPath = __DIR__ . '/../../.env';
    if (file_exists($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }

    // ── Build DSN (works for both local and server) ───────────────────
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_NAME') ?: 'core_app'
    );

    $pdo = new PDO(
        $dsn,
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') ?: '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    return $pdo;
}
?>
