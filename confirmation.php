<?php
require_once __DIR__ . '/config/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// 1. Vérifier que l'utilisateur est connecté
if (empty($_SESSION['user']['id'])) {
    header('Location: auth/login.php');
    exit;
}

// 2. Récupérer les paramètres PayPal (GET ou POST)
$token          = $_GET['token']          ?? $_POST['token']          ?? null;
$payerId        = $_GET['PayerID']        ?? $_POST['PayerID']        ?? null;
$paymentId      = $_GET['paymentId']      ?? $_POST['paymentId']      ?? null;
$subscriptionId = $_GET['subscriptionId'] ?? $_POST['subscriptionId'] ?? null;

// Si on n'a pas de token, tenter celui en session
if (!$token && isset($_SESSION['payment_token'])) {
    $token = $_SESSION['payment_token'];
}

// Vérifier la présence de données PayPal
$hasPaymentData = $token || $payerId || $paymentId || $subscriptionId;
if (!$hasPaymentData) {
    header('Location: index.php');
    exit;
}

// 3. Générateur de code de retrait unique
function generateUniquePickupCode(PDO $pdo) {
    do {
        $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE pickup_code = :code");
        $stmt->execute([':code' => $code]);
        $count = $stmt->fetchColumn();
    } while ($count > 0);
    return $code;
}

// 4. Vérifier que le panier existe
if (empty($_SESSION['cart'])) {
    $_SESSION['error_message'] = "Votre panier est vide.";
    header('Location: pay.php');
    exit;
}

// 5. Traitement et insertion des réservations
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    $userId     = $_SESSION['user']['id'];
    $pickupCode = generateUniquePickupCode($pdo);
    $paymentRef = $paymentId ?? $subscriptionId ?? 'PP-' . uniqid();

    // Préparer requêtes
    $stmtInsert = $pdo->prepare(
        "INSERT INTO reservations
         (user_id, car_id, car_marque, car_modele, car_type, car_motorisation, car_prix, car_image,
          start_date, end_date, total_price, payment_amount, payment_id,
          payment_status, status, pickup_code)
         VALUES
         (:user_id, :car_id, :marque, :modele, :type, :motorisation, :prix, :image,
          :start_date, :end_date, :total_price, :payment_amount, :payment_id,
          'completed', 'paid', :pickup_code)"
    );
    $stmtCheck = $pdo->prepare(
        "SELECT 1 FROM cars
         WHERE id = :car_id
           AND (available_from IS NULL OR available_from <= CURDATE())"
    );
    $stmtUpdateCar = $pdo->prepare("UPDATE cars SET available_from = :available_from, staged = 1 WHERE id = :car_id");
    $stmtDeleteCart = $pdo->prepare("DELETE FROM carts WHERE user_id = :user_id AND car_id = :car_id");

    // Vérifier la disponibilité
    foreach ($_SESSION['cart'] as $item) {
        $stmtCheck->execute([':car_id' => $item['car']['id']]);
        if (!$stmtCheck->fetch()) {
            throw new Exception("La voiture {$item['car']['marque']} {$item['car']['modele']} n'est pas disponible.");
        }
    }

    // Insérer chaque réservation
    foreach ($_SESSION['cart'] as $item) {
        $car = $item['car'];
        $duration = (int)($item['duration'] ?? 1);
        $start = new DateTime();
        $end = (clone $start)->modify("+{$duration} months");
        $total = $car['prix'] * $duration;

        $stmtInsert->execute([
            'user_id'        => $userId,
            'car_id'         => $car['id'],
            'marque'         => $car['marque'],
            'modele'         => $car['modele'],
            'type'           => $car['type'],
            'motorisation'   => $car['motorisation'],
            'prix'           => $car['prix'],
            'image'          => $car['image_url'] ?? null,
            'start_date'     => $start->format('Y-m-d'),
            'end_date'       => $end->format('Y-m-d'),
            'total_price'    => $total,
            'payment_amount' => $total,
            'payment_id'     => $paymentRef,
            'pickup_code'    => $pickupCode
        ]);
        $stmtUpdateCar->execute([':available_from' => $end->format('Y-m-d'), ':car_id' => $car['id']]);
        $stmtDeleteCart->execute([':user_id' => $userId, ':car_id' => $car['id']]);
    }

    $pdo->commit();

    // Stocker le code et nettoyage
    $_SESSION['last_pickup_code']  = $pickupCode;
    unset($_SESSION['cart'], $_SESSION['payment_token']);

    // Log de réussite (sans arrêter en cas d'erreur de log)
    try {
        $log = $pdo->prepare("INSERT IGNORE INTO logs(type, message) VALUES('payment_success', :msg)");
        $log->execute(['msg' => "Réservation user={$userId} code={$pickupCode}"]);
    } catch (Exception $ignored) {}

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log d'erreur (sans stopper)
    try {
        $log = $pdo->prepare("INSERT IGNORE INTO logs(type, message) VALUES('payment_error', :msg)");
        $log->execute(['msg' => "Erreur réservation user={$userId}: {$e->getMessage()}"]);
    } catch (Exception $ignored) {}

    $_SESSION['error_message'] = 'Erreur lors de la réservation : ' . $e->getMessage();
    header('Location: pay.php');
    exit;
}

