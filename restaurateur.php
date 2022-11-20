<?php
session_start();

// Page réservée aux restaurateurs
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] == false || $_SESSION['role'] == 'utilisateur') {
    header("location: index.php");
    exit();
}

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    $id_utilisateur = $_SESSION['id_utilisateur'];

    // On récupère les restaurants non approuvés pour les afficher
    $restos = array();
    $query = "SELECT * FROM restaurants WHERE id_utilisateur = '$id_utilisateur' ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $id_restaurant = $row['id'];
        // Pour chaque restaurant, on liste ses tags
        $tags_du_resto = array();
        $query_get_tags_restaurant = "SELECT tags.nom_tag FROM restaurants_tags JOIN tags ON restaurants_tags.id_tag = tags.id WHERE restaurants_tags.id_restaurant = '$id_restaurant'";
        $result_get_tags_restaurant = mysqli_query($conn, $query_get_tags_restaurant);
        while ($tag_restaurant = mysqli_fetch_array($result_get_tags_restaurant, MYSQLI_ASSOC)) {
            array_push($tags_du_resto, $tag_restaurant['nom_tag']);
        }

        // Pour chaque restaurant, on liste ses plats et le type de ses plats
        $plats_du_resto = array();
        $query_get_plats_restaurant = "SELECT * FROM plats WHERE id_restaurant='$id_restaurant'";
        $result_get_plats_restaurant = mysqli_query($conn, $query_get_plats_restaurant);
        while ($plat_restaurant = mysqli_fetch_array($result_get_plats_restaurant, MYSQLI_ASSOC)) {
            $id_plat = $plat_restaurant['id'];
            $query_get_type_restaurant = "SELECT types_de_plats.nom_type, types_de_plats.id FROM plats_types JOIN types_de_plats ON types_de_plats.id = plats_types.id_type WHERE plats_types.id_plat='$id_plat'";
            $result_get_type_restaurant = mysqli_query($conn, $query_get_type_restaurant);
            $type = mysqli_fetch_assoc($result_get_type_restaurant);
            array_push($plats_du_resto, array(
                'nom' => $plat_restaurant['nom'],
                'prix' => $plat_restaurant['prix'],
                'image' => $plat_restaurant['image'],
                'type' => array(
                    'nom' => $type['nom_type'],
                    'id' => $type['id']
                ),
                'id' => $plat_restaurant['id']
            ));
        }

        array_push($restos, array(
            'nom' => $row['nom'],
            'image' => $row['image'],
            'id' => $row['id'],
            'approuve' => $row['approuve'],
            'tags' => $tags_du_resto,
            'plats' => $plats_du_resto
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

    // On récupère les types de plats pour les afficher (lors de l'ajout d'un plat)
    $types = array();
    $query = "SELECT * FROM types_de_plats";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        array_push($types, array(
            'nom_type' => $row["nom_type"],
            'id_type' => $row["id"]
        ));
    }

    $nb_approuves = count(array_filter($restos, function ($resto) {
        return $resto["approuve"] == "true";
    }));

    $nb_en_attente = count(array_filter($restos, function ($resto) {
        return $resto["approuve"] == "false";
    }));

    // Après avoir cliqué sur annuler la demande d'approbation) / ou supprimer le resto
    if (isset($_POST['supprimer']) && isset($conn)) {
        do {
            $id_restaurant_a_suppr = $_POST['supprimer'];

            // On vérifie si la valeur associée au bouton "supprimer" (l'id du resto que l'utilisateur veut supprimer)
            // est bien dans la liste des restaurants appartenant à cet utilisateur,
            // pour éviter qu'il puisse supprimer un autre restaurant en inspectant l'élément
            // et en modifiant la valeur du bouton.
            $nb_restos_de_cet_user_ayant_cet_id = count(array_filter($restos, function ($resto) {
                return $resto['id'] == $_POST['supprimer'];
            }));

            if ($nb_restos_de_cet_user_ayant_cet_id == 0) {
                // Aucun resto avec cet id appartenant à l'utilisateur
                array_push($erreurs, "Le restaurant que vous tentez de supprimer ne vous appartient pas");
                break;
            }

            $query = "DELETE FROM restaurants WHERE id='$id_restaurant_a_suppr'";
            if (mysqli_query($conn, $query)) {
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

    // Après avoir soumis le formulaire d'ajout de restaurant
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
                $query = "INSERT INTO restaurants (nom, `image`, `id_utilisateur`, `approuve`) VALUES ('$nom_resto', '$image_resto', '$id_utilisateur', 'true')";
            } else {
                $query = "INSERT INTO restaurants (nom, `image`, `id_utilisateur`) VALUES ('$nom_resto', '$image_resto', '$id_utilisateur')";
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

                // Vérification que le tag est bien dans la BDD (et qu'il a pas été modifié avec inspecter l'élément)
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

            $_SESSION['successMessage'] = "Restaurant crée";
            header('location: ' . $_SERVER['PHP_SELF']);
            exit();
        } while (0);
    }

    // Passer en mode ajout de restaurant
    if (isset($_POST['ajouter']) && isset($conn)) {
        $veutAjouterRestaurant = true;
    }

    // Bouton annuler l'opération en cours
    if (isset($_POST['annuler']) && isset($conn)) {
        $veutAjouterRestaurant = false;
        $veutGererResto = false;
    }

    // Passer en mode gestion du restaurant
    if (isset($_POST['gerer']) && isset($conn)) {
        // On vérifie si la valeur associée au bouton "gérér" (l'id du resto que l'utilisateur veut modifier)
        // est bien dans la liste des restaurants appartenant à cet utilisateur,
        // pour éviter qu'il puisse modifier un autre restaurant en inspectant l'élément
        // et en modifiant la valeur du bouton

        // La fonction current récupère le premier élement d'une liste, ou false si elle est vide
        $resto_a_modifier = current(array_filter($restos, function ($resto) {
            return $resto['id'] == $_POST['gerer'];
        }));

        if ($resto_a_modifier != false) {
            // On active le mode modification
            $veutGererResto = true;
            // On met en tant que variable de session l'id du resto à modifier
            $_SESSION['idRestoAModifier'] = $_POST['gerer'];
        } else {
            array_push($erreurs, "Le restaurant que vous tentez de modifier ne vous appartient pas");
        }
    }

    // Pour rester sur la page de modification du resto quand la page est rechargée
    if (isset($_SESSION['idRestoAModifier'])) {
        // On vérifie si la valeur associée au bouton "gérér" (l'id du resto que l'utilisateur veut modifier)
        // est bien dans la liste des restaurants appartenant à cet utilisateur,
        // pour éviter qu'il puisse modifier un autre restaurant en inspectant l'élément
        // et en modifiant la valeur du bouton

        // La fonction current récupère le premier élement d'une liste, ou false si elle est vide
        $resto_a_modifier = current(array_filter($restos, function ($resto) {
            return $resto['id'] == $_SESSION['idRestoAModifier'];
        }));

        // On active le mode modification
        $veutGererResto = true;
    }

    // Après avoir soumis le formulaire de modification de restaurant (bouton enregistrer)
    if (isset($_POST['modifier']) && isset($conn)) {
        do {
            // On récupère les infos sur le restaurant à modifier depuis l'id stocké dans la variable de session
            $resto_a_modifier = current(array_filter($restos, function ($resto) {
                return $resto['id'] == $_SESSION['idRestoAModifier'];
            }));

            $id_restaurant_a_modif = $resto_a_modifier['id'];

            // On récupère les valeurs du formulaire
            $nom_resto_updated = mysqli_real_escape_string($conn, htmlspecialchars($_POST['update_nom']));
            $image_resto_updated = mysqli_real_escape_string($conn, htmlspecialchars($_POST['update_image']));

            $checkboxes = $_POST['tags'];

            // On parcourt les checkboxes checkées pour ajouter les éventuels nouveaux tags
            foreach ($checkboxes as $c) {
                $tag_nom = explode(":", $c)[0];
                $tag_id = explode(":", $c)[1];

                // On ajoute seulement les tags qui ne font pas déjà partie des tags du resto
                if (!in_array($tag_nom, $resto_a_modifier['tags'])) {

                    // Vérification que le tag est bien dans la BDD (et qu'il a pas été modifié avec inspecter l'élément)
                    $result = mysqli_query($conn, "SELECT * FROM tags WHERE nom_tag='$tag_nom' AND id='$tag_id'");
                    $count = mysqli_num_rows($result);

                    if ($count == 1) {
                        $query = "INSERT INTO restaurants_tags (`id_restaurant`, `id_tag`) VALUES ('$id_restaurant_a_modif', '$tag_id')";

                        if (mysqli_query($conn, $query)) {
                            $auMoinsUnTagModifie = true;
                        } else {
                            array_push($erreurs, "Impossible d'ajouter les tags");
                            break;
                        }
                    } else {
                        array_push($erreurs, $tag_nom . " n'est pas un tag reconnu");
                        break;
                    }
                }
            }

            $noms_tags_selectionnes = array();
            foreach ($checkboxes as $c) {
                array_push($noms_tags_selectionnes, explode(":", $c)[0]);
            }

            // On parcourt les tags existants du resto pour vérifier si certains n'ont pas été décochés
            // Si c'est le cas on les supprime du resto
            foreach ($resto_a_modifier['tags'] as $tag_existant) {
                // Si le tag n'est pas dans la liste des tags cochés, on le supprime
                if (!in_array($tag_existant, $noms_tags_selectionnes)) {
                    $query = "SELECT id FROM tags WHERE nom_tag='$tag_existant'";
                    $result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($result);
                    $id_tag_a_suppr = $row['id'];

                    $query = "DELETE FROM restaurants_tags WHERE id_restaurant='$id_restaurant_a_modif' AND id_tag='$id_tag_a_suppr'";

                    if (mysqli_query($conn, $query)) {
                        $auMoinsUnTagModifie = true;
                    } else {
                        array_push($erreurs, "Impossible de supprimer le tag " . $tag_existant);
                        break;
                    }
                }
            }

            if (isset($auMoinsUnTagModifie))
                $_SESSION['successMessage'] = "Tags mis a jour";

            if ($nom_resto_updated != $resto_a_modifier['nom'] || $image_resto_updated != $resto_a_modifier['image']) {
                if (empty($nom_resto_updated) || empty($image_resto_updated)) {
                    array_push($erreurs, "Un des champs requis est vide");
                    break;
                }

                $query = "UPDATE restaurants SET `nom`='$nom_resto_updated', `image`='$image_resto_updated' WHERE id='$id_restaurant_a_modif'";
                if (mysqli_query($conn, $query)) {
                    if (isset($auMoinsUnTagModifie)) {
                        $_SESSION['successMessage'] = "Restaurant et tags mis a jour";
                    } else {
                        $_SESSION['successMessage'] = "Restaurant mis a jour";
                    }
                } else {
                    array_push($erreurs, mysqli_error($conn));
                    break;
                }
            }

            // On retire la variable de session pour revenir à la liste de tous les restos
            unset($_SESSION['idRestoAModifier']);
            header('location: ' . $_SERVER['PHP_SELF']);
            exit();
        } while (0);
    }

    // Après avoir soumis le formulaire d'ajout de plat
    if (isset($_POST['ajouter_plat']) && isset($conn)) {
        do {
            $id_restaurant = $_SESSION['idRestoAModifier'];

            // On récupère les valeurs du formulaire
            $nom_plat = mysqli_real_escape_string($conn, htmlspecialchars($_POST['nom_plat']));
            $prix_plat = mysqli_real_escape_string($conn, htmlspecialchars($_POST['prix_plat']));
            $image_plat = mysqli_real_escape_string($conn, htmlspecialchars($_POST['image_plat']));
            $id_type_plat = mysqli_real_escape_string($conn, htmlspecialchars((int) $_POST['type_plat']));

            // Si un des champs est vide, on stop
            if (empty($nom_plat) || empty($prix_plat) || empty($image_plat) || empty($id_type_plat)) {
                array_push($erreurs, "Un des champs requis est vide");
                break;
            }

            if (1 < $prix_plat && $prix_plat > 99) {
                array_push($erreurs, "Ce prix ne correspond pas à nos normes");
                break;
            }

            // Vérification que le type est bien dans la BDD (et qu'il a pas été modifié avec inspecter l'élément)
            $result = mysqli_query($conn, "SELECT * FROM `types_de_plats` WHERE id='$id_type_plat'");
            $count = mysqli_num_rows($result);

            if (!($count == 1)) {
                array_push($erreurs, "Ce type de plat n'est pas reconnu");
                break;
            }

            $query = "INSERT INTO `plats` (`nom`, `prix`, `image`, `id_restaurant`) VALUES ('$nom_plat', '$prix_plat', '$image_plat', '$id_restaurant')";
            if (!mysqli_query($conn, $query)) {
                array_push($erreurs, "Impossible d'ajouter ce plat");
                break;
            } else {
                $id_plat = mysqli_insert_id($conn);
                $query = "INSERT INTO `plats_types` (`id_plat`, `id_type`) VALUES ('$id_plat', '$id_type_plat')";
                if (!mysqli_query($conn, $query)) {
                    array_push($erreurs, "Impossible d'ajouter le type de ce plat");
                    break;
                }
            }

            $_SESSION['successMessage'] = "Plat crée";
            header('location: ' . $_SERVER['PHP_SELF']);
            exit();
        } while (0);
    }

    // Après avoir soumis le formulaire de suppression de plat
    if (isset($_POST['supprimer_plat']) && isset($conn)) {
        do {
            $id_restaurant = $_SESSION['idRestoAModifier'];
            $id_plat_a_suppr = $_POST['supprimer_plat'];

            // On vérifie que le plat que l'utilisateur souhaite supprimer
            // existe bien et appartient bien à ce restaurant,
            // pour éviter qu'il puisse supprimer un autre plat en inspectant l'élément
            // et en modifiant la valeur du bouton.
            $result = mysqli_query($conn, "SELECT * FROM `plats` WHERE id='$id_plat_a_suppr' AND id_restaurant='$id_restaurant'");
            $count = mysqli_num_rows($result);

            if (!($count == 1)) {
                array_push($erreurs, "Ce plat n'existe pas ou ne vous appartient pas");
                break;
            }

            $query = "DELETE FROM plats WHERE id='$id_plat_a_suppr'";
            if (mysqli_query($conn, $query)) {
                // On ajoute un message en variable de session pour qu'il puisse être affiché après le reload
                $_SESSION['successMessage'] = "Plat supprimé";
                header('location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                array_push($erreurs, mysqli_error($conn));
                break;
            }
        } while (0);
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

    <!-- Si le seul resto de l'user est en attente de validation -->
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

        <!-- Si aucun resto ou veut ajouter un nouveau resto -->
        <?php if (($nb_approuves == 0 && $nb_en_attente == 0) || $veutAjouterRestaurant) : ?>

            <div class="hero min-h-screen" style="background-image: url(https://online.jwu.edu/sites/default/files/styles/article_feature_page/public/field/image/opening%20a%20restaurant.jpg);">
                <div class="hero-overlay bg-opacity-80"></div>
                <div class="hero-content flex-col lg:flex-row-reverse gap-6 my-5 lg:m-0">
                    <div class="text-center lg:text-left text-white max-w-md">
                        <?php if ($veutAjouterRestaurant || isset($_GET['nouveaurestaurateur'])) : ?>
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

            <!-- Affichage de la liste des restos -->
        <?php elseif (!$veutGererResto) : ?>

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
                <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1">Restaurants approuvés</h2>
                <div class="flex items-stretch gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
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
                <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1">Restaurants en attente d'approbation</h2>
                <div class="flex items-stretch gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
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


        <?php else : ?>

            <div class="hero bg-green min-h-[12rem] text-center">
                <div class="hero-content">
                    <div class="max-w-md">
                        <h1 class="text-5xl font-bold text-white">Gestion restaurant</h1>
                    </div>
                </div>
            </div>

            <form method="post">
                <div class="hero">
                    <div class="hero-content flex-col lg:flex-row">
                        <div>
                            <img src="<?php echo $resto_a_modifier['image']; ?>" class="h-[250px] rounded-3xl overflow-hidden w-auto mx-auto shadow-2xl" onerror="if (this.src != 'img/error.png') this.src = 'img/error.png';" />
                            <!-- Pas encore implémenté, valeurs fictives -->
                            <div class="stats shadow mt-5">
                                <div class="stat place-items-center">
                                    <div class="stat-title">Commandes</div>
                                    <div class="stat-value">0</div>
                                </div>
                                <div class="stat place-items-center">
                                    <div class="stat-title">Note</div>
                                    <div class="stat-value">4.5</div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <input type="text" value="<?php echo $resto_a_modifier['nom'] ?>" name="update_nom" class="text-5xl font-bold mb-2 input input-lg input-bordered bg-slate-100 w-full" />
                            <div tabindex="0" class="collapse collapse-arrow border border-base-300 rounded-box my-2 max-h-56 overflow-scroll">
                                <input type="checkbox" />
                                <div class="collapse-title">
                                    Catégories du restaurant
                                </div>
                                <div class="collapse-content">
                                    <div class="form-control">
                                        <?php foreach ($tags as $t) : ?>
                                            <label class="label cursor-pointer">
                                                <span class="label-text"><?php echo $t['nom_tag']; ?></span>
                                                <input type="checkbox" class="checkbox" name="tags[]" value="<?php echo $t['nom_tag'] . ":" . $t['id_tag'] ?>" <?php if (in_array($t['nom_tag'], $resto_a_modifier['tags'])) echo "checked"; ?> />
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <label for="update_image" class="label">
                                <span class="label-text">Lien vers l'image</span>
                            </label>
                            <input type="text" value="<?php echo $resto_a_modifier['image'] ?>" name="update_image" class="input input-bordered bg-slate-100 w-full" />
                            <button name="modifier" class="btn btn-success mt-5 gap-2">
                                Enregistrer
                                <svg xmlns=" http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 stroke-current">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="divider" id="menu">Gestion du menu</div>

            <div class="flex content-center justify-center mb-5">
                <label for="ajout-plat" class="btn btn-wide bg-blue text-black border-none hover:text-white mt-3">Ajouter un plat</label>
            </div>

            <?php
            $types_non_vides = array();

            foreach ($resto_a_modifier['plats'] as $p) {
                if (!in_array($p['type']['nom'], $types_non_vides))
                    array_push($types_non_vides, $p['type']['nom']);
            }
            ?>

            <?php foreach ($types_non_vides as $tnv) : ?>
                <div class="p-4 md:p-7 lg:mx-16">
                    <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1"><?php echo $tnv; ?></h2>
                    <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                        <?php foreach ($resto_a_modifier['plats'] as $p) : ?>
                            <?php if ($p['type']['nom'] == $tnv) : ?>
                                <?php include('platcard.php'); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <form method="post">
                <input type="checkbox" id="ajout-plat" class="modal-toggle" />
                <div class="modal">
                    <div class="modal-box flex flex-col gap-2">
                        <h3 class="font-bold text-lg">Ajouter un plat</h3>
                        <div>
                            <label for="nom_plat" class="label">
                                <span class="label-text">Nom du plat</span>
                            </label>
                            <input type="text" name="nom_plat" placeholder="Steak frites" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                        <div>
                            <label for="prix_plat" class="label">
                                <span class="label-text">Prix du plat</span>
                                <span class="label-text">en €</span>
                            </label>
                            <input type="number" name="prix_plat" placeholder="4,99" class="input input-bordered bg-slate-100 w-full" required min="1" max="99" step=".01" />
                        </div>
                        <div>
                            <label for="image_plat" class="label">
                                <span class="label-text">URL d'une image du plat</span>
                            </label>
                            <input type="url" name="image_plat" placeholder="https://google.com/image.jpg" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                        <select class="select select-bordered w-full max-w-xs mt-5" name="type_plat" required>
                            <option disabled selected value="">Quel est le type du plat ?</option>
                            <?php foreach ($types as $t) : ?>
                                <option value="<?php echo $t['id_type']; ?>"><?php echo $t['nom_type']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="modal-action">
                            <label for="ajout-plat" class="btn btn-ghost">Annuler</label>
                            <button class="btn" name="ajouter_plat">Ajouter</button>
                        </div>
                    </div>
                </div>
            </form>

        <?php endif; ?>

    <?php endif; ?>

    <!-- Footer -->
    <?php include('footer.php'); ?>

</body>

</html>