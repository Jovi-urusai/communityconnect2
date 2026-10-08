<?php
// Database connection for CommunityConnect

define('DB_HOST', 'localhost');
define('DB_NAME', 'communityconnect2');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please import sql/communityconnect.sql and check config/db.php credentials. (' . htmlspecialchars($e->getMessage()) . ')');
}
