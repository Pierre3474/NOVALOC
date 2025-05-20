<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/fpdf.php';  // Fonction generateInvoice()

if (session_status() === PHP_SESSION_NONE) session_start();
$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// === Import JSON ===
if (isset($_POST['import_json']) && isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
    $content = file_get_contents($_FILES['import_file']['tmp_name']);
    $data = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($data['cars']) && is_array($data['cars'])) {
        $stmt = $pdo->prepare(
            "INSERT INTO cars (badge, marque, modele, type, motorisation, prix, images, image_url, available_from, description, staged)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $count = 0;
        foreach ($data['cars'] as $c) {
            if (isset($c['badge'], $c['marque'], $c['modele'], $c['type'], $c['motorisation'], $c['prix'])) {
                $images    = isset($c['images']) ? json_encode($c['images'], JSON_UNESCAPED_UNICODE) : json_encode([]);
                $url       = $c['image_url'] ?? '';
                $available = $c['available_from'] ?? null;
                $desc      = $c['description'] ?? null;
                $stmt->execute([
                    $c['badge'], $c['marque'], $c['modele'], $c['type'], $c['motorisation'], (float)$c['prix'],
                    $images, $url, $available, $desc
                ]);
                $count++;
            }
        }
        $_SESSION['success_message'] = "Importé $count voitures depuis le JSON.";
    } else {
        $_SESSION['error_message'] = 'Fichier JSON invalide ou mal formé.';
    }
    header('Location: dashboard.php');
    exit;
}

