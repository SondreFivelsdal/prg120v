<?php
// student.php – CRUD for tabellen "student"
// Funksjoner: Registrer, vis og slett (sletting via POST)
// Bruker prepared statements + vennlige feilmeldinger.

require_once 'db_connection.php';

$message = "";

/* =========================
   1) Hent klasser til nedtrekk
   ========================= */
$klasser = [];
$r = $conn->query("SELECT klassekode, klassenavn FROM klasse ORDER BY klassekode");
if ($r) {
    while ($row = $r->fetch_assoc()) { $klasser[] = $row; }
    $r->close();
}

/* =========================
   2) Registrer ny student
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrer'])) {
    $brukernavn = trim($_POST['brukernavn'] ?? '');
    $fornavn    = trim($_POST['fornavn'] ?? '');
    $etternavn  = trim($_POST['etternavn'] ?? '');
    $klassekode = trim($_POST['klassekode'] ?? '');

    if ($brukernavn !== '' && $fornavn !== '' && $etternavn !== '' && $klassekode !== '') {
        // sjekk at klassen finnes (unngå FK-feil)
        $chkK = $conn->prepare("SELECT 1 FROM klasse WHERE klassekode = ?");
        $chkK->bind_param("s", $klassekode);
        $chkK->execute();
        $chkK->store_result();
        $klasseFinnes = $chkK->num_rows > 0;
        $chkK->close();

        if (!$klasseFinnes) {
            $message = "⚠️ Klassen «" . htmlspecialchars($klassekode) . "» finnes ikke.";
        } else {
            // sjekk duplikat brukernavn
            $chk = $conn->prepare("SELECT 1 FROM student WHERE brukernavn = ?");
            $chk->bind_param("s", $brukernavn);
            $chk->execute();
            $chk->store_result();

            if ($chk->num_rows > 0) {
                $message = "⚠️ Brukernavn «" . htmlspecialchars($brukernavn) . "» finnes allerede.";
            } else {
                $stmt = $conn->prepare("INSERT INTO student (brukernavn, fornavn, etternavn, klassekode) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $brukernavn, $fornavn, $etternavn, $klassekode);
                if ($stmt->execute()) {
                    $message = "✅ Student «" . htmlspecialchars($brukernavn) . "» ble registrert.";
                    $_POST = []; // tøm felter
                } else {
                    if ($stmt->errno == 1062) {
                        $message = "⚠️ Brukernavn «" . htmlspecialchars($brukernavn) . "» finnes allerede.";
                    } else {
                        $message = "⚠️ Feil ved registrering: (" . $stmt->errno . ") " . htmlspecialchars($stmt->error);
                    }
                }
                $stmt->close();
            }
            $chk->close();
        }
    } else {
        $message = "⚠️ Alle felter må fylles ut.";
    }
}

/* =========================
   3) Slett student (POST)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slett'])) {
    $bn = $_POST['slett']; // brukernavn

    $del = $conn->prepare("DELETE FROM student WHERE brukernavn = ?");
    $del->bind_param("s", $bn);

    if ($del->execute()) {
        if ($del->affected_rows > 0) {
            $message = "🗑️ Student «" . htmlspecialchars($bn) . "» ble slettet.";
        } else {
            $message = "⚠️ Fant ingen student med brukernavn «" . htmlspecialchars($bn) . "».";
        }
    } else {
        // Student har normalt ingen barn-tabeller, men vis feilmelding om noe annet feiler
        $message = "⚠️ Kunne ikke slette «" . htmlspecialchars($bn) . "»: (" . $del->errno . ") " . htmlspecialchars($del->error);
    }
    $del->close();
}

/* =========================
   4) Hent alle studenter (med klassenavn)
   ========================= */
$studenter = [];
$sql = "SELECT s.brukernavn, s.fornavn, s.etternavn, s.klassekode, k.klassenavn
        FROM student s
        JOIN klasse k ON k.klassekode = s.klassekode
        ORDER BY s.brukernavn";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) { $studenter[] = $row; }
    $res->close();
}

