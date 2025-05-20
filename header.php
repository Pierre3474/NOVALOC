<?php
// header.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$cartCount = count($_SESSION['cart'] ?? []);
$user      = $_SESSION['user'] ?? null;
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

if (basename($base) === 'commercial') {
    if (!$user || $user['role'] !== 'commercial') {
        header('Location: ' . $base . '/auth/login.php');
        exit;
    }
    $base = dirname($base);
}
if (basename($base) === 'admin') {
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ' . $base . '/auth/login.php');
        exit;
    }
    $base = dirname($base);
}
?>
<head>
    <link rel="icon" href="assets/images/Novaloc.ico" type="image/x-icon" />
</head>
<!-- favicon -->

<header class="site-header">
    <div class="header-top">
        <a href="<?= $base ?>/index.php" class="logo">NOVALOC</a>

        <nav class="top-nav">
            <?php if (!$user || !in_array($user['role'], ['commercial', 'admin'], true)): ?>
                <?php if (!$user): ?>
                    <!-- Si NON connecté, on renvoie vers login au clic -->
                    <a href="<?= $base ?>/auth/login.php" id="cartLink">🛒 Panier(<?= $cartCount ?>)</a>
                <?php else: ?>
                    <!-- Si connecté, on va sur le panier normalement -->
                    <a href="<?= $base ?>/cart.php" id="cartLink">🛒 Panier(<?= $cartCount ?>)</a>
                <?php endif; ?>
            <?php endif; ?>
            <div class="user-menu">
                <button class="user-button" id="userBtn">
                    <?php if ($user): ?>
                        <?= htmlspecialchars(ucfirst($user['username']), ENT_QUOTES) ?>
                    <?php else: ?>
                        Menu
                    <?php endif; ?>
                    <span class="dots">⋮</span>
                </button>

                <div class="dropdown" id="userDropdown">
                    <?php if (!$user): ?>
                        <a href="<?= $base ?>/auth/login.php">Connexion</a>
                        <a href="<?= $base ?>/index.php">Accueil</a>
                    <?php else: ?>
                        <?php if ($user['role'] === 'user'): ?>
                            <a href="<?= $base ?>/locations_en_cours.php">Mes locations en cours</a>
                            <a href="<?= $base ?>/favorites.php">Voitures favorites</a>
                        <?php elseif ($user['role'] === 'commercial'): ?>
                            <a href="<?= $base ?>/commercial/site_view.php">Prévisualiser mon site</a>
                            <a href="<?= $base ?>/commercial/dashboard.php">Gérer le stock</a>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <a href="<?= $base ?>/admin/dashboard.php">Tableau de bord Admin</a>
                        <?php endif; ?>
                        <a href="<?= $base ?>/auth/logout.php">Déconnexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </div>

    <script>
        // gérer le clic pour basculer l’affichage du dropdown
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('userBtn');
            const dd  = document.getElementById('userDropdown');
            btn.addEventListener('click', () => {
                dd.classList.toggle('open');
            });
            document.addEventListener('click', e => {
                if (!btn.contains(e.target) && !dd.contains(e.target)) {
                    dd.classList.remove('open');
                }
            });
        });
    </script>
</header>
