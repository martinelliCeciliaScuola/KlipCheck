<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: ./login/Accesso.php");
    exit;
}

$db = new PDO("mysql:host=localhost;dbname=klipcheckdb;charset=utf8mb4", "root", "mysql");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$messaggio = "";

if (isset($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM utente WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $messaggio = "Account cancellato!";
       
    } catch (PDOException $e) {
        $messaggio = "Errore nella cancellazione dell'account";
    }
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Area riservata Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .btn-login {
            color: white !important;
            text-decoration: none;
        }
    </style>
</head>
<body>

<header>
    <h1>KlipCheck</h1>
    <nav>
        <a href="index.php">Home</a>
        
    </nav>
</header>

<div class="container">
    <div class="login-box">
        <h2 class="login-title">Benvenuto, <?php echo $_SESSION['user']; ?>!</h2>

        <?php if ($messaggio): ?>
            <div class="<?php echo strpos($messaggio, 'cancellato') !== false || strpos($messaggio, 'aggiornato') !== false ? 'success-message' : 'error-message'; ?>">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 20px; justify-content: center; margin-top: 30px;">
            <a href="logout.php" class="btn-login">logout</a>
        </div>
        <div style="display: flex; gap: 20px; justify-content: center; margin-top: 30px;">
            <a href="?delete=<?php echo $_SESSION['user']; ?>" onclick="return confirm('Vuoi eliminare account?)" class="btn-login">Elimina Account</a>
        </div>
    </div>
</div>

<footer>
    <p>2026 KlipCheck - Tutti i diritti riservati</p>
</footer>

</body>
</html>