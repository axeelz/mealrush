<?php
function OuvrirConnexion()
{
    $dbhost = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $db = "mealrush";
    $conn = new mysqli($dbhost, $dbuser, $dbpass, $db);

    if ($conn->connect_error) {
        die("Erreur de connexion: " . $conn->connect_error);
    }
    return $conn;
}

function FermerConnexion($conn)
{
    $conn->close();
}

$erreurs = array();
$succes = array();
