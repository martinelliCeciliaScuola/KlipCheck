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
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['azione'] ?? '') === 'recensisci') {
    $testo = trim($_POST['testo'] ?? '');

    if (empty($testo)) {
        $error = "Il testo della recensione non può essere vuoto.";
    } elseif (mb_strlen($testo) > 1020) {
        $error = "La recensione non può superare 1020 caratteri.";
    } else {
        try {
            $ins = $pdo->prepare("
                INSERT IGNORE INTO recensione (testo, utente_id, film_id)
                VALUES (:testo, :uid, :fid)
            ");
            $ins->execute([':testo' => $testo, ':uid' => $userId, ':fid' => $film_id]);

            if ($ins->rowCount() > 0) {
                $success = "Recensione aggiunta con successo!";
            } else {
                $error = "Hai già inserito una recensione per questo film.";
            }
        } catch (PDOException $e) {
            $error = "Errore durante il salvataggio della recensione.";
        }
    }
}

// --- AZIONE: MODIFICA RECENSIONE ---
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['azione'] ?? '') === 'modifica') {
    $rec_id = (int)($_POST['recensione_id'] ?? 0);
    $testo  = trim($_POST['testo'] ?? '');

    if ($rec_id <= 0) {
        $error = "Recensione non valida.";
    } elseif (empty($testo)) {
        $error = "Il testo della recensione non può essere vuoto.";
    } elseif (mb_strlen($testo) > 1020) {
        $error = "La recensione non può superare 1020 caratteri.";
    } else {
        // Verifica che la recensione appartenga all'utente loggato e a questo film
        $chk = $pdo->prepare("SELECT id FROM recensione WHERE id = :rid AND utente_id = :uid AND film_id = :fid");
        $chk->execute([':rid' => $rec_id, ':uid' => $userId, ':fid' => $film_id]);

        if ($chk->fetch()) {
            try {
                $upd = $pdo->prepare("UPDATE recensione SET testo = :testo WHERE id = :rid");
                $upd->execute([':testo' => $testo, ':rid' => $rec_id]);
                $success = "Recensione modificata con successo!";
            } catch (PDOException $e) {
                $error = "Errore durante la modifica della recensione.";
            }
        } else {
            $error = "Non sei autorizzato a modificare questa recensione.";
        }
    }
    header("Location: Recensione.php?film_id=$film_id" . ($success ? "&ok=1" : ""));
    exit;
}

// --- AZIONE: ELIMINA RECENSIONE ---
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['azione'] ?? '') === 'elimina') {
    $rec_id = (int)($_POST['recensione_id'] ?? 0);

    if ($rec_id > 0) {
        // Verifica che la recensione appartenga all'utente loggato e a questo film
        $chk = $pdo->prepare("SELECT id FROM recensione WHERE id = :rid AND utente_id = :uid AND film_id = :fid");
        $chk->execute([':rid' => $rec_id, ':uid' => $userId, ':fid' => $film_id]);

        if ($chk->fetch()) {
            try {
                // I like vengono rimossi automaticamente dalla CASCADE nel DB
                $del = $pdo->prepare("DELETE FROM recensione WHERE id = :rid");
                $del->execute([':rid' => $rec_id]);
            } catch (PDOException $e) {
                // Errore silenzioso, redirect comunque
            }
        }
    }
    header("Location: Recensione.php?film_id=$film_id");
    exit;
}

