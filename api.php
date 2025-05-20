<?php
require_once __DIR__ . '/config/config.php';

// En-tête pour du JSON
header('Content-Type: application/json; charset=utf-8');

// Requête les voitures
$cars = $pdo->query(
    "SELECT id, badge, marque, modele, type, motorisation, prix, available_from, staged
     FROM cars
     ORDER BY id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Requête les réservations
$reservations = $pdo->query(
    "SELECT id, user_id, car_id, start_date, end_date, total_price, payment_amount, payment_status, created_at
     FROM reservations
     ORDER BY created_at ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Prépare le JSON à retourner
$export = [
    'generated_at'  => date('c'),
    'total_stock'   => count($cars),
    'cars'          => $cars,
    'reservations'  => $reservations,
];

// Retourne le JSON
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
