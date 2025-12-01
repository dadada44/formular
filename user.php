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

// === 2. NAHRÁVÁNÍ VLASTNÍHO AVATARU ===
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
            if ($avatar !== "default.png" && file_exists($uploadDir . $avatar)) {
                @unlink($uploadDir . $avatar);
            }

            // Uložit do databáze nový soubor
            $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newFile, $user_id);
            mysqli_stmt_execute($stmt);

            // Aktualizace hodnot pro stránku
            $avatar = $newFile;
            $avatar_path = $uploadDir . $newFile;

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
    } elseif (mysqli_fetch_row(mysqli_query($conn, "SELECT 1 FROM users WHERE username = '$new_username' AND id != $user_id"))) {
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
    <title>Profil | Apex Inventory</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body class="user-page">
    <main>
        <section>
            <div class="form">
                <div class="form-header">
                    <img src="/img/apexlogo.png" alt="Logo" style="width:90px;">
                    <h2>Vítej, <span style="color:#ff00e1;"><?= htmlspecialchars($username) ?></span>!</h2>
                </div>

                <?php if ($error): ?>
                    <div class="msg error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="msg success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <!-- Mini dashboard -->
                <div class="dashboard">
                    <div class="card"><h3>Status</h3><p style="color:#4caf50;font-size:1.4rem;">Aktivní</p></div>
                    <div class="card"><h3>E-mail</h3><p><?= $email ? htmlspecialchars($email) : "<em>není vyplněn</em>" ?></p></div>
                </div>

                <!-- VÝBĚR + NAHRÁVÁNÍ AVATARU -->
                <div class="section text-center">
                    <h3 class="section-title">Tvůj aktuální avatar</h3>
                    <img src="<?= $avatar_path ?>?v=<?= time() ?>" alt="Aktuální avatar" class="avatar-img">

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
                    <h3 style="color:#ff00e1;">Upravit přezdívku a e-mail</h3>
                    <form method="POST">
                        <div class="form-main-inputs"><input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required pattern="[a-zA-Z0-9]+"></div>
                        <div class="form-main-inputs"><input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="tvuj@email.cz"></div>
                        <button type="submit" name="update_profile">Uložit změny</button>
                    </form>
                </div>

                <!-- Změna hesla -->
                <div class="section">
                    <h3 style="color:#ff00e1;">Změnit heslo</h3>
                    <form method="POST">
                        <div class="form-main-inputs"><input type="password" name="old_password" placeholder="Staré heslo" required></div>
                        <div class="form-main-inputs"><input type="password" name="new_password" placeholder="Nové heslo (min. 6)" required minlength="6"></div>
                        <div class="form-main-inputs"><input type="password" name="new_password_confirm" placeholder="Zopakovat nové heslo" required></div>
                        <button type="submit" name="change_password" style="background:#f25a5a;">Změnit heslo</button>
                    </form>
                </div>

                <div style="text-align:center; margin:40px 0;">
                    <a href="logout.php" style="background:#932929; color:white; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:600;">
                        Odhlásit se
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>