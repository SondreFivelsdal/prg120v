<?php
// create_tables.php
include('db_connection.php'); // kobler til databasen

$sql = "
CREATE TABLE klasse (
    klassekode CHAR(5) NOT NULL,
    klassenavn VARCHAR(50) NOT NULL,
    studiumkode VARCHAR(50) NOT NULL,
    PRIMARY KEY (klassekode)
);

CREATE TABLE student (
    brukernavn CHAR(7) NOT NULL,
    fornavn VARCHAR(50) NOT NULL,
    etternavn VARCHAR(50) NOT NULL,
    klassekode CHAR(5) NOT NULL,
    PRIMARY KEY (brukernavn),
    FOREIGN KEY (klassekode) REFERENCES klasse (klassekode)
);
";

// Kjør kommandoene
if ($conn->multi_query($sql) === TRUE) {
    echo "Tabellene ble opprettet!";
} else {
    echo "Feil: " . $conn->error;
}

$conn->close();
?>