// === Export JSON ===
if (isset($_GET['download_log'])) {
    $cars = $pdo->query(
        "SELECT id, badge, marque, modele, type, motorisation, prix, available_from, staged
         FROM cars
         ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    $reservations = $pdo->query(
        "SELECT id, user_id, car_id, start_date, end_date, total_price, payment_amount, payment_status, created_at
         FROM reservations
         ORDER BY created_at ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    $export = [
        'generated_at'  => date('c'),
        'total_stock'   => count($cars),
        'cars'          => $cars,
        'reservations'  => $reservations,
    ];
    $filename = 'dashboard_log_' . date('Ymd_His') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Génération automatique des factures
$resList = $pdo->query(
    "SELECT id, start_date, end_date, car_id
     FROM reservations
     WHERE payment_status = 'completed'"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($resList as $resv) {
    $fileName = sprintf(
        "%s_%s_%s.pdf",
        $resv['car_id'],
        date('Ymd', strtotime($resv['start_date'])),
        date('Ymd', strtotime($resv['end_date']))
    );
    $filePath = __DIR__ . '/../pdf/files/' . $fileName;
    if (!file_exists($filePath)) {
        generateInvoice((int)$resv['id'], $pdo);
    }
}

// Téléchargement manuel de facture
if (isset($_GET['invoice_reservation'])) {
    try {
        $fileName = generateInvoice((int)$_GET['invoice_reservation'], $pdo);
        $filePath = __DIR__ . '/../pdf/files/' . $fileName;
        if (!file_exists($filePath)) throw new Exception('Fichier introuvable.');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: dashboard.php');
        exit;
    }
}

// Statistiques CA
$stmt = $pdo->prepare(
    "SELECT c.id, CONCAT(c.marque,' ',c.modele,' (#',c.id,')') AS label,
            SUM(r.payment_amount) AS total
     FROM reservations r
     JOIN cars c ON r.car_id = c.id
     WHERE r.payment_status = 'completed'
     GROUP BY c.id"
);
$stmt->execute();
$revs      = $stmt->fetchAll(PDO::FETCH_ASSOC);
$revLabels = array_column($revs, 'label');
$revData   = array_map('floatval', array_column($revs, 'total'));
$totalStock = (int)$pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();

$today = date('Y-m-d');
$stmt  = $pdo->prepare(
    "SELECT r.id, CONCAT(c.marque,' ',c.modele) AS voiture,
            u.username AS client, r.start_date, r.end_date
     FROM reservations r
     JOIN cars c ON r.car_id = c.id
     JOIN users u ON r.user_id = u.id
     WHERE r.payment_status = 'completed'
       AND r.start_date <= ?
       AND r.end_date >= ?
     ORDER BY r.start_date ASC"
);
$stmt->execute([$today, $today]);
$rentedCars = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query(
    "SELECT c.id, CONCAT(c.marque,' ',c.modele,' (#',c.id,')') AS label,
            COUNT(r.id) AS cnt
     FROM cars c
     LEFT JOIN reservations r ON r.car_id = c.id AND r.payment_status='completed'
     GROUP BY c.id"
);
$counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalReservations = array_sum(array_column($counts, 'cnt'));

// ---------- Gestion des utilisateurs (ajout / changement de rôle / suppression) ----------

// Ajout d'un nouvel utilisateur
if (isset($_POST['add_user'])) {
    $newUsername = trim($_POST['new_username'] ?? '');
    $newEmail = trim($_POST['new_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $newRole = $_POST['new_role'] ?? 'user';
    
    // Conversion du rôle en ID numérique pour le stockage interne
    $roleId = 2; // Par défaut user = ID 2
    if ($newRole === 'admin') {
        $roleId = 0; // admin = ID 0
    } elseif ($newRole === 'commercial') {
        $roleId = 1; // commercial = ID 1
    }
    
    $errors = [];
    
    // Validation
    if (empty($newUsername)) {
        $errors[] = "Le nom d'utilisateur est requis.";
    }
    
    if (empty($newEmail)) {
        $errors[] = "L'adresse e-mail est requise.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse e-mail n'est pas valide.";
    }
    
    if (empty($newPassword)) {
        $errors[] = "Le mot de passe est requis.";
    } elseif (strlen($newPassword) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    
    if (!in_array($newRole, ['user', 'commercial', 'admin'])) {
        $errors[] = "Le rôle sélectionné n'est pas valide.";
    }
    
    // Vérifier si l'email ou le nom d'utilisateur existe déjà
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR username = :username");
        $stmt->execute(['email' => $newEmail, 'username' => $newUsername]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ce nom d'utilisateur ou cette adresse e-mail existe déjà.";
        }
    }
    
    // Insérer le nouvel utilisateur s'il n'y a pas d'erreurs
    if (empty($errors)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, password, role, role_id, is_verified) 
             VALUES (:username, :email, :password, :role, :role_id, 1)"
        );
        
        try {
            $stmt->execute([
                'username' => $newUsername,
                'email' => $newEmail,
                'password' => $hashedPassword,
                'role' => $newRole,
                'role_id' => $roleId
            ]);
            $_SESSION['success_message'] = "Nouvel utilisateur ajouté avec succès avec ID de rôle: $roleId.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
    
    header("Location: dashboard.php#users");
    exit;
}

// Modification du rôle utilisateur
if (isset($_POST['update_role'], $_POST['user_id'], $_POST['role'])) {
    $id = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    
    // Conversion du rôle en ID numérique
    $roleId = 2; // Par défaut user = ID 2
    if ($newRole === 'admin') {
        $roleId = 0; // admin = ID 0
    } elseif ($newRole === 'commercial') {
        $roleId = 1; // commercial = ID 1
    }
    
    if (in_array($newRole, ['user', 'commercial', 'admin'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = :role, role_id = :role_id WHERE id = :id");
        $stmt->execute([
            'role' => $newRole, 
            'role_id' => $roleId,
            'id' => $id
        ]);
        $_SESSION['success_message'] = "Rôle modifié avec succès (ID de rôle: $roleId).";
    }
    header("Location: dashboard.php#users");
    exit;
}

// Suppression d'un utilisateur
if (isset($_POST['delete_user'], $_POST['user_id'])) {
    $id = (int)$_POST['user_id'];
    if ($id !== (int)$user['id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $_SESSION['success_message'] = "Utilisateur supprimé.";
    }
    header("Location: dashboard.php#users");
    exit;
}

$users = $pdo->query("SELECT id, username, email, role, role_id, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Pour les utilisateurs existants qui n'ont pas encore de role_id, attribuer l'ID approprié
foreach ($users as $u) {
    if (!isset($u['role_id']) || $u['role_id'] === null) {
        $roleId = 2; // Par défaut user = ID 2
        if ($u['role'] === 'admin') {
            $roleId = 0;
        } elseif ($u['role'] === 'commercial') {
            $roleId = 1;
        }
        
        $stmt = $pdo->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
        $stmt->execute(['role_id' => $roleId, 'id' => $u['id']]);
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>
<main class="container" style="margin:2rem auto;max-width:1200px;">

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Camembert CA -->
    <section style="background:#fff;padding:1.5rem;border-radius:8px;
                    box-shadow:0 2px 6px rgba(0,0,0,0.1);margin-bottom:2rem;">
        <h2>Chiffre d'Affaires par Voiture</h2>
        <canvas id="revChart"></canvas>
    </section>

    <!-- Stock total -->
    <section style="background:#fff;padding:1.5rem;border-radius:8px;
                    box-shadow:0 2px 6px rgba(0,0,0,0.1);margin-bottom:2rem;
                    display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h3>Stock Total de Voitures</h3>
            <p style="font-size:2rem;font-weight:bold;color:#2c3e50;margin:0.5rem 0;"><?= $totalStock ?></p>
        </div>
        <a href="dashboard.php" class="btn-gray">Rafraîchir</a>
    </section>

    <!-- Voitures louées aujourd'hui -->
    <section style="background:#fff;padding:1.5rem;border-radius:8px;
                    box-shadow:0 2px 6px rgba(0,0,0,0.1);margin-bottom:2rem;">
        <h2>Voitures Louées Aujourd'hui (<?= date('d/m/Y') ?>)</h2>
        <?php if (empty($rentedCars)): ?>
            <p>Aucune voiture n'est louée actuellement.</p>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;margin-top:1rem;">
                <thead>
                <tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6;">
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">ID Réservation</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">Véhicule</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">Client</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">Du</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">Au</th>
                    <th style="padding:0.75rem;text-align:center;font-weight:600;">Facture</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rentedCars as $rc): ?>
                    <tr style="border-bottom:1px solid #dee2e6;">
                        <td style="padding:0.75rem;"><?= htmlspecialchars($rc['id']) ?></td>
                        <td style="padding:0.75rem;"><?= htmlspecialchars($rc['voiture']) ?></td>
                        <td style="padding:0.75rem;"><?= htmlspecialchars($rc['client']) ?></td>
                        <td style="padding:0.75rem;"><?= date('d/m/Y', strtotime($rc['start_date'])) ?></td>
                        <td style="padding:0.75rem;"><?= date('d/m/Y', strtotime($rc['end_date'])) ?></td>
                        <td style="padding:0.75rem;text-align:center;">
                            <a href="?invoice_reservation=<?= $rc['id'] ?>" class="btn-gray">Télécharger PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- Taux de location -->
    <section style="background:#fff;padding:1.5rem;border-radius:8px;
                    box-shadow:0 2px 6px rgba(0,0,0,0.1);margin-bottom:2rem;">
        <h2>Taux de Location par Voiture</h2>
        <table style="width:100%;border-collapse:collapse;margin-top:1rem;">
            <thead>
            <tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6;">
                <th style="padding:0.75rem;text-align:left;font-weight:600;">Véhicule</th>
                <th style="padding:0.75rem;text-align:center;font-weight:600;">Nb locations</th>
                <th style="padding:0.75rem;text-align:center;font-weight:600;">% du total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($counts as $cnt): ?>
                <?php $pct = $totalReservations > 0 ? round($cnt['cnt'] / $totalReservations * 100, 2) : 0; ?>
                <tr style="border-bottom:1px solid #dee2e6;">
                    <td style="padding:0.75rem;"><?= htmlspecialchars($cnt['label']) ?></td>
                    <td style="padding:0.75rem;text-align:center;"><?= $cnt['cnt'] ?></td>
                    <td style="padding:0.75rem;text-align:center;"><?= $pct ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Bloc Import & Export JSON (en bas, 2 colonnes) -->
    <section style="display:flex;gap:2rem;flex-wrap:wrap;justify-content:space-between;margin-top:3rem;">
        <!-- Bloc Import (gauche) -->
        <div style="flex:1 1 320px;min-width:320px;max-width:48%;background:#fff;padding:1.5rem 1rem;
                    border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);margin-bottom:2rem;">
            <h2 style="margin-bottom:1rem;">Importer des voitures via JSON</h2>
            <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:1rem;">
                <input type="file" name="import_file" accept="application/json" required style="padding:0.5rem;">
                <button type="submit" name="import_json" class="btn" style="padding:0.5rem 1rem;width:fit-content;">Importer</button>
            </form>
        </div>
        <!-- Bloc Export (droite) -->
        <div style="flex:1 1 320px;min-width:320px;max-width:48%;background:#fff;padding:1.5rem 1rem;
                    border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);margin-bottom:2rem;text-align:center;">
            <h2 style="margin-bottom:1rem;">Exporter les données</h2>
            <a href="?download_log=1" class="btn-blue" style="padding:0.5rem 1.5rem;font-size:1rem;">Exporter JSON</a>
        </div>
    </section>

    <!-- GESTION DES UTILISATEURS -->
    <section id="users" style="background:#fff;padding:1.5rem;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.1);margin-bottom:2rem;">
        <h2>Gestion des utilisateurs</h2>
        
        <!-- Formulaire d'ajout d'utilisateur -->
        <div style="margin-bottom:2rem;padding:1rem;background:#f8f9fa;border-radius:6px;border:1px solid #e9ecef;">
            <h3 style="margin-bottom:1rem;font-size:1.2rem;color:#2c3e50;">Ajouter un nouvel utilisateur</h3>
            <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;">
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <label for="new_username" style="font-weight:600;">Nom d'utilisateur</label>
                    <input type="text" id="new_username" name="new_username" required style="padding:0.5rem;border:1px solid #ced4da;border-radius:4px;">
                </div>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <label for="new_email" style="font-weight:600;">Email</label>
                    <input type="email" id="new_email" name="new_email" required style="padding:0.5rem;border:1px solid #ced4da;border-radius:4px;">
                </div>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <label for="new_password" style="font-weight:600;">Mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required style="padding:0.5rem;border:1px solid #ced4da;border-radius:4px;">
                </div>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <label for="new_role" style="font-weight:600;">Rôle</label>
                    <select id="new_role" name="new_role" required style="padding:0.5rem;border:1px solid #ced4da;border-radius:4px;">
                        <option value="user">Utilisateur</option>
                        <option value="commercial">Commercial</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="submit" name="add_user" class="btn-blue" style="padding:0.5rem 1rem;width:fit-content;">Ajouter l'utilisateur</button>
                </div>
            </form>
        </div>
        
        <!-- Liste des utilisateurs -->
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6;">
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">ID</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">Nom d'utilisateur</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">Email</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">Rôle</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">ID Rôle</th>
                    <th style="padding:0.75rem;text-align:left;font-weight:600;">Créé le</th>
                    <th style="padding:0.75rem;text-align:center;font-weight:600;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom:1px solid #dee2e6;">
                        <td style="padding:0.75rem;"><?= $u['id'] ?></td>
                        <td style="padding:0.75rem;"><?= htmlspecialchars($u['username']) ?></td>
                        <td style="padding:0.75rem;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding:0.75rem;">
                            <?php if ($u['id'] != $user['id']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" onchange="this.form.submit()" style="padding:0.3rem;border:1px solid #ced4da;border-radius:4px;">
                                        <option value="user"<?= $u['role']=='user'?' selected':'' ?>>Utilisateur</option>
                                        <option value="commercial"<?= $u['role']=='commercial'?' selected':'' ?>>Commercial</option>
                                        <option value="admin"<?= $u['role']=='admin'?' selected':'' ?>>Admin</option>
                                    </select>
                                    <input type="hidden" name="update_role" value="1">
                                </form>
                            <?php else: ?>
                                <b><?= ucfirst($u['role']) ?></b>
                            <?php endif; ?>
                        </td>
                        <td style="padding:0.75rem;"><?= $u['role_id'] ?? '-' ?></td>
                        <td style="padding:0.75rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td style="padding:0.75rem;text-align:center;">
                            <?php if ($u['id'] != $user['id']): ?>
                                <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" name="delete_user" class="btn-red" style="padding:0.3rem 0.75rem;font-size:0.9rem;">Supprimer</button>
                                </form>
                            <?php else: ?>
                                <em>Moi-même</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

</main>
<script>
    const ctx = document.getElementById('revChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: { labels: <?= json_encode($revLabels) ?>, datasets: [{ data: <?= json_encode($revData) ?> }] },
        options: { responsive: true, plugins: { tooltip: { callbacks: { label: c => `${c.label}: ${c.parsed} €` } }, datalabels: { formatter: v => v+' €', color:'#fff', font:{weight:'bold'} } } },
        plugins: [ChartDataLabels]
    });
</script>
</body>
</html>