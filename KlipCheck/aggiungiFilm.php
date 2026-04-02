<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: ./login/Accesso.php");
    exit;
}

$success = "";
$error = "";
$formData = [];

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
        die("Errore connessione DB: " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty($_POST["titolo"])) {
        $error = "Il titolo è obbligatorio";
    } elseif (empty($_POST["trama"])) {
        $error = "La trama è obbligatoria";
    } elseif (empty($_POST["locandina"])) {
        $error = "L'URL della locandina è obbligatorio";
    } elseif (empty($_POST["trailer"])) {
        $error = "L'URL del trailer è obbligatorio";
    } elseif (empty($_POST["piattaforme"])) {
        $error = "Le piattaforme streaming sono obbligatorie";
    } elseif (empty($_POST["cast"])) {
        $error = "Il cast è obbligatorio";
    } elseif (empty($_POST["regista"])) {
        $error = "Il regista è obbligatorio";
    } else {
        
        $formData = [
            'titolo' => trim($_POST["titolo"]),
            'trama' => trim($_POST["trama"]),
            'locandina' => trim($_POST["locandina"]),
            'trailer' => trim($_POST["trailer"]),
            'piattaforme' => trim($_POST["piattaforme"]),
            'cast' => trim($_POST["cast"]),
            'regista' => trim($_POST["regista"])
        ];

        try {
            $conn = connectToDb();

            $stmt = $conn->prepare("
                INSERT INTO film (titolo, trama, locandina, trailer, piattaforme, cast, regista)
                VALUES (:titolo, :trama, :locandina, :trailer, :piattaforme, :cast, :regista)
            ");

            $stmt->execute($formData);

            $success = "Film aggiunto con successo!";
            $formData = [];

        } catch (PDOException $e) {
            // Mostra l'errore reale per il debug
            $error = "Errore DB: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Film - KlipCheck</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>KlipCheck</h1>
    <nav>
        <a href="../index.php">Home</a>
        <a href="AggiungiFilm.php">Aggiungi Film</a>
        <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION["user"]) ?>)</a>
    </nav>
</header>

<div class="container">
    <div class="login-box">
        <h2 class="login-title">Aggiungi un Nuovo Film</h2>

        <?php if (!empty($success)): ?>
            <div class="success-message" style="background-color:rgba(76, 175, 80, 0.1); border-left:3px solid #4caf50; padding:10px; border-radius:5px; margin-bottom:15px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message" style="background-color:rgba(229, 9, 20, 0.1); border-left:3px solid #e50914; padding:10px; border-radius:5px; margin-bottom:15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="titolo">Titolo *</label>
                <input type="text" id="titolo" name="titolo" required
                       value="<?= htmlspecialchars($formData['titolo'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="trama">Trama *</label>
                <textarea id="trama" name="trama" required
                          style="width:100%; padding:8px; margin-top:10px; border-radius:5px; border:none; background-color:#2a2a2a; color:#ffffff; min-height:100px; font-family:Arial, sans-serif;"><?= htmlspecialchars($formData['trama'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="locandina">URL Locandina *</label>
                <input type="url" id="locandina" name="locandina" required
                       value="<?= htmlspecialchars($formData['locandina'] ?? '') ?>"
                       placeholder="https://example.com/poster.jpg">
                <small style="display:block; margin-top:5px; color:#cccccc; font-size:12px;">URL dell'immagine della locandina</small>
            </div>

            <div class="form-group">
                <label for="trailer">URL Trailer *</label>
                <input type="url" id="trailer" name="trailer" required
                       value="<?= htmlspecialchars($formData['trailer'] ?? '') ?>"
                       placeholder="https://youtube.com/watch?v=...">
                <small style="display:block; margin-top:5px; color:#cccccc; font-size:12px;">URL del trailer (es. YouTube)</small>
            </div>

            <div class="form-group">
                <label for="piattaforme">Piattaforme Streaming *</label>
                <input type="text" id="piattaforme" name="piattaforme" required
                       value="<?= htmlspecialchars($formData['piattaforme'] ?? '') ?>"
                       placeholder="Netflix, Prime Video, Disney+">
                <small style="display:block; margin-top:5px; color:#cccccc; font-size:12px;">Separa le piattaforme con virgole</small>
            </div>

            <div class="form-group">
                <label for="cast">Cast *</label>
                <input type="text" id="cast" name="cast" required
                       value="<?= htmlspecialchars($formData['cast'] ?? '') ?>"
                       placeholder="Attore 1, Attore 2, Attore 3">
                <small style="display:block; margin-top:5px; color:#cccccc; font-size:12px;">Separa gli attori con virgole</small>
            </div>

            <div class="form-group">
                <label for="regista">Regista *</label>
                <input type="text" id="regista" name="regista" required
                       value="<?= htmlspecialchars($formData['regista'] ?? '') ?>"
                       placeholder="Christopher Nolan">
            </div>

            <button type="submit" class="btn-login">Aggiungi Film</button>
        </form>
    </div>
</div>

<footer>
    <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
</footer>

</body>
</html>