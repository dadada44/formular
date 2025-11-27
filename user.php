<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = $success = "";

// Načtení uživatele
$stmt = mysqli_prepare($conn, "SELECT username, email, avatar FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $username, $email, $avatar);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Výchozí avatar, pokud není nastaven
if (!$avatar || !file_exists("img/avatars/$avatar")) {
    $avatar = "default.png"; // musí být v img/avatars/
}

$avatar_path = "img/avatars/" . $avatar;

// === VÝBĚR AVATARU Z GALERIE ===
if (isset($_POST['change_avatar']) && !empty($_POST['selected_avatar'])) {
    $new_avatar = basename($_POST['selected_avatar']);
    $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    $ext = strtolower(pathinfo($new_avatar, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) && file_exists("img/avatars/$new_avatar")) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_avatar, $user_id);
        mysqli_stmt_execute($stmt);
        $avatar = $new_avatar;
        $avatar_path = "img/avatars/$new_avatar";
        $success = "Avatar byl úspěšně změněn!";
    } else {
        $error = "Neplatný avatar!";
    }
}

// === ZMĚNA PŘEZDÍVKY + E-MAILU ===
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9]+$/', $new_username)) {
        $error = "Přezdívka smí obsahovat jen písmena a čísla!";
    } else {
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
    }
}

// === ZMĚNA HESLA ===
if (isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new1 = $_POST['new_password'];
    $new2 = $_POST['new_password_confirm'];

    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $hash);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

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
    <style>
        .avatar-img { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 5px solid #ff00e1; box-shadow: 0 0 25px rgba(255,0,225,0.5); transition: .3s; }
        .avatar-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); 
            gap: 15px; 
            padding: 15px; 
            background: #222; 
            border-radius: 12px; 
            border: 1px solid #434355; 
            max-height: 420px; 
            overflow-y: auto; 
        }
        .avatar-option img { 
            width: 100%; 
            height: 100px; 
            object-fit: cover; 
            border-radius: 12px; 
            transition: all .3s; 
            cursor: pointer; 
        }
        .avatar-option img:hover { transform: scale(1.1); box-shadow: 0 0 20px #ff00e1; }
        .avatar-option button { background:none; border:none; padding:0; }
        .section { background:#2a2b35; padding:25px; border-radius:12px; margin:20px 0; border:1px solid #434355; }
        .dashboard { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:20px; margin:30px 0; }
        .card { background:#2a2b35; padding:25px; border-radius:12px; text-align:center; border:1px solid #434355; }
    </style>
</head>
<body>
    <main>
        <section>
            <div class="form" style="max-width:950px;">
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

                <!-- VÝBĚR AVATARU Z GALERIE -->
                <div class="section" style="text-align:center;">
                    <h3 style="color:#ff00e1; margin-bottom:20px;">Tvůj aktuální avatar</h3>
                    <img src="<?= $avatar_path ?>?v=<?= time() ?>" alt="Avatar" class="avatar-img">

                    <h3 style="margin:30px 0 15px; color:#ff00e1;">Vyber nový avatar</h3>
                    <div class="avatar-grid">
                        <?php
                        $folder = 'img/avatars/';
                        $files = scandir($folder);
                        $allowed = ['png','jpg','jpeg','gif','webp'];

                        foreach ($files as $file) {
                            if ($file === '.' || $file === '..') continue;
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            if (in_array($ext, $allowed)) {
                                $active = ($file === $avatar) ? 'border:4px solid #ff00e1; box-shadow:0 0 20px #ff00e1;' : '';
                                echo '
                                <div class="avatar-option">
                                    <form method="POST">
                                        <input type="hidden" name="selected_avatar" value="'.$file.'">
                                        <button type="submit" name="change_avatar">
                                            <img src="img/avatars/'.$file.'" alt="'.$file.'" style="'.$active.'">
                                        </button>
                                    </form>
                                </div>';
                            }
                        }
                        ?>
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