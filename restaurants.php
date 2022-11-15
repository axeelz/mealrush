<?php
session_start();

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    $id_restaurant = (int)$_GET['id'];

    if (empty($id_restaurant)) {
        header("location: index.php");
        exit();
    }

    // On récupère les infos sur le restaurant en question
    $query = "SELECT * FROM restaurants WHERE id = '$id_restaurant'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    // Si aucun resultat, on redirige vers l'accueil
    if (!$row) {
        header("location: index.php");
        exit();
    }

    // On récupère ses tags
    $tags_du_resto = array();
    $query_get_tags_restaurant = "SELECT tags.nom_tag FROM restaurants_tags JOIN tags ON restaurants_tags.id_tag = tags.id WHERE restaurants_tags.id_restaurant = '$id_restaurant'";
    $result_get_tags_restaurant = mysqli_query($conn, $query_get_tags_restaurant);
    while ($tag_restaurant = mysqli_fetch_array($result_get_tags_restaurant, MYSQLI_ASSOC)) {
        array_push($tags_du_resto, $tag_restaurant['nom_tag']);
    }

    // On récupère ses plats et le type de ses plats
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

    $r = array(
        'nom' => $row['nom'],
        'image' => $row['image'],
        'id' => $row['id'],
        'tags' => $tags_du_resto,
        'plats' => $plats_du_resto
    );

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
    <title><?php echo $r['nom']; ?> - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <div class="hero">
        <div class="hero-content flex-col lg:flex-row">
            <img src="<?php echo $r['image']; ?>" class="h-[250px] rounded-3xl overflow-hidden w-auto mx-auto shadow-2xl" onerror="if (this.src != 'img/error.png') this.src = 'img/error.png';" />
            <div>
                <h1 class="text-5xl font-bold mb-2"><?php echo $r['nom']; ?></h1>
                <div>
                    <?php foreach ($r['tags'] as $t) : ?>
                        <div class="badge badge-ghost"><?php echo $t; ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="stats shadow mt-4 text-center">

                    <div class="stat">
                        <div class="stat-title">Noté</div>
                        <div class="stat-value">
                            <script>
                                document.write((Math.random() * (5 - 3) + 3).toFixed(1));
                            </script>
                        </div>
                        <div class="stat-desc">/ 5</div>
                    </div>

                    <div class="stat">
                        <div class="stat-title">Livré en</div>
                        <div class="stat-value">
                            <script>
                                document.write(Math.round(Math.random() * (30 - 5) + 5));
                            </script>
                        </div>
                        <div class="stat-desc">minutes</div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="divider">Menu de <?php echo $r['nom']; ?></div>

    <?php
    $types_non_vides = array();

    foreach ($r['plats'] as $p) {
        if (!in_array($p['type']['nom'], $types_non_vides))
            array_push($types_non_vides, $p['type']['nom']);
    }
    ?>

    <?php foreach ($types_non_vides as $tnv) : ?>
        <div class="p-7 lg:mx-16">
            <h2 class="text-2xl font-bold md:text-3xl text-slate-700 mb-5 ml-1"><?php echo $tnv; ?></h2>
            <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                <?php foreach ($r['plats'] as $p) : ?>
                    <?php if ($p['type']['nom'] == $tnv) : ?>
                        <?php include('platcard.php'); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Footer -->
    <?php include('footer.php'); ?>

</body>

</html>