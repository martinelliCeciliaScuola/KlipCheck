<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: ./login/Accesso.php");
    exit;
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
        
        <div style="display: flex; gap: 20px; justify-content: center; margin-top: 30px;">
            <a href="GestisciFilm.php" class="btn-login">Gestisci Film</a>
            <a href="AggiungiFilm.php" class="btn-login">Aggiungi Film</a>
            <a href="logout.php" class="btn-login">logout</a>
        </div>
    </div>
</div>

<footer>
    <p>2026 KlipCheck - Tutti i diritti riservati</p>
</footer>

</body>
</html>