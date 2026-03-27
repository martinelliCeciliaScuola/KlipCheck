
<?php
 
$servername = "localhost";
$username = "mysql";
$password = "mysql";
$dbname = "klipcheckdb";
 
try {
$conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
 

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 

$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
 
echo "Connessione al database '$dbname' avvenuta con successo!";
 
} catch (PDOException $e) {
echo "Connessione fallita: " . $e->getMessage();
}