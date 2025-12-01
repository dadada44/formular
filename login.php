<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT id, password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) === 1) {
        mysqli_stmt_bind_result($stmt, $id, $hash);
        mysqli_stmt_fetch($stmt);

        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: user.php");
            exit;
        }
    }
    $error = "Špatné jméno nebo heslo";
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Školní přihlášení</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <main>
        <section>
            <div class="form">
                <div class="form-header">
                    <img src="/img/apexlogo.png" alt="Logo">
                    <h2>Školní přihlášení</h2>
                    <p style="margin-top:8px;font-size:0.9rem;color:#6b7280;">
                        Přihlas se svým školním účtem a pokračuj do svého profilu.
                    </p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="msg error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-main">
                        <div class="form-main-inputs">
                            <div class="form-main-inputs-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                <label>Uživatelské jméno</label>
                            </div>
                            <input type="text" name="username" required>
                        </div>

                        <div class="form-main-inputs">
                            <div class="form-main-inputs-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                <label>Heslo</label>
                            </div>
                            <input type="password" name="password" required>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit">Přihlásit se</button>
                    </div>
                </form>

                <div class="form-footer">
                    <a href="register.php"><span>Ještě nemáš účet?</span> Zaregistrovat se</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>