// For repopulering ved valideringsfeil
$valBn   = htmlspecialchars($_POST['brukernavn'] ?? '', ENT_QUOTES);
$valFn   = htmlspecialchars($_POST['fornavn'] ?? '', ENT_QUOTES);
$valEn   = htmlspecialchars($_POST['etternavn'] ?? '', ENT_QUOTES);
$valKl   = htmlspecialchars($_POST['klassekode'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <title>Studenter</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; color:#222; }
    a { text-decoration: none; color: #2563eb; }
    a:hover { text-decoration: underline; }
    h1 { font-size: 38px; margin-bottom: 8px; }
    h2 { margin-top: 28px; }
    .msg { margin: 12px 0 20px; }
    .ok { color: #047857; }
    .warn { color: #b91c1c; }
    label { display: block; margin: 10px 0 4px; font-weight: 600; }
    input[type="text"], select { padding: 8px 10px; width: 280px; border:1px solid #ccc; border-radius:6px; }
    button { margin-top: 12px; padding: 8px 14px; border:0; background:#1f2937; color:#fff; border-radius:6px; cursor:pointer; }
    button:hover { background:#111827; }
    table { border-collapse: collapse; margin-top: 18px; min-width: 800px; }
    th, td { border: 1px solid #e5e7eb; padding: 10px 12px; }
    th { background: #f5f5f5; text-align: left; }
    .danger { color:#dc2626; background:none; border:none; cursor:pointer; }
  </style>
</head>
<body>
  <h1>Studenter</h1>
  <p><a href="index.php">← Tilbake til meny</a></p>

  <?php if ($message): ?>
    <div class="msg <?= str_starts_with(strip_tags($message), '✅') ? 'ok' : 'warn' ?>">
      <?= $message ?>
    </div>
  <?php endif; ?>

  <h2>Registrer ny student</h2>
  <form method="post" action="student.php">
    <label for="brukernavn">Brukernavn:</label>
    <input type="text" id="brukernavn" name="brukernavn" maxlength="7" value="<?= $valBn ?>" required />

    <label for="fornavn">Fornavn:</label>
    <input type="text" id="fornavn" name="fornavn" maxlength="50" value="<?= $valFn ?>" required />

    <label for="etternavn">Etternavn:</label>
    <input type="text" id="etternavn" name="etternavn" maxlength="50" value="<?= $valEn ?>" required />

    <label for="klassekode">Klasse:</label>
    <select id="klassekode" name="klassekode" required>
      <option value="">Velg klasse</option>
      <?php foreach ($klasser as $k): ?>
        <option value="<?= htmlspecialchars($k['klassekode']) ?>" <?= $valKl === $k['klassekode'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($k['klassekode'] . ' — ' . $k['klassenavn']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <br/>
    <button type="submit" name="registrer">Registrer</button>
  </form>

  <h2>Alle studenter</h2>
  <table>
    <tr>
      <th>Brukernavn</th><th>Fornavn</th><th>Etternavn</th>
      <th>Klassekode</th><th>Klassenavn</th><th>Handling</th>
    </tr>
    <?php if (empty($studenter)): ?>
      <tr><td colspan="6">Ingen studenter registrert ennå.</td></tr>
    <?php else: ?>
      <?php foreach ($studenter as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['brukernavn']) ?></td>
          <td><?= htmlspecialchars($s['fornavn']) ?></td>
          <td><?= htmlspecialchars($s['etternavn']) ?></td>
          <td><?= htmlspecialchars($s['klassekode']) ?></td>
          <td><?= htmlspecialchars($s['klassenavn']) ?></td>
          <td>
            <!-- Slettes via POST for robusthet -->
            <form method="post" action="student.php"
                  onsubmit="return confirm('Slette student «<?= htmlspecialchars($s['brukernavn']) ?>»?');"
                  style="display:inline">
              <input type="hidden" name="slett" value="<?= htmlspecialchars($s['brukernavn']) ?>">
              <button type="submit" class="danger">Slett</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</body>
</html>
