<?php /* Hovedmeny som lar deg velge tabell og operasjoner */ ?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <title>PRG120V – Obligatorisk oppgave 2</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    a { text-decoration: none; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
  </style>
</head>
<body>
  <h1>PRG120V – Obligatorisk oppgave 2</h1>
  <p>Velg en funksjon:</p>

  <div class="card">
    <h2>Klasse</h2>
    <p><a href="klasse.php">Administrer klasser </a></p>
  </div>

  <div class="card">
    <h2>Student</h2>
    <p><a href="student.php">Administrer studenter </a></p>
  </div>
</body>
</html>
