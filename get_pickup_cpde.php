<?php
// get_pickup_code.php

session_start();

if (empty($_SESSION['user_id']) || empty($_GET['reservation_id'])) {
    http_response_code(400);
    exit('Accès non autorisé');
}

$dsn = 'mysql:host=localhost;dbname=db_URTADO;charset=utf8mb4';
$user = 'ton_user';
$pass = 'ton_mdp';
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$resId = (int) $_GET['reservation_id'];
$stmt = $pdo->prepare("
    SELECT pickup_code, status, start_date, end_date
    FROM reservations
    WHERE id = :id AND user_id = :uid
");
$stmt->execute([
    ':id'  => $resId,
    ':uid' => $_SESSION['user_id'],
]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation || $reservation['status'] !== 'paid') {
    http_response_code(404);
    exit('Réservation introuvable ou non payée');
}

echo "<h1>Code de retrait</h1>";
echo "<p>Réservation du {$reservation['start_date']} au {$reservation['end_date']}<br>";
echo "Votre code : <strong>{$reservation['pickup_code']}</strong></p>";
