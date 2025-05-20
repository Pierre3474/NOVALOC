<?php
require __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Vérifier droits favoris
$isLogged = !empty($_SESSION['user']['id']);
$userRole = $isLogged ? ($_SESSION['user']['role'] ?? '') : '';
$canUseFavorites = $isLogged && $userRole === 'user' && $_SESSION['user']['id'] == 2;

// Récupération des filtres GET
$search       = $_GET['search']       ?? '';
$motorisation = $_GET['motorisation'] ?? '';
$marque       = $_GET['marque']       ?? '';
$type         = $_GET['type']         ?? '';
$sort         = $_GET['sort']         ?? 'asc';
$sortDir      = ($sort === 'desc') ? 'DESC' : 'ASC';

// Construction requête SQL selon les filtres
$where  = [];
$params = [];
if ($search)       { $where[] = "modele LIKE ?";    $params[] = "%$search%"; }
if ($motorisation) { $where[] = "motorisation = ?"; $params[] = $motorisation; }
if ($marque)       { $where[] = "marque = ?";       $params[] = $marque; }
if ($type)         { $where[] = "`type` = ?";       $params[] = $type; }
$sql = "SELECT * FROM cars"
    . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "")
    . " ORDER BY prix $sortDir";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

// Récupérer les favoris utilisateur (si autorisé)
$favoriteIds = [];
if ($canUseFavorites) {
    $favStmt = $pdo->prepare("SELECT car_id FROM favorites WHERE user_id = ?");
    $favStmt->execute([$_SESSION['user']['id']]);
    $favoriteIds = $favStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<?php foreach ($cars as $car): ?>
    <div class="car-card bg-white rounded-lg shadow relative overflow-hidden"
        data-id="<?= (int)$car['id'] ?>"
        data-type="<?= htmlspecialchars($car['type'], ENT_QUOTES) ?>"
        data-motorisation="<?= htmlspecialchars($car['motorisation'], ENT_QUOTES) ?>"
        data-marque="<?= htmlspecialchars($car['marque'], ENT_QUOTES) ?>"
        data-prix="<?= (int)$car['prix'] ?>"
        data-modele="<?= htmlspecialchars($car['modele'], ENT_QUOTES) ?>"
    >
        <!-- Bouton favoris : visible uniquement pour user id 2 -->
        <?php if ($canUseFavorites): ?>
            <button class="favorite-btn absolute bottom-4 right-4 focus:outline-none" data-car-id="<?= (int)$car['id'] ?>">
                <span class="icon <?= in_array($car['id'], $favoriteIds) ? 'fav' : 'no-fav' ?>">
                    <?= in_array($car['id'], $favoriteIds) ? '★' : '☆' ?>
                </span>
            </button>
        <?php endif; ?>

        <!-- Badge -->
        <?php if (!empty($car['badge'])): ?>
            <span class="badge absolute bg-yellow-300 text-gray-800 px-2 py-1 rounded-tr-lg">
                <?= htmlspecialchars($car['badge'], ENT_QUOTES) ?>
            </span>
        <?php endif; ?>

        <!-- Image -->
        <img src="<?= htmlspecialchars($car['image_url'], ENT_QUOTES) ?>"
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

<?php if (empty($cars)): ?>
    <div class="no-results">Aucune voiture trouvée.</div>
<?php endif; ?>
