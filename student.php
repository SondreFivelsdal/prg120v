<?php
// student.php – CRUD for tabellen "student"
require_once 'db_connection.php';

// Hent klasser til nedtrekksliste (listeboks)
$klasser = [];
$r = $conn->query("SELECT klassekode, klassenavn FROM klasse ORDER BY klassekode");
if ($r) {
    while ($row = $r->fetch_assoc()) { $klasser[] = $row; }
    $r->close();
}

// Registrer ny student (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrer'])) {
    $brukernavn = trim($_POST['brukernavn'] ?? '');
    $fornavn    = trim($_POST['fornavn'] ?? '');
    $etternavn  = trim($_POST['etternavn'] ?? '');
    $klassekode = trim($_POST['klassekode'] ?? '');

    if ($brukernavn !== '' && $fornavn !== '' && $etternavn !== '' && $klassekode !== '') {
        $stmt = $conn->prepare("INSERT INTO student (brukernavn, fornavn, etternavn, klassekode) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $brukernavn, $fornavn, $etternavn, $klassekode);
        if ($stmt->execute()) {
            $message = "✅ Student registrert!";
        } else {
            $message = "⚠️ Feil ved registrering: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $message = "⚠️ Alle felter må fylles ut.";
    }
}

// Slett student (GET ?slett=brukernavn)
if (isset($_GET['slett'])) {
    $bn = $_GET['slett'];
    $stmt = $conn->prepare("DELETE FROM student WHERE brukernavn = ?");
    $stmt->bind_param("s", $bn);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = "🗑️ Student '$bn' ble slettet.";
        } else {
            $message = "⚠️ Fant ingen student med brukernavn '$bn'.";
        }
    } else {
        $message = "⚠️ Kunne ikke slette: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// Hent alle studenter (JOIN for å vise klassenavn)
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
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <title>Administrer studenter</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    table { border-collapse: collapse; margin-top: 16px; min-width: 700px; }
    th, td { border: 1px solid #ddd; padding: 8px 12px; }
    th { background: #f5f5f5; }
    .msg { margin: 12px 0; }
    a { text-decoration: none; }
  </style>
</head>
<body>
  <h1>Studenter</h1>
  <p><a href="index.php">← Tilbake til meny</a></p>

  <?php if (!empty($message)): ?>
    <div class="msg"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <h2>Registrer ny student</h2>
  <form method="post">
    <label>Brukernavn:
      <input type="text" name="brukernavn" maxlength="7" required />
    </label><br><br>
    <label>Fornavn:
      <input type="text" name="fornavn" maxlength="50" required />
    </label><br><br>
    <label>Etternavn:
      <input type="text" name="etternavn" maxlength="50" required />
    </label><br><br>

    <label>Klasse:
      <select name="klassekode" required>
        <option value="">Velg klasse</option>
        <?php foreach ($klasser as $k): ?>
          <option value="<?= htmlspecialchars($k['klassekode']) ?>">
            <?= htmlspecialchars($k['klassekode'] . ' — ' . $k['klassenavn']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label><br><br>

    <button type="submit" name="registrer">Registrer</button>
  </form>

  <h2>Alle studenter</h2>
  <table>
    <tr><th>Brukernavn</th><th>Fornavn</th><th>Etternavn</th><th>Klassekode</th><th>Klassenavn</th><th>Handling</th></tr>
    <?php foreach ($studenter as $s): ?>
      <tr>
        <td><?= htmlspecialchars($s['brukernavn']) ?></td>
        <td><?= htmlspecialchars($s['fornavn']) ?></td>
        <td><?= htmlspecialchars($s['etternavn']) ?></td>
        <td><?= htmlspecialchars($s['klassekode']) ?></td>
        <td><?= htmlspecialchars($s['klassenavn']) ?></td>
        <td>
          <a href="?slett=<?= urlencode($s['brukernavn']) ?>" onclick="return confirm('Slette student?');">Slett</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($studenter)): ?>
      <tr><td colspan="6">Ingen studenter registrert ennå.</td></tr>
    <?php endif; ?>
  </table>
</body>
</html>
