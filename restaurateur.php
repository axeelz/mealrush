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

    // On récupère les tags pour les afficher (lors de l'ajout d'un resto)
    $tags = array();
    $query = "SELECT * FROM tags";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        array_push($tags, array(
            'nom_tag' => $row["nom_tag"],
            'id_tag' => $row["id"]
        ));
    }

    $nb_approuves = count(array_filter($restos, function ($resto) {
        return $resto["approuve"] == "true";
    }));

    $nb_en_attente = count(array_filter($restos, function ($resto) {
        return $resto["approuve"] == "false";
    }));

    // Après avoir cliqué sur annuler (demande d'approbation) / ou supprimer resto
    if (isset($_POST['supprimer']) && isset($conn)) {
        do {
            $id_restaurant_a_suppr = $_POST['supprimer'];

            // On vérifie que le restaurant que l'utilisateur veur supprimer est bien à lui
            // (pour pas qu'un utilisateur puisse supprimer le resto d'un autre en modifiant l'id situé dans la valeur du bouton)
            // en inspectant l'élement

            $query_verif = "SELECT * FROM restaurants WHERE id = '$id_restaurant_a_suppr'";
            $result = mysqli_query($conn, $query_verif);
            $row = mysqli_fetch_assoc($result);

            // Soit on a bien trouvé une valeur, soit on renvoie false
            $proprietaire_resto = $row['id_utilisateur'] ?? false;

            if ($proprietaire_resto == false) {
                // Aucun resto avec cet id
                array_push($erreurs, "Le restaurant que vous tentez de supprimer n'existe pas");
                break;
            }

            $query = "DELETE FROM restaurants_tags WHERE id_restaurant='$id_restaurant_a_suppr'";
            $query2 = "DELETE FROM restaurants WHERE id='$id_restaurant_a_suppr'";
            if (mysqli_query($conn, $query) && mysqli_query($conn, $query2)) {
                FermerConnexion($conn);
                // On ajoute un message en variable de session pour qu'il puisse être affiché après le reload
                $_SESSION['successMessage'] = "Restaurant supprimé";
                header('location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                array_push($erreurs, mysqli_error($conn));
                break;
            }
        } while (0);
    }

    if (isset($_POST['create']) && isset($conn)) {
        do {
            // On récupère les valeurs du formulaire
            $nom_resto = mysqli_real_escape_string($conn, htmlspecialchars($_POST['nom_resto']));
            $image_resto = mysqli_real_escape_string($conn, htmlspecialchars($_POST['image_resto']));

            // Si un des champs est vide, on stop
            if (empty($nom_resto) || empty($image_resto)) {
                array_push($erreurs, "Un des champs requis est vide");
                break;
            }

            // Si tout est bon, on crée un restaurant
            if ($_SESSION['role'] == 'admin') {
                $query = "INSERT INTO `restaurants` (`nom`, `image`, `id_utilisateur`, `approuve`) VALUES ('$nom_resto', '$image_resto', '$id_utilisateur', 'true')";
            } else {
                $query = "INSERT INTO `restaurants` (`nom`, `image`, `id_utilisateur`) VALUES ('$nom_resto', '$image_resto', '$id_utilisateur')";
            }
            if (mysqli_query($conn, $query)) {
                $id_restaurant = mysqli_insert_id($conn);
                // Restaurant crée !
            } else {
                array_push($erreurs, mysqli_error($conn));
                break;
            }

            // Ajout des tags correspondants aux restos dans la BDD
            $checkboxes = $_POST['tags'];

            // Pour chaque tag coché
            foreach ($checkboxes as $c) {
                $tag_nom = explode(":", $c)[0];
                $tag_id = explode(":", $c)[1];
                $result = mysqli_query($conn, "SELECT * FROM `tags` WHERE nom_tag='$tag_nom' AND id='$tag_id'");
                $count = mysqli_num_rows($result);

                if ($count == 1) {
                    $query = "INSERT INTO `restaurants_tags` (`id_restaurant`, `id_tag`) VALUES ('$id_restaurant', '$tag_id')";
                    if (!mysqli_query($conn, $query)) {
                        array_push($erreurs, "Impossible d'ajouter les tags");
                        break;
                    }
                } else {
                    array_push($erreurs, $tag_nom . " n'est pas un tag reconnu");
                    break;
                }
            }

            FermerConnexion($conn);
            $_SESSION['successMessage'] = "Restaurant crée";
            header('location: ' . $_SERVER['PHP_SELF']);
            exit();
        } while (0);
    }

    if (isset($_POST['ajouter']) && isset($conn)) {
        $veutAjouterRestaurant = true;
    }

    if (isset($_POST['annuler']) && isset($conn)) {
        $veutAjouterRestaurant = false;
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
                    <form method="post">
                        <button class="btn bg-white text-black hover:text-white" name="supprimer" value="<?php echo $restos[0]['id']; ?>">Annuler la demande</button>
                    </form>
                </div>
            </div>
        </div>

    <?php else : ?>

        <?php if (($nb_approuves == 0 && $nb_en_attente == 0) || $veutAjouterRestaurant) : ?>

            <div class="hero min-h-screen" style="background-image: url(https://online.jwu.edu/sites/default/files/styles/article_feature_page/public/field/image/opening%20a%20restaurant.jpg);">
                <div class="hero-overlay bg-opacity-80"></div>
                <div class="hero-content flex-col lg:flex-row-reverse gap-6 my-5 lg:m-0">
                    <div class="text-center lg:text-left text-white max-w-md">
                        <?php if ($veutAjouterRestaurant) : ?>
                            <h1 class="text-5xl font-bold">Développons votre restaurant</h1>
                        <?php else : ?>
                            <h1 class="text-5xl font-bold">Aucun restaurant enregistré</h1>
                        <?php endif; ?>
                        <p class="py-6">Ajoutez votre restaurant sur MealRush et attirez de nouveaux clients&nbsp;!</p>
                        <?php if ($veutAjouterRestaurant) : ?>
                            <form method="post">
                                <button name="annuler" class="btn bg-white text-black hover:text-white">Annuler l'opération</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card flex-shrink-0 w-full max-w-sm shadow-2xl bg-base-100">
                        <div class="card-body">
                            <form method="post">
                                <label for="nom_resto" class="label">
                                    <span class="label-text">Nom du restaurant</span>
                                </label>
                                <input type="text" name="nom_resto" id="nom_resto" placeholder="McDonald's" class="input input-bordered bg-slate-100 w-full mb-2" required />
                                <label for="image_resto" class="label">
                                    <span class="label-text">URL d'une image du restaurant</span>
                                </label>
                                <input type="url" name="image_resto" id="image_resto" placeholder="https://google.com/image.jpg" class="input input-bordered bg-slate-100 w-full" required />
                                <div tabindex="0" class="collapse collapse-arrow border border-base-300 rounded-box my-5 max-h-56 overflow-scroll">
                                    <input type="checkbox" />
                                    <div class="collapse-title">
                                        A quelles catégories correspond votre restaurant&nbsp;?
                                    </div>
                                    <div class="collapse-content">
                                        <div class="form-control">
                                            <?php foreach ($tags as $t) : ?>
                                                <label class="label cursor-pointer">
                                                    <span class="label-text"><?php echo $t['nom_tag']; ?></span>
                                                    <input type="checkbox" class="checkbox" name="tags[]" value="<?php echo $t['nom_tag'] . ":" . $t['id_tag'] ?>" />
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current flex-shrink-0 w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>Il y aura une vérification de votre restaurant avant sa publication</span>
                                    </div>
                                </div>
                                <button class="btn btn-block bg-primary border-none hover:text-white text-black mt-5" name="create">C'est parti</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php else : ?>

            <div class="hero bg-green min-h-[12rem] text-center">
                <div class="hero-content">
                    <div class="max-w-md">
                        <h1 class="text-5xl font-bold text-white">Mes restaurants</h1>
                        <form method="post">
                            <button name="ajouter" class="btn bg-white text-black hover:text-white mt-5">Ajouter un restaurant</button>
                        </form>
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