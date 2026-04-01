<?php
session_start();

$error = "";
$success = "";
$username_input = "";
$email_input = "";

function connectToDb()
{
    $servername = "localhost";
    $dbUsername = "root";
    $dbPassword = "mysql";
    $dbname = "klipcheckdb";

    try {
        $conn = new PDO(
            "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
            $dbUsername,
            $dbPassword
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Errore connessione DB");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["Username"]) && isset($_POST["Email"]) && isset($_POST["Password"]) 
        && !empty($_POST["Username"]) && !empty($_POST["Email"]) && !empty($_POST["Password"])) {
        
        $username_input = trim($_POST["Username"]);
        $email_input = trim($_POST["Email"]);
        $password = $_POST["Password"];

        try {
            $conn = connectToDb();

            $stmt = $conn->prepare("SELECT * FROM utente WHERE username = :username");
            $stmt->execute(["username" => $username_input]);

            if ($stmt->rowCount() > 0) {
                $error = "Username già esistente";
            } else {
                $stmt = $conn->prepare("SELECT * FROM utente WHERE email = :email");
                $stmt->execute(["email" => $email_input]);

                if ($stmt->rowCount() > 0) {
                    $error = "Email già registrata";
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO utente (username, email, password, grado) 
                        VALUES (:username, :email, :password, :grado)
                    ");

                    $stmt->execute([
                        "username" => $username_input,
                        "email" => $email_input,
                        "password" => $password,
                        "grado" => "visualizzatore"
                    ]);

                    $success = "Registrazione completata con successo! Reindirizzamento al login...";
                    header("refresh:2; url=login.php");
                }
            }
        } catch (PDOException $e) {
            $error = "Errore del server durante la registrazione";
        }
    } else {
        $error = "Tutti i campi sono obbligatori";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione – KlipCheck</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <h1>KlipCheck</h1>
        <nav>
            <a href="../index.html">Home</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <div class="container">
        <div class="login-box">
            <h2 class="login-title">Registrazione</h2>
            
            <?php if (!empty($error)): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <p class="success-message"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username"
                        name="Username" 
                        value="<?= htmlspecialchars($username_input) ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email"
                        name="Email" 
                        value="<?= htmlspecialchars($email_input) ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password"
                        name="Password" 
                        required
                    >
                    <small>Minimo 6 caratteri</small>
                </div>
                <button type="submit" class="btn-login">Registrati</button>
            </form>
            
            <div style="text-align: center; margin-top: 15px;">
                <p>Hai già un account? <a href="login.php">Accedi qui</a></p>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
    </footer>
</body>
</html>