<?php
session_start();

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    $id_restaurant = (int)$_GET['id'];

    // On récupère les infos sur le restaurant en question
    $query = "SELECT * FROM restaurants WHERE id = '$id_restaurant'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    // On récupère ses tags
    $tags_du_resto = array();
    $query_get_tags_restaurant = "SELECT tags.nom_tag FROM restaurants_tags JOIN tags ON restaurants_tags.id_tag = tags.id WHERE restaurants_tags.id_restaurant = '$id_restaurant'";
    $result_get_tags_restaurant = mysqli_query($conn, $query_get_tags_restaurant);
    while ($tag_restaurant = mysqli_fetch_array($result_get_tags_restaurant, MYSQLI_ASSOC)) {
        array_push($tags_du_resto, $tag_restaurant['nom_tag']);
    }

    $r = array(
        'nom' => $row['nom'],
        'image' => $row['image'],
        'id' => $row['id'],
        'tags' => $tags_du_resto
    );

    echo $r[0]['nom'];
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

    <?php if ($isConnecte) : ?>
        <?php if ($hasAdresse) : ?>
            <div class="p-3 w-full text-sm font-bold text-center" id="adresse">
                Livraison ->
                <a class="text-sm text-opacity-80 font-normal ml-1 link" href="compte.php?selection=1#ouvrir-adresses">5 rue de Rivoli</a>
            </div>
        <?php else : ?>
            <div class="p-3 w-full text-sm font-bold text-center" id="adresse">
                Livraison ->
                <a class="text-sm text-opacity-80 font-normal ml-1 link text-error" href="compte.php">Vous n'avez pas encore défini d'adresse</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

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

    <?php include('footer.php'); ?>

</body>

</html>