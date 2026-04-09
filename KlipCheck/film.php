<?php
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
            <form method="GET" action="../index.php">
                <input
                    type="text"
                    name="q"
                    placeholder="Search.."
                >
                <button type="submit">🔍</button>
            </form>
            <a href="./index.php">Home</a>
            <a href="../login/Accesso.php">Accedi</a>
            <a href="../login/Registrazione.php">Registrati</a>
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