<?php
session_start();
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] == false) {
    header("location: index.php");
    exit();
}

include 'config/ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    $id_utilisateur = $_SESSION['id_utilisateur'];
    $commandes = array();

    // On récupère la dernière commande de l'utilisateur
    $query = "SELECT * FROM commandes WHERE id_utilisateur='$id_utilisateur' ORDER BY id DESC";
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $panier = json_decode($row['panier'], true);

        // On vérifie si la commande a été livrée
        $livraison = new DateTime($row['livraison']);
        $maintenant = new DateTime();

        // On récupère les restaurants figurant dans la commande
        $restos_livraison = array();
        foreach ($panier['items'] as $i) {
            if (!in_array($i['restaurant'], $restos_livraison))
                array_push($restos_livraison, $i['restaurant']);
        }

        // On récupère les images des restos
        $images = array();
        foreach ($restos_livraison as $r) {
            $r = mysqli_real_escape_string($conn, $r);
            $query_get_img = "SELECT `image` FROM `restaurants` WHERE nom='$r' LIMIT 1";
            $result_get_img = mysqli_query($conn, $query_get_img);
            $data_resto = mysqli_fetch_assoc($result_get_img);
            array_push($images, $data_resto['image']);
        }


        if ($livraison < $maintenant) {
            $terminee = true;
        } else {
            $terminee = false;
        }

        array_push($commandes, array(
            "heure_commande" => "Le " . date("d/m/Y", strtotime(explode(" ", $row['enregistrement'])[0])) . " à " . date('H:i', strtotime(explode(" ", $row['enregistrement'])[1])),
            "heure_livraison" =>  $row['livraison'],
            "nb_items" => $panier['nb_total'],
            "montant" => str_replace(".", ",", $row['montant']),
            "restaurant" => implode(", ", $restos_livraison),
            "terminee" => $terminee,
            "id" => $row['id'],
            "images" => $images
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
    <title>Commandes - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('components/navbar.php'); ?>

    <div class="hero bg-green min-h-[12rem] text-center">
        <div class="hero-content">
            <div class="max-w-md">
                <h1 class="text-3xl md:text-5xl font-bold text-white">Mes commandes</h1>
            </div>
        </div>
    </div>

    <?php if (empty($commandes)) : ?>

        <div class="flex flex-col justify-center items-center min-h-[50vh] max-w-xs mx-auto">
            <h2 class="text-center font-bold text-xl">Vous n'avez pas encore commandé</h2>
            <a href="index.php" class="btn btn-ghost mt-3">Passez votre première commande&nbsp;!</a>
        </div>

    <?php else : ?>

        <div class="p-5 md:p-10">

            <div class="overflow-x-auto">
                <table class="table w-full">
                    <!-- head -->
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Restaurant</th>
                            <th>Montant</th>
                            <th>Date et heure</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $c) : ?>
                            <tr>
                                <th><?php echo $c['id']; ?></th>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <div class="avatar">
                                            <div class="mask mask-squircle w-12 h-12">
                                                <?php foreach ($c['images'] as $i) : ?>
                                                    <img src="<?php echo $i; ?>" alt="Image du restaurant" class="rounded" onerror="if (this.src != 'img/error.png') this.src = 'img/error.png';" />
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold"><?php echo $c['restaurant']; ?></div>
                                            <div class="text-sm opacity-60"><?php echo $c['nb_items']; ?> plats</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-bold"><?php echo $c['montant']; ?>€</td>
                                <td class="opacity-60"><?php echo $c['heure_commande']; ?></td>
                                <?php if ($c['terminee']) : ?>
                                    <td>
                                        <span class="badge badge-success">
                                            Livrée
                                        </span>
                                    </td>
                                <?php else : ?>
                                    <td>
                                        <span class="badge badge-ghost">
                                            Livraison dans
                                            <span class="countdown ml-1">
                                                <span style="--value: 30;" id="min-<?php echo $c['id']; ?>"></span>
                                            </span>
                                            min
                                        </span>
                                    </td>
                                    <script>
                                        // On fait le compte à rebours entre maintenant et l'heure de livraison
                                        setInterval(function() {
                                            var now = new Date().getTime();

                                            var distance = new Date("<?php echo $c['heure_livraison']; ?>").getTime() - now;

                                            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

                                            document.getElementById("min-<?php echo $c['id']; ?>").style.setProperty('--value', minutes);

                                            if (distance < 0) {
                                                location.reload();
                                            }
                                        }, 1000);
                                    </script>
                                <?php endif; ?>
                                <td>
                                    <a href="suivi.php?id=<?php echo $c['id']; ?>" class="btn btn-ghost btn-sm">Détails</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

    <?php endif; ?>

    <!-- Footer -->
    <?php include('components/footer.php'); ?>

</body>

</html>