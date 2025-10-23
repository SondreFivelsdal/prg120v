<?php
// klasse.php — administrasjon av klasser
require_once 'db_connection.php'; // bruker samme kobling som du allerede har

$message = ''; // melding som vises i toppen

// ---------------- Registrer ny klasse ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrer'])) {
    $kode    = trim($_POST['klassekode'] ?? '');
    $navn    = trim($_POST['klassenavn'] ?? '');
    $studium = trim($_POST['studiumkode'] ?? '');

    if ($kode !== '' && $navn !== '' && $studium !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO klasse (klassekode, klassenavn, studiumkode) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $kode, $navn, $studium);

        if ($stmt->execute()) {
            $message = "✅ Klasse «{$kode}» ble registrert.";
        } else {
            // Gi brukervennlige feilmeldinger
            if ($stmt->errno == 1062) { // 1062 = duplikatnøkkel
                $message = "❌ Klassekoden «{$kode}» finnes allerede. Velg en annen kode.";
            } else {
                $message = "❌ Feil ved registrering ({$stmt->errno}): " . htmlspecialchars($stmt->error);
            }
        }
        $stmt->close();
    } else {
        $message = "❌ Alle felter må fylles ut.";
    }
}

// ---------------- Slett klasse ----------------
if (isset($_GET['slett'])) {
    $kode = trim($_GET['slett']);

    $stmt = $conn->prepare("DELETE FROM klasse WHERE klassekode = ?");
    $stmt->bind_param("s", $kode);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = "🗑️ Klassen «{$kode}» ble slettet.";
        } else {
            $message = "ℹ️ Fant ingen klasse med kode «{$kode}».";
        }
    } else {
        // 1451 = fremmednøkkel-brudd (studenter peker på klassen)
        if ($stmt->errno == 1451) {
            $message = "❌ Kan ikke slette «{$kode}»: Det finnes studenter i denne klassen. "
                     . "Flytt eller slett studentene først.";
        } else {
            $message = "❌ Feil under sletting ({$stmt->errno}): " . htmlspecialchars($stmt->error);
        }
    }
    $stmt->close();
}

// ---------------- Hent alle klasser ----------------
$klasser = [];
$res = $conn->query("SELECT klassekode, klassenavn, studiumkode FROM klasse ORDER BY klassekode");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $klasser[] = $row;
    }
    $res->close();
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Administrer klasser</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { border-collapse: collapse; margin-top: 16px; min-width: 520px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; }
        th { background: #f5f5f5; }
        .msg { margin: 16px 0; font-weight: bold; }
        a { text-decoration: none; }
        .success { color: green; }
        .error { color: red; }
        .info { color: #333; }
    </style>
</head>
<body>

<h1>Klasser</h1>
<p><a href="index.php">Tilbake til meny</a></p>

<?php if (!empty($message)): ?>
    <div class="msg"><?= $message ?></div>
<?php endif; ?>

<h2>Registrer ny klasse</h2>
<form method="post">
    <label>
        Klassekode:
        <input type="text" name="klassekode" maxlength="5" required>
    </label><br><br>
    <label>
        Klassenavn:
        <input type="text" name="klassenavn" maxlength="50" required>
    </label><br><br>
    <label>
        Studiumkode:
        <input type="text" name="studiumkode" maxlength="50" required>
    </label><br><br>
    <input type="submit" name="registrer" value="Registrer">
</form>

<h2>Alle klasser</h2>
<table>
    <tr>
        <th>Kode</th>
        <th>Navn</th>
        <th>Studium</th>
        <th>Handling</th>
    </tr>
    <?php if (!empty($klasser)): ?>
        <?php foreach ($klasser as $k): ?>
            <tr>
                <td><?= htmlspecialchars($k['klassekode']) ?></td>
                <td><?= htmlspecialchars($k['klassenavn']) ?></td>
                <td><?= htmlspecialchars($k['studiumkode']) ?></td>
                <td>
                    <a href="?slett=<?= urlencode($k['klassekode']) ?>"
                       onclick="return confirm('Slette klassen «<?= htmlspecialchars($k['klassekode']) ?>»?');">
                       Slett
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="4">Ingen klasser registrert ennå.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
