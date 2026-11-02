<?php
// includes/db.php - the ONE place that connects PHP to MySQL (via PDO).
// Every other PHP file does:  require_once __DIR__ . '/db.php';  then uses $pdo.

$DB_HOST = 'localhost';
$DB_NAME = 'mentality_db';
$DB_USER = 'root';   // limited user - create it by importing sql/11_app_user.sql
$DB_PASS = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // show real errors while developing
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows come back as arrays by column name
    PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements (stops SQL injection)
];

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, $options);
    $pdo->exec("SET time_zone = '+08:00'");   // same clock as PHP's Asia/Manila, so CURDATE()/NOW() agree
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
