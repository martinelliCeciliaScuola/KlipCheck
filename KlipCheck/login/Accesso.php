<?php
session_start();


$servername = "localhost";
$db_username = "mysql";
$db_password = "mysql";
$dbname = "klipcheckdb";

$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username_input = trim($_POST["Username"] ?? "");
    $password_input = $_POST["Password"] ?? "";

    if (empty($username_input) || empty($password_input)) {
        $error = "Inserisci username e password.";
    } else {
        try {
            $conn = new PDO(
                "mysql:host=$servername;dbname=$dbname;charset=utf8",
                $db_username,
                $db_password
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Query sicura con prepared statement
            $stmt = $conn->prepare("SELECT * FROM utenti WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password_input, $user['password'])) {
                // Login riuscito
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: ../dashboard.php");
                exit;
            } else {
                $error = "Username o password non corretti.";
            }

        } catch (PDOException $e) {
            $error = "Errore di connessione al database.";
            
            // $error .= " Dettaglio: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso – KlipCheck</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

  
    <header>
        <h1>KlipCheck</h1>
        <nav>
            <a href="../index.html">Home</a>
        </nav>
    </header>

    <div class="container">
        <div class="login-box">
            <h2 class="login-title">Login</h2>

            <?php if ($error): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="post" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="Username"
                        value="<?= htmlspecialchars($username_input ?? '') ?>"
                        required
                        autocomplete="username"
                    >
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="Password"
                        required
                        autocomplete="current-password"
                    >
                </div>
                <button type="submit" class="btn-login">Accedi</button>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
    </footer>

</body>
</html>