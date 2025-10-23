<?php
// student.php — administrasjon av studenter
require_once 'db_connection.php';

$message = '';

// ---------------- Hent klasser til nedtrekk ----------------
$klasser = [];
$resK = $conn->query("SELECT klassekode, klassenavn FROM klasse ORDER BY klassekode");
if ($resK) {
    while ($row = $resK->fetch_assoc()) {
        $klasser[] = $row;
    }
    $resK->close();
}

// ---------------- Registrer ny student (POST) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrer'])) {
    $bn = trim($_POST['brukernavn'] ?? '');
    $fn = trim($_POST['fornavn'] ?? '');
    $en = trim($_POST['etternavn'] ?? '');
    $kk = trim($_POST['klassekode'] ?? '');

    if ($bn !== '' && $fn !== '' && $en !== '' && $kk !== '') {
        // Sjekk at klassekode finnes
        $stmtK = $conn->prepare("SELECT 1 FROM klasse WHERE klassekode = ?");
        $stmtK->bind_param("s", $kk);
        $stmtK->execute();
        $stmtK->store_result();
        $klasseFinnes = $stmtK->num_rows > 0;
        $stmtK->close();

        if (!$klasseFinnes) {
            $message = "❌ Klassen «{$kk}» finnes ikke. Velg en gyldig klassekode.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO student (brukernavn, fornavn, etternavn, klassekode) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssss", $bn, $fn, $en, $kk);

            if ($stmt->execute()) {
                $message = "✅ Student «{$bn}» ble registrert.";
            } else {
                if ($stmt->errno == 1062) {
                    $message = "❌ Brukernavnet «{$bn}» finnes allerede. Velg et annet.";
                } elseif ($stmt->errno == 1452) {
                    $message = "❌ Klassen «{$kk}» finnes ikke. Velg en gyldig klassekode.";
                } else {
                    $message = "❌ Feil ved registrering ({$stmt->errno}): " . htmlspecialchars($stmt->error);
                }
            }
            $stmt->close();
        }
    } else {
        $message = "❌ Alle felter må fylles ut.";
    }
}

// ---------------- Slett student (POST) ----------------
if (isset($_POST['slett'])) {
    $bn = trim($_POST['slett']);

    $stmt = $conn->prepare("DELETE FROM student WHERE brukernavn = ?");
    $stmt->bind_param("s", $bn);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = "🗑️ Student «{$bn}» ble slettet.";
        } else {
            $message = "ℹ️ Fant ingen student med brukernavn «{$bn}».";
        }
    } else {
        $message = "❌ Feil under sletting ({$stmt->errno}): " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// ---------------- Hent alle studenter (med klassenavn) ----------------
$studenter = [];
$sqlList = "
    SELECT s.brukernavn, s.fornavn, s.etternavn, s.klassekode, k.klassenavn
    FROM student s
    LEFT JOIN klasse k ON k.klassekode = s.klassekode
    ORDER BY s.brukernavn
";
$res = $conn->query($sqlList);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $studenter[] = $row;
    }
    $res->close();
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Administrer studenter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { border-collapse: collapse; margin-top: 16px; min-width: 680px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; }
        th { background: #f5f5f5; }
        .msg { margin: 16px 0; font-weight: bold; }
        a { text-decoration: none; }
        button.link { background:none;border:none;color:#06c;cursor:pointer;padding:0; }
    </style>
</head>
<body>

<h1>Studenter</h1>
<p><a href="index.php">Tilbake til meny</a> | <a href="klasse.php">Gå til klasser</a></p>

<?php if (!empty($message)): ?>
    <div class="msg"><?= $message ?></div>
<?php endif; ?>

<h2>Registrer ny student</h2>
<form method="post">
    <label>
        Brukernavn:
        <input type="text" name="brukernavn" maxlength="50" required>
    </label><br><br>
    <label>
        Fornavn:
        <input type="text" name="fornavn" maxlength="100" required>
    </label><br><br>
    <label>
        Etternavn:
        <input type="text" name="etternavn" maxlength="100" required>
    </label><br><br>
    <label>
        Klasse:
        <select name="klassekode" required>
            <option value="">— Velg klasse —</option>
            <?php foreach ($klasser as $k): ?>
                <option value="<?= htmlspecialchars($k['klassekode']) ?>">
                    <?= htmlspecialchars($k['klassekode']) ?> — <?= htmlspecialchars($k['klassenavn']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br><br>
    <input type="submit" name="registrer" value="Registrer">
</form>

<h2>Alle studenter</h2>
<table>
    <tr>
        <th>Brukernavn</th>
        <th>Fornavn</th>
        <th>Etternavn</th>
        <th>Klassekode</th>
        <th>Klassenavn</th>
        <th>Handling</th>
    </tr>
    <?php if (!empty($studenter)): ?>
        <?php foreach ($studenter as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['brukernavn']) ?></td>
                <td><?= htmlspecialchars($s['fornavn']) ?></td>
                <td><?= htmlspecialchars($s['etternavn']) ?></td>
                <td><?= htmlspecialchars($s['klassekode']) ?></td>
                <td><?= htmlspecialchars($s['klassenavn'] ?? '') ?></td>
                <td>
                    <form method="post" action="student.php"
                          onsubmit="return confirm('Slette studenten «<?= htmlspecialchars($s['brukernavn']) ?>»?');"
                          style="display:inline">
                        <input type="hidden" name="slett" value="<?= htmlspecialchars($s['brukernavn']) ?>">
                        <button type="submit" class="link">Slett</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6">Ingen studenter registrert ennå.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
