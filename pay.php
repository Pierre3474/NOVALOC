<?php
// === File: pay.php ===
require_once __DIR__ . '/config/config.php';

// 1. Démarrage de la session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2. Vérifier si l'utilisateur est connecté
if (empty($_SESSION['user']['id'])) {
    header('Location: auth/login.php');
    exit;
}

// 3. Calcul du panier
$cart = $_SESSION['cart'] ?? [];
$monthlyTotal = 0;
$details = [];
foreach ($cart as $item) {
    $car = $item['car'];
    $monthlyTotal += (float)$car['prix'];
    $details[] = [
        'image'    => $car['image_url'],
        'label'    => $car['marque'].' '.$car['modele'],
        'price'    => (float)$car['prix'],
        'duration' => $item['duration'] ?? 1
    ];
}

// 4. Formatage
$formattedTotal = number_format($monthlyTotal, 2, ',', ' ');
$paypalAmount   = number_format($monthlyTotal, 2, '.', '');

// 5. Génération du token et stockage en session
$token = $_GET['token'] ?? bin2hex(random_bytes(16));
$_SESSION['payment_token'] = $token;

// 6. URLs PayPal
$base = 'https://r207.borelly.net/~u22405463/NOVALOC';
$confirmationUrl = "$base/confirmation.php?token=" . urlencode($token);
$cancelUrl       = "$base/cancel.php?token=" . urlencode($token);

// 7. Récupération des messages d'erreur
$errorMessage = $_SESSION['error_message'] ?? null;
if ($errorMessage) {
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Abonnement mensuel - NOVALOC</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .paypal-button {
            background: linear-gradient(135deg, #ffd700 0%, #ffb700 100%);
            border: none;
            border-radius: 8px;
            color: #003087;
            font-weight: bold;
            font-size: 18px;
            padding: 16px 32px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            min-width: 250px;
            position: relative;
            overflow: hidden;
        }

        .paypal-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #ffed4e 0%, #ffc107 100%);
        }

        .paypal-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .paypal-logo {
            height: 24px;
            width: auto;
            margin-left: 8px;
        }

        .paypal-button-text {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
<?php include __DIR__ . '/header.php'; ?>
<main class="container mx-auto px-6 py-8 flex-1 text-center">
    <h1 class="text-3xl font-bold mb-6">Votre abonnement mensuel</h1>

    <?php if ($errorMessage): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Erreur!</strong>
            <span class="block sm:inline"> <?= htmlspecialchars($errorMessage) ?></span>
        </div>
    <?php endif; ?>

    <?php if (empty($details)): ?>
        <p class="text-gray-700">Votre panier est vide.</p>
        <p class="mt-4">
            <a href="index.php" class="inline-block px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Retour à l'accueil
            </a>
        </p>
    <?php else: ?>
        <div class="space-y-4 mb-8">
            <?php foreach ($details as $d): ?>
                <div class="bg-white p-4 rounded shadow flex items-center justify-between">
                    <img src="<?= htmlspecialchars($d['image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($d['label'], ENT_QUOTES) ?>" class="w-24 h-16 object-cover rounded">
                    <div class="text-left">
                        <div class="font-medium"><?= htmlspecialchars($d['label'], ENT_QUOTES) ?></div>
                        <div class="text-gray-600">Durée : <?= intval($d['duration']) ?> mois</div>
                    </div>
                    <div class="font-semibold text-lg"><?= number_format($d['price'], 2, ',', ' ') ?> €/mois</div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="bg-white p-6 rounded shadow-lg inline-block text-center">
            <p class="text-xl">Total mensuel à payer :</p>
            <p class="text-4xl font-bold my-4"><?= $formattedTotal ?> €/mois</p>

            <!-- Bouton PayPal personnalisé pour abonnement -->
            <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
                <input type="hidden" name="business" value="sb-merchant@example.com">
                <input type="hidden" name="cmd" value="_xclick-subscriptions">
                <input type="hidden" name="item_name" value="Abonnement NOVALOC">
                <input type="hidden" name="currency_code" value="EUR">
                <input type="hidden" name="a3" value="<?= $paypalAmount ?>">
                <input type="hidden" name="p3" value="1">
                <input type="hidden" name="t3" value="M">
                <input type="hidden" name="src" value="1">
                <input type="hidden" name="sra" value="1">
                <input type="hidden" name="notify_url" value="<?= $base . '/ipn_listener.php' ?>">
                <input type="hidden" name="return" value="<?= htmlspecialchars($confirmationUrl, ENT_QUOTES) ?>">
                <input type="hidden" name="cancel_return" value="<?= htmlspecialchars($cancelUrl, ENT_QUOTES) ?>">

                <button type="submit" class="paypal-button">
                    <div class="paypal-button-text">
                        <span>Payer avec</span>
                        <img src="assets/images/LogoPayPal.png" alt="PayPal" class="paypal-logo">
                    </div>
                </button>
            </form>

            <div class="mt-4 text-sm text-gray-500">
                En vous abonnant, vous acceptez les conditions générales d'utilisation.
            </div>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>