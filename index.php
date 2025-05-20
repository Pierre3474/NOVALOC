<?php
// index.php complet avec le système de filtres amélioré et la gestion des favoris
require __DIR__ . '/config/config.php';

// 1. Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Variables utilisateur pour droits favoris
$isLogged = !empty($_SESSION['user']['id']);
$userRole = $isLogged ? ($_SESSION['user']['role'] ?? '') : '';
$canUseFavorites = $isLogged && $userRole === 'user';

// 3. AJAX toggle favoris
if (isset($_GET['ajax']) && $_GET['ajax'] === 'toggle_fav') {
    header('Content-Type: application/json');
    $response = ['success' => false];

    // Vérifier si l'utilisateur peut utiliser les favoris
    if ($canUseFavorites && isset($_POST['carId'])) {
        $userId = $_SESSION['user']['id'];
        $carId  = (int) $_POST['carId'];
        $check  = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND car_id = ?");
        $check->execute([$userId, $carId]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?")
                ->execute([$userId, $carId]);
            $response['removed'] = true;
        } else {
            $pdo->prepare("INSERT IGNORE INTO favorites (user_id, car_id) VALUES (?, ?)")
                ->execute([$userId, $carId]);
            $response['removed'] = false;
        }
        $response['success'] = true;
    }
    echo json_encode($response);
    exit;
}

// 4. Récupération des filtres et du tri
$search       = $_GET['search']       ?? '';
$motorisation = $_GET['motorisation'] ?? '';
$marque       = $_GET['marque']       ?? '';
$type         = $_GET['type']         ?? '';
$sort         = $_GET['sort']         ?? 'asc';
$sortDir      = ($sort === 'desc') ? 'DESC' : 'ASC';

// 5. Construction dynamique de la requête
$where  = [];
$params = [];
if ($search)       { $where[] = "modele LIKE ?";    $params[] = "%$search%"; }
if ($motorisation) { $where[] = "motorisation = ?"; $params[] = $motorisation; }
if ($marque)       { $where[] = "marque = ?";       $params[] = $marque; }
if ($type)         { $where[] = "`type` = ?";       $params[] = $type; }

// Option de tri spéciale pour Coupé et 4x4
if ($sort === 'sport') {
    $where[] = "`type` = ?";
    // $params[] = "Coupé";
    $sortDir = "ASC";
} elseif ($sort === '4x4') {
    $where[] = "`type` = ?";
    $params[] = "4x4";
    $sortDir = "ASC";
}

$sql = "SELECT * FROM cars"
    . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "")
    . " ORDER BY prix $sortDir";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

// 6. Récupération des favoris de l'utilisateur (uniquement pour les users)
$favoriteIds = [];
if ($canUseFavorites) {
    $favStmt = $pdo->prepare("SELECT car_id FROM favorites WHERE user_id = ?");
    $favStmt->execute([$_SESSION['user']['id']]);
    $favoriteIds = $favStmt->fetchAll(PDO::FETCH_COLUMN);
}

// 7. Pour les listes déroulantes (filtres dynamiques)
// Récupération de toutes les marques distinctes
$brandQuery = $pdo->query("SELECT DISTINCT marque FROM cars ORDER BY marque");
$brands = $brandQuery->fetchAll(PDO::FETCH_COLUMN);

// Récupération de tous les types distincts
$typeQuery = $pdo->query("SELECT DISTINCT `type` FROM cars ORDER BY `type`");
$types = $typeQuery->fetchAll(PDO::FETCH_COLUMN);

// Récupération de toutes les motorisations distinctes
$motorQuery = $pdo->query("SELECT DISTINCT motorisation FROM cars ORDER BY motorisation");
$motors = $motorQuery->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>NOVALOC</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50 flex flex-col">

<?php include __DIR__ . '/header.php'; ?>

