<?php
session_start();
session_destroy();
unset($_SESSION["connecte"]);
header("location: index.php");
// // echo 'Déconnecté . <a href="index.php">Accueil</a>';