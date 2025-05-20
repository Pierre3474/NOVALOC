<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'commercial') {
    header('Location: ../auth/login.php');
    exit;
}

// Initialisation
$code_check_result = null;

// Import ZIP photos par lot
$zip_import_msg = '';
if (isset($_POST['import_zip_photos']) && !empty($_FILES['zip_photos']['tmp_name'])) {
    $zipPath = $_FILES['zip_photos']['tmp_name'];
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === TRUE) {
        $extractDir = sys_get_temp_dir() . '/cars_zip_' . uniqid();
        mkdir($extractDir);
        $zip->extractTo($extractDir);
        $zip->close();

        foreach (scandir($extractDir) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $carId = (int)$dir;
            if ($carId <= 0) continue;
            $carDir = $extractDir . '/' . $dir;
            if (!is_dir($carDir)) continue;

            $stmt = $pdo->prepare('SELECT images FROM cars WHERE id = ?');
            $stmt->execute([$carId]);
            $car = $stmt->fetch();
            if (!$car) continue;
            $existingImages = json_decode($car['images'] ?? '[]', true);
            $imgCount = count($existingImages);

            $baseDir = __DIR__ . '/../assets/images/cars';
            $targetDir = "$baseDir/voiture_$carId";
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

            foreach (scandir($carDir) as $img) {
                if (in_array($img, ['.', '..'])) continue;
                $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;
                if ($imgCount >= 3) break;

                $imgSource = $carDir . '/' . $img;
                $imgCount++;
                $filename = "voiture_{$carId}_{$imgCount}.{$ext}";
                $destPath = "$targetDir/$filename";
                copy($imgSource, $destPath);
                $existingImages[] = "assets/images/cars/voiture_$carId/$filename";
            }
            $imgUrl = $existingImages[0] ?? '';
            $upd = $pdo->prepare('UPDATE cars SET images = ?, image_url = ? WHERE id = ?');
            $upd->execute([json_encode($existingImages), $imgUrl, $carId]);
        }
        function deleteDir($dir) {
            if (!is_dir($dir)) return;
            foreach(scandir($dir) as $f) {
                if ($f !== '.' && $f !== '..') {
                    if (is_dir("$dir/$f")) deleteDir("$dir/$f");
                    else unlink("$dir/$f");
                }
            }
            rmdir($dir);
        }
        deleteDir($extractDir);
        $zip_import_msg = "Import terminé avec succès.";
    } else {
        $zip_import_msg = "Échec de l'ouverture du ZIP.";
    }
}

// Suppression multiple d'images d'une voiture (AJAX ou POST classique)
if (isset($_POST['delete_multi_car_id'], $_POST['delete_images']) && is_array($_POST['delete_images'])) {
    $carId = (int)$_POST['delete_multi_car_id'];
    $idxsToDelete = array_map('intval', $_POST['delete_images']);
    sort($idxsToDelete);

    $stmt = $pdo->prepare('SELECT images FROM cars WHERE id = ?');
    $stmt->execute([$carId]);
    $car = $stmt->fetch();
    $imgs = json_decode($car['images'] ?? '[]', true);

    foreach (array_reverse($idxsToDelete) as $idx) {
        if (isset($imgs[$idx])) {
            $filePath = __DIR__ . '/../' . $imgs[$idx];
            if (file_exists($filePath)) unlink($filePath);
            array_splice($imgs, $idx, 1);
        }
    }
    $baseDir = __DIR__ . '/../assets/images/cars/voiture_' . $carId;
    foreach ($imgs as $i => $relPath) {
        $ext = pathinfo($relPath, PATHINFO_EXTENSION);
        $newName = "voiture_{$carId}_" . ($i+1) . ".{$ext}";
        $newRelPath = "assets/images/cars/voiture_{$carId}/$newName";
        $absOld = __DIR__ . '/../' . $relPath;
        $absNew = "$baseDir/$newName";
        if ($absOld !== $absNew && file_exists($absOld)) rename($absOld, $absNew);
        $imgs[$i] = $newRelPath;
    }
    $imgUrl = $imgs[0] ?? '';
    $upd = $pdo->prepare('UPDATE cars SET images = ?, image_url = ? WHERE id = ?');
    $upd->execute([json_encode($imgs), $imgUrl, $carId]);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode([
            'success' => true,
            'images' => $imgs
        ]);
        exit;
    }
    header('Location: dashboard.php?imgdel=' . $carId);
    exit;
}

