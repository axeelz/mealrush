<?php
session_start();

if ($_SESSION['panier']['nb_total'] == 0) {
    header("location: index.php");
    exit();
}

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();
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
    <title>Commande - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <div class="p-7 lg:mx-16 flex flex-col justify-center items-center container">

        <ul class="steps mx-auto w-1/2">
            <li class="step step-primary">Choix</li>
            <li class="step step-primary">Récapitulatif</li>
            <li class="step">Paiement</li>
            <li class="step">Livraison</li>
        </ul>

        <div class="divider"></div>

        <?php
        $restos_livraison = array();

        foreach ($_SESSION['panier']['items'] as $i) {
            if (!in_array($i['restaurant'], $restos_livraison))
                array_push($restos_livraison, $i['restaurant']);
        }
        ?>

        <h2 class="text-3xl font-bold mb-5">Livraison de <?php echo implode(", ", $restos_livraison) ?></h2>

        <!-- TODO
        Adresse
        Estimation de temps de livraison
        Rappeler le prix
        Payer -->

        <?php foreach ($_SESSION['panier']['items'] as $i) : ?>
            <div class="w-1/2 flex justify-between h-24 items-center">
                <p class="text-lg">
                    <?php echo $i['nom']; ?>
                    <span class="badge badge-xl badge-outline"><?php echo $i['quantite']; ?></span><br />
                    <?php echo str_replace(".", ",", $i['prix']); ?>€
                </p>
                <form method="post">
                    <div class="gap-2">
                        <button name="moins1" class="btn btn-circle btn-outline btn-sm" value="<?php echo $i['id']; ?>">
                            -
                        </button>
                        <button name="plus1" class="btn btn-circle btn-outline btn-sm" value="<?php echo $i['id']; ?>">
                            +
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="form-control w-1/2">
            <label class="label">
                <span class="label-text">Instructions supplémentaires</span>
            </label>
            <textarea class="textarea textarea-bordered" placeholder="Beaucoup de sauce, laissez la commande devant la porte"></textarea>
        </div>

    </div>

    <?php include('footer.php'); ?>

</body>

</html>