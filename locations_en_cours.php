<?php
// locations_en_cours.php
require __DIR__ . '/config/config.php';
require __DIR__ . '/admin/fpdf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection si non connecté
if (empty($_SESSION['user']['id'])) {
    header('Location: auth/login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// 0) Téléchargement manuel de la facture
if (isset($_GET['invoice_reservation'])) {
    $reservationId = (int) $_GET['invoice_reservation'];

    // Vérifier que la réservation appartient bien à cet utilisateur
    $check = $pdo->prepare("
        SELECT id
        FROM reservations
        WHERE id = :id
          AND user_id = :user_id
          AND payment_status = 'completed'
    ");
    $check->execute([
        ':id'      => $reservationId,
        ':user_id' => $userId
    ]);
    if ($check->fetch()) {
        // Générer et envoyer le PDF
        $fileName = generateInvoice($reservationId, $pdo);
        $filePath = __DIR__ . "/pdf/files/{$fileName}";
        if (file_exists($filePath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            $flash = "Erreur : facture introuvable.";
        }
    } else {
        $flash = "Accès refusé ou réservation invalide.";
    }
}

// 1) Traitement de la suppression de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['pickup_code'])) {
    $pickupCode = $_POST['pickup_code'];

    // Vérifie que la réservation existe et appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT id, car_id FROM reservations WHERE pickup_code = :pickup_code AND user_id = :user_id AND status = 'paid'");
    $stmt->execute([
        ':pickup_code' => $pickupCode,
        ':user_id' => $userId
    ]);
    $reservation = $stmt->fetch();

    if ($reservation) {
    // 1. Annuler la réservation (status à 'cancelled')
    $stmtCancel = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = :id");
    $stmtCancel->execute([':id' => $reservation['id']]);

    // 2. Mettre à jour la voiture : available_from à aujourd'hui (la voiture redevient dispo)
    $today = date('Y-m-d');
    $stmtCar = $pdo->prepare("UPDATE cars SET available_from = :today WHERE id = :car_id");
    $stmtCar->execute([
        ':today' => $today,
        ':car_id' => $reservation['car_id']
    ]);

    // 3. Supprimer la réservation annulée de la DB
    $stmtDeleteReservation = $pdo->prepare("DELETE FROM reservations WHERE id = :id");
    $stmtDeleteReservation->execute([
        ':id' => $reservation['id']
    ]);

    $flash = "La réservation a bien été annulée, supprimée et la voiture est à nouveau disponible.";
    } else {
    $flash = "Erreur : réservation introuvable ou déjà annulée.";
    }

}

// 2) Requête des réservations toujours actives
$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.pickup_code,
        r.start_date,
        r.end_date,
        c.id        AS car_id,
        c.marque,
        c.modele,
        c.image_url,
        r.total_price
    FROM reservations r
    JOIN cars c ON r.car_id = c.id
    WHERE r.user_id = :user_id
      AND r.end_date >= CURDATE()
      AND r.status = 'paid'
    ORDER BY r.start_date DESC
");
$stmt->execute([':user_id' => $userId]);
$reservations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mes locations en cours</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50">

  <?php include __DIR__ . '/header.php'; ?>

  <main class="container mx-auto p-6">

    <?php if (!empty($flash)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <h1 class="text-3xl font-bold mb-6">Mes locations en cours</h1>

    <?php if (empty($reservations)): ?>
      <p class="text-gray-700">Vous n'avez aucune location en cours.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($reservations as $res): ?>
          <div class="bg-white p-4 rounded-lg shadow flex items-center justify-between">
            <div class="flex items-center">
              <img
                src="<?= htmlspecialchars($res['image_url'], ENT_QUOTES) ?>"
                alt="<?= htmlspecialchars($res['marque'].' '.$res['modele'], ENT_QUOTES) ?>"
                class="w-24 h-16 object-cover rounded mr-4"
              >
              <div>
                <div class="font-semibold text-lg">
                  <?= htmlspecialchars($res['marque'].' '.$res['modele'], ENT_QUOTES) ?>
                </div>
                <div class="text-sm text-gray-600">
                  Du <?= htmlspecialchars($res['start_date']) ?> au <?= htmlspecialchars($res['end_date']) ?>
                </div>
                <div class="text-sm text-gray-800 mt-1">
                  Prix total : <?= number_format($res['total_price'], 2, ',', ' ') ?> €
                </div>
                <div class="text-sm text-gray-800">
                  Code pickup : <span class="font-mono font-semibold"><?= htmlspecialchars($res['pickup_code'], ENT_QUOTES) ?></span>
                </div>
              </div>
            </div>
            <div class="flex flex-col space-y-2">
              <form method="POST" onsubmit="return confirm('Voulez-vous vraiment annuler cette réservation ?');">
                <input type="hidden" name="pickup_code" value="<?= htmlspecialchars($res['pickup_code'], ENT_QUOTES) ?>">
                <button
                  type="submit"
                  class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                >Annuler</button>
              </form>
              <a
                href="?invoice_reservation=<?= $res['id'] ?>"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-center"
              >Télécharger la facture</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>

  <?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
