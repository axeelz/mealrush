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

    $id_utilisateur = $_SESSION['id_utilisateur'];

    // Récupérer les adresses
    $query = "SELECT * FROM `utilisateurs_adresses` WHERE `id_utilisateur` = '$id_utilisateur'";
    $veutAjouterAdresse = isset($_GET['ajouteradresse']);
    $result = mysqli_query($conn, $query);
    $count = mysqli_num_rows($result);

    // Si l'utilisateur n'a pas d'adresse
    if ($count == 0) {
        $hasAdresse = false;
    } else {
        $hasAdresse = true;
        $_SESSION['adresses'] = array();

        $query = "SELECT adresses.rue, adresses.numero, adresses.code_postal, adresses.ville, adresses.pays FROM utilisateurs_adresses JOIN adresses ON utilisateurs_adresses.id_adresse = adresses.id WHERE utilisateurs_adresses.id_utilisateur = '$id_utilisateur'";
        $result = mysqli_query($conn, $query);
        $count = mysqli_num_rows($result);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            array_push($_SESSION['adresses'], $row["numero"] . " " . lcfirst($row["rue"]) . ", " . $row["code_postal"] . ", " . $row["ville"] . ", " . $row["pays"]);
        }
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
    <title>Commande - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <div class="p-7 lg:mx-16 flex flex-col justify-center items-center container">

        <ul class="steps mx-auto w-1/2">
            <li class="step step-primary"><a href="index.php#restos-container">Choix</a></li>
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

        <?php if ($isConnecte) : ?>
            <?php if ($hasAdresse) : ?>
                <div class="p-3 w-full text-sm font-bold text-center" id="adresse">
                    Destination ->
                    <a class="text-sm text-opacity-80 font-normal ml-1 link" href="compte.php?selection=1#ouvrir-adresses"><?php echo $_SESSION['adresses'][0] ?></a>
                </div>
            <?php else : ?>
                <div class="p-3 w-full text-sm font-bold text-center" id="adresse">
                    Destination ->
                    <a class="text-sm text-opacity-80 font-normal ml-1 link text-error" href="compte.php?ajouteradresse=1">Vous n'avez pas encore défini d'adresse</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

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