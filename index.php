<?php
session_start();

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    // On récupère les tags des resto pour les afficher
    $tags = array();
    $query = "SELECT * FROM tags";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        array_push($tags, $row["nom_tag"] . ":" . $row["id"]);
    }
} catch (\Throwable $th) {
    array_push($erreurs, $th->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="dist/output.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <title>MealRush - Vos plats livrés rapidement</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <div class="hero min-h-fit" style="background-image: url(img/main-hero.jpeg);">
        <div class="hero-overlay bg-opacity-70"></div>
        <div class="hero-content text-center text-neutral-content flex-col">
            <img src="img/logo.png" class="w-72 rounded-lg shadow-2xl mb-3" />
            <div class="max-w-md">
                <h1 class="text-5xl font-bold">Votre repas préféré livré vite fait !</h1>
                <p class="py-6">
                    Recevez votre plat sur le pas de votre porte en un rien de temps avec MealRush.
                </p>
                <a class="btn bg-white text-black hover:text-white" href="#tags-container">On mange quoi ?</a>
                <!-- <a class="btn bg-blue text-black hover:text-white ml-1" href="connexion.php">Se connecter</a> -->
            </div>
        </div>
    </div>

    <!-- Livrer a ...
    Lister les catégories -->

    <?php include('footer.php'); ?>

</body>

</html>