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
</head>
<body>

<h2>Login </h2>

<?php if (!empty($error)): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <input type="text" name="Username" required value="<?= htmlspecialchars($username_input) ?>">
    <br><br>
    <input type="password" name="Password" required>
    <br><br>
    <button type="submit">Accedi subito</button>
</form>

</body>
</html>