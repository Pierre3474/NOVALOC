<?php
// cart.php
require_once __DIR__ . '/config/config.php';

// 1. Session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2. Suppression d’un article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $removeId = (int)$_POST['remove_id'];
    if (isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
    }
    header('Location: cart.php');
    exit;
}

// 3. Nombre d’articles pour le header
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// 4. Si le panier n’est pas vide, on récupère tous les cars d’un coup
$carsById = [];
if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    // Crée une liste de placeholders (?, ?, …)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT * FROM cars WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Indexe par ID pour accès rapide
    foreach ($fetched as $car) {
        $carsById[$car['id']] = $car;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Votre Panier (<?= $cartCount ?>)</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50 flex flex-col">

<?php include __DIR__ . '/header.php'; ?>

<main class="container px-6 py-8 flex-1">
    <h1 class="text-3xl font-bold mb-6">Votre Panier (<?= $cartCount ?>)</h1>

    <?php if (empty($_SESSION['cart'])): ?>
        <p class="text-gray-700">Votre panier est vide.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php
            $grandTotal = 0;
            foreach ($_SESSION['cart'] as $id => $item):
                // On récupère les infos du car directement depuis la BDD
                if (!isset($carsById[$id])) {
                    continue; // sécurité : si l'ID n'existe plus
                }
                $c         = $carsById[$id];
                $lineTotal = $c['prix'] * $item['qty'];
                $grandTotal += $lineTotal;
            ?>
            <div class="bg-white p-4 rounded-lg shadow flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <img src="<?= htmlspecialchars($c['image_url'], ENT_QUOTES) ?>"
                         alt="<?= htmlspecialchars($c['marque'].' '.$c['modele'], ENT_QUOTES) ?>"
                         class="w-16 h-10 object-cover rounded">
                    <div>
                        <div class="font-medium"><?= htmlspecialchars($c['marque'].' '.$c['modele'], ENT_QUOTES) ?></div>
                        <div class="text-sm text-gray-600">
                            Du <?= htmlspecialchars($item['start_date']) ?>
                            au <?= htmlspecialchars($item['end_date']) ?> •
                            Durée : <?= $item['duration'] ?> mois<br>
                            Acompte : <?= $item['deposit'] ?> € •
                            Km : <?= $item['km'] ?> km/mois
                        </div>
                    </div>
                </div>
                <div class="text-right space-y-2">
                    <div class="font-semibold">
                        <?= number_format($lineTotal, 2, ',', ' ') ?> €
                    </div>
                    <form method="post" action="cart.php">
                        <input type="hidden" name="remove_id" value="<?= $id ?>">
                        <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 text-right">
            <div class="text-xl font-bold">Total : <?= number_format($grandTotal, 2, ',', ' ') ?> €</div>
            <a href="pay.php" class="mt-4 inline-block px-6 py-3 bg-green-600 text-white rounded text-center">Passer à la caisse</a>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
