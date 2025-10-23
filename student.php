<?php
// student.php – CRUD for tabellen "student"
require_once 'db_connection.php';

// Håndter innsending av ny student (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrer'])) {
    $brukernavn = trim($_POST['brukernavn'] ?? '');
    $fornavn = trim($_POST['fornavn'] ?? '');
    $etternavn = trim($_POST['etternavn'] ?? '');
    $klassekode = trim($_POST['klassekode'] ?? '');

    if ($brukernavn && $fornavn && $etternavn && $klassekode) {
        try {
            $stmt = $conn->prepare("INSERT INTO student (brukernavn, fornavn, etternavn, klassekode) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $brukernavn, $fornavn, $etternavn, $klassekode);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $message = "✅ Student «" . htmlspecialchars($brukernavn) . "» ble registrert.";
            } else {
                $message = "ℹ️ Ingen endringer ble gjort.";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $message = "⚠️ Brukernavnet «" . htmlspecialchars($brukernavn) . "» finnes allerede. Velg et annet.";
            } elseif ($e->getCode() === 1406) {
                $message = "⚠️ Brukernavnet er for langt – maks 50 tegn.";
            } else {
                $message = "❌ Feil ved registrering: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $message = "⚠️ Alle felt må fylles ut før du kan registrere en student.";
    }
}

// Håndter sletting (GET ?slett=)
if (isset($_GET['slett'])) {
    $brukernavn = trim($_GET['slett']);
    try {
        $stmt = $conn->prepare("DELETE FROM student WHERE brukernavn = ?");
        $stmt->bind_param("s", $brukernavn);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "🗑️ Student «" . htmlspecialchars($brukernavn) . "» ble slettet.";
        } else {
            $message = "ℹ️ Fant ingen student med brukernavn «" . htmlspecialchars($brukernavn) . "».";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "❌ Feil under sletting: " . htmlspecialchars($e->getMessage());
    }
}

// Hent alle klasser for dropdown
$klasser = [];
$res = $conn->query("SELECT klassekode, klassenavn FROM klasse ORDER BY klassekode");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $klasser[] = $row;
    }
}

// Hent alle studenter for visning
$studenter = [];
$res = $conn->query("SELECT s.brukernavn, s.fornavn, s.etternavn, s.klassekode, k.klassenavn
                     FROM student s
                     LEFT JOIN klasse k ON s.klassekode = k.klassekode
                     ORDER BY s.brukernavn");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $studenter[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Administrer studenter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { border-collapse: collapse; margin-top: 16px; min-width: 720px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; }
        th { background: #f5f5f5; }
        .msg { margin: 16px 0; padding: 10px; background: #f5f5f5; border-radius: 8px; }
        a { text-decoration: none; }
    </style>
</head>
<body>
    <h1>Studenter</h1>
    <p><a href="index.php">Tilbake til meny</a> | <a href="klasse.php">Gå til klasser</a></p>

    <?php if (!empty($message)): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <h2>Registrer ny student</h2>
    <form method="post">
        <label>Brukernavn:</label>
        <input type="text" name="brukernavn" maxlength="50" required><br><br>

        <label>Fornavn:</label>
        <input type="text" name="fornavn" maxlength="50" required><br><br>

        <label>Etternavn:</label>
        <input type="text" name="etternavn" maxlength="50" required><br><br>

        <label>Klasse:</label>
        <select name="klassekode" required>
            <option value="">— Velg klasse —</option>
            <?php foreach ($klasser as $k): ?>
                <option value="<?= htmlspecialchars($k['klassekode']) ?>"><?= htmlspecialchars($k['klassenavn']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <input type="submit" name="registrer" value="Registrer">
    </form>

    <h2>Alle studenter</h2>
    <table>
        <tr><th>Brukernavn</th><th>Fornavn</th><th>Etternavn</th><th>Klassekode</th><th>Klassenavn</th><th>Handling</th></tr>
        <?php if (!empty($studenter)): ?>
            <?php foreach ($studenter as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['brukernavn']) ?></td>
                    <td><?= htmlspecialchars($s['fornavn']) ?></td>
                    <td><?= htmlspecialchars($s['etternavn']) ?></td>
                    <td><?= htmlspecialchars($s['klassekode']) ?></td>
                    <td><?= htmlspecialchars($s['klassenavn']) ?></td>
                    <td><a href="student.php?slett=<?= urlencode($s['brukernavn']) ?>" onclick="return confirm('Slette studenten «<?= htmlspecialchars($s['brukernavn']) ?>»?')" style="color:red;">Slett</a></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">Ingen studenter registrert ennå.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
