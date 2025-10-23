<?php
// db_connection.php
// ⬇️ Sett inn verdiene fra Dokploy-portalen (ikke localhost hvis DB er egen tjeneste)
$servername = "mysql";     // f.eks. mysql.dokploy.usn.no eller navnet på db-tjenesten
$username   = "250158";  
$password   = "6d61250158";     
$dbname     = "250158";        

// Koble til MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Sjekk tilkobling
if ($conn->connect_error) {
    die("Tilkoblingsfeil: " . $conn->connect_error);
}
?>
