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
 
// --- ID FILM DA URL ---
$film_id = isset($_GET['film_id']) ? (int)$_GET['film_id'] : 0;
if ($film_id <= 0) {
    header('Location: index.php');
    exit;
}
 
// --- DATI FILM ---
$stmtFilm = $pdo->prepare("SELECT id, titolo, regista FROM film WHERE id = :id");
$stmtFilm->execute([':id' => $film_id]);
$film = $stmtFilm->fetch(PDO::FETCH_ASSOC);
 
if (!$film) {
    header('Location: index.php');
    exit;
}
 
$loggedIn = isset($_SESSION['user_id']);
$userId   = $loggedIn ? (int)$_SESSION['user_id'] : null;
 
$success = "";
$error   = "";
 
// --- AZIONE: AGGIUNGI RECENSIONE ---
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione']) && $_POST['azione'] === 'recensisci') {
    $testo = trim($_POST['testo'] ?? '');
 
    if (empty($testo)) {
        $error = "Il testo della recensione non può essere vuoto.";
    } elseif (mb_strlen($testo) > 1020) {
        $error = "La recensione non può superare 1020 caratteri.";
    } else {
        $chk = $pdo->prepare("SELECT id FROM recensione WHERE utente_id = :uid AND film_id = :fid");
        $chk->execute([':uid' => $userId, ':fid' => $film_id]);
 
        if ($chk->fetch()) {
            $error = "Hai già inserito una recensione per questo film.";
        } else {
            $ins = $pdo->prepare("INSERT INTO recensione (testo, utente_id, film_id) VALUES (:testo, :uid, :fid)");
            $ins->execute([':testo' => $testo, ':uid' => $userId, ':fid' => $film_id]);
            $success = "Recensione aggiunta con successo!";
        }
    }
}
 
// --- AZIONE: LIKE / RIMUOVI LIKE ---
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione']) && $_POST['azione'] === 'like') {
    $rec_id = (int)($_POST['recensione_id'] ?? 0);
 
    if ($rec_id > 0) {
        $chkRec = $pdo->prepare("SELECT utente_id FROM recensione WHERE id = :rid AND film_id = :fid");
        $chkRec->execute([':rid' => $rec_id, ':fid' => $film_id]);
        $recRow = $chkRec->fetch(PDO::FETCH_ASSOC);
 
        if ($recRow && (int)$recRow['utente_id'] !== $userId) {
            $chkLike = $pdo->prepare("SELECT id FROM mipiace WHERE utente_id = :uid AND recensione_id = :rid");
            $chkLike->execute([':uid' => $userId, ':rid' => $rec_id]);
 
            if ($chkLike->fetch()) {
                $del = $pdo->prepare("DELETE FROM mipiace WHERE utente_id = :uid AND recensione_id = :rid");
                $del->execute([':uid' => $userId, ':rid' => $rec_id]);
            } else {
                $addLike = $pdo->prepare("INSERT INTO mipiace (utente_id, recensione_id) VALUES (:uid, :rid)");
                $addLike->execute([':uid' => $userId, ':rid' => $rec_id]);
            }
        }
    }
 
    header("Location: recensione.php?film_id=$film_id");
    exit;
}
 
