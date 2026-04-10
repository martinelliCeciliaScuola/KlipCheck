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
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connessione fallita: " . $e->getMessage());
}

// --- RICERCA ---
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// --- QUERY ---
if ($search !== '') {

    $stmt = $pdo->prepare("
        SELECT 
            f.*,
            vm.voto_medio
        FROM film f

        LEFT JOIN (
            SELECT film_id, AVG(CAST(valore AS DECIMAL(4,1))) AS voto_medio
            FROM valutazione
            GROUP BY film_id
        ) vm ON vm.film_id = f.id

        WHERE 
            f.titolo LIKE :q
            OR f.regista LIKE :q
            OR f.`cast` LIKE :q
            OR f.piattaforme LIKE :q

        ORDER BY f.titolo
    ");

    $stmt->execute([
        ':q' => "%$search%"
    ]);

} else {

    $stmt = $pdo->query("
        SELECT 
            f.*,
            vm.voto_medio
        FROM film f

        LEFT JOIN (
            SELECT film_id, AVG(CAST(valore AS DECIMAL(4,1))) AS voto_medio
            FROM valutazione
            GROUP BY film_id
        ) vm ON vm.film_id = f.id

        ORDER BY f.titolo
    ");
}

$films = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>KlipCheck</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>KlipCheck</h1>

    <nav>
        <!-- 🔍 BARRA DI RICERCA -->
        <form method="GET" action="">
            <input
                type="text"
                name="q"
                placeholder="Cerca film, regista, attori..."
                value="<?= htmlspecialchars($search) ?>"
            >
            <button type="submit">🔍</button>
        </form>

        <!-- LOGIN / AREA RISERVATA -->
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

    <!-- TESTO RISULTATI -->
    <?php if ($search !== ''): ?>
        <p style="margin-bottom: 15px; color: #ccc;">
            Risultati per:
            <strong style="color:#e50914">
                <?= htmlspecialchars($search) ?>
            </strong>
            — <a href="index.php" style="color:#e50914">Mostra tutti</a>
        </p>
    <?php endif; ?>

    <!-- LISTA FILM -->
    <div class="movie-list">

        <?php if (empty($films)): ?>
            <p style="color:#ccc;">Nessun film trovato.</p>
        <?php else: ?>

            <?php foreach ($films as $film): ?>

                <?php
                // --- LOCANDINA ---
                $locandina = $film['locandina'];

                if ($locandina && !str_starts_with($locandina, 'http')) {
                    $locandina = 'img/' . $locandina;

                    if (!file_exists($locandina)) {
                        $locandina = 'https://via.placeholder.com/250x350?text=' . urlencode($film['titolo']);
                    }
                } elseif (!$locandina) {
                    $locandina = 'https://via.placeholder.com/250x350?text=' . urlencode($film['titolo']);
                }

                // --- VOTO MEDIO ---
                $voto = $film['voto_medio'] !== null
                    ? number_format($film['voto_medio'], 1)
                    : 'N/D';

                // --- PIATTAFORME ---
                $piattaforme = $film['piattaforme'] ?? '';
                ?>

                <div class="movie-card">
                    <img src="<?= htmlspecialchars($locandina) ?>" alt="<?= htmlspecialchars($film['titolo']) ?>">

                    <div class="movie-info">
                        <h2><?= htmlspecialchars($film['titolo']) ?></h2>

                        <p><?= htmlspecialchars($film['regista']) ?></p>

                        <?php if (!empty($film['cast'])): ?>
                            <p style="font-size:12px; color:#aaa;">
                                <?= htmlspecialchars($film['cast']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($piattaforme): ?>
                            <p style="font-size:12px; color:#888;">
                                <?= htmlspecialchars($piattaforme) ?>
                            </p>
                        <?php endif; ?>

                        <div class="rating">⭐ <?= $voto ?></div>

                        <a href="film.php?id=<?= $film['id'] ?>">
                            <button>Dettagli</button>
                        </a>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<footer>
    <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
</footer>

</body>
</html>
