<?php
// commercial/site_view.php
require_once __DIR__ . '/../config/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'commercial') {
    header('Location: ../auth/login.php');
    exit;
}

$cars = $pdo->query("SELECT * FROM cars ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévisualisation Commercial</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <?php include __DIR__ . '/../header.php'; ?>

    <main class="container mx-auto p-6 flex-1">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Prévisualisation du site</h1>
            <a href="dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                Retour au dashboard
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($cars as $car): ?>
                <div class="car-card bg-white rounded-lg shadow-lg overflow-hidden <?= $car['staged'] ? 'ring-4 ring-yellow-300' : '' ?>">
                    <?php 
                    $imgs = json_decode($car['images'], true) ?: [];
                    $mainImage = !empty($imgs) ? $imgs[0] : $car['image_url'];
                    ?>
                    <img 
                        src="../<?= htmlspecialchars($mainImage) ?>" 
                        alt="<?= htmlspecialchars($car['marque'].' '.$car['modele']) ?>"
                        class="w-full h-48 object-cover"
                    >
                    
                    <?php if ($car['staged']): ?>
                        <span class="absolute top-2 left-2 bg-yellow-400 text-gray-800 px-2 py-1 rounded text-sm font-bold">
                            STAGING
                        </span>
                    <?php endif; ?>

                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-bold">
                                <?= htmlspecialchars($car['marque'].' '.$car['modele']) ?>
                            </h3>
                            <?php if (!empty($car['badge'])): ?>
                                <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded">
                                    <?= htmlspecialchars($car['badge']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-gray-600 mt-1">
                            <?= htmlspecialchars($car['type']) ?> • 
                            <?= htmlspecialchars($car['motorisation']) ?>
                        </p>
                        
                        <p class="text-xl font-bold mt-2">
                            <?= number_format($car['prix'], 2, ',', ' ') ?> €/mois
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