// Suppression d'une image d'une voiture (bouton X individuel, AJAX)
if (isset($_POST['delete_car_image'], $_POST['image_idx'])) {
    $carId = (int)$_POST['delete_car_image'];
    $idx = (int)$_POST['image_idx'];
    $stmt = $pdo->prepare('SELECT images FROM cars WHERE id = ?');
    $stmt->execute([$carId]);
    $car = $stmt->fetch();
    $imgs = json_decode($car['images'] ?? '[]', true);

    if (isset($imgs[$idx])) {
        $filePath = __DIR__ . '/../' . $imgs[$idx];
        if (file_exists($filePath)) unlink($filePath);
        array_splice($imgs, $idx, 1);
        $baseDir = __DIR__ . '/../assets/images/cars/voiture_' . $carId;
        foreach ($imgs as $i => $relPath) {
            $ext = pathinfo($relPath, PATHINFO_EXTENSION);
            $newName = "voiture_{$carId}_" . ($i+1) . ".{$ext}";
            $newRelPath = "assets/images/cars/voiture_{$carId}/$newName";
            $absOld = __DIR__ . '/../' . $relPath;
            $absNew = "$baseDir/$newName";
            if ($absOld !== $absNew && file_exists($absOld)) rename($absOld, $absNew);
            $imgs[$i] = $newRelPath;
        }
        $imgUrl = $imgs[0] ?? '';
        $upd = $pdo->prepare('UPDATE cars SET images = ?, image_url = ? WHERE id = ?');
        $upd->execute([json_encode($imgs), $imgUrl, $carId]);
    }
    echo json_encode([
        'success' => true,
        'images' => $imgs
    ]);
    exit;
}

