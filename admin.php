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
                    <div class="card shadow-md h-96 w-[310px] min-w-[310px] snap-center">
                        <figure class="h-44 overflow-hidden">
                            <img src="<?php echo $r['image']; ?>" alt="Image Restaurant" class="object-cover" />
                        </figure>
                        <div class="card-body">
                            <h2 class="card-title">
                                <?php echo $r['nom']; ?>
                            </h2>
                            <div class="card-actions justify-start">
                                <?php if (empty($r['tags'])) : ?>
                                    <div class="badge badge-ghost">Pas de tag</div>
                                <?php else : ?>
                                    <?php foreach ($r['tags'] as $t) : ?>
                                        <div class="badge badge-ghost"><?php echo $t; ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <p>Par <?php echo $r['prenom_u'] . " " . $r['nom_u'] . " (" . $r['email_u'] . ")"; ?></p>
                            <div class="card-actions justify-end">
                                <form method="post">
                                    <button name="supprimer" value="<?php echo $r['id']; ?>" class="btn btn-error mt-2">Supprimer</button>
                                    <button name="approuver" value="<?php echo $r['id']; ?>" class="btn btn-success mt-2">Approuver</button>
                                </form>
                            </div>
                        </div>
                    </div>
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
                    <div class="card shadow-md h-96 w-[310px] min-w-[310px] snap-center">
                        <figure class="h-44 overflow-hidden">
                            <img src="<?php echo $r['image']; ?>" alt="Image Restaurant" class="object-cover" />
                        </figure>
                        <div class="card-body">
                            <h2 class="card-title">
                                <?php echo $r['nom']; ?>
                            </h2>
                            <div class="card-actions justify-start">
                                <?php if (empty($r['tags'])) : ?>
                                    <div class="badge badge-ghost">Pas de tag</div>
                                <?php else : ?>
                                    <?php foreach ($r['tags'] as $t) : ?>
                                        <div class="badge badge-ghost"><?php echo $t; ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <p>Par <?php echo $r['prenom_u'] . " " . $r['nom_u'] . " (" . $r['email_u'] . ")"; ?></p>
                            <div class="card-actions justify-end">
                                <form method="post">
                                    <button name="supprimer" value="<?php echo $r['id']; ?>" class="btn btn-error mt-2">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php if (!isset($auMoinsUnResultat)) : ?>
            <div class="alert shadow-lg alert-error md:w-1/2 mx-auto">
                Aucun restaurant
            </div>
        <?php endif; ?>
    </div>

    <?php include('footer.php'); ?>

    <script>
        // Empêche de resoumettre le formulaire quand on refresh la page
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>

</body>

</html>