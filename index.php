<?php
session_start();

include 'config/ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    // On récupère les tags des resto pour les afficher
    $tags = array();
    $query = "SELECT * FROM tags";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        array_push($tags, array(
            'nom_tag' => $row["nom_tag"],
            'id_tag' => $row["id"]
        ));
    }

    // On récupère les restaurants pour les afficher
    $restos = array();
    $query = "SELECT * FROM restaurants WHERE approuve = 'true' ORDER by id DESC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        // Pour chaque restaurant, on liste ses tags
        $tags_du_resto = array();
        $id_restaurant = $row['id'];
        $query_get_tags_restaurant = "SELECT tags.nom_tag FROM restaurants_tags JOIN tags ON restaurants_tags.id_tag = tags.id WHERE restaurants_tags.id_restaurant = '$id_restaurant'";
        $result_get_tags_restaurant = mysqli_query($conn, $query_get_tags_restaurant);
        while ($tag_restaurant = mysqli_fetch_array($result_get_tags_restaurant, MYSQLI_ASSOC)) {
            array_push($tags_du_resto, $tag_restaurant['nom_tag']);
        }
        array_push($restos, array(
            'nom' => $row['nom'],
            'image' => $row['image'],
            'id' => $row['id'],
            'tags' => $tags_du_resto
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
    <link href="css/output.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <title>MealRush - Livraison de plats</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('components/navbar.php'); ?>

    <div class="flex items-center p-4 max-w-100 bg-base-200 overflow-x-auto" id="tags-container">
        <?php if (!empty($_GET['tag'])) : ?>
            <a class="btn btn-ghost first:ml-auto last:mr-auto" href="index.php">Tous</a>
        <?php endif; ?>
        <?php foreach ($tags as $t) : ?>
            <a class="btn btn-ghost first:ml-auto last:mr-auto <?php if ($_GET['tag'] == $t['id_tag']) echo 'bg-gray' ?>" href="index.php?tag=<?php echo $t['id_tag']; ?>"><?php echo $t['nom_tag']; ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($_GET['tag'])) : ?>

        <?php if (!$isConnecte) : ?>
            <div class="hero min-h-fit" id="main-hero">
                <div class="hero-overlay bg-opacity-70"></div>
                <div class="hero-content text-center text-neutral-content flex-col">
                    <img src="img/logo.png" class="w-56 md:w-72 rounded-lg shadow-2xl mb-3" />
                    <div class="max-w-md">
                        <h1 class="text-3xl md:text-5xl font-bold">Vos repas livrés en quelques clics&nbsp;!</h1>
                        <p class="py-6">
                            Recevez vos plats sur le pas de votre porte en un rien de temps avec MealRush.
                        </p>
                        <a class="btn bg-white text-black hover:text-white border-none" href="#restos-container">On mange quoi ?</a>
                        <a class="btn bg-blue text-black hover:text-white ml-1 border-none" href="connexion.php">Se connecter</a>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="hero min-h-fit animate-in fade-in duration-500" id="main-hero">
                <div class="hero-overlay bg-opacity-70"></div>
                <div class="hero-content text-center text-neutral-content flex-col">
                    <img src="img/logo.png" class="w-56 rounded-lg shadow-2xl" />
                </div>
            </div>
        <?php endif; ?>

        <div class="p-7 lg:mx-16" id="restos-container">
            <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1">Tous les restaurants</h2>
            <div class="flex items-stretch gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
                <?php if (empty($restos)) : ?>
                    <div class="alert shadow-lg">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info flex-shrink-0 w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Nous n'avons aucun restaurant à afficher pour le moment</span>
                        </div>
                    </div>
                <?php else : ?>
                    <?php foreach ($restos as $r) : ?>
                        <?php include('components/restocard.php'); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $tags_non_vides = array();
        $query = "SELECT DISTINCT id_tag FROM restaurants_tags JOIN restaurants ON restaurants_tags.id_restaurant = restaurants.id WHERE restaurants.approuve = 'true' ORDER BY id_tag DESC";
        $result = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            foreach ($tags as $t) {
                if ($row['id_tag'] == $t['id_tag']) {
                    array_push($tags_non_vides, $t['nom_tag']);
                }
            }
        }
        ?>

        <?php foreach ($tags_non_vides as $tnv) : ?>
            <div class="p-7 lg:mx-16">
                <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1"><?php echo $tnv; ?></h2>
                <div class="flex items-stretch gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
                    <?php foreach ($restos as $r) : ?>
                        <?php if (in_array($tnv, $r['tags'])) : ?>
                            <?php $auMoinsUnResto = true; ?>
                            <?php include('components/restocard.php'); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php else : ?>

        <?php
        foreach ($tags as $t) {
            if ($_GET['tag'] == $t['id_tag']) {
                $nom_tag_selectionne = $t['nom_tag'];
            }
        }
        ?>

        <div class="hero bg-green min-h-[7rem]">
            <div class="hero-content">
                <div class="max-w-md">
                    <h1 class="text-3xl md:text-5xl font-bold text-white"><?php echo $nom_tag_selectionne ?></h1>
                </div>
            </div>
        </div>

        <div class="p-7 lg:mx-16">
            <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1">Restaurants correspondants</h2>
            <div class="flex items-stretch gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
                <?php foreach ($restos as $r) : ?>
                    <?php if (in_array($nom_tag_selectionne, $r['tags'])) : ?>
                        <?php $auMoinsUnResultat = true; ?>
                        <?php include('components/restocard.php'); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (!isset($auMoinsUnResultat)) : ?>
                    <div class="alert shadow-lg">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info flex-shrink-0 w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Nous n'avons aucun restaurant correspondant à <?php echo $nom_tag_selectionne ?> pour le moment</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

    <!-- Footer -->
    <?php include('components/footer.php'); ?>

    <script>
        // Changer l'image de fond aléatoirement

        const hero = document.getElementById("main-hero");

        const images = [
            "img/hero-burgers.jpeg",
            "img/hero-sushi.jpeg",
            "img/hero-nouilles.jpeg"
        ]

        hero.style.backgroundImage = "url(" + images[Math.floor(Math.random() * images.length)] + ")";
    </script>

</body>

</html>