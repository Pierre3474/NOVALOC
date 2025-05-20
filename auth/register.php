<?php
require __DIR__ . '/../config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Redirection si déjà connecté
if (!empty($_SESSION['user']['id'])) {
    header('Location: ../index.php');
    exit;
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données
    $username         = trim($_POST['username'] ?? '');
    $email            = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Vérifications de base
    if ($username === '' || strlen($username) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Veuillez saisir une adresse e-mail valide.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Empêche inscription @admin.com ou @commercial.com
    if (empty($errors)) {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        if ($domain === 'admin.com' || $domain === 'commercial.com') {
            $errors[] = "Vous ne pouvez pas vous inscrire avec une adresse @$domain.";
        }
    }

    // Vérifie si email ou username existe déjà
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username');
        $stmt->execute([
            'email'    => $email,
            'username' => $username,
        ]);
        if ($stmt->fetch()) {
            $errors[] = "Cette adresse e-mail ou ce nom d'utilisateur est déjà utilisé.";
        }
    }

    // Inscription (rôle toujours 'user')
    if (empty($errors)) {
        $role = 'user';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)'
        );
        $stmt->execute([
            'username' => $username,
            'email'    => $email,
            'password' => $hash,
            'role'     => $role,
        ]);

        // Connexion automatique
        $userId = $pdo->lastInsertId();
        $_SESSION['user'] = [
            'id'       => $userId,
            'username' => $username,
            'email'    => $email,
            'role'     => $role,
        ];

        header('Location: ../index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Inscription – NOVALOC</title>
  <link rel="stylesheet" href="auth.css">
</head>
<body>

  <a href="../index.php" class="back-btn">← Retour</a>
  <main class="auth-card">
    <h2>Inscription</h2>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err, ENT_QUOTES) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="register.php" novalidate>
      <div class="form-group">
        <label for="username">Nom d’utilisateur</label>
        <input
          type="text"
          id="username"
          name="username"
          required
          placeholder="votre pseudo"
          value="<?= htmlspecialchars($username, ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-group">
        <label for="email">Adresse e-mail</label>
        <input
          type="email"
          id="email"
          name="email"
          required
          placeholder="exemple@domaine.com"
          value="<?= htmlspecialchars($email, ENT_QUOTES) ?>"
        >
      </div>

      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input
          type="password"
          id="password"
          name="password"
          required
        >
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirmer le mot de passe</label>
        <input
          type="password"
          id="confirm_password"
          name="confirm_password"
          required
        >
      </div>

      <button type="submit" class="btn-auth">S'inscrire</button>
    </form>

    <div class="auth-footer">
      <p>Vous avez déjà un compte ? <a href="login.php">Connectez-vous</a></p>
    </div>
  </main>
</body>
</html>
