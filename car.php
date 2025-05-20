<?php
require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// --------- 1) Toggle favoris ---------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_car_id'])) {
    if (!empty($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
        $favCarId = (int)$_POST['favorite_car_id'];
        $check = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND car_id = ?");
        $check->execute([$userId, $favCarId]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?")->execute([$userId, $favCarId]);
        } else {
            $pdo->prepare("INSERT IGNORE INTO favorites (user_id, car_id) VALUES (?, ?)")->execute([$userId, $favCarId]);
        }
    }
    header('Location: car.php?id=' . (int)$_GET['id']);
    exit;
}

// --------- 2) Ajout au panier (et blocage des doublons de période) ---------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_id']) && !isset($_POST['favorite_car_id'])) {
    // Si le user est admin/commercial, on ne fait rien !
    $role = $_SESSION['user']['role'] ?? '';
    if ($role === 'admin' || $role === 'commercial') {
        header('Location: car.php?id=' . $id);
        exit;
    }
    $id = (int)$_POST['car_id'];
    $start = DateTime::createFromFormat('Y-m-d', $_POST['start_date'] ?? '');
    $end   = DateTime::createFromFormat('Y-m-d', $_POST['end_date'] ?? '');
    $m = null;
    if ($start && $end) {
        $m = $start->diff($end)->y * 12 + $start->diff($end)->m;
        // ---- Durée min 3 mois ----
        if ($m < 3) {
            header('Location: car.php?id=' . $id . '&error=duration'); exit;
        }
        // Vérifie chevauchement réservation (modifie la requête selon ta table de réservations !)
        $overlapCheck = $pdo->prepare("
            SELECT COUNT(*) FROM reservations
            WHERE car_id = ?
            AND (
                (? BETWEEN start_date AND end_date)
                OR
                (? BETWEEN start_date AND end_date)
                OR
                (start_date BETWEEN ? AND ?)
                OR
                (end_date BETWEEN ? AND ?)
            )
        ");
        $overlapCheck->execute([
            $id,
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $start->format('Y-m-d'), $end->format('Y-m-d'),
            $start->format('Y-m-d'), $end->format('Y-m-d'),
        ]);
        if ($overlapCheck->fetchColumn() > 0) {
            header('Location: car.php?id=' . $id . '&error=overlap'); exit;
        }
    }
    $stmt = $pdo->prepare(
        'SELECT id, marque, modele, prix, image_url, images, type, motorisation FROM cars WHERE id = :id'
    );
    $stmt->execute(['id' => $id]);
    $prd = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($prd && $start && $end) {
        $_SESSION['cart'][$id] = [
            'car' => $prd,
            'deposit' => (int)($_POST['deposit'] ?? 0),
            'duration' => $m,
            'km' => (int)($_POST['km'] ?? 0),
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'qty' => 1
        ];
    }
    header('Location: car.php?id=' . $id . '&added=1');
    exit;
}

// --------- 3) Charger la fiche véhicule ---------
$car = $pdo->prepare('SELECT * FROM cars WHERE id = :id');
$car->execute(['id' => $id]);
$car = $car->fetch(PDO::FETCH_ASSOC);

// --------- 4) Pré-favori ---------
$isFavorite = false;
if ($car && !empty($_SESSION['user']['id'])) {
    $chk = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id=? AND car_id=?");
    $chk->execute([$_SESSION['user']['id'], $id]);
    $isFavorite = (bool)$chk->fetchColumn();
}

// --------- 5) Galerie ---------
$imgs = [];
if ($car) {
    if (!empty($car['images'])) {
        $arr = json_decode($car['images'], true);
        $imgs = (json_last_error() === JSON_ERROR_NONE && is_array($arr)) ? $arr : [$car['images']];
    } elseif (!empty($car['image_url'])) {
        $imgs = [$car['image_url']];
    }
}
$galleryImages = array_slice($imgs, 0, 3);

// --------- 6) Options ---------
$depositOptions = [500, 1000, 2000];
$kmOptions = [1000, 2000, 3000];
$others = [];
if ($car) {
    $sth = $pdo->prepare(
        'SELECT id, CONCAT(marque," ",modele) AS title, prix AS price, image_url AS image FROM cars WHERE id != :id LIMIT 4'
    );
    $sth->execute(['id' => $id]);
    $others = $sth->fetchAll(PDO::FETCH_ASSOC);
}

