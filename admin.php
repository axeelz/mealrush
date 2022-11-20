<?php
session_start();

// Page réservée aux admins
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] == false || $_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit();
}

include 'config/ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    // On récupère les restaurants non approuvés pour les afficher
    $restos = array();
    $query = "SELECT restaurants.nom, restaurants.image, restaurants.id, restaurants.approuve, utilisateurs.prenom, utilisateurs.nom AS nom_u, utilisateurs.email FROM restaurants JOIN utilisateurs ON restaurants.id_utilisateur = utilisateurs.id";
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
            'prenom_u' => $row['prenom'],
            'nom_u' => $row['nom_u'],
            'email_u' => $row['email'],
            'tags' => $tags_du_resto
        ));
    }

    // On récupère les utilisateurs (sauf soi même)
    $users = array();
    $moi = $_SESSION['id_utilisateur'];
    $query = "SELECT * FROM utilisateurs WHERE id != '$moi'";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        array_push($users, array(
            'prenom' => $row['prenom'],
            'nom' => $row['nom'],
            'email' => $row['email'],
            'role' => $row['role'],
            'creation' => new DateTime($row['creation']),
            'id' => $row['id']
        ));
    }
} catch (\Throwable $th) {
    array_push($erreurs, $th->getMessage());
}

// Après avoir cliqué sur approuver
if (isset($_POST['approuver']) && isset($conn)) {
    do {
        $id_restaurant_a_modif = $_POST['approuver'];
        $query = "UPDATE restaurants SET approuve = 'true' WHERE id='$id_restaurant_a_modif'";
        if (mysqli_query($conn, $query)) {
            FermerConnexion($conn);
            // On ajoute un message en variable de session pour qu'il puisse être affiché après le reload
            $_SESSION['successMessage'] = "Restaurant approuvé";
            header('location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }
    } while (0);
}

// Après avoir cliqué sur masquer
if (isset($_POST['masquer']) && isset($conn)) {
    do {
        $id_restaurant_a_modif = $_POST['masquer'];
        $query = "UPDATE restaurants SET approuve = 'false' WHERE id='$id_restaurant_a_modif'";
        if (mysqli_query($conn, $query)) {
            FermerConnexion($conn);
            // On ajoute un message en variable de session pour qu'il puisse être affiché après le reload
            $_SESSION['successMessage'] = "Restaurant masqué";
            header('location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }
    } while (0);
}

// Après avoir cliqué sur supprimer
if (isset($_POST['supprimer']) && isset($conn)) {
    do {
        $id_restaurant_a_suppr = $_POST['supprimer'];
        $query = "DELETE FROM restaurants WHERE id='$id_restaurant_a_suppr'";
        if (mysqli_query($conn, $query)) {
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

// Après avoir cliqué sur supprimer user
if (isset($_POST['supprimer_user']) && isset($conn)) {
    do {
        $id_user_a_suppr = $_POST['supprimer_user'];

        // On supprime les commandes de l'utilisateur
        $query = "DELETE FROM commandes WHERE id_utilisateur='$id_user_a_suppr'";
        // On supprime le lien entre l'adresse et l'utilisateur, puis on supprime les adresses qui ne sont liées à aucun autre utilisateur
        // et à aucune commande d'un autre utilisateur
        $query2 = "DELETE FROM utilisateurs_adresses WHERE id_utilisateur='$id_user_a_suppr'";
        $query3 = "DELETE FROM adresses WHERE id NOT IN (SELECT id_adresse FROM utilisateurs_adresses) AND id NOT IN (SELECT id_adresse FROM commandes)";
        // On supprime les restaurants de l'utilisateur (cela va aussi supprimer tous les plats du restaurant)
        $query4 = "DELETE FROM restaurants WHERE id_utilisateur='$id_user_a_suppr'";
        // Enfin, on supprime l'utilisateur
        $query5 = "DELETE FROM utilisateurs WHERE id='$id_user_a_suppr'";

        if (mysqli_query($conn, $query) && mysqli_query($conn, $query2) && mysqli_query($conn, $query3) && mysqli_query($conn, $query4) && mysqli_query($conn, $query5)) {
            FermerConnexion($conn);
            // On ajoute un message en variable de session pour qu'il puisse être affiché après le reload
            $_SESSION['successMessage'] = "Utilisateur et ses restaurants supprimés";
            header('location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }
    } while (0);
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
    <title>Admin - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('components/navbar.php'); ?>

    <div class="hero bg-green min-h-[7rem] text-center">
        <div class="hero-content">
            <div class="max-w-md">
                <h1 class="text-5xl font-bold text-white">Salut, <?php echo $_SESSION['prenom']; ?>&nbsp;!</h1>
            </div>
        </div>
    </div>

    <div class="bg-[#f2f2f2] rounded-box flex items-center p-4 justify-center shadow-lg w-fit mx-auto mt-5">
        <div class="px-2 text-center">
            <h2 class="text-3xl font-extrabold"><?php echo count($restos); ?></h2>
            <p class="text-sm text-opacity-80">Restaurants</p>
        </div>
    </div>

    <div class="p-7 lg:mx-16">
        <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1">Restaurants en attente d'approbation</h2>
        <div class="flex items-stretch gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
            <?php foreach ($restos as $r) : ?>
                <?php if ($r['approuve'] == 'false') : ?>
                    <?php $auMoinsUnRestoEnAttente = true; ?>
                    <?php include('components/restocard.php'); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php if (!isset($auMoinsUnRestoEnAttente)) : ?>
            <?php if (count($restos) > 0) : ?>
                <div class="alert shadow-lg alert-success md:w-1/2 mx-auto">
                    Tous les restaurants sont approuvés !
                </div>
            <?php else : ?>
                <div class="alert shadow-lg md:w-1/2 mx-auto">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info flex-shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Aucun restaurant en attente d'approbation</span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="p-7 lg:mx-16">
        <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1">Restaurants approuvés</h2>
        <div class="flex items-stretch gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
            <?php foreach ($restos as $r) : ?>
                <?php if ($r['approuve'] == 'true') : ?>
                    <?php $auMoinsUnRestoApprouve = true; ?>
                    <?php include('components/restocard.php'); ?>
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
        <h2 class="text-2xl font-bold md:text-3xl opacity-80 mb-5 ml-1">Utilisateurs</h2>
        <div class="flex items-center gap-4 pb-5 px-1 overflow-x-scroll">
            <?php foreach ($users as $u) : ?>
                <div class="card w-96 bg-base-100 shadow-md">
                    <div class="card-body">
                        <h2 class="card-title">
                            <?php echo $u['prenom'] . " " . $u['nom']; ?>
                        </h2>
                        <p>
                            <?php echo $u['email']; ?> <br />
                            <?php echo $u['creation']->format('d/m/Y à H:i'); ?>
                        </p>
                        <div class="card-actions justify-start">
                            <div class="badge badge-outline"><?php echo $u['role']; ?></div>
                        </div>
                        <div class="card-actions justify-end">
                            <form method="post">
                                <button class="btn btn-error" name="supprimer_user" value="<?php echo $u['id']; ?>" onClick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include('components/footer.php'); ?>

</body>

</html>