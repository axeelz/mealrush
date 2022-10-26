<?php
session_start();

// Page réservée aux admins
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] == false || $_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit;
}

include 'ouvrirconnexion.php';
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

    // On récupère les utilisateurs
    $users = array();
    $query = "SELECT * FROM utilisateurs";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        array_push($users, array(
            'prenom' => $row['prenom'],
            'nom' => $row['nom'],
            'email' => $row['email'],
            'role' => $row['role']
        ));
    }
} catch (\Throwable $th) {
    array_push($erreurs, $th->getMessage());
}

// Après avoir cliqué sur approuver
if (isset($_POST['approuver']) && isset($conn)) {
    $id_restaurant_a_modif = $_POST['approuver'];
    do {
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
    $id_restaurant_a_modif = $_POST['masquer'];
    do {
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="dist/output.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <title>Admin - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <div class="hero bg-green min-h-[7rem] text-center">
        <div class="hero-content">
            <div class="max-w-md">
                <h1 class="text-5xl font-bold text-white">Salut, <?php echo $_SESSION['prenom']; ?>&nbsp;!</h1>
            </div>
        </div>
    </div>

    <div class="bg-info rounded-box flex items-center p-4 justify-center shadow-lg w-fit mx-auto mt-5">
        <div class="px-2">
            <h2 class="text-3xl font-extrabold"><?php echo count($restos); ?></h2>
            <p class="text-sm text-opacity-80">Restaurants</p>
        </div>
    </div>

    <div class="p-7 lg:mx-16">
        <h2 class="text-2xl font-bold md:text-3xl text-slate-700 mb-5 ml-1">Restaurants en attente d'approbation</h2>
        <div class="flex items-center gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
            <?php foreach ($restos as $r) : ?>
                <?php if ($r['approuve'] == 'false') : ?>
                    <?php $auMoinsUnResultat = true; ?>
                    <?php include('restocard.php'); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php if (!isset($auMoinsUnResultat)) : ?>
            <div class="alert shadow-lg alert-success md:w-1/2 mx-auto">
                Tous les restaurants sont approuvés !
            </div>
        <?php endif; ?>
    </div>

    <div class="p-7 lg:mx-16">
        <h2 class="text-2xl font-bold md:text-3xl text-slate-700 mb-5 ml-1">Restaurants approuvés</h2>
        <div class="flex items-center gap-4 pb-5 px-1 overflow-x-scroll snap-mandatory snap-x">
            <?php foreach ($restos as $r) : ?>
                <?php if ($r['approuve'] == 'true') : ?>
                    <?php $auMoinsUnResultat = true; ?>
                    <?php include('restocard.php'); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php if (!isset($auMoinsUnResultat)) : ?>
            <div class="alert shadow-lg alert-error md:w-1/2 mx-auto">
                Aucun restaurant
            </div>
        <?php endif; ?>
    </div>

    <div class="p-7 lg:mx-16">
        <h2 class="text-2xl font-bold md:text-3xl text-slate-700 mb-5 ml-1">Utilisateurs</h2>
        <div class="flex items-center gap-4 pb-5 px-1 overflow-x-scroll">
            <?php foreach ($users as $u) : ?>
                <div class="card w-96 bg-base-100 shadow-md">
                    <div class="card-body">
                        <h2 class="card-title">
                            <?php echo $u['prenom'] . " " . $u['nom']; ?>
                        </h2>
                        <p><?php echo $u['email']; ?></p>
                        <div class="card-actions justify-start">
                            <div class="badge badge-outline"><?php echo $u['role']; ?></div>
                        </div>
                        <div class="card-actions justify-end">
                            <form method="post">
                                <button class="btn btn-error" name="supprimer_user" value="<?php echo $u['id']; ?>">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include('footer.php'); ?>

</body>

</html>