// --------- 7) Récupérer les périodes déjà réservées pour la voiture ---------
$reservedRanges = [];
$reservedDates = [];
if ($car) {
    $res = $pdo->prepare("SELECT start_date, end_date FROM reservations WHERE car_id=?");
    $res->execute([$id]);
    foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $reservedRanges[] = [$r['start_date'], $r['end_date']];
        
        // Générer toutes les dates individuelles dans cette plage
        $startDate = new DateTime($r['start_date']);
        $endDate = new DateTime($r['end_date']);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);
        
        foreach ($dateRange as $date) {
            $reservedDates[] = $date->format('Y-m-d');
        }
        // Ajouter également la date de fin
        $reservedDates[] = $endDate->format('Y-m-d');
    }
}
$role = $_SESSION['user']['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $car ? htmlspecialchars($car['marque'].' '.$car['modele'], ENT_QUOTES) : 'Introuvable' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
      .flatpickr-calendar {
        font-size: 1.15rem !important;
      }
      .flatpickr-calendar.open {
        z-index: 99 !important;
        box-shadow: 0 0 25px 2px #2563eb33;
      }
      .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
        background: #2563eb;
        color: #fff;
      }
      .flatpickr-input {
        cursor: pointer;
        font-size: 1.1rem;
      }
      /* Styles pour les dates réservées */
      .flatpickr-day.reserved {
        background-color: #fecaca; /* Rouge clair */
        color: #b91c1c; /* Rouge foncé */
        text-decoration: line-through;
        cursor: not-allowed;
      }
      .flatpickr-day.reserved:hover {
        background-color: #fecaca !important;
        color: #b91c1c !important;
      }
      .flatpickr-day.disabled.reserved {
        background-color: #fecaca !important;
        color: #b91c1c !important;
      }
    </style>