// 6. Affichage de la confirmation
$pickupCode = $_SESSION['last_pickup_code'] ?? 'NON DISPONIBLE';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Confirmation - NOVALOC</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
  <?php include __DIR__ . '/header.php'; ?>
  <main class="container mx-auto px-6 py-8 flex-1">
    <div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-md text-center">
      <svg class="w-12 h-12 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <h1 class="text-3xl font-bold text-gray-800 mb-4">Paiement confirmé !</h1>
      <p class="mb-6 text-gray-600">Votre réservation est enregistrée.</p>
      <div class="bg-gray-100 p-4 rounded-lg mb-6">
        <p class="font-medium text-gray-700">Votre code de retrait :</p>
        <p class="my-2 font-mono text-2xl font-bold text-gray-900"><?= htmlspecialchars($pickupCode) ?></p>
        <p class="text-sm text-gray-500">Conservez-le pour récupérer votre véhicule.</p>
      </div>
      <div class="flex flex-col space-y-4">
        <a href="locations_en_cours.php" class="px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">Mes locations</a>
        <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Accueil</a>
      </div>
    </div>

    <!-- Bloc MAP -->
    <div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-md text-center mt-8">
        <h2 class="text-xl font-semibold mb-2">Rendez-vous à l'IUT de Béziers pour retirer votre voiture</h2>
        <div id="map" style="height: 350px; border-radius: 0.75rem;" class="mb-4"></div>
        <div id="route-info" class="text-gray-600"></div>
        <p class="text-sm text-gray-500 mt-2">Adresse : <strong>3 Place du 14 Juillet, 34500 Béziers</strong></p>
    </div>
  </main>
  <?php include __DIR__ . '/footer.php'; ?>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <!-- Script de calcul de l'itinéraire -->
  <script>
    const destination = [43.34690342454306, 3.222171415341779]; // IUT Béziers [lat, lng]
    let map = L.map('map').setView(destination, 13);

    // Fond de carte
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Marker IUT
    const markerIUT = L.marker(destination).addTo(map)
        .bindPopup("<b>IUT de Béziers</b>").openPopup();

    // Fonction calcul et affichage itinéraire
    function showRoute(userLat, userLng) {
        const userMarker = L.marker([userLat, userLng], {title: "Votre position"}).addTo(map)
            .bindPopup("Vous êtes ici").openPopup();

        map.fitBounds([destination, [userLat, userLng]], {padding: [50, 50]});

        fetch("https://api.openrouteservice.org/v2/directions/driving-car/geojson", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "5b3ce3597851110001cf62488cbe146d14af428faec9c1702ddadef1" // <---- REMPLACE ICI PAR TA CLÉ
            },
            body: JSON.stringify({
                coordinates: [
                    [userLng, userLat], // lng, lat
                    [destination[1], destination[0]]
                ]
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data || !data.features || !data.features.length) {
                document.getElementById("route-info").innerHTML =
                    "<span class='text-red-500'>Impossible de calculer l'itinéraire.</span>";
                return;
            }
            const coords = data.features[0].geometry.coordinates.map(pt => [pt[1], pt[0]]);
            L.polyline(coords, {color: 'blue', weight: 5}).addTo(map);

            const props = data.features[0].properties.segments[0];
            let minutes = Math.round(props.duration / 60);
            let km = (props.distance / 1000).toFixed(1);
            document.getElementById("route-info").innerHTML =
                `Temps estimé : <b>${minutes} min</b> &mdash; Distance : <b>${km} km</b>`;
        })
        .catch(() => {
            document.getElementById("route-info").innerHTML =
                "<span class='text-red-500'>Impossible de calculer l'itinéraire. Veuillez réessayer.</span>";
        });
    }

    // Géolocalisation utilisateur
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            showRoute(pos.coords.latitude, pos.coords.longitude);
        }, function() {
            document.getElementById("route-info").innerHTML =
                "<span class='text-red-500'>Autorisez la géolocalisation pour voir l'itinéraire.</span>";
        });
    } else {
        document.getElementById("route-info").innerHTML =
            "<span class='text-red-500'>Géolocalisation non supportée par votre navigateur.</span>";
    }
  </script>
</body>
</html>
