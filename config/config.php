<?php
// Database config
$host    = 'localhost';
$db      = 'db_URTADO';
$user    = '22405463';
$pass    = '297518';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Ne démarre la session que si elle n'existe pas déjà
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
