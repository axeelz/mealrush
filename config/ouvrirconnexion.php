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

// On initialise les tableaux qui serviront à ajouter les messages d'erreurs
// ou les messages de succès
$erreurs = array();
$succes = array();
