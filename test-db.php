<?php
require_once __DIR__ . '/db.php';
try {
    $row = db()->query('SELECT COUNT(*) AS c FROM users')->fetch();
    echo 'Connected OK. Users in table: ' . $row['c'];
} catch (Exception $e) {
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}
