<?php
// favorites.php
require __DIR__ . '/config/config.php';

// Démarre la session seulement si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection si non connecté
if (empty($_SESSION['user']['id'])) {
    header('Location: auth/login.php');
    exit;
}
$userId = $_SESSION['user']['id'];

// Récupérer les favoris
$stmt = $pdo->prepare("
    SELECT c.*
    FROM favorites f
    JOIN cars c ON f.car_id = c.id
    WHERE f.user_id = ?
");
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes voitures favorites</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Style perso (après Tailwind pour surcharge si besoin) -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50">

  <!-- HEADER -->
  <?php include __DIR__ . '/header.php'; ?>

  <!-- CONTENU PRINCIPAL -->
  <main class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Mes voitures favorites</h1>

    <?php if (empty($favorites)): ?>
      <p class="text-gray-700">Vous n'avez pas encore ajouté de coups de cœur.</p>
    <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($favorites as $car): ?>
          <div class="bg-white rounded-lg shadow hover:shadow-lg overflow-hidden">
            <img
              src="<?= htmlspecialchars($car['image_url'], ENT_QUOTES) ?>"
              alt="<?= htmlspecialchars($car['marque'].' '.$car['modele'], ENT_QUOTES) ?>"
              class="w-full h-48 object-cover cursor-pointer"
              onclick="location.href='car.php?id=<?= (int)$car['id'] ?>'"
            >
            <div class="p-4">
              <h3 class="text-lg font-semibold mb-2">
                <?= htmlspecialchars($car['marque'].' '.$car['modele'], ENT_QUOTES) ?>
              </h3>
              <p class="text-gray-700"><?= (int)$car['prix'] ?> € / mois</p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- FOOTER -->
  <?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
