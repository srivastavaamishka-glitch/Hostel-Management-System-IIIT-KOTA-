<?php
/**
 * Database Configuration Example
 * IIIT Kota Hostel Management System
 *
 * Copy this file to db.php. Defaults work for a fresh XAMPP install.
 * Do NOT commit db.php — it is listed in .gitignore.
 */

declare(strict_types=1);

/**
 * Returns the PDO database singleton instance.
 * Configured with strict error handling and native prepared statements.
 *
 * @return PDO
 */
function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // Default XAMPP settings with optional environment variable overrides
        $host    = getenv('DB_HOST') !== false ? (string)getenv('DB_HOST') : '127.0.0.1';
        $port    = getenv('DB_PORT') !== false ? (string)getenv('DB_PORT') : '3306';
        $dbname  = getenv('DB_NAME') !== false ? (string)getenv('DB_NAME') : 'hostel_db';
        $user    = getenv('DB_USER') !== false ? (string)getenv('DB_USER') : 'root';
        $pass    = getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());

            if (php_sapi_name() === 'cli') {
                fwrite(STDERR, "Database connection failed. Check db.php settings." . PHP_EOL);
                exit(1);
            }

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }

            echo json_encode([
                'ok'    => false,
                'error' => 'Database connection failed. Please ensure MySQL is running in XAMPP and hostel_db is imported.'
            ]);
            exit;
        }
    }

    return $pdo;
}
