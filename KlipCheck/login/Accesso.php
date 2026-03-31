<?php
session_start();


$error = "";
$username_input = "";


function connectToDb ()
{
    $servername = "localhost";
    $dbUsername = "root";
    $dbPassword = "mysql";
    $dbname = "Klipcheckdb";

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


if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    if (isset($_POST["Username"]) && isset($_POST["Password"]))
    {
        $username_input = $_POST["Username"];
        $password = $_POST["Password"];

        try {
            $conn = connectToDb();

            $stmt = $conn->prepare("
                SELECT * 
                FROM utente 
                WHERE username = :username
            ");

            $stmt->execute(["username" => $username_input]);

            if ($stmt->rowCount() > 0)
            {
                $user = $stmt->fetch();

                
                if ($user["password"] === $password)
                {
                    $_SESSION["user"] = $user["username"];
                    $_SESSION["grado"] = $user["grado"];
                    $_SESSION["user_id"] = $user["id"];

                    header("Location: home.php");
                    exit;
                }
                else
                {
                    $error = "Password errata";
                }
            }
            else
            {
                $error = "Utente non trovato";
            }

        } catch (PDOException $e) {
            $error = "Errore del server";
        }
    }
    else
    {
        $error = "Inserisci tutti i campi";
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

        <?php if (!empty($error)): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="Username"
                    value="<?= htmlspecialchars($username_input) ?>"
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

<footer>
    <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
</footer>

</body>
</html>