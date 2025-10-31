<?php

$host = 'localhost';
$db = 'kanban_system';
$user = 'root';
$pass = 'root';

function getDBConnection() {
    global $host, $db, $user, $pass;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        echo "Erro de conexão: " . $e->getMessage();
        return null;
    }
}
?>
