<?php
// klasse.php – CRUD for tabellen "klasse"
// Funksjoner: Registrer, vis og slett (sletting via POST for robusthet)

require_once 'db_connection.php';

$message = "";

// =========================
// 1) Registrering av klasse
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrer'])) {
    $kode    = trim($_POST['klassekode'] ?? '');
    $navn    = trim($_POST['klassenavn'] ?? '');
    $studium = trim($_POST['studiumkode'] ?? '');

    if ($kode !== '' && $navn !== '' && $studium !== '') {
        // Sjekk om klassekode finnes fra før
        $chk = $conn->prepare("SELECT 1 FROM klasse WHERE klassekode = ?");
        $chk->bind_param("s", $kode);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $message = "⚠️ Klassekode «" . htmlspecialchars($kode) . "» finnes allerede.";
        } else {
            $stmt = $conn->prepare("INSERT INTO klasse (klassekode, klassenavn, studiumkode) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $kode, $navn, $studium);
            if ($stmt->execute()) {
                $message = "✅ Klasse «" . htmlspecialchars($kode) . "» ble registrert.";
                $_POST = []; // tøm feltene
            } else {
                if ($stmt->errno == 1062) {
                    $message = "⚠️ Klassekode «" . htmlspecialchars($kode) . "» finnes allerede.";
                } else {
                    $message = "⚠️ Feil ved registrering: (" . $stmt->errno . ") " . htmlspecialchars($stmt->error);
                }
            }
            $stmt->close();
        }
        $chk->close();
    } else {
        $message = "⚠️ Alle felter må fylles ut.";
    }
}


// =========================
// 2) Sletting av klasse (POST)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slett'])) {
  $kode = $_POST['slett'];

  // a) Sjekk om det finnes studenter i denne klassen
  $cnt = $conn->prepare("SELECT COUNT(*) FROM student WHERE klassekode = ?");
  $cnt->bind_param("s", $kode);
  $cnt->execute();
  $cnt->bind_result($antStud);
  $cnt->fetch();
  $cnt->close();

  if ($antStud > 0) {
      // Vennlig beskjed – ikke prøv å slette
      $message = "⚠️ Kan ikke slette «" . htmlspecialchars($kode) . "». "
               . "Det er registrert {$antStud} student" . ($antStud == 1 ? "" : "er")
               . " i denne klassen.";
  } else {
      // b) Forsøk sletting
      $del = $conn->prepare("DELETE FROM klasse WHERE klassekode = ?");
      $del->bind_param("s", $kode);

      if ($del->execute()) {
          if ($del->affected_rows > 0) {
              $message = "🗑️ Klassen «" . htmlspecialchars($kode) . "» ble slettet.";
          } else {
              $message = "⚠️ Fant ingen klasse med kode «" . htmlspecialchars($kode) . "».";
          }
      } else {
          // c) Fang FK-feil (1451 = row is referenced)
          if ($del->errno == 1451) {
              $message = "⚠️ Kan ikke slette «" . htmlspecialchars($kode) . "» fordi studenter peker på den. "
                       . "Slett/flytt studentene først.";
          } else {
              $message = "⚠️ Kunne ikke slette «" . htmlspecialchars($kode) . "»: ("
                       . $del->errno . ") " . htmlspecialchars($del->error);
          }
      }
      $del->close();
  }
}


// =========================
// 3) Hent alle klasser
// =========================
$klasser = [];
$res = $conn->query("SELECT klassekode, klassenavn, studiumkode FROM klasse ORDER BY klassekode");
if ($res) {
    while ($row = $res->fetch_assoc()) { $klasser[] = $row; }
    $res->close();
}

// Re-populer felter ved valideringsfeil
$valKode    = htmlspecialchars($_POST['klassekode'] ?? '', ENT_QUOTES);
$valNavn    = htmlspecialchars($_POST['klassenavn'] ?? '', ENT_QUOTES);
$valStudium = htmlspecialchars($_POST['studiumkode'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <title>Klasser</title>
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
    input[type="text"] { padding: 8px 10px; width: 280px; border:1px solid #ccc; border-radius:6px; }
    button { margin-top: 12px; padding: 8px 14px; border:0; background:#1f2937; color:#fff; border-radius:6px; cursor:pointer; }
    button:hover { background:#111827; }
    table { border-collapse: collapse; margin-top: 18px; min-width: 640px; }
    th, td { border: 1px solid #e5e7eb; padding: 10px 12px; }
    th { background: #f5f5f5; text-align: left; }
    .danger { color:#dc2626; background:none; border:none; cursor:pointer; }
  </style>
</head>
<body>
  <h1>Klasser</h1>
  <p><a href="index.php">← Tilbake til meny</a></p>

  <?php if ($message): ?>
    <div class="msg <?= str_starts_with(strip_tags($message), '✅') ? 'ok' : 'warn' ?>">
      <?= $message ?>
    </div>
  <?php endif; ?>

  <h2>Registrer ny klasse</h2>
  <form method="post" action="klasse.php">
    <label for="klassekode">Klassekode:</label>
    <input type="text" id="klassekode" name="klassekode" maxlength="5" value="<?= $valKode ?>" required />

    <label for="klassenavn">Klassenavn:</label>
    <input type="text" id="klassenavn" name="klassenavn" maxlength="50" value="<?= $valNavn ?>" required />

    <label for="studiumkode">Studiumkode:</label>
    <input type="text" id="studiumkode" name="studiumkode" maxlength="50" value="<?= $valStudium ?>" required />

    <br/>
    <button type="submit" name="registrer">Registrer</button>
  </form>

  <h2>Alle klasser</h2>
  <table>
    <tr>
      <th>Kode</th>
      <th>Navn</th>
      <th>Studium</th>
      <th>Handling</th>
    </tr>
    <?php if (empty($klasser)): ?>
      <tr><td colspan="4">Ingen klasser registrert ennå.</td></tr>
    <?php else: ?>
      <?php foreach ($klasser as $k): ?>
        <tr>
          <td><?= htmlspecialchars($k['klassekode']) ?></td>
          <td><?= htmlspecialchars($k['klassenavn']) ?></td>
          <td><?= htmlspecialchars($k['studiumkode']) ?></td>
          <td>
            <!-- Slett via POST-skjema for robusthet -->
            <form method="post" action="klasse.php" onsubmit="return confirm('Slette klassen «<?= htmlspecialchars($k['klassekode']) ?>»?');" style="display:inline">
              <input type="hidden" name="slett" value="<?= htmlspecialchars($k['klassekode']) ?>">
              <button type="submit" class="danger">Slett</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</body>
</html>

