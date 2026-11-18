<?php

/**
 * JJJ GentleCare - MySQL connection
 * Update DB_NAME if your database is "jjj gentlecare" (with a space).
 */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'jjj gentlecare'); // change to 'jjj gentlecare' if needed
define('DB_USER', 'root');
define('DB_PASS', '');               // default XAMPP password is empty
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Fallback: try database name with a space (phpMyAdmin often uses this)
        if (DB_NAME !== 'jjj gentlecare') {
            try {
                $dsn2 = 'mysql:host=' . DB_HOST . ';dbname=jjj gentlecare;charset=' . DB_CHARSET;
                $pdo = new PDO($dsn2, DB_USER, DB_PASS, $options);
                return $pdo;
            } catch (PDOException $e2) {
                // ignore, throw original
            }
        }
        http_response_code(500);
        die('Database connection failed. Check db.php settings. Error: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}