// --- RECENSIONI CON LIKE ---
$stmtRec = $pdo->prepare("
    SELECT r.id,
           r.testo,
           r.utente_id,
           u.username,
           COUNT(m.id) AS num_like
    FROM recensione r
    JOIN utente u ON u.id = r.utente_id
    LEFT JOIN mipiace m ON m.recensione_id = r.id
    WHERE r.film_id = :fid
    GROUP BY r.id, r.testo, r.utente_id, u.username
    ORDER BY num_like DESC, r.id DESC
");
$stmtRec->execute([':fid' => $film_id]);
$recensioni = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
 
// --- LIKE MESSI DALL'UTENTE CORRENTE ---
$miLike = [];
if ($loggedIn) {
    $stmtMiLike = $pdo->prepare("
        SELECT m.recensione_id
        FROM mipiace m
        JOIN recensione r ON r.id = m.recensione_id
        WHERE m.utente_id = :uid AND r.film_id = :fid
    ");
    $stmtMiLike->execute([':uid' => $userId, ':fid' => $film_id]);
    $miLike = array_column($stmtMiLike->fetchAll(PDO::FETCH_ASSOC), 'recensione_id');
}
 
// --- L'UTENTE HA GIÀ RECENSITO? ---
$haRecensito = false;
if ($loggedIn) {
    $chkMia = $pdo->prepare("SELECT id FROM recensione WHERE utente_id = :uid AND film_id = :fid");
    $chkMia->execute([':uid' => $userId, ':fid' => $film_id]);
    $haRecensito = (bool)$chkMia->fetch();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recensioni – <?= htmlspecialchars($film['titolo']) ?> — KlipCheck</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
 
<header>
    <h1>KlipCheck</h1>
    <nav>
        <a href="index.php">Home</a>
        <?php if ($loggedIn): ?>
            <?php if (isset($_SESSION['grado']) && $_SESSION['grado'] === 'admin'): ?>
                <a href="areaRiservataAdmin.php">Area Admin</a>
            <?php else: ?>
                <a href="areaRiservata.php">Area Riservata</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login/Accesso.php">Accedi</a>
            <a href="login/Registrazione.php">Registrati</a>
        <?php endif; ?>
    </nav>
</header>
 
<div class="container">
 
    <div class="film-breadcrumb">
        <a href="index.php">Home</a> &rsaquo;
        <a href="film.php?id=<?= $film_id ?>"><?= htmlspecialchars($film['titolo']) ?></a> &rsaquo;
        Recensioni
    </div>
 
    <h2 class="section-title">
        Recensioni — <?= htmlspecialchars($film['titolo']) ?>
        <?php if ($film['regista']): ?>
            <span class="section-title-sub">di <?= htmlspecialchars($film['regista']) ?></span>
        <?php endif; ?>
    </h2>
 
    <?php if (!empty($success)): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
 
    <?php if ($loggedIn && !$haRecensito): ?>
        <div class="review-form-box">
            <h3>Scrivi la tua recensione</h3>
            <form method="post" action="recensione.php?film_id=<?= $film_id ?>" class="review-form">
                <input type="hidden" name="azione" value="recensisci">
                <textarea
                    name="testo"
                    id="testoRecensione"
                    placeholder="Condividi la tua opinione su questo film..."
                    maxlength="1020"
                    required
                ></textarea>
                <div class="char-counter" id="charCounter">0 / 1020</div>
                <button type="submit" class="btn-login">Pubblica recensione</button>
            </form>
        </div>
 
    <?php elseif ($loggedIn && $haRecensito): ?>
        <div class="info-box">
            ✅ Hai già recensito questo film. Puoi mettere like alle recensioni degli altri utenti.
        </div>
 
    <?php else: ?>
        <div class="info-box">
            <a href="login/Accesso.php">Accedi</a> o
            <a href="login/Registrazione.php">registrati</a>
            per scrivere una recensione e mettere like.
        </div>
    <?php endif; ?>
 
    <p class="review-count">
        <?= count($recensioni) ?> recension<?= count($recensioni) === 1 ? 'e' : 'i' ?>
    </p>
 
    <?php if (empty($recensioni)): ?>
        <p class="no-reviews">Nessuna recensione ancora. Sii il primo a lasciarne una!</p>
    <?php else: ?>
        <?php foreach ($recensioni as $rec): ?>
            <?php
                $isMyRec     = $loggedIn && (int)$rec['utente_id'] === $userId;
                $hoMessoLike = $loggedIn && in_array($rec['id'], $miLike);
            ?>
            <div class="review">
                <div class="review-header">
                    <div>
                        <strong><?= htmlspecialchars($rec['username']) ?></strong>
                        <?php if ($isMyRec): ?>
                            <span class="my-review-badge">La tua</span>
                        <?php endif; ?>
                    </div>
                    <span class="review-likes">👍 <?= (int)$rec['num_like'] ?> like</span>
                </div>
 
                <p><?= nl2br(htmlspecialchars($rec['testo'])) ?></p>
 
                <div class="review-footer">
                    <?php if ($loggedIn && !$isMyRec): ?>
                        <form method="post" action="recensione.php?film_id=<?= $film_id ?>">
                            <input type="hidden" name="azione" value="like">
                            <input type="hidden" name="recensione_id" value="<?= (int)$rec['id'] ?>">
                            <button
                                type="submit"
                                class="btn-like <?= $hoMessoLike ? 'liked' : '' ?>"
                                title="<?= $hoMessoLike ? 'Rimuovi like' : 'Metti like' ?>"
                            >
                                <?= $hoMessoLike ? '👍 Liked' : '👍 Like' ?>
                            </button>
                        </form>
                    <?php elseif (!$loggedIn): ?>
                        <button class="btn-like" disabled title="Accedi per mettere like">👍 Like</button>
                    <?php else: ?>
                        <span class="own-like-note">Non puoi mettere like alla tua recensione</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
 
    <div class="back-wrapper">
        <a href="film.php?id=<?= $film_id ?>" class="back-link">← Torna alla scheda del film</a>
    </div>
 
</div>
 
<footer>
    <p>© 2026 KlipCheck - Tutti i diritti riservati</p>
</footer>
 
<script>
    const textarea = document.getElementById('testoRecensione');
    const counter  = document.getElementById('charCounter');
    if (textarea && counter) {
        textarea.addEventListener('input', function () {
            const len = this.value.length;
            counter.textContent = len + ' / 1020';
            counter.classList.toggle('over', len >= 1020);
        });
    }
</script>
 
</body>
</html>
 