<?php
// klasse.php – CRUD for tabellen "klasse"
require_once 'db_connection.php'; // bruker samme tilkobling som du allerede har

// Håndter innsending av ny klasse (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrer'])) {
    // Les verdier fra skjema (server-validering i tillegg til "required" i HTML)
    $kode    = trim($_POST['klassekode'] ?? '');
    $navn    = trim($_POST['klassenavn'] ?? '');
    $studium = trim($_POST['studiumkode'] ?? '');

    if ($kode !== '' && $navn !== '' && $studium !== '') {
        // Bruk prepared statement (sikrere enn stringkonkatenasjon)
        $stmt = $conn->prepare("INSERT INTO klasse (klassekode, klassenavn, studiumkode) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $kode, $navn, $studium);
        if ($stmt->execute()) {
            $message = "✅ Klasse registrert!";
        } else {
            // F.eks. duplisert primærnøkkel gir feilmelding
            $message = "⚠️ Feil ved registrering: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $message = "⚠️ Alle felter må fylles ut.";
    }
}

// Håndter sletting (GET ?slett=IT1)
if (isset($_GET['slett'])) {
    $kode = $_GET['slett'];
    $stmt = $conn->prepare("DELETE FROM klasse WHERE klassekode = ?");
    $stmt->bind_param("s", $kode);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = "🗑️ Klassen '$kode' ble slettet.";
        } else {
            $message = "⚠️ Fant ingen klasse med kode '$kode'.";
        }
    } else {
        // Kan feile pga. fremmednøkler (hvis studenter peker på denne klassen)
        $message = "⚠️ Kunne ikke slette (sannsynligvis fordi det finnes studenter i denne klassen).";
    }
    $stmt->close();
}

// Hent alle klasser for visning
$klasser = [];
$res = $conn->query("SELECT klassekode, klassenavn, studiumkode FROM klasse ORDER BY klassekode");
if ($res) {
    while ($row = $res->fetch_assoc()) { $klasser[] = $row; }
    $res->close();
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <title>Administrer klasser</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    table { border-collapse: collapse; margin-top: 16px; min-width: 520px; }
    th, td { border: 1px solid #ddd; padding: 8px 12px; }
    th { background: #f5f5f5; }
    .msg { margin: 12px 0; }
    a { text-decoration: none; }
  </style>
</head>
<body>
  <h1>Klasser</h1>
  <p><a href="index.php">← Tilbake til meny</a></p>

  <?php if (!empty($message)): ?>
    <div class="msg"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <h2>Registrer ny klasse</h2>
  <form method="post">
    <label>Klassekode:
      <input type="text" name="klassekode" maxlength="5" required />
    </label><br><br>
    <label>Klassenavn:
      <input type="text" name="klassenavn" maxlength="50" required />
    </label><br><br>
    <label>Studiumkode:
      <input type="text" name="studiumkode" maxlength="50" required />
    </label><br><br>
    <button type="submit" name="registrer">Registrer</button>
  </form>

  <h2>Alle klasser</h2>
  <table>
    <tr><th>Kode</th><th>Navn</th><th>Studium</th><th>Handling</th></tr>
    <?php foreach ($klasser as $k): ?>
      <tr>
        <td><?= htmlspecialchars($k['klassekode']) ?></td>
        <td><?= htmlspecialchars($k['klassenavn']) ?></td>
        <td><?= htmlspecialchars($k['studiumkode']) ?></td>
        <td>
          <a href="?slett=<?= urlencode($k['klassekode']) ?>" onclick="return confirm('Slette klassen?');">Slett</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($klasser)): ?>
      <tr><td colspan="4">Ingen klasser registrert ennå.</td></tr>
    <?php endif; ?>
  </table>
</body>
</html>
