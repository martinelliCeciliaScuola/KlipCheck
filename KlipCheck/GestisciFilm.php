<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: ./login/Accesso.php");
    exit;
}
if($_SESSION["grado"]!= "admin") {
  die("non sei un admin ;), torna alla home");
 
}

$db = new PDO("mysql:host=localhost;dbname=klipcheckdb;charset=utf8mb4", "root", "mysql");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$messaggio = "";
$filmEdit = null;

if (isset($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM film WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $messaggio = "Film cancellato!";
       
    } catch (PDOException $e) {
        $messaggio = "Errore nella cancellazione";
    }
}

if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM film WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $filmEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$filmEdit) $messaggio = "Film non trovato!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $titolo = trim($_POST['titolo']);
    $regista = trim($_POST['regista']);
    $trama=trim($_POST['trama']);
    $locandina=trim($_POST['locandina']);
    $trailer=trim($_POST['trailer']);
    $piattaforme=trim($_POST['piattaforme']);
    $cast=trim($_POST['cast']); 
    
    if (empty($titolo) || empty($regista)||empty($trama)||empty($trailer)||empty($locandina)||empty($piattaforme)||empty($cast)) {
        $messaggio = "campi obbligatori!";
    } else {
        try {
            $sql = "UPDATE film SET titolo=?, regista=?, trama=?, locandina=?, trailer=?, piattaforme=?, cast=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $titolo, $regista, $trama, 
                $locandina, $trailer, 
                $piattaforme, $cast, $_POST['id']
            ]);
            $messaggio = "Film aggiornato!";
            $filmEdit = null;
        } catch (PDOException $e) {
            $messaggio = "Errore nell'aggiornamento";
        }
    }
}

$films = $db->query("SELECT id, titolo, regista FROM film ORDER BY titolo")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestisci Film </title>
    <link rel="stylesheet" href="style.css">
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
        <h2 class="login-title">Gestisci Film</h2>

        <?php if ($messaggio): ?>
            <div class="<?php echo strpos($messaggio, 'cancellato') !== false || strpos($messaggio, 'aggiornato') !== false ? 'success-message' : 'error-message'; ?>">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($films)): ?>
            <p>Nessun film presente nel database.</p>
        <?php else: ?>
            <table style="width:100%; border-collapse: collapse; margin-bottom:20px;">
                <thead>
                    <tr style="background-color:#333;">
                        <th style="padding:12px; text-align:left; color:#e50914;">Titolo</th>
                        <th style="padding:12px; text-align:left; color:#e50914;">Regista</th>
                        <th style="padding:12px; text-align:left; color:#e50914;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($films as $film): ?>
                    <tr style="border-bottom:1px solid #333;">
                        <td style="padding:12px;"><?php echo $film['titolo']; ?></td>
                        <td style="padding:12px;"><?php echo $film['regista']; ?></td>
                        <td style="padding:12px;">
                            <a href="?edit=<?php echo $film['id']; ?>" style="color:#e50914; margin-right:10px;">Modifica</a>
                            <a href="?delete=<?php echo $film['id']; ?>" onclick="return confirm('Cancellare il film?')" style="color:#e50914;">Cancella</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($filmEdit): ?>
            <div>
                <h3 style="color:#e50914; margin-bottom:20px;">Modifica Film</h3>
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $filmEdit['id']; ?>">
                    
                    <div class="form-group">
                        <label>Titolo</label>
                        <input type="text" name="titolo" required value="<?php echo $filmEdit['titolo']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Regista</label>
                        <input type="text" name="regista" required value="<?php echo $filmEdit['regista']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Trama</label>
                        <textarea name="trama" style="width:100%; padding:10px; border:1px solid #333; border-radius:5px; background-color:#2a2a2a; color:#fff; min-height:100px;"><?php echo $filmEdit['trama']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>URL Locandina</label>
                        <input type="text" name="locandina" value="<?php echo $filmEdit['locandina']; ?>">
                    </div>

                    <div class="form-group">
                        <label>URL Trailer</label>
                        <input type="text" name="trailer" value="<?php echo $filmEdit['trailer']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Piattaforme</label>
                        <input type="text" name="piattaforme" value="<?php echo $filmEdit['piattaforme']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Cast</label>
                        <input type="text" name="cast" value="<?php echo $filmEdit['cast']; ?>">
                    </div>

                    <button type="submit" name="update" class="btn-login">Salva modifiche</button>
                    <a href="GestisciFilm.php" style="display: inline-block; margin-top: 10px;">Annulla</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>2026 KlipCheck - Tutti i diritti riservati</p>
</footer>

</body>
</html>