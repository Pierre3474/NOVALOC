<?php
// File: auth/login.php
require __DIR__ . '/../config/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$error   = '';
$message = '';

// Si on vient de réinitialiser le mot de passe avec succès
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $message = 'Votre mot de passe a bien été réinitialisé. Vous pouvez maintenant vous connecter.';
}

// 1) Mot de passe oublié
if (isset($_GET['action']) && $_GET['action'] === 'forgot') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['reset_email'] = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$_SESSION['reset_email']) {
            $error = 'Veuillez fournir une adresse e-mail valide.';
        } else {
            header('Location: login.php?action=verify');
            exit;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Mot de passe oublié – NOVALOC</title>
        <link rel="stylesheet" href="auth.css">
    </head>
    <body>

    <!-- BOUTON RETOUR -->
    <a href="../index.php" class="back-btn">← Retour</a>

    <main class="auth-card">
        <h2>Mot de passe oublié</h2>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" action="?action=forgot" novalidate>
            <div class="form-group">
                <label for="email">Votre adresse e-mail</label>
                <input type="email" id="email" name="email" required placeholder="exemple@domaine.com">
            </div>
            <button type="submit" class="btn-auth">Envoyer le code</button>
        </form>
        <div class="auth-footer">
            <p><a href="login.php">Retour à la connexion</a></p>
        </div>
    </main>
    </body>
    </html>
    <?php
    exit;
}

// 2) Vérification du code
if (isset($_GET['action']) && $_GET['action'] === 'verify') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = trim($_POST['code'] ?? '');
        if ($code !== '0000') {
            $error = 'Code incorrect.';
        } else {
            header('Location: login.php?action=reset');
            exit;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Vérification de code – NOVALOC</title>
        <link rel="stylesheet" href="auth.css">
    </head>
    <body>

    <!-- BOUTON RETOUR -->
    <a href="../index.php" class="back-btn">← Retour</a>

    <main class="auth-card">
        <h2>Entrez le code reçu</h2>
        <p>Votre code de réinitialisation est : <strong>0000</strong></p>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" action="?action=verify" novalidate>
            <div class="form-group">
                <label for="code">Code</label>
                <input type="text" id="code" name="code" required placeholder="0000">
            </div>
            <button type="submit" class="btn-auth">Valider le code</button>
        </form>
    </main>
    </body>
    </html>
    <?php
    exit;
}

// 3) Réinitialisation du mot de passe
if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';
        if (!$pass1 || $pass1 !== $pass2) {
            $error = 'Les mots de passe doivent correspondre.';
        } else {
            $email = $_SESSION['reset_email'] ?? '';
            if ($email) {
                $hash = password_hash($pass1, PASSWORD_BCRYPT);
                $u = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $u->execute([$hash, $email]);
                unset($_SESSION['reset_email']);
                header('Location: login.php?reset=success');
                exit;
            } else {
                $error = 'Aucune adresse e-mail trouvée.';
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Nouveau mot de passe – NOVALOC</title>
        <link rel="stylesheet" href="auth.css">
    </head>
    <body>

    <!-- BOUTON RETOUR -->
    <a href="../index.php" class="back-btn">← Retour</a>

    <main class="auth-card">
        <h2>Nouveau mot de passe</h2>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" action="?action=reset" novalidate>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmer</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn-auth">Enregistrer</button>
        </form>
    </main>
    </body>
    </html>
    <?php
    exit;
}

// 4) Traitement du login classique
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $error = 'Veuillez fournir email et mot de passe.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ];
            if ($user['role'] === 'user') {
                $c = $pdo->prepare("SELECT car_id, start_date, end_date FROM carts WHERE user_id = ?");
                $c->execute([$user['id']]);
                $_SESSION['cart'] = $c->fetchAll(PDO::FETCH_ASSOC);
            }
            switch ($user['role']) {
                case 'admin':      header('Location: ../admin/dashboard.php');      break;
                case 'commercial': header('Location: ../commercial/dashboard.php'); break;
                default:           header('Location: ../index.php');               break;
            }
            exit;
        }
        $error = 'Identifiants invalides.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Connexion – NOVALOC</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<!-- BOUTON RETOUR -->
<a href="../index.php" class="back-btn">← Retour</a>

<main class="auth-card">
    <h2>Connexion</h2>
    <?php if ($message): ?><p class="message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error):   ?><p class="error"><?= htmlspecialchars($error)   ?></p><?php endif; ?>
    <form method="post" action="login.php" novalidate>
        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
            <button type="button" class="toggle-pass" onclick="
          const p = document.getElementById('password');
          p.type = p.type === 'password' ? 'text' : 'password';
        ">👁</button>
        </div>
        <button type="submit" class="btn-auth">Se connecter</button>
    </form>
    <div class="auth-footer">
        <p><a href="?action=forgot">Mot de passe oublié ?</a></p>
        <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous</a></p>
    </div>
</main>

</body>
</html>