// --- AZIONE: LIKE / RIMUOVI LIKE ---
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['azione'] ?? '') === 'like') {
    $rec_id = (int)($_POST['recensione_id'] ?? 0);

    if ($rec_id > 0) {
        $chkRec = $pdo->prepare("
            SELECT utente_id FROM recensione
            WHERE id = :rid AND film_id = :fid
        ");
        $chkRec->execute([':rid' => $rec_id, ':fid' => $film_id]);
        $recRow = $chkRec->fetch(PDO::FETCH_ASSOC);

        if ($recRow && (int)$recRow['utente_id'] !== $userId) {
            $count = $pdo->prepare("
                SELECT COUNT(*) FROM mipiace
                WHERE utente_id = :uid AND recensione_id = :rid
            ");
            $count->execute([':uid' => $userId, ':rid' => $rec_id]);
            $likeEsistente = (int)$count->fetchColumn() > 0;

            if ($likeEsistente) {
                $del = $pdo->prepare("DELETE FROM mipiace WHERE utente_id = :uid AND recensione_id = :rid");
                $del->execute([':uid' => $userId, ':rid' => $rec_id]);
            } else {
                $addLike = $pdo->prepare("INSERT IGNORE INTO mipiace (utente_id, recensione_id) VALUES (:uid, :rid)");
                $addLike->execute([':uid' => $userId, ':rid' => $rec_id]);
            }
        }
    }

    header("Location: Recensione.php?film_id=$film_id");
    exit;
}

// --- MESSAGGIO DI SUCCESSO ---
if (isset($_GET['ok'])) {
    $success = "Recensione modificata con successo!";
}

// --- RECENSIONI CON CONTEGGIO LIKE ---
$stmtRec = $pdo->prepare("
    SELECT r.id,
           r.testo,
           r.utente_id,
           u.username,
           COUNT(m.id) AS num_like, v.valore
    FROM recensione r
    JOIN utente u ON u.id = r.utente_id
    LEFT JOIN mipiace m ON m.recensione_id = r.id
    LEFT JOIN valutazione v ON v.utente_id = r.utente_id AND v.film_id = r.film_id
    WHERE r.film_id = :fid
    GROUP BY r.id, r.testo, r.utente_id, u.username, v.valore
    ORDER BY num_like DESC, r.id DESC
");
$stmtRec->execute([':fid' => $film_id]);
$recensioni = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

$soloRecensione = [];
$entrambi       = [];
foreach ($tutteRecensioni as $rec) {
    if ($rec['valore'] !== null) {
        $entrambi[] = $rec;
    } else {
        $soloRecensione[] = $rec;
    }
}
$recensioni = $tutteRecensioni;
 
// --- UTENTI CON SOLO VALUTAZIONE ---
$stmtSoloVoto = $pdo->prepare("
    SELECT v.id,
           v.valore,
           v.utente_id,
           u.username
    FROM valutazione v
    JOIN utente u ON u.id = v.utente_id
    LEFT JOIN recensione r ON r.utente_id = v.utente_id AND r.film_id = v.film_id
    WHERE v.film_id = :fid
      AND r.id IS NULL
    ORDER BY v.valore DESC, v.id DESC
");
$stmtSoloVoto->execute([':fid' => $film_id]);
$soloValutazione = $stmtSoloVoto->fetchAll(PDO::FETCH_ASSOC);
 

// --- ID RECENSIONI A CUI L'UTENTE HA MESSO LIKE ---
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

// --- L'UTENTE HA GIÀ RECENSITO QUESTO FILM? ---
$haRecensito    = false;
$miaRecensione  = null;
if ($loggedIn) {
    $chkMia = $pdo->prepare("SELECT id, testo FROM recensione WHERE utente_id = :uid AND film_id = :fid");
    $chkMia->execute([':uid' => $userId, ':fid' => $film_id]);
    $miaRecensione = $chkMia->fetch(PDO::FETCH_ASSOC);
    $haRecensito   = (bool)$miaRecensione;
}

// --- RECENSIONE DA MODIFICARE ---
$recensioneInModifica = null;
$modificaId = isset($_GET['modifica']) ? (int)$_GET['modifica'] : 0;
if ($loggedIn && $modificaId > 0 && $miaRecensione && (int)$miaRecensione['id'] === $modificaId) {
    $recensioneInModifica = $miaRecensione;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recensioni – <?= htmlspecialchars($film['titolo']) ?> — KlipCheck</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Pulsanti azione recensione propria */
        .btn-action {
            background: none;
            border: 1px solid #555;
            color: #aaa;
            padding: 5px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            margin-top: 0;
            transition: border-color 0.2s, color 0.2s;
        }
        .btn-action:hover { border-color: #e50914; color: #fff; }
        .btn-action.btn-danger { border-color: #555; }
        .btn-action.btn-danger:hover { border-color: #e50914; color: #e50914; }
        .btn-action.btn-edit:hover { border-color: #4caf50; color: #4caf50; }

        /* Form modifica inline */
        .edit-form-box {
            background-color: #252525;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 20px 25px;
            margin-bottom: 30px;
        }
        .edit-form-box h3 { color: #4caf50; margin-bottom: 14px; font-size: 18px; }
        .edit-form-box textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #444;
            background-color: #1e1e1e;
            color: #fff;
            font-size: 15px;
            resize: vertical;
        }
        .edit-form-box textarea:focus { outline: none; border-color: #4caf50; }
        .btn-save {
            padding: 8px 20px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 10px;
        }
        .btn-save:hover { background-color: #388e3c; }
        .btn-cancel-edit {
            display: inline-block;
            margin-top: 10px;
            margin-left: 12px;
            color: #aaa;
            font-size: 14px;
            text-decoration: none;
        }
        .btn-cancel-edit:hover { color: #fff; }
        .review-actions-own { display: flex; gap: 8px; align-items: center; }

        
        /* Badge voto nella card */
        .review-rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background-color: #1a1a2e;
            border: 1px solid #e50914;
            color: #e50914;
            font-weight: bold;
            font-size: 13px;
            padding: 2px 10px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
        }
 
        /* Sezioni distinte */
        .section-group {
            margin-top: 36px;
        }
        .section-group-title {
            font-size: 16px;
            font-weight: bold;
            color: #aaa;
            border-bottom: 1px solid #333;
            padding-bottom: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-group-title .group-badge {
            display: inline-block;
            font-size: 12px;
            background: #2a2a2a;
            color: #777;
            border-radius: 12px;
            padding: 2px 10px;
        }
 
        /* Card solo voto (nessuna recensione) */
        .only-rating-card {
            background: #1e1e1e;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .only-rating-card .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ddd;
            font-size: 15px;
        }
        .no-interactions {
            color: #666;
            font-style: italic;
            margin-top: 8px;
        }
    </style>
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
        <?php else: ?>
            <a href="login/Accesso.php">Accedi</a>
            <a href="login/Registrazione.php">Registrati</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">

    <!-- BREADCRUMB -->
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

    <!-- MESSAGGI -->
    <?php if (!empty($success)): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- FORM NUOVA RECENSIONE (solo se loggato e non ha già recensito) -->
    <?php if ($loggedIn && !$haRecensito): ?>
        <div class="review-form-box">
            <h3>Scrivi la tua recensione</h3>
            <form method="post" action="Recensione.php?film_id=<?= $film_id ?>" class="review-form">
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

    <?php elseif ($loggedIn && $haRecensito && $recensioneInModifica): ?>
        <!-- FORM MODIFICA RECENSIONE -->
        <div class="edit-form-box">
            <h3>✏️ Modifica la tua recensione</h3>
            <form method="post" action="Recensione.php?film_id=<?= $film_id ?>" class="review-form">
                <input type="hidden" name="azione" value="modifica">
                <input type="hidden" name="recensione_id" value="<?= (int)$recensioneInModifica['id'] ?>">
                <textarea
                    name="testo"
                    id="testoModifica"
                    maxlength="1020"
                    required
                ><?= htmlspecialchars($recensioneInModifica['testo']) ?></textarea>
                <div class="char-counter" id="charCounterModifica">
                    <?= mb_strlen($recensioneInModifica['testo']) ?> / 1020
                </div>
                <button type="submit" class="btn-save">💾 Salva modifiche</button>
                <a href="Recensione.php?film_id=<?= $film_id ?>" class="btn-cancel-edit">Annulla</a>
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

<!-- CONTATORE TOTALE INTERAZIONI -->
<p class="review-count">
    <?= count($recensioni) ?> recension<?= count($recensioni) === 1 ? 'e' : 'i' ?>
    <?php if (count($soloValutazione) > 0): ?>
        · <?= count($soloValutazione) ?> solo vot<?= count($soloValutazione) === 1 ? 'o' : 'i' ?>
    <?php endif; ?>
</p>

<!-- UTENTI CON SOLO VALUTAZIONE -->
<?php if (!empty($soloValutazione)): ?>
    <div class="section-group">
        <div class="section-group-title">
            ⭐ Solo valutazione
            <span class="group-badge"><?= count($soloValutazione) ?></span>
        </div>
        <?php foreach ($soloValutazione as $sv): ?>
            <div class="only-rating-card">
                <div class="user-info">
                    <strong><?= htmlspecialchars($sv['username']) ?></strong>
                    <?php if ($loggedIn && (int)$sv['utente_id'] === $userId): ?>
                        <span class="my-review-badge">Tu</span>
                    <?php endif; ?>
                </div>
                <span class="review-rating-badge">⭐ <?= number_format((float)$sv['valore'], 1) ?> / 10</span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- UTENTI CON SOLO RECENSIONE -->
<?php if (!empty($soloRecensione)): ?>
    <div class="section-group">
        <div class="section-group-title">
            💬 Solo recensione
            <span class="group-badge"><?= count($soloRecensione) ?></span>
        </div>
        <?php foreach ($soloRecensione as $rec): ?>
            <?php
                $isMyRec     = $loggedIn && (int)$rec['utente_id'] === $userId;
                $hoMessoLike = $loggedIn && in_array((int)$rec['id'], array_map('intval', $miLike));
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
                    <?php if ($loggedIn && $isMyRec): ?>
                        <div class="review-actions-own">
                            <a href="Recensione.php?film_id=<?= $film_id ?>&modifica=<?= (int)$rec['id'] ?>"
                               class="btn-action btn-edit">✏️ Modifica</a>
                            <form method="post" action="Recensione.php?film_id=<?= $film_id ?>" class="like-form"
                                  onsubmit="return confirm('Sei sicuro di voler eliminare la tua recensione?')">
                                <input type="hidden" name="azione" value="elimina">
                                <input type="hidden" name="recensione_id" value="<?= (int)$rec['id'] ?>">
                                <button type="submit" class="btn-action btn-danger">🗑️ Elimina</button>
                            </form>
                            <span class="own-like-note">Non puoi mettere like alla tua recensione</span>
                        </div>
                    <?php elseif ($loggedIn && !$isMyRec): ?>
                        <form method="post" action="Recensione.php?film_id=<?= $film_id ?>" class="like-form">
                            <input type="hidden" name="azione" value="like">
                            <input type="hidden" name="recensione_id" value="<?= (int)$rec['id'] ?>">
                            <button type="submit" class="btn-like <?= $hoMessoLike ? 'liked' : '' ?>">
                                <?= $hoMessoLike ? '👍 Liked' : '👍 Like' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn-like" disabled>👍 Like</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- UTENTI CON VALUTAZIONE + RECENSIONE -->
<?php if (!empty($entrambi)): ?>
    <div class="section-group">
        <div class="section-group-title">
            ⭐💬 Valutazione e recensione
            <span class="group-badge"><?= count($entrambi) ?></span>
        </div>
        <?php foreach ($entrambi as $rec): ?>
            <?php
                $isMyRec     = $loggedIn && (int)$rec['utente_id'] === $userId;
                $hoMessoLike = $loggedIn && in_array((int)$rec['id'], array_map('intval', $miLike));
            ?>
            <div class="review">
                <div class="review-header">
                    <div>
                        <strong><?= htmlspecialchars($rec['username']) ?></strong>
                        <?php if ($isMyRec): ?>
                            <span class="my-review-badge">La tua</span>
                        <?php endif; ?>
                        <span class="review-rating-badge">⭐ <?= number_format((float)$rec['valore'], 1) ?> / 10</span>
                    </div>
                    <span class="review-likes">👍 <?= (int)$rec['num_like'] ?> like</span>
                </div>
                <p><?= nl2br(htmlspecialchars($rec['testo'])) ?></p>
                <div class="review-footer">
                    <?php if ($loggedIn && $isMyRec): ?>
                        <div class="review-actions-own">
                            <a href="Recensione.php?film_id=<?= $film_id ?>&modifica=<?= (int)$rec['id'] ?>"
                               class="btn-action btn-edit">✏️ Modifica</a>
                            <form method="post" action="Recensione.php?film_id=<?= $film_id ?>" class="like-form"
                                  onsubmit="return confirm('Sei sicuro di voler eliminare la tua recensione?')">
                                <input type="hidden" name="azione" value="elimina">
                                <input type="hidden" name="recensione_id" value="<?= (int)$rec['id'] ?>">
                                <button type="submit" class="btn-action btn-danger">🗑️ Elimina</button>
                            </form>
                            <span class="own-like-note">Non puoi mettere like alla tua recensione</span>
                        </div>
                    <?php elseif ($loggedIn && !$isMyRec): ?>
                        <form method="post" action="Recensione.php?film_id=<?= $film_id ?>" class="like-form">
                            <input type="hidden" name="azione" value="like">
                            <input type="hidden" name="recensione_id" value="<?= (int)$rec['id'] ?>">
                            <button type="submit" class="btn-like <?= $hoMessoLike ? 'liked' : '' ?>">
                                <?= $hoMessoLike ? '👍 Liked' : '👍 Like' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn-like" disabled>👍 Like</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Nessuna interazione -->
<?php if (empty($recensioni) && empty($soloValutazione)): ?>
    <p class="no-reviews">Nessuna recensione o valutazione ancora. Sii il primo!</p>
<?php endif; ?>
    <?php else: ?>
        <?php foreach ($recensioni as $rec): ?> 
            <?php
                $isMyRec     = $loggedIn && (int)$rec['utente_id'] === $userId;
                $hoMessoLike = $loggedIn && in_array((int)$rec['id'], array_map('intval', $miLike));
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
                    <?php if ($loggedIn && $isMyRec): ?>


                        <!-- Azioni sulla propria recensione: modifica ed elimina -->
                        <div class="review-actions-own">
                            <a href="Recensione.php?film_id=<?= $film_id ?>&modifica=<?= (int)$rec['id'] ?>"
                               class="btn-action btn-edit"
                               title="Modifica la tua recensione">
                                ✏️ Modifica
                            </a>

                            <form method="post"
                                  action="Recensione.php?film_id=<?= $film_id ?>"
                                  class="like-form"
                                  onsubmit="return confirm('Sei sicuro di voler eliminare la tua recensione?')">
                                <input type="hidden" name="azione" value="elimina">
                                <input type="hidden" name="recensione_id" value="<?= (int)$rec['id'] ?>">
                                <button type="submit" class="btn-action btn-danger" title="Elimina la tua recensione">
                                    🗑️ Elimina
                                </button>
                            </form>

                            <span class="own-like-note">Non puoi mettere like alla tua recensione</span>
                        </div>

                    <?php elseif ($loggedIn && !$isMyRec): ?>
                        <form method="post" action="Recensione.php?film_id=<?= $film_id ?>" class="like-form">
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

                    <?php else: ?>
                        <button class="btn-like" disabled title="Accedi per mettere like">👍 Like</button>
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
    // Contatore caratteri — form nuova recensione
    const textarea = document.getElementById('testoRecensione');
    const counter  = document.getElementById('charCounter');
    if (textarea && counter) {
        textarea.addEventListener('input', function () {
            const len = this.value.length;
            counter.textContent = len + ' / 1020';
            counter.classList.toggle('over', len >= 1020);
        });
    }

    // Contatore caratteri — form modifica recensione
    const textareaEdit  = document.getElementById('testoModifica');
    const counterEdit   = document.getElementById('charCounterModifica');
    if (textareaEdit && counterEdit) {
        textareaEdit.addEventListener('input', function () {
            const len = this.value.length;
            counterEdit.textContent = len + ' / 1020';
            counterEdit.classList.toggle('over', len >= 1020);
        });
    }
</script>

</body>
</html>