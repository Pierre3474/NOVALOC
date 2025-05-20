<?php
require_once __DIR__ . '/config/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$token = $_GET['token'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement annulé</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/header.php'; ?>
    <main class="container mx-auto p-6 text-center">
        <div class="bg-white p-6 rounded-lg shadow-md max-w-md mx-auto">
            <h1 class="text-2xl font-bold mb-4">⚠️ Paiement annulé</h1>
            <p class="text-gray-700">Votre paiement a été annulé. Votre panier est conservé.</p>
            <?php if ($token): ?>
                <a href="pay.php?token=<?= urlencode($token) ?>" class="mt-4 inline-block px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Réessayer le paiement
                </a>
            <?php endif; ?>
        </div>
    </main>
    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>