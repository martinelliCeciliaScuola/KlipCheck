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
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Layout dettaglio film */
        .film-detail {
            display: flex;
            gap: 40px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .film-detail img {
            width: 300px;
            height: 450px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .film-meta {
            flex: 1;
            min-width: 250px;
        }

        .film-meta h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #fff;
        }

        .film-meta .rating {
            font-size: 22px;
            margin-bottom: 15px;
        }

        .film-meta .meta-row {
            margin-bottom: 10px;
            color: #ccc;
            font-size: 15px;
        }

        .film-meta .meta-row strong {
            color: #fff;
        }

        .trama {
            margin-top: 20px;
            line-height: 1.7;
            color: #ddd;
        }

        .trailer-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #e50914;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s;
        }

        .trailer-btn:hover {
            background-color: #b20710;
        }

        /* Sezione recensioni */
        .section-title {
            font-size: 22px;
            color: #e50914;
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 8px;
        }

        .review {
            background-color: #1e1e1e;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .review-header strong {
            color: #e50914;
            font-size: 15px;
        }

        .review-likes {
            color: #aaa;
            font-size: 13px;
        }

        .review p {
            color: #ddd;
            line-height: 1.6;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #e50914;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .no-reviews {
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header>
        <h1>KlipCheck</h1>
        <nav>
            <input type="text" placeholder="Search.." onkeydown="if(event.key==='Enter') window.location='../index.php?q='+this.value">
            <a href="../login/Accesso.php">Accedi</a>
            <a href="../login/Registrazione.php">Registrati</a>
        </nav>
    </header>

    <div class="container">

        <a href="../index.php" class="back-link">← Torna alla home</a>

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

        <?php if (empty($recensioni)): ?>
            <p class="no-reviews">Nessuna recensione ancora. Sii il primo!</p>
        <?php else: ?>
            <?php foreach ($recensioni as $rec): ?>
                <div class="review">
                    <div class="review-header">
                        <strong><?= htmlspecialchars($rec['username']) ?></strong>
                        <span class="review-likes">👍 <?= $rec['num_like'] ?> like</span>
                    </div>
                    <p><?= nl2br(htmlspecialchars($rec['testo'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- FOOTER -->
    <footer>
        <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
    </footer>

</body>
</html>