<!-- ===== FILTRES ===== -->
<div class="filter-zone">
    <div class="filter-banner container" id="filterBanner">
        <div class="filter-item" data-panel="sort">Trier par</div>
        <div class="filter-item" data-panel="type">Type de voiture</div>
        <div class="filter-item" data-panel="motor">Motorisation</div>
        <div class="filter-item" data-panel="brand">Marques</div>
    </div>

    <div class="filter-panel container" id="filterPanel">
        <div class="panel-column">
            <div class="pills-container">
                <button class="pill <?= $sort === 'asc' ? 'active' : '' ?>"
                        data-filter-key="sort" data-filter="asc">Prix croissant</button>
                <button class="pill <?= $sort === 'desc' ? 'active' : '' ?>"
                        data-filter-key="sort" data-filter="desc">Prix décroissant</button>
            </div>
        </div>
        <div class="panel-column">
            <div class="pills-container">
                <button class="pill <?= empty($type) ? 'active' : '' ?>"
                        data-filter-key="type" data-filter="tous">Tous</button>
                <button class="pill <?= $type === 'Coupé' ? 'active' : '' ?>"
                        data-filter-key="type" data-filter="Coupé">Coupé</button>
                <button class="pill <?= $type === 'Berline' ? 'active' : '' ?>"
                        data-filter-key="type" data-filter="Berline">Berline</button>
                <button class="pill <?= $type === 'SUV' ? 'active' : '' ?>"
                        data-filter-key="type" data-filter="SUV">SUV</button>
                <button class="pill <?= $type === 'Cabriolet' ? 'active' : '' ?>"
                        data-filter-key="type" data-filter="Cabriolet">Cabriolet</button>
                <button class="pill <?= $type === 'Hatchback' ? 'active' : '' ?>"
                        data-filter-key="type" data-filter="Hatchback">Hatchback</button>
                <button class="pill <?= $type === '4x4' ? 'active' : '' ?>"
                        data-filter-key="type" data-filter="4x4">4x4</button>
                <button class="pill <?= $type === 'Break' ? 'active' : '' ?>"
                        data-filter-key="type" data-filter="Break">Break</button>
            </div>
        </div>
        <div class="panel-column">
            <div class="pills-container">
                <button class="pill <?= empty($motorisation) ? 'active' : '' ?>"
                        data-filter-key="motorisation" data-filter="tous">Tous</button>
                <button class="pill <?= $motorisation === 'Thermique' ? 'active' : '' ?>"
                        data-filter-key="motorisation" data-filter="Thermique">Thermique</button>
                <button class="pill <?= $motorisation === 'Hybride' ? 'active' : '' ?>"
                        data-filter-key="motorisation" data-filter="Hybride">Hybride</button>
                <button class="pill <?= $motorisation === 'Électrique' ? 'active' : '' ?>"
                        data-filter-key="motorisation" data-filter="Électrique">Électrique</button>
            </div>
        </div>
        <div class="panel-column">
            <div class="pills-container">
                <button class="pill <?= empty($marque) ? 'active' : '' ?>"
                        data-filter-key="marque" data-filter="tous">Tous</button>
                <button class="pill <?= $marque === 'Porsche' ? 'active' : '' ?>"
                        data-filter-key="marque" data-filter="Porsche">Porsche</button>
                <button class="pill <?= $marque === 'BMW' ? 'active' : '' ?>"
                        data-filter-key="marque" data-filter="BMW">BMW</button>
                <button class="pill <?= $marque === 'Audi' ? 'active' : '' ?>"
                        data-filter-key="marque" data-filter="Audi">Audi</button>
                <button class="pill <?= $marque === 'Mercedes' ? 'active' : '' ?>"
                        data-filter-key="marque" data-filter="Mercedes">Mercedes</button>
                <button class="pill <?= $marque === 'Jaguar' ? 'active' : '' ?>"
                        data-filter-key="marque" data-filter="Jaguar">Jaguar</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== RECHERCHE ===== -->
<div class="search-container">
    <input
            type="text"
            id="searchInput"
            placeholder="Rechercher un modèle..."
            class="search-input"
            value="<?= htmlspecialchars($search) ?>"
    >
</div>
<div
        id="no-results"
        class="no-results"
        style="display:none;"
>
    Aucune voiture trouvée. Redirection...
</div>

