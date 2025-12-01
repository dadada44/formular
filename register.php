<?php require 'db.php'; ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vytvořit školní účet</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <main>
        <section>
            <div class="form">
                <div class="form-header">
                    <img src="/img/apexlogo.png" alt="Logo">
                    <h2>Vytvořit školní účet</h2>
                    <p style="margin-top:8px;font-size:0.9rem;color:#6b7280;">
                        Registrace pro žáky. Zvol si přezdívku, kterou si snadno zapamatuješ.
                    </p>
                </div>

                <?php
                $error = $_GET['error'] ?? '';
                $success = $_GET['success'] ?? '';

                if ($error) {
                    echo "<div class='msg error'>$error</div>";
                }
                if ($success) {
                    echo "<div class='msg success'>Účet byl úspěšně vytvořen! <a href='login.php'>Přihlásit se</a></div>";
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $username = trim($_POST['username']);
                    $pass1 = $_POST['password'];
                    $pass2 = $_POST['password_confirm'];

                    if (empty($username) || empty($pass1) || empty($pass2)) {
                        header("Location: register.php?error=Všechna pole jsou povinná");
                        exit;
                    }
                    if ($pass1 !== $pass2) {
                        header("Location: register.php?error=Hesla se neshodují");
                        exit;
                    }
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
                        header("Location: register.php?error=Pouze písmena a čísla v uživatelském jménu");
                        exit;
                    }
                    if (strlen($pass1) < 6) {
                        header("Location: register.php?error=Heslo musí mít minimálně 6 znaků");
                        exit;
                    }

                    // Kontrola duplicity
                    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
                    mysqli_stmt_bind_param($check, "s", $username);
                    mysqli_stmt_execute($check);
                    mysqli_stmt_store_result($check);

                    if (mysqli_stmt_num_rows($check) > 0) {
                        header("Location: register.php?error=Toto jméno je již zabrané");
                        exit;
                    }

                    // Uložení uživatele
                    $hash = password_hash($pass1, PASSWORD_DEFAULT);
                    $insert = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
                    mysqli_stmt_bind_param($insert, "ss", $username, $hash);
                    mysqli_stmt_execute($insert);

                    header("Location: register.php?success=1");
                    exit;
                }
                ?>

                <form method="POST">
                    <div class="form-main">
                        <div class="form-main-inputs">
                            <div class="form-main-inputs-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                <label>Uživatelské jméno</label>
                            </div>
                            <input type="text" name="username" required pattern="[a-zA-Z0-9]+" placeholder="Pouze písmena a čísla">
                        </div>

                        <div class="form-main-inputs">
                            <div class="form-main-inputs-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                <label>Heslo</label>
                            </div>
                            <input type="password" name="password" required minlength="6" placeholder="Min. 6 znaků">
                        </div>

                        <div class="form-main-inputs">
                            <div class="form-main-inputs-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                <label>Potvrdit heslo</label>
                            </div>
                            <input type="password" name="password_confirm" required minlength="6" placeholder="Zopakuj heslo">
                        </div>
                    </div>

                    <div class="form-control">
                        <input type="checkbox" id="terms" required>
                        <label for="terms">Souhlasím s <a href="#">podmínkami služby</a></label>
                    </div>

                    <div class="form-buttons">
                        <button type="submit">Vytvořit účet</button>
                    </div>
                </form>

                <div class="form-footer">
                    <a href="login.php"><span>Už máš účet?</span> Přihlásit se</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>