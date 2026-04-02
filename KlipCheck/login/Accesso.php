<?php 
session_start();

$error = "";
$username_input = "";

function connectToDb ()
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
        die("Errore connessione DB: " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    if (!empty($_POST["Username"]) && !empty($_POST["Password"]))
    {
        $username_input = trim($_POST["Username"]);
        $password = $_POST["Password"];

        try {
            $conn = connectToDb();

            $stmt = $conn->prepare("
                SELECT id, username, password, grado
                FROM utente 
                WHERE username = :username
            ");

            $stmt->execute(["username" => $username_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user)
            {
                if ($password === $user["password"])
                {
                    $_SESSION["user"] = $user["username"];
                    $_SESSION["grado"] = $user["grado"];
                    $_SESSION["user_id"] = $user["id"];

                    header("Location: C:\Program Files\Ampps\www\KlipCheck\KlipCheck\index.php");
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
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso – KlipCheck</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

    <header>
        <h1>KlipCheck</h1>
        <nav>
            <a href="../index.php">Home</a>
            <a href="./Registrazione.php">Registrati</a>
        </nav>
    </header>
    <div class="container">
        <div class="login-box">
            <h2 class="login-title">Login</h2>
            <?php if (!empty($error)): ?>
                <p style="color:red"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="post" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="Username"
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