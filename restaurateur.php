<?php
session_start();

// Page réservée aux restaurateurs
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] == false || $_SESSION['role'] == 'utilisateur') {
    header("location: index.php");
    exit;
}

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    $id_utilisateur = $_SESSION['id_utilisateur'];

    // On récupère les restaurants non approuvés pour les afficher
    $restos = array();
    $query = "SELECT * FROM restaurants WHERE id_utilisateur = '$id_utilisateur'";
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
            'approuve' => $row['approuve'],
            'tags' => $tags_du_resto
        ));
    }

    $nb_approuves = count(array_filter($restos, function ($resto) {
        return $resto["approuve"] == "true";
    }));

    $nb_en_attente = count(array_filter($restos, function ($resto) {
        return $resto["approuve"] == "false";
    }));
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
    <title>Restaurateurs - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <?php if ($nb_approuves == 0 && $nb_en_attente == 1) : ?>

        <div class="hero min-h-screen" style="background-image: url(<?php echo $restos[0]['image']; ?>);">
            <div class="hero-overlay bg-opacity-80"></div>
            <div class=" hero-content text-center text-white">
                <div class="max-w-md">
                    <h1 class="text-5xl font-bold">En attente de validation...</h1>
                    <p class="py-6">Votre restaurant <b><?php echo $restos[0]['nom']; ?></b> est en attente de validation par un de nos modérateurs. Vous pourrez le gérer ici lorsque nous aurons vérifié les informations.</p>
                    <button class="btn bg-white text-black hover:text-white" name="supprimer">Annuler la demande</button>
                </div>
            </div>
        </div>

    <?php else : ?>

        <?php if ($nb_approuves == 0 && $nb_en_attente == 0) : ?>

            <div class="hero min-h-screen" style="background-image: url(https://i.pinimg.com/originals/d3/6d/46/d36d462db827833805497d9ea78a1343.jpg);">
                <div class="hero-overlay bg-opacity-80"></div>
                <div class=" hero-content text-center text-white">
                    <div class="max-w-md">
                        <h1 class="text-5xl font-bold">Aucun restaurant</h1>
                        <p class="py-6">Vous n'avez aucun restaurant</p>
                        <button class="btn bg-white text-black hover:text-white" name="ajouter">Ajouter un restaurant</button>
                    </div>
                </div>
            </div>

        <?php else : ?>

            <div class="hero bg-green min-h-[7rem] text-center">
                <div class="hero-content">
                    <div class="max-w-md">
                        <h1 class="text-5xl font-bold text-white">Bonjour, <?php echo $_SESSION['prenom']; ?>&nbsp;!</h1>
                    </div>
                </div>
            </div>

            <div class="p-7 lg:mx-16">
                <h2 class="text-2xl font-bold md:text-3xl text-slate-700 mb-5 ml-1">Restaurants approuvés</h2>
                <div class="flex items-center gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
                    <?php foreach ($restos as $r) : ?>
                        <?php if ($r['approuve'] == 'true') : ?>
                            <?php $auMoinsUnRestoApprouve = true; ?>
                            <?php include('restocard.php'); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if (!isset($auMoinsUnRestoApprouve)) : ?>
                    <div class="alert shadow-lg md:w-1/2 mx-auto">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info flex-shrink-0 w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Aucun restaurant approuvé</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="p-7 lg:mx-16">
                <h2 class="text-2xl font-bold md:text-3xl text-slate-700 mb-5 ml-1">Restaurants en attente d'approbation</h2>
                <div class="flex items-center gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
                    <?php foreach ($restos as $r) : ?>
                        <?php if ($r['approuve'] == 'false') : ?>
                            <?php $auMoinsUnRestoEnAttente = true; ?>
                            <?php include('restocard.php'); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if (!isset($auMoinsUnRestoEnAttente)) : ?>
                    <div class="alert shadow-lg alert-success md:w-1/2 mx-auto">
                        Tous vos restaurants sont approuvés !
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    <?php endif; ?>

    <?php include('footer.php'); ?>

</body>

</html>