<?php
// test_connection.php
include('db_connection.php'); // kobler til databasen
echo "✅ DB-tilkobling fungerer!";
$conn->close();
?>
