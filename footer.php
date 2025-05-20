<?php
// footer.php — Footer dynamique, liens filtres sur marques/types depuis la base db_URTADO
if (!isset($pdo)) {
    require_once __DIR__ . '/config/config.php';
}

// Récupération des marques distinctes
$brands = [];
try {
    $brandQuery = $pdo->query("SELECT DISTINCT marque FROM cars ORDER BY marque ASC");
    $brands = $brandQuery->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// Récupération des types distincts
$types = [];
try {
    $typeQuery = $pdo->query("SELECT DISTINCT `type` FROM cars ORDER BY `type` ASC");
    $types = $typeQuery->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// Calcul du chemin de base (pour gestion sous-dossier)
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '' || $base === '.' || $base === '/') $base = '.';
?>
<footer class="site-footer">
    <div class="footer-top container" style="display:flex;flex-wrap:wrap;gap:2rem;justify-content:space-between;">
        <div class="col" style="min-width:180px;flex:1;">
            <h4>Informations générales</h4>
            <p>
                Novaloc France<br>
                Société au capital de 3 812 430 €<br>
            </p>
            <p>
                Novaloc Allemagne, Suisse<br>
                Société au capital de 3 000 000 €<br>
            </p>
            <p>
                Mail : <a href="mailto:info@novaloc.com">info@novaloc.com</a>
            </p>
        </div>

        <div class="col" style="min-width:120px;flex:1;">
            <h4>Pages</h4>
            <ul>
                <li><a href="<?= $base ?>/index.php">Accueil</a></li>
                <li><a href="<?= $base ?>/auth/login.php">Connexion</a></li>
            </ul>
        </div>

        <div class="col" style="min-width:120px;flex:1;">
            <h4>Marque</h4>
            <ul class="brands">
                <?php foreach ($brands as $marque): ?>
                    <li>
                        <a href="<?= $base ?>/index.php?marque=<?= urlencode($marque) ?>">
                            <?= htmlspecialchars($marque) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col" style="min-width:120px;flex:1;">
            <h4>Type de voiture</h4>
            <ul>
                <?php foreach ($types as $type): ?>
                    <li>
                        <a href="<?= $base ?>/index.php?type=<?= urlencode($type) ?>">
                            <?= htmlspecialchars($type) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col" style="min-width:150px;flex:1;">
            <h4>Locations</h4>
            <ul>
                <li>3, 6, 12, 18, 24 mois</li>
                <li>Véhicule France</li>
                <li>Véhicule Allemagne</li>
                <li>Véhicule Espagne</li>
                <li>Véhicule Suisse</li>
                <li>Véhicule de luxe</li>
                <li>Véhicule en société</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom" style="text-align:center;padding:1rem 0;margin-top:1rem;">
        <p>© <?= date('Y') ?> NOVALOC</p>
    </div>
</footer>