// Gestion des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Suppression d'une voiture (AJAX)
    if (isset($_POST['delete_car'])) {
        $id = (int)$_POST['delete_car'];
        $pdo->prepare('DELETE FROM cars WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // Suppression en masse (AJAX)
    if (isset($_POST['delete_bulk']) && is_array($_POST['delete_bulk'])) {
        $ids = $_POST['delete_bulk'];
        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM cars WHERE id IN ($placeholders)")->execute($ids);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Vérification du code de réservation
    if (isset($_POST['check_pickup_code'])) {
        $check_code = substr(trim($_POST['check_pickup_code']), 0, 8);
        if ($check_code !== '') {
            $stmt = $pdo->prepare("
                SELECT r.*, u.username, u.email, c.marque, c.modele
                FROM reservations r
                JOIN users u ON r.user_id = u.id
                JOIN cars c ON r.car_id = c.id
                WHERE r.pickup_code = ?
                LIMIT 1
            ");
            $stmt->execute([$check_code]);
            $row = $stmt->fetch();
            if ($row) {
                $code_check_result = [
                    'found'      => true,
                    'marque'     => $row['marque'],
                    'modele'     => $row['modele'],
                    'username'   => $row['username'],
                    'email'      => $row['email'],
                    'start_date' => $row['start_date'],
                    'end_date'   => $row['end_date'],
                ];
            } else {
                $code_check_result = ['found' => false];
            }
        }
    }

    // Ajout d’une nouvelle voiture
    if (isset($_POST['add_car'])) {
        $stmt = $pdo->prepare(
            'INSERT INTO cars (badge, marque, modele, type, motorisation, prix, images, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $_POST['badge'],
            $_POST['marque'],
            $_POST['modele'],
            $_POST['type'],
            $_POST['motorisation'],
            $_POST['prix'],
            json_encode([]),
            ''
        ]);
        $carId = $pdo->lastInsertId();

        $baseDir   = __DIR__ . '/../assets/images/cars';
        $targetDir = "$baseDir/voiture_$carId";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $images = [];
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($_FILES["image{$i}"]['tmp_name'])) {
                $ext      = pathinfo($_FILES["image{$i}"]['name'], PATHINFO_EXTENSION);
                $filename = "voiture_{$carId}_{$i}.{$ext}";
                $destPath = "$targetDir/$filename";
                move_uploaded_file($_FILES["image{$i}"]['tmp_name'], $destPath);
                $images[] = "assets/images/cars/voiture_$carId/$filename";
            }
        }
        $imageUrl = count($images) ? $images[0] : '';
        $upd = $pdo->prepare('UPDATE cars SET images = ?, image_url = ? WHERE id = ?');
        $upd->execute([json_encode($images), $imageUrl, $carId]);

        header('Location: dashboard.php?added=1');
        exit;
    }

    // Ajout d’images à une voiture existante (AJAX)
    if (isset($_POST['add_car_images']) && !empty($_POST['add_car_images'])) {
        $carId = (int)$_POST['add_car_images'];

        $stmt = $pdo->prepare('SELECT images FROM cars WHERE id = ?');
        $stmt->execute([$carId]);
        $car = $stmt->fetch();
        $existingImages = json_decode($car['images'] ?? '[]', true);

        $baseDir = __DIR__ . '/../assets/images/cars';
        $targetDir = "$baseDir/voiture_$carId";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $count = count($existingImages);
        if (isset($_FILES['car_images']) && is_array($_FILES['car_images']['name'])) {
            $maxToAdd = min(3, count($_FILES['car_images']['name']));
            for ($i = 0; $i < $maxToAdd && $count < 3; $i++) {
                if (!empty($_FILES['car_images']['tmp_name'][$i])) {
                    $ext = pathinfo($_FILES['car_images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = "voiture_{$carId}_" . ($count + 1) . ".$ext";
                    $destPath = "$targetDir/$filename";
                    move_uploaded_file($_FILES['car_images']['tmp_name'][$i], $destPath);
                    $existingImages[] = "assets/images/cars/voiture_$carId/$filename";
                    $count++;
                }
            }
            $imgUrl = $existingImages[0] ?? '';
            $upd = $pdo->prepare('UPDATE cars SET images = ?, image_url = ? WHERE id = ?');
            $upd->execute([json_encode($existingImages), $imgUrl, $carId]);
        }
        echo json_encode([
            'success' => true,
            'images' => $existingImages
        ]);
        exit;
    }

    // Mise à jour du stock
    if (isset($_POST['save_stock']) || isset($_POST['publish'])) {
        if (!empty($_POST['cars']) && is_array($_POST['cars'])) {
            foreach ($_POST['cars'] as $c) {
                $pdo->prepare(
                    'UPDATE cars SET marque = ?, modele = ?, type = ?, motorisation = ?, prix = ? WHERE id = ?'
                )->execute([
                    $c['marque'], $c['modele'], $c['type'], $c['motorisation'], $c['prix'], $c['id']
                ]);
            }
        }
        if (isset($_POST['save_stock'])) {
            header('Location: dashboard.php?updated=' . count($_POST['cars']));
        } else {
            header('Location: ../index.php');
        }
        exit;
    }
}

$filterBadges = ['Exclusivité','Premium','Édition Limitée','Sport','Luxe','Performance','Racing','Prestige','Innovation','Eco','Confort','Fiabilité','Urbain','Aventure'];
$filterTypes = ['Citadine','Berline','Break','Monospace','Ludospace','SUV','4x4 (Tout-terrain)','Crossover','Coupé','Cabriolet','Pick-up','Utilitaire','Break tout-terrain'];
$filterMotorisations = ['Thermique','Electrique','Hybride','Hydrogène'];
$filterMarques = ['Renault','Peugeot','Citroën','Dacia','Volkswagen','Toyota','Mercedes','Audi','BMW','Hyundai','Kia','Ford','Tesla','Opel','Fiat','Skoda','Nissan','MG','Suzuki','Seat','Volvo','Mini','DS','Cupra','Jeep','Land Rover','Mazda','Lexus','Porsche','BYD','Alfa Romeo','Honda','Mitsubishi','Alpine','Smart','Abarth','Lynk & Co','Jaguar','Ferrari','Leapmotor','Maserati','Lotus','Lamborghini','Bentley','Rolls Royce','Mobilize','Bugatt'];

$cars = $pdo->query("SELECT * FROM cars ORDER BY id DESC")->fetchAll();
$imageCounts = [];
foreach ($cars as $c) {
    $imgs = json_decode($c['images'], true) ?: [];
    $imageCounts[$c['id']] = count($imgs);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Commercial</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="../assets/images/fav.ico">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <style>
    .dropzone { @apply border-2 border-dashed border-gray-300 p-2 rounded cursor-pointer transition duration-300 text-center; }
    .dropzone:hover { @apply border-blue-400 bg-blue-50; }
    .multi-delete-form input[type=checkbox] { width: 18px; height: 18px; }
  </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
  <?php include __DIR__ . '/../header.php'; ?>
  <main class="container mx-auto p-6 flex-1">

    <!-- Vérif code réservation -->
    <section class="max-w-md mx-auto mb-8 bg-white p-6 rounded shadow">
      <h2 class="text-2xl font-bold mb-4">🔎 Vérifier un code de réservation</h2>
      <form method="post" class="space-y-4">
        <input type="text" name="check_pickup_code" maxlength="8" required placeholder="Code ex : IS1EAGO2"
               class="w-full text-center text-lg font-mono p-3 border rounded outline-none focus:ring-2 focus:ring-blue-400" autocomplete="off">
        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded">Vérifier</button>
      </form>
      <?php if (isset($code_check_result)): ?>
        <div class="mt-4">
          <?php if ($code_check_result['found']): ?>
            <div class="bg-green-100 border border-green-400 text-green-800 p-4 rounded">
              <div class="text-3xl">✅</div>
              <div class="font-semibold mb-2">Code valide !</div>
              <p><span class="font-bold">Véhicule :</span> <?= htmlspecialchars($code_check_result['marque'] . ' ' . $code_check_result['modele']) ?></p>
              <p><span class="font-bold">Client :</span> <?= htmlspecialchars($code_check_result['username']) ?> <span class="text-sm text-gray-600">(<?= htmlspecialchars($code_check_result['email']) ?>)</span></p>
              <p><span class="font-bold">Période :</span> du <?= date('d/m/Y', strtotime($code_check_result['start_date'])) ?> au <?= date('d/m/Y', strtotime($code_check_result['end_date'])) ?></p>
            </div>
          <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-800 p-4 rounded">
              <div class="text-3xl">❌</div>
              <p>Aucune réservation trouvée pour ce code.</p>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Ajout voiture -->
    <section class="bg-white p-6 rounded shadow mb-8">
      <h2 class="text-xl font-semibold mb-4">Ajouter une voiture</h2>
      <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <input type="hidden" name="add_car" value="1">
        <div>
          <label class="block text-sm">Badge</label>
          <select name="badge" required class="w-full p-2 border rounded">
            <?php foreach($filterBadges as $b): ?>
              <option><?= htmlspecialchars($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm">Marque</label>
          <input list="marqueList" name="marque" required class="w-full p-2 border rounded" placeholder="Sélectionnez ou saisissez">
          <datalist id="marqueList">
            <?php foreach($filterMarques as $m): ?><option value="<?= htmlspecialchars($m) ?>"><?php endforeach; ?>
          </datalist>
        </div>
        <div>
          <label class="block text-sm">Modèle</label>
          <input name="modele" required class="w-full p-2 border rounded">
        </div>
        <div>
          <label class="block text-sm">Type</label>
          <select name="type" required class="w-full p-2 border rounded">
            <?php foreach($filterTypes as $t): ?><option><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm">Motorisation</label>
          <select name="motorisation" required class="w-full p-2 border rounded">
            <?php foreach($filterMotorisations as $mo): ?><option><?= htmlspecialchars($mo) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm">Prix (€/mois)</label>
          <input name="prix" type="number" step="0.01" required class="w-full p-2 border rounded">
        </div>
        <div class="md:col-span-2 lg:col-span-3">
          <label class="block text-sm">Photos (max 3)</label>
          <div class="grid grid-cols-3 gap-2">
            <input type="file" name="image1" class="dropzone">
            <input type="file" name="image2" class="dropzone">
            <input type="file" name="image3" class="dropzone">
          </div>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 rounded md:col-span-2 lg:col-span-3">+ Ajouter la voiture</button>
      </form>
    </section>

    <!-- Gestion du stock -->
    <section class="bg-white p-6 rounded shadow">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Voitures en stock</h2>
        <div class="space-x-2">
          <button id="bulkDeleteBtn" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">Supprimer la sélection</button>
          <button form="stockForm" name="save_stock" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">Enregistrer modifications</button>
        </div>
      </div>
      <form method="post" id="stockForm">
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-2 py-2"><input type="checkbox" id="selectAll"></th>
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Marque</th>
                <th class="px-4 py-2">Modèle</th>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Motorisation</th>
                <th class="px-4 py-2">Prix</th>
                <th class="px-4 py-2">Images</th>
                <th class="px-4 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($cars as $car): ?>
                <tr class="border-b">
                  <td class="px-2 py-2"><input type="checkbox" name="delete_bulk[]" value="<?= $car['id'] ?>" class="bulk-checkbox"></td>
                  <td class="px-4 py-2 text-center"><?= $car['id'] ?></td>
                  <td class="px-4 py-2"><input type="text" name="cars[<?= $car['id'] ?>][marque]" value="<?= htmlspecialchars($car['marque']) ?>" class="w-24 p-1 border rounded"></td>
                  <td class="px-4 py-2"><input type="text" name="cars[<?= $car['id'] ?>][modele]" value="<?= htmlspecialchars($car['modele']) ?>" class="w-24 p-1 border rounded"></td>
                  <td class="px-4 py-2"><input type="text" name="cars[<?= $car['id'] ?>][type]" value="<?= htmlspecialchars($car['type']) ?>" class="w-24 p-1 border rounded"></td>
                  <td class="px-4 py-2"><input type="text" name="cars[<?= $car['id'] ?>][motorisation]" value="<?= htmlspecialchars($car['motorisation']) ?>" class="w-24 p-1 border rounded"></td>
                  <td class="px-4 py-2"><input type="number" step="0.01" name="cars[<?= $car['id'] ?>][prix]" value="<?= htmlspecialchars($car['prix']) ?>" class="w-20 p-1 border rounded"></td>
                  <td class="px-4 py-2 text-center">
                    <div class="flex flex-col items-center gap-2 car-images-list" data-carid="<?= $car['id'] ?>">
                      <form class="multi-delete-form" method="post" autocomplete="off">
                        <div class="flex gap-1">
                          <?php
                          $imgs = json_decode($car['images'], true) ?: [];
                          foreach ($imgs as $idx => $imgPath):
                          ?>
                            <div class="relative group">
                              <input type="checkbox" name="delete_images[]" value="<?= $idx ?>" class="absolute top-0 left-0 z-10 bg-white">
                              <img src="../<?= htmlspecialchars($imgPath) ?>" class="w-16 h-16 object-cover rounded border" alt="">
                              <button type="button" data-carid="<?= $car['id'] ?>" data-idx="<?= $idx ?>" class="bg-red-600 text-white text-xs px-1 rounded opacity-80 group-hover:opacity-100 absolute top-0 right-0 delete-image-btn">✖</button>
                            </div>
                          <?php endforeach; ?>
                        </div>
                        <div><?= count($imgs) ?> photo(s)</div>
                        <input type="hidden" name="delete_multi_car_id" value="<?= $car['id'] ?>">
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded text-xs mt-2">Supprimer sélection</button>
                      </form>
                      <form class="inline-block add-img-form" data-carid="<?= $car['id'] ?>" enctype="multipart/form-data" method="post" style="display:inline">
                        <input type="hidden" name="add_car_images" value="<?= $car['id'] ?>">
                        <input type="file" name="car_images[]" accept=".jpg,.jpeg,.png" multiple style="display:none;" onchange="this.form.submit();" max="3">
                        <button type="button" class="bg-blue-400 hover:bg-blue-600 text-white py-1 px-2 rounded text-xs add-img-btn">Add JPG</button>
                      </form>
                    </div>
                  </td>
                  <td class="px-4 py-2 text-center">
                    <button type="button" data-id="<?= $car['id'] ?>" class="delete-car-btn bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded text-sm">Supprimer</button>
                  </td>
                  <input type="hidden" name="cars[<?= $car['id'] ?>][id]" value="<?= $car['id'] ?>">
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-6 text-center">
          <button form="stockForm" name="publish" class="bg-purple-500 hover:bg-purple-600 text-white py-3 px-6 rounded-lg text-lg">PUBLIER TOUTES LES MODIFICATIONS</button>
        </div>
      </form>
    </section>

    <!-- Import ZIP photos par lot EN BAS, CENTRÉ -->
    <div class="flex justify-center my-8">
      <section class="bg-white p-6 rounded shadow w-full max-w-lg">
        <h2 class="text-xl font-semibold mb-4 text-center">Importer des photos pour plusieurs voitures</h2>
        <form method="post" enctype="multipart/form-data" class="flex flex-col items-center gap-3">
          <input type="file" name="zip_photos" accept=".zip" required class="border p-2 rounded w-full">
          <button type="submit" name="import_zip_photos" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-6 rounded">Importer</button>
        </form>
        <p class="text-gray-500 mt-2 text-sm text-center">
          Structure attendue : Un dossier ZIP contenant un dossier <strong>par id de voiture</strong>, et dans chaque dossier, les photos.<br>
          Exemple : <code>zip/  ├─ 1/voiture1.jpg, ...  ├─ 2/...</code>
        </p>
        <?php if (!empty($zip_import_msg)) echo '<div class="mt-2 text-green-600 text-center">'.$zip_import_msg.'</div>'; ?>
      </section>
    </div>

  </main>
  <script>
    // Suppression AJAX single voiture
    document.querySelectorAll('.delete-car-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        if (confirm('Supprimer cette voiture ?')) {
          fetch('dashboard.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({delete_car: id})
          }).then(res => res.json()).then(data => {
            if (data.success) btn.closest('tr').remove();
          });
        }
      });
    });
    // Suppression en masse AJAX
    document.getElementById('bulkDeleteBtn').addEventListener('click', () => {
      const checks = Array.from(document.querySelectorAll('.bulk-checkbox:checked')).map(cb => cb.value);
      if (checks.length && confirm('Supprimer toutes les voitures sélectionnées ?')) {
        fetch('dashboard.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: new URLSearchParams({delete_bulk: checks})
        }).then(res => res.json()).then(data => {
          if (data.success) location.reload();
        });
      }
    });
    // Sélectionner tout
    document.getElementById('selectAll').addEventListener('change', e => {
      document.querySelectorAll('.bulk-checkbox').forEach(cb => cb.checked = e.target.checked);
    });

    // FONCTIONS DYNAMIQUES AJAX POUR LES IMAGES
    function updateCarImages(carId, images) {
      const cell = document.querySelector(`.car-images-list[data-carid="${carId}"]`);
      if (!cell) return;
      let html = `<form class="multi-delete-form" method="post" autocomplete="off"><div class="flex gap-1">`;
      images.forEach((img, idx) => {
        html += `
          <div class="relative group">
            <input type="checkbox" name="delete_images[]" value="${idx}" class="absolute top-0 left-0 z-10 bg-white">
            <img src="../${img}" class="w-16 h-16 object-cover rounded border" alt="">
            <button type="button" data-carid="${carId}" data-idx="${idx}" class="bg-red-600 text-white text-xs px-1 rounded opacity-80 group-hover:opacity-100 absolute top-0 right-0 delete-image-btn">✖</button>
          </div>`;
      });
      html += `</div>
        <div>${images.length} photo(s)</div>
        <input type="hidden" name="delete_multi_car_id" value="${carId}">
        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded text-xs mt-2">Supprimer sélection</button>
        </form>
        <form class="inline-block add-img-form" data-carid="${carId}" enctype="multipart/form-data" method="post" style="display:inline">
          <input type="hidden" name="add_car_images" value="${carId}">
          <input type="file" name="car_images[]" accept=".jpg,.jpeg,.png" multiple style="display:none;" onchange="this.form.submit();" max="3">
          <button type="button" class="bg-blue-400 hover:bg-blue-600 text-white py-1 px-2 rounded text-xs add-img-btn">Add JPG</button>
        </form>
      `;
      cell.innerHTML = html;

      // Rebind events
      bindImageEvents(cell, carId);
    }

    function bindImageEvents(cell, carId) {
      // Suppression bouton X (individuel)
      cell.querySelectorAll('.delete-image-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          const idx = btn.dataset.idx;
          const fd = new FormData();
          fd.append('delete_car_image', carId);
          fd.append('image_idx', idx);
          fetch('dashboard.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(data => {
            if (data.success && data.images) {
              updateCarImages(carId, data.images);
            }
          });
        });
      });
      // Ajout images AJAX
      const addForm = cell.querySelector('.add-img-form');
      if (addForm) {
        const fileInput = addForm.querySelector('input[type="file"]');
        addForm.querySelector('.add-img-btn').onclick = function() {
          fileInput.value = "";
          fileInput.click();
        };
        fileInput.onchange = function() {
          if (!fileInput.files.length) return;
          const fd = new FormData(addForm);
          fetch('dashboard.php', {
            method: 'POST',
            body: fd,
          })
          .then(r => r.json())
          .then(data => {
            if (data.success && data.images) {
              updateCarImages(carId, data.images);
            }
          });
        };
      }
      // Suppression multiple AJAX
      const multiForm = cell.querySelector('.multi-delete-form');
      if (multiForm) {
        multiForm.addEventListener('submit', function (e) {
          e.preventDefault();
          const checked = Array.from(multiForm.querySelectorAll('input[name="delete_images[]"]:checked'));
          if (!checked.length) return;
          const fd = new FormData();
          fd.append('delete_multi_car_id', carId);
          checked.forEach(cb => fd.append('delete_images[]', cb.value));
          fetch('dashboard.php', {
            method: 'POST',
            body: fd,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
          })
          .then(r => r.json())
          .then(data => {
            if (data.success && data.images) {
              updateCarImages(carId, data.images);
            }
          });
        });
      }
    }

    // INIT sur toutes les lignes au chargement
    document.querySelectorAll('.car-images-list').forEach(cell => {
      const carId = cell.dataset.carid;
      bindImageEvents(cell, carId);
    });
  </script>
</body>
</html>
