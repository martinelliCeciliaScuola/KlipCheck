<?php

session_start();

// --- CONNESSIONE DB ---
$host    = 'localhost';
$db      = 'klipcheckdb';
$user    = 'root';
$pass    = 'mysql';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}

// --- ID FILM ---
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// --- DATI FILM + VOTO MEDIO ---
$stmt = $pdo->prepare("
    SELECT f.*, AVG(CAST(v.valore AS DECIMAL(4,1))) AS voto_medio
    FROM film f
    LEFT JOIN valutazione v ON v.film_id = f.id
    WHERE f.id = :id
    GROUP BY f.id
");
$stmt->execute([':id' => $id]);
$film = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$film) {
    header('Location: index.php');
    exit;
}

// --- RECENSIONI CON UTENTE E LIKE ---
$stmtRec = $pdo->prepare("
    SELECT r.id, r.testo, u.username,
           COUNT(m.id) AS num_like
    FROM recensione r
    JOIN utente u ON u.id = r.utente_id
    LEFT JOIN mipiace m ON m.recensione_id = r.id
    WHERE r.film_id = :id
    GROUP BY r.id
    ORDER BY num_like DESC, r.id DESC
");
$stmtRec->execute([':id' => $id]);
$recensioni = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

// --- LOCANDINA ---
$locandina = $film['locandina'];
if ($locandina && !str_starts_with($locandina, 'http')) {
    $locandina = 'img/' . $locandina;
    if (!file_exists($locandina)) {
        $locandina = 'https://via.placeholder.com/300x450?text=' . urlencode($film['titolo']);
    }
} elseif (!$locandina) {
    $locandina = 'https://via.placeholder.com/300x450?text=' . urlencode($film['titolo']);
}

$voto = $film['voto_medio'] !== null
    ? number_format($film['voto_medio'], 1)
    : 'Nessun voto';

// --- VOTO UTENTE CORRENTE ---
$mioVoto = null;
if (isset($_SESSION['user_id']) && in_array($_SESSION['grado'] ?? '', ['registrato', 'admin'])) {
    $stmtMioVoto = $pdo->prepare("SELECT valore FROM valutazione WHERE utente_id = :uid AND film_id = :fid");
    $stmtMioVoto->execute([':uid' => $_SESSION['user_id'], ':fid' => $id]);
    $mioVoto = $stmtMioVoto->fetchColumn();
}

// --- AZIONE: SALVA / AGGIORNA VALUTAZIONE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valore'])) {
    $grado = $_SESSION['grado'] ?? '';
    if (isset($_SESSION['user_id']) && in_array($grado, ['registrato', 'admin'])) {
        $val = (float) $_POST['valore'];
        // Valida che sia un valore ammesso (1, 1.5, 2, ..., 10)
        $valoriAmmessi = [];
        for ($v = 1.0; $v <= 10.0; $v += 0.5) $valoriAmmessi[] = $v;

        if (in_array($val, $valoriAmmessi)) {
            if ($mioVoto !== false && $mioVoto !== null) {
                // Aggiorna voto esistente
                $upd = $pdo->prepare("UPDATE valutazione SET valore = :val WHERE utente_id = :uid AND film_id = :fid");
                $upd->execute([':val' => $val, ':uid' => $_SESSION['user_id'], ':fid' => $id]);
            } else {
                // Inserisci nuovo voto
                $ins = $pdo->prepare("INSERT INTO valutazione (valore, utente_id, film_id) VALUES (:val, :uid, :fid)");
                $ins->execute([':val' => $val, ':uid' => $_SESSION['user_id'], ':fid' => $id]);
            }
            $mioVoto = $val;
            // Ricarica voto medio
            $stmtRicarica = $pdo->prepare("SELECT AVG(CAST(valore AS DECIMAL(4,1))) FROM valutazione WHERE film_id = :fid");
            $stmtRicarica->execute([':fid' => $id]);
            $film['voto_medio'] = $stmtRicarica->fetchColumn();
            $voto = $film['voto_medio'] !== null ? number_format($film['voto_medio'], 1) : 'Nessun voto';
        }
    }
    header("Location: film.php?id=$id");
    exit;
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($film['titolo']) ?> — KlipCheck</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>

    <!-- HEADER -->
    <header>
        <h1>KlipCheck</h1>
        <nav>
            <form method="GET" action="./index.php">
                <input
                    type="text"
                    name="q"
                    placeholder="Search.."
                >
                <button type="submit">🔍</button>
            </form>
            <a href="./index.php">Home</a>
            <?php if (isset($_SESSION['user_id'])): ?>
            
            <?php if ($_SESSION['grado'] === 'registrato'): ?>
                <a href="areaRiservata.php">Area Riservata</a>
            <?php endif; ?>

            <?php if ($_SESSION['grado'] === 'admin'): ?>
                <a href="areaRiservataAdmin.php">Area Admin</a>
            <?php endif; ?>

            <?php else: ?>
                <a href="./login/Accesso.php">Accedi</a>
                <a href="./login/Registrazione.php">Registrati</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">

        <!-- DETTAGLIO FILM -->
        <div class="film-detail">
            <img src="<?= htmlspecialchars($locandina) ?>" alt="<?= htmlspecialchars($film['titolo']) ?>">

            <div class="film-meta">
                <h1><?= htmlspecialchars($film['titolo']) ?></h1>

                <div class="rating">⭐ <?= $voto ?> / 10</div>

                <?php if ($film['regista']): ?>
                    <div class="meta-row">
                        <strong>Regista:</strong> <?= htmlspecialchars($film['regista']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($film['cast']): ?>
                    <div class="meta-row">
                        <strong>Cast:</strong> <?= htmlspecialchars($film['cast']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($film['piattaforme']): ?>
                    <div class="meta-row">
                        <strong>Disponibile su:</strong> <?= htmlspecialchars($film['piattaforme']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($film['trama']): ?>
                    <p class="trama"><?= nl2br(htmlspecialchars($film['trama'])) ?></p>
                <?php endif; ?>

                <?php if ($film['trailer']): ?>
                    <a href="<?= htmlspecialchars($film['trailer']) ?>" target="_blank" class="trailer-btn">
                        ▶ Guarda il Trailer
                    </a>
                <?php endif; ?>
                <?php
                    $grado = $_SESSION['grado'] ?? '';
                    if (isset($_SESSION['user_id']) && in_array($grado, ['registrato', 'admin'])):
                    ?>
                    <div class="valutazione-box">
                        <h3>La tua valutazione</h3>
                        <form method="post" action="film.php?id=<?= $id ?>">
                            <select name="valore" required>
                                <option value="">-- Scegli un voto --</option>
                                <?php for ($v = 1.0; $v <= 10.0; $v += 0.5): ?>
                                    <option value="<?= $v ?>" <?= ($mioVoto == $v) ? 'selected' : '' ?>>
                                        <?= number_format($v, 1) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" class="trailer-btn">
                                <?= $mioVoto ? '✏️ Aggiorna voto' : '⭐ Vota' ?>
                            </button>
                        </form>
                        <?php if ($mioVoto): ?>
                            <p class="voto-attuale">Il tuo voto attuale: <strong><?= number_format($mioVoto, 1) ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <p class="info-box"><a href="login/Accesso.php">Accedi</a> per votare questo film.</p>
                    <?php else: ?>
                        <p class="info-box">Solo utenti registrati possono votare.</p>
                    <?php endif; ?>
            </div>
        </div>

        <!-- RECENSIONI -->
        <h2 class="section-title">Recensioni (<?= count($recensioni) ?>)</h2>
<a href="recensione.php?film_id=<?= $film['id'] ?>" class="trailer-btn" style="margin-left:10px;">
    💬 Leggi/Scrivi recensioni
</a>
    <!-- FOOTER -->
    <footer>
        <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
    </footer>

</body>
</html>