<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = $success = "";

// === 1. NAČTI UŽIVATELE Z DB (nejdřív!) ===
$stmt = mysqli_prepare($conn, "SELECT username, email, avatar FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $username, $email, $avatar);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Cesta k avataru (fallback na výchozí, pokud není nastaveno nebo soubor chybí)
$avatarFile = $avatar ?: 'default.png';
$avatar_path = 'img/avatars/' . $avatarFile;
if (!file_exists($avatar_path)) {
    // Když neexistuje avatar ve složce, použij logo jako náhradu
    $avatar_path = 'img/apexlogo.png';
}

// === 2. ZMĚNA AVATARU – PŘEDDEFINOVANÝ VÝBĚR ZE SLOŽKY ===
if (isset($_POST['choose_avatar']) && isset($_POST['avatar_name'])) {
    $chosen = basename($_POST['avatar_name']); // ochrana před ../
    $uploadDir = 'img/avatars/';
    $targetPath = $uploadDir . $chosen;

    if (file_exists($targetPath)) {
        // Smazat starý avatar pokud byl vlastní (a není logo / default)
        if ($avatar && $avatar !== 'default.png' && $avatar !== $chosen && file_exists($uploadDir . $avatar)) {
            @unlink($uploadDir . $avatar);
        }

        $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $chosen, $user_id);
        mysqli_stmt_execute($stmt);

        $avatar = $chosen;
        $avatar_path = $targetPath;
        $success = "Avatar byl změněn.";
    } else {
        $error = "Zvolený avatar nebyl nalezen.";
    }
}

// === 3. NAHRÁVÁNÍ VLASTNÍHO AVATARU Z DISKU ===
if (isset($_POST['upload_new_avatar']) && isset($_FILES['upload_avatar']) && $_FILES['upload_avatar']['error'] === UPLOAD_ERR_OK) {

    $file = $_FILES['upload_avatar'];
    $uploadDir = 'img/avatars/';
    $maxSize = 5 * 1024 * 1024; // 5 MB

    // Povolené typy obrázků
    $allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    $allowedMime = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
    
    // Mime type pro bezpečnost
    $fileMime = mime_content_type($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validace
    if (!in_array($fileMime, $allowedMime) || !in_array($ext, $allowedExt)) {
        $error = "Neplatný formát souboru!";
    } elseif ($file['size'] > $maxSize) {
        $error = "Soubor je příliš velký (max 5MB)!";
    } else {
        // Vygeneruj unikátní název
        $newFile = $user_id . "_" . time() . "." . $ext;
        $targetPath = $uploadDir . $newFile;

        // Přesun souboru
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {

            // Smazat starý avatar (pokud není defaultní)
            if ($avatar && $avatar !== "default.png" && file_exists($uploadDir . $avatar)) {
                @unlink($uploadDir . $avatar);
            }

            // Uložit do databáze nový soubor
            $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newFile, $user_id);
            mysqli_stmt_execute($stmt);

            // Aktualizace hodnot pro stránku
            $avatar = $newFile;
            $avatar_path = $targetPath;

            $success = "Avatar byl úspěšně nahrán!";
        } else {
            $error = "Nepodařilo se uložit soubor!";
        }
    }
}

// === 4. ZMĚNA PŘEZDÍVKY + E-MAILU ===
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9]+$/', $new_username)) {
        $error = "Přezdívka smí obsahovat jen písmena a čísla!";
    } else {
        // Bezpečná kontrola duplicity pomocí prepared statement
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
        mysqli_stmt_bind_param($check, "si", $new_username, $user_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Tato přezdívka je už zabraná!";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET username = ?, email = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $new_username, $new_email, $user_id);
            mysqli_stmt_execute($stmt);

            $_SESSION['username'] = $new_username;
            $username = $new_username;
            $email = $new_email;
            $success = "Profil byl aktualizován!";
        }
        mysqli_stmt_close($check);
    }
}

