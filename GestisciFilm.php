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
    <title>Gestisci Film - KlipCheck</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #ffffff;
            margin: 0;
            padding-bottom: 60px;
        }

        header {
            background-color: #000;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            color: #e50914;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        nav a {
            color: white;
            text-decoration: none;
        }

        nav a:hover {
            color: #e50914;
        }

        .container {
            width: 90%;
            margin: 20px auto;
        }

        .login-box {
            max-width: 800px;
            margin: 50px auto;
            background-color: #1e1e1e;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .login-title {
            text-align: center;
            margin-bottom: 25px;
            color: #e50914;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ffffff;
            font-weight: bold;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #2a2a2a;
            color: #ffffff;
            font-size: 16px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #e50914;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: #b20710;
        }

        .success-message {
            color: #4caf50;
            background-color: rgba(76, 175, 80, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 3px solid #4caf50;
        }

        .error-message {
            color: #e50914;
            background-color: rgba(229, 9, 20, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 3px solid #e50914;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        th {
            background-color: #333;
            color: #e50914;
        }

        td a {
            color: #e50914;
            text-decoration: none;
            margin-right: 10px;
        }

        td a:hover {
            text-decoration: underline;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #111;
            color: white;
            text-align: center;
            padding: 10px;
        }

        a {
            color: #e50914;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        h3 {
            color: #e50914;
            margin-bottom: 20px;
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
                        <label>Titolo</label>
                        <input type="text" name="titolo" required value="<?php echo $filmDaModificare['titolo']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Regista</label>
                        <input type="text" name="regista" required value="<?php echo $filmDaModificare['regista']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Trama</label>
                        <textarea name="trama" required><?php echo $filmDaModificare['trama']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>URL Locandina</label>
                        <input type="text" name="locandina" value="<?php echo $filmDaModificare['locandina']; ?>">
                    </div>

                    <div class="form-group">
                        <label>URL Trailer</label>
                        <input type="text" name="trailer" value="<?php echo $filmDaModificare['trailer']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Piattaforme</label>
                        <input type="text" name="piattaforme" value="<?php echo $filmDaModificare['piattaforme']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Cast</label>
                        <input type="text" name="cast" value="<?php echo $filmDaModificare['cast']; ?>">
                    </div>

                    <button type="submit" name="update" class="btn-login">Salva</button>
                    <a href="GestisciFilm.php" style="display: inline-block; margin-top: 10px;">Annulla</a>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="index.php">Torna alla Home</a>
        </div>
    </div>
</div>

<footer>
    <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
</footer>

</body>
</html>