<!-- ===== GRILLE DE VOITURES ===== -->
<main class="cars-grid container flex-1">

    <?php foreach ($cars as $car): ?>
        <div
                class="car-card bg-white rounded-lg shadow relative overflow-hidden"
                data-id="<?= (int)$car['id'] ?>"
                data-type="<?= htmlspecialchars($car['type'], ENT_QUOTES) ?>"
                data-motorisation="<?= htmlspecialchars($car['motorisation'], ENT_QUOTES) ?>"
                data-marque="<?= htmlspecialchars($car['marque'], ENT_QUOTES) ?>"
                data-prix="<?= (int)$car['prix'] ?>"
                data-modele="<?= htmlspecialchars($car['modele'], ENT_QUOTES) ?>"
        >
            <!-- Bouton favoris - Affiché pour tous -->
            <button class="favorite-btn absolute bottom-4 right-4 focus:outline-none" data-car-id="<?= (int)$car['id'] ?>">
                <span class="icon <?= in_array($car['id'], $favoriteIds) ? 'fav' : 'no-fav' ?>">
                    <?= in_array($car['id'], $favoriteIds) ? '★' : '☆' ?>
                </span>
            </button>

            <!-- Badge -->
            <?php if (!empty($car['badge'])): ?>
                <span class="badge absolute bg-yellow-300 text-gray-800 px-2 py-1 rounded-tr-lg">
                    <?= htmlspecialchars($car['badge'], ENT_QUOTES) ?>
                </span>
            <?php endif; ?>

            <!-- Image -->
            <img
                    src="<?= htmlspecialchars($car['image_url'], ENT_QUOTES) ?>"
                    alt="<?= htmlspecialchars($car['marque'].' '.$car['modele'], ENT_QUOTES) ?>"
                    onclick="location.href='car.php?id=<?= (int)$car['id'] ?>'"
                    class="w-full h-48 object-cover cursor-pointer"
            >

            <!-- Infos -->
            <div class="p-4">
                <h3 class="text-lg font-semibold mb-2">
                    <?= htmlspecialchars($car['marque'].' '.$car['modele'], ENT_QUOTES) ?>
                </h3>
                <p class="text-gray-700">
                    <?= number_format($car['prix'], 2, ',', ' ') ?> € / mois
                </p>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- ==== SCRIPT FAVORIS ==== -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                <?php if ($canUseFavorites): ?>
                // Utilisateur avec rôle "user" : gestion normale des favoris
                const carId = this.dataset.carId;
                fetch('?ajax=toggle_fav', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ carId }).toString()
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const icon = this.querySelector('.icon');
                            if (data.removed) {
                                icon.textContent = '☆';
                                icon.classList.replace('fav','no-fav');
                            } else {
                                icon.textContent = '★';
                                icon.classList.replace('no-fav','fav');
                            }
                        }
                    })
                    .catch(console.error);
                <?php elseif ($isLogged && $userRole !== 'user'): ?>
                // Utilisateur connecté avec rôle admin/commercial
                alert('Les favoris ne sont disponibles que pour les comptes utilisateurs.');
                <?php else: ?>
                // Visiteur non connecté : redirection vers login
                window.location.href = 'auth/login.php';
                <?php endif; ?>
            });
        });
    });
</script>

<!-- ==== SCRIPT FILTRES & RECHERCHE ==== -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ===== GESTION DES FILTRES (BANNIÈRE ET PANNEAU) =====
        const filterBanner = document.getElementById('filterBanner');
        const filterPanel = document.getElementById('filterPanel');
        const filterItems = document.querySelectorAll('.filter-item');
        const panelColumns = document.querySelectorAll('.panel-column');

        // Initialisation : cacher toutes les colonnes du panneau
        panelColumns.forEach(column => {
            column.style.display = 'none';
        });

        // Gestionnaire pour chaque élément de la bannière
        filterItems.forEach((item, index) => {
            item.addEventListener('mouseenter', () => {
                filterPanel.classList.add('open');
                panelColumns.forEach(column => column.style.display = 'none');
                if (panelColumns[index]) {
                    panelColumns[index].style.display = 'flex';
                }
            });
        });

        // Ouvrir le panneau au survol de la bannière
        filterBanner.addEventListener('mouseenter', () => {
            filterPanel.classList.add('open');
        });

        // Fermer quand on quitte la zone (bannière+panneau)
        const filterZone = document.querySelector('.filter-zone');
        filterZone.addEventListener('mouseleave', () => {
            filterPanel.classList.remove('open');
            setTimeout(() => {
                if (!filterPanel.classList.contains('open')) {
                    panelColumns.forEach(column => column.style.display = 'none');
                }
            }, 300);
        });

        // Gestion du clic sur les boutons de filtre (pills)
        document.querySelectorAll('.filter-panel .pill').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.filterKey;
                const value = btn.dataset.filter;
                const url = new URL(window.location.href);
                if (value === 'tous') {
                    url.searchParams.delete(key);
                } else {
                    url.searchParams.set(key, value);
                }
                window.location.href = url.toString();
            });
        });

        // ===== MODIFICATION DU COMPORTEMENT DE RECHERCHE =====
        // Initialiser le champ de recherche à partir de l'URL
        const searchInput = document.getElementById('searchInput');
        const urlParams = new URLSearchParams(window.location.search);

        // Utiliser l'événement input pour mettre à jour l'URL sans recharger
        searchInput.addEventListener('input', () => {
            const searchText = searchInput.value.trim();
            const url = new URL(window.location.href);

            if (searchText) {
                url.searchParams.set('search', searchText);
            } else {
                url.searchParams.delete('search');
            }

            // Mettre à jour l'URL sans recharger la page
            window.history.replaceState({}, '', url.toString());
        });
    });
</script>

<script src="assets/js/filter.js"></script>
<script src="assets/js/cart.js"></script>
</body>
</html>