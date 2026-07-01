<?php
// AegisZ Sentinel — Database Connection
// XAMPP default: root / no password

$host = 'localhost';
$db   = 'aegisz_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    return new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('AegisZ DB Connection Failed: ' . $e->getMessage());
    die('Database connection failed. Check config/database.php settings.');
}