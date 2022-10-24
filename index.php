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

    // On récupère les restaurants pour les afficher
    $restos = array();
    $query = "SELECT * FROM restaurants WHERE approuve = 'true'";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        array_push($restos, array(
            'nom' => $row['nom'],
            'image' => $row['image'],
            'id' => $row['id']
        ));
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

    <!-- <div class="flex items-center p-4 md:w-1/3 mx-auto">
        <div class="flex-1 px-2">
            <h2 class="text-xl font-extrabold">Livraison à</h2>
            <p class="text-md text-opacity-80">5 rue de Rivoli</p>
        </div>
        <div class="flex-0">
            <div class="dropdown dropdown-top dropdown-end">
                <div tabindex="0">
                    <div class="flex space-x-1">
                        <button aria-label="button component" class="btn btn-ghost btn-square">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-6 w-6 stroke-current">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <div class="flex first:ml-auto last:mr-auto items-center p-4 max-w-100 bg-base-300 overflow-x-auto justify-around" id="tags-container">
        <?php foreach ($tags as $t) : ?>
            <a class="btn btn-ghost"><?php echo explode(":", $t)[0]; ?></a>
        <?php endforeach; ?>
    </div>

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

    <div class="grid grid-cols-3 gap-4 p-5" id="tags-container">
        <?php foreach ($restos as $r) : ?>
            <div class="card w-96 bg-base-100 shadow-xl max-w-full">
                <figure class="max-h-36 overflow-hidden">
                    <img src="<?php echo $r['image']; ?>" alt="Image Restaurant" class="object-cover" />
                </figure>
                <div class="card-body">
                    <h2 class="card-title">
                        <?php echo $r['nom']; ?>
                        <div class="badge badge-secondary">Nouveau</div>
                    </h2>
                    <div class="card-actions justify-start">
                        <div class="badge badge-outline">Kebab</div>
                        <div class="badge badge-outline">Pizza</div>
                    </div>
                    <div class="card-actions justify-end">
                        <button class="btn bg-blue text-black border-none hover:text-white">Voir les plats</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php include('footer.php'); ?>

</body>

</html>