// === 5. ZMĚNA HESLA ===
if (isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new1 = $_POST['new_password'];
    $new2 = $_POST['new_password_confirm'];

    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $hash);
    mysqli_stmt_fetch($stmt);

    if (!password_verify($old, $hash)) {
        $error = "Staré heslo je špatné!";
    } elseif ($new1 !== $new2) {
        $error = "Nová hesla se neshodují!";
    } elseif (strlen($new1) < 6) {
        $error = "Nové heslo musí mít alespoň 6 znaků!";
    } else {
        $new_hash = password_hash($new1, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_hash, $user_id);
        mysqli_stmt_execute($stmt);
        $success = "Heslo bylo úspěšně změněno!";
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Školní profil</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body class="user-page">
    <main>
        <section>
            <div class="form">
                <div class="form-header">
                    <img src="/img/apexlogo.png" alt="Logo" style="width:80px;">
                    <h2>Školní profil</h2>
                    <p style="margin-top:6px;font-size:0.9rem;color:#6b7280;">
                        Přihlášen jako <strong><?= htmlspecialchars($username) ?></strong>.
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="msg error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="msg success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <!-- Přehled účtu -->
                <div class="dashboard">
                    <div class="card">
                        <h3 style="font-size:0.9rem;color:#6b7280;margin-bottom:4px;">Stav účtu</h3>
                        <p style="color:#16a34a;font-size:1rem;font-weight:600;">Aktivní žák</p>
                    </div>
                    <div class="card">
                        <h3 style="font-size:0.9rem;color:#6b7280;margin-bottom:4px;">E‑mail pro školu</h3>
                        <p style="font-size:0.95rem;">
                            <?= $email ? htmlspecialchars($email) : "<em>není vyplněn</em>" ?>
                        </p>
                    </div>
                </div>

                <!-- VÝBĚR + NAHRÁVÁNÍ AVATARU -->
                <div class="section text-center">
                    <h3 class="section-title">Tvůj aktuální avatar</h3>
                    <img src="<?= htmlspecialchars($avatar_path) ?>?v=<?= time() ?>" alt="Aktuální avatar" class="avatar-img">

                    <!-- PŘEDDEFINOVANÉ AVATARY ZE SLOŽKY -->
                    <div class="mt-5">
                        <h4 class="section-subtitle">Vyber si z galerie</h4>
                        <div class="avatar-grid">
                            <?php
                            $avatarDirPath = __DIR__ . '/img/avatars';
                            if (is_dir($avatarDirPath)) {
                                $files = scandir($avatarDirPath);
                                $allowedExt = ['png','jpg','jpeg','gif','webp'];
                                foreach ($files as $fileName) {
                                    if ($fileName === '.' || $fileName === '..') continue;
                                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                    if (!in_array($ext, $allowedExt)) continue;
                                    $isActive = ($avatar === $fileName);
                                    ?>
                                    <form method="POST" class="avatar-option<?= $isActive ? ' avatar-option--active' : '' ?>">
                                        <input type="hidden" name="avatar_name" value="<?= htmlspecialchars($fileName) ?>">
                                        <button type="submit" name="choose_avatar">
                                            <img src="img/avatars/<?= htmlspecialchars($fileName) ?>" alt="Avatar <?= htmlspecialchars($fileName) ?>">
                                        </button>
                                    </form>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- NAHRÁVÁNÍ VLASTNÍHO AVATARU -->
                    <div class="upload-section mt-5">
                        <form method="POST" enctype="multipart/form-data" class="upload-form">
                            <label for="upload_avatar" class="upload-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Nahrát vlastní avatar
                            </label>
                            <input type="file" name="upload_avatar" id="upload_avatar" accept="image/*" required>
                            <button type="submit" name="upload_new_avatar" class="btn-primary mt-3">Nahrát a použít</button>
                        </form>
                    </div>
                </div>

                <!-- Upravit profil -->
                <div class="section">
                    <h3 style="font-size:1rem;color:#111827;margin-bottom:10px;">Osobní údaje</h3>
                    <form method="POST">
                        <div class="form-main-inputs">
                            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required pattern="[a-zA-Z0-9]+" placeholder="Školní přezdívka">
                        </div>
                        <div class="form-main-inputs">
                            <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="školní e‑mail (volitelné)">
                        </div>
                        <button type="submit" name="update_profile">Uložit změny</button>
                    </form>
                </div>

                <!-- Změna hesla -->
                <div class="section">
                    <h3 style="font-size:1rem;color:#111827;margin-bottom:10px;">Heslo k účtu</h3>
                    <form method="POST">
                        <div class="form-main-inputs"><input type="password" name="old_password" placeholder="Staré heslo" required></div>
                        <div class="form-main-inputs"><input type="password" name="new_password" placeholder="Nové heslo (min. 6 znaků)" required minlength="6"></div>
                        <div class="form-main-inputs"><input type="password" name="new_password_confirm" placeholder="Zopakovat nové heslo" required></div>
                        <button type="submit" name="change_password">Změnit heslo</button>
                    </form>
                </div>

                <div style="text-align:center; margin:24px 0 8px;">
                    <a href="logout.php">
                        Odhlásit se ze školního účtu
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>