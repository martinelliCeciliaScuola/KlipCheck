<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: ./login/Accesso.php");
    exit;
}

$success = "";
$error = "";
$filmDaModificare = null;

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

$conn = connectToDb();

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM film WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $success = "Film cancellato con successo!";
    } catch (PDOException $e) {
        $error = "Errore durante la cancellazione";
    }
}

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM film WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $filmDaModificare = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$filmDaModificare) {
        $error = "Film non trovato!";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id = $_POST['id'];
    $titolo = trim($_POST['titolo']);
    $regista = trim($_POST['regista']);
    $trama = trim($_POST['trama']);
    $locandina = trim($_POST['locandina']);
    $trailer = trim($_POST['trailer']);
    $piattaforme = trim($_POST['piattaforme']);
    $cast = trim($_POST['cast']);
    
    if (empty($titolo) || empty($regista) || empty($trama)) {
        $error = "Titolo, regista e trama sono obbligatori!";
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE film 
                SET titolo = :titolo, 
                    regista = :regista, 
                    trama = :trama, 
                    locandina = :locandina, 
                    trailer = :trailer, 
                    piattaforme = :piattaforme, 
                    cast = :cast 
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $id,
                'titolo' => $titolo,
                'regista' => $regista,
                'trama' => $trama,
                'locandina' => $locandina,
                'trailer' => $trailer,
                'piattaforme' => $piattaforme,
                'cast' => $cast
            ]);
            $success = "Film aggiornato con successo!";
            $filmDaModificare = null;
        } catch (PDOException $e) {
            $error = "Errore durante l'aggiornamento";
        }
    }
}

$stmt = $conn->query("SELECT id, titolo, regista FROM film ORDER BY titolo");
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestisci Film k</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>KlipCheck</h1>
    <nav>
        <a href="index.php">Home</a>
       
        <a href="ModificaFilm.php">Gestisci Film</a>
      
    </nav>
</header>

<div class="container">
    <div class="login-box">
        <h2 class="login-title">Gestisci Film</h2>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($films)): ?>
            <p>Nessun film presente nel database.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Regista</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($films as $film): ?>
                        <tr>
                            <td><?php echo $film['titolo']; ?></td>
                            <td><?php echo $film['regista']; ?></td>
                            <td>
                                <a href="?edit=<?php echo $film['id']; ?>">Modifica</a>
                                <a href="?delete=<?php echo $film['id']; ?>" onclick="return confirm('Cancellare il film?')">Cancella</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($filmDaModificare != null): ?>
            <div>
                <h3>Modifica Film</h3>
                <form method="post" action="">
                    <input type="hidden" name="id" value="<?php echo $filmDaModificare['id']; ?>">
                    
                    <div class="form-group">
                        <label for="titolo">Titolo</label>
                        <input type="text" id="titolo" name="titolo" required
                               value="<?php echo $filmDaModificare['titolo']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="regista">Regista</label>
                        <input type="text" id="regista" name="regista" required
                               value="<?php echo $filmDaModificare['regista']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="trama">Trama</label>
                        <textarea id="trama" name="trama" required><?php echo $filmDaModificare['trama']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="locandina">URL Locandina</label>
                        <?php
                        $locandina = "";
                        if (isset($filmDaModificare['locandina'])) {
                            $locandina = $filmDaModificare['locandina'];
                        }
                        ?>
                        <input type="text" id="locandina" name="locandina"
                               value="<?php echo $locandina; ?>">
                    </div>

                    <div class="form-group">
                        <label for="trailer">URL Trailer</label>
                        <?php
                        $trailer = "";
                        if (isset($filmDaModificare['trailer'])) {
                            $trailer = $filmDaModificare['trailer'];
                        }
                        ?>
                        <input type="text" id="trailer" name="trailer"
                               value="<?php echo $trailer; ?>">
                    </div>

                    <div class="form-group">
                        <label for="piattaforme">Piattaforme</label>
                        <?php
                        $piattaforme = "";
                        if (isset($filmDaModificare['piattaforme'])) {
                            $piattaforme = $filmDaModificare['piattaforme'];
                        }
                        ?>
                        <input type="text" id="piattaforme" name="piattaforme"
                               value="<?php echo $piattaforme; ?>">
                    </div>

                    <div class="form-group">
                        <label for="cast">Cast</label>
                        <?php
                        $cast = "";
                        if (isset($filmDaModificare['cast'])) {
                            $cast = $filmDaModificare['cast'];
                        }
                        ?>
                        <input type="text" id="cast" name="cast"
                               value="<?php echo $cast; ?>">
                    </div>

                    <button type="submit" name="update" class="btn-login">Salva</button>
                    <a href="GestisciFilm.php">Annulla</a>
                </form>
            </div>
        <?php endif; ?>
        
        <div>
            <a href="index.php">Torna alla Home</a>
        </div>
    </div>
</div>

<footer>
    <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
</footer>

</body>
</html>