</head>
<body class="bg-gray-50">
<?php include __DIR__.'/header.php'; ?>
<main class="container mx-auto px-6 py-8">
    <?php if (!$car): ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'duration'): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                Durée min : 3 mois.
            </div>
        <?php endif; ?>
        <p class="text-center text-red-600">Véhicule introuvable.</p>
    <?php else: ?>
        <?php if (isset($_GET['added'])): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                Ajouté au panier !
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'overlap'): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                Cette voiture est déjà réservée pour cette période.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'duration'): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                Durée ≥ 3 mois requise.
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Galerie + favoris -->
            <div class="w-full lg:w-2/3 relative">
                <img id="mainImage"
                     src="<?= htmlspecialchars($galleryImages[0] ?? '', ENT_QUOTES) ?>"
                     class="w-full rounded-lg shadow-lg object-cover">
                <?php if (!empty($_SESSION['user']['id'])): ?>
                    <form method="post" action="car.php?id=<?= $id ?>" class="absolute top-4 right-4">
                        <input type="hidden" name="favorite_car_id" value="<?= $id ?>">
                        <button type="submit" class="text-3xl text-yellow-500 focus:outline-none">
                            <?= $isFavorite ? '★' : '☆' ?>
                        </button>
                    </form>
                <?php endif; ?>
                <div class="flex gap-2 mt-4">
                    <?php foreach($galleryImages as $i => $img): ?>
                        <img src="<?= htmlspecialchars($img, ENT_QUOTES) ?>"
                             data-index="<?= $i ?>"
                             class="thumbnail w-20 h-12 object-cover rounded cursor-pointer">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar réservation -->
            <aside class="w-full lg:w-1/3 bg-white rounded-lg shadow-lg p-6 flex flex-col">
                <h1 class="text-2xl font-semibold mb-4">
                    <?= htmlspecialchars($car['marque'].' '.$car['modele'], ENT_QUOTES) ?>
                </h1>
                <p id="priceDisplay" class="text-3xl font-bold mb-4">
                    <?= number_format($car['prix'], 2, ',', ' ') ?> €<span class="text-base font-normal">/mois</span>
                </p>
                <form method="post" action="car.php?id=<?= $id ?>" class="space-y-4 flex-grow">
                    <!-- dates -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium">Début</label>
                        <input type="text" id="start_date" name="start_date"
                               class="w-full mt-1 border rounded flatpickr-input" autocomplete="off" required>
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium">Fin</label>
                        <input type="text" id="end_date" name="end_date"
                               class="w-full mt-1 border rounded flatpickr-input" autocomplete="off" required>
                    </div>
                    <input type="hidden" name="car_id" value="<?= $id ?>">

                    <!-- acompte : toujours visible -->
                    <div id="depositWrapper">
                        <label for="deposit" class="block text-sm font-medium">Acompte</label>
                        <select id="deposit" name="deposit" class="w-full mt-1 border rounded">
                            <?php foreach($depositOptions as $d): ?>
                                <option value="<?= $d ?>"><?= $d ?> €</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- km -->
                    <div>
                        <label for="km" class="block text-sm font-medium">Forfait km</label>
                        <select id="km" name="km" class="w-full mt-1 border rounded">
                            <?php foreach($kmOptions as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?> km/mois</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($role !== 'admin' && $role !== 'commercial'): ?>
                    <button type="submit"
                            class="w-full py-3 bg-blue-600 text-white rounded-lg">
                        Ajouter au panier
                    </button>
                    <?php endif; ?>
                </form>

                <p class="mt-4 text-sm text-gray-600">
                    Type : <?= htmlspecialchars($car['type'], ENT_QUOTES) ?><br>
                    Motorisation : <?= htmlspecialchars($car['motorisation'], ENT_QUOTES) ?><br>
                    Marque : <?= htmlspecialchars($car['marque'], ENT_QUOTES) ?>
                </p>
            </aside>
        </div>

        <!-- Autres véhicules -->
        <section class="mt-12">
            <h2 class="text-xl font-semibold mb-4">Autres véhicules</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach($others as $o): ?>
                    <a href="car.php?id=<?= $o['id'] ?>"
                       class="block bg-white p-4 rounded-lg shadow hover:shadow-lg">
                        <img src="<?= htmlspecialchars($o['image'], ENT_QUOTES) ?>"
                             class="w-full h-32 object-cover rounded">
                        <h3 class="mt-2 font-medium"><?= htmlspecialchars($o['title'], ENT_QUOTES) ?></h3>
                        <p class="text-gray-500">
                            <?= number_format($o['price'], 2, ',', ' ') ?> €/mois
                        </p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php include __DIR__.'/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const basePrice     = <?= $car ? (float)$car['prix'] : 0 ?>;
    const MIN_KM        = <?= $kmOptions[0] ?>;
    const depositSelect = document.getElementById('deposit');
    const kmSelect      = document.getElementById('km');
    const priceDisplay  = document.getElementById('priceDisplay');
    const startInput    = document.getElementById('start_date');
    const endInput      = document.getElementById('end_date');
    const minMonths     = 3;

    // Fonctions utiles
    function monthDiff(d1, d2) {
        return (d2.getFullYear()-d1.getFullYear())*12
            + (d2.getMonth()-d1.getMonth())
            + (d2.getDate()>=d1.getDate()?0:-1);
    }

    function updatePrice(){
        let dur = monthDiff(new Date(startInput.value), new Date(endInput.value));
        if (isNaN(dur)||dur<minMonths) dur=minMonths;
        const dep = depositSelect ? parseInt(depositSelect.value,10) : 0;
        const km  = parseInt(kmSelect.value,10);
        let newPrice = basePrice
                     + (km - MIN_KM)*0.02
                     - (dur - minMonths)*2
                     - dep*0.005;
        if (!isFinite(newPrice)||newPrice<0) newPrice=basePrice;
        priceDisplay.innerHTML = newPrice.toFixed(2).replace('.',',')
                                + ' €<span class="text-base font-normal">/mois</span>';
    }

    [depositSelect, kmSelect, startInput, endInput].forEach(el => {
        if (el) el.addEventListener('change', updatePrice);
    });

    // Dates réservées
    const reservedRanges = <?= json_encode($reservedRanges) ?>;
    const reservedDates = <?= json_encode($reservedDates) ?>;

    // Fonction pour marquer les jours réservés
    function markReservedDays(date) {
        const dateString = date.toISOString().split('T')[0];
        if (reservedDates.includes(dateString)) {
            return true;
        }
        return false;
    }

    // Configuration du calendrier avec personnalisation des jours réservés
    flatpickr("#start_date", {
        minDate: "today",
        dateFormat: "Y-m-d",
        disable: reservedRanges,
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const dateStr = dayElem.dateObj.toISOString().split('T')[0];
            if (reservedDates.includes(dateStr)) {
                dayElem.classList.add("reserved");
            }
        },
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length && endInput.value) {
                if (new Date(dateStr) > new Date(endInput.value)) {
                    endInput.value = '';
                }
            }
            // Mise à jour du calendrier de fin
            const endpickr = document.querySelector("#end_date")._flatpickr;
            endpickr.set('minDate', dateStr);
        }
    });

    flatpickr("#end_date", {
        minDate: "today",
        dateFormat: "Y-m-d",
        disable: reservedRanges,
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const dateStr = dayElem.dateObj.toISOString().split('T')[0];
            if (reservedDates.includes(dateStr)) {
                dayElem.classList.add("reserved");
            }
        }
    });

    // Mini galerie
    document.querySelectorAll('.thumbnail').forEach(el=>
        el.addEventListener('click', ()=> document.getElementById('mainImage').src=el.src)
    );

    // Init affichages
    updatePrice();
});
</script>
</body>
</html>