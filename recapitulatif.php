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

    $codes_promo = array(
        "BLACKFRIDAY22" => 20,
        "FREE" => 100,
    );

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

// Après avoir cliqué sur code promo
if (isset($_POST['code_promo'])) {
    // On récupère le code qu'a entré l'utilisateur
    $code = mysqli_real_escape_string($conn, htmlspecialchars($_POST['code']));

    foreach ($codes_promo as $c => $reduc) {
        if ($code == $c && !isset($codePromo)) {
            $codePromo = $c;
            $pourcentage_remise = $reduc;
            array_push($succes, "Remise " . $codePromo . " ajoutée");
        }
    }

    if (!isset($codePromo))
        array_push($erreurs, "Code invalide");
}

if (isset($_POST['payer']) && isset($conn)) {
    do {
        $adresse = mysqli_real_escape_string($conn, htmlspecialchars($_POST['adresse']));
        $instructions = mysqli_real_escape_string($conn, htmlspecialchars($_POST['instructions']));

        $paiementEffectue = true;
    } while (0);
}

if (isset($_POST['finaliser'])) {
    $finalisationCommande = true;
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
    <!-- Librairie de confettis -->
    <script src="https://cdn.jsdelivr.net/npm/js-confetti@latest/dist/js-confetti.browser.js"></script>
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <div class="flex items-center justify-center">
        <ul class="steps mx-auto sm:w-2/3 lg:w-1/2">
            <li class="step step-primary"><a href="index.php#restos-container">Choix</a></li>
            <li class="step step-primary">Récapitulatif</li>
            <li class="step <?php if ($paiementEffectue || $finalisationCommande) echo 'step-primary'; ?>">Paiement</li>
            <li class="step <?php if ($finalisationCommande) echo 'step-primary'; ?>">Livraison</li>
        </ul>
    </div>

    <div class="divider"></div>

    <?php if ($paiementEffectue) : ?>

        <div class="flex items-center justify-center min-h-[50vh] mb-5">
            <div class="radial-progress animate-spin" style="--value:60;" id="spin"></div>
            <div class="hidden" id="check" style="opacity: 1; transition: opacity 1s;">
                <svg xmlns="http://www.w3.org/2000/svg" class="rotate-center stroke-success flex-shrink-0 h-24 w-24" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Formulaire servant simplement à indiquer au code PHP que l'animation de paiement est finie -->
        <form method="post" id="formulaire-final" class="hidden">
            <input type="hidden" name="finaliser"></input>
        </form>

        <script>
            const jsConfetti = new JSConfetti()

            setTimeout(function() {
                document.getElementById("spin").style.display = "none";
                document.getElementById("check").style.display = "block";
                jsConfetti.addConfetti();
                setTimeout(function() {
                    document.getElementById("check").style.opacity = 0;
                    setTimeout(function() {
                        document.getElementById("formulaire-final").submit();
                    }, 1000);
                }, 1500);
            }, 2000);
        </script>

    <?php elseif ($finalisationCommande) : ?>

        <h1 class="text-2xl text-center">Merci d'avoir commandé chez MealRush...</h1>

    <?php else : ?>

        <div class="md:grid grid-cols-3 gap-4 container mx-auto mb-5">

            <div class="col-span-2 flex flex-col items-center justify-center" id="recap">

                <?php
                $restos_livraison = array();

                foreach ($_SESSION['panier']['items'] as $i) {
                    if (!in_array($i['restaurant'], $restos_livraison))
                        array_push($restos_livraison, $i['restaurant']);
                }
                ?>

                <h2 class="text-2xl lg:text-3xl font-bold mb-5">Livraison de <?php echo implode(", ", $restos_livraison) ?></h2>

                <?php if ($isConnecte) : ?>
                    <?php if ($hasAdresse) : ?>
                        <div class="form-control my-3 w-2/3" id="adresse">
                            <label class="label">
                                <span class="label-text">Dès que possible au</span>
                            </label>
                            <select name="adresse" form="formulaire-payer" class="select select-bordered" id="address-select">
                                <option selected value="<?php echo $_SESSION['adresses'][0] ?>"><?php echo $_SESSION['adresses'][0] ?></option>
                                <?php for ($offset = 1; $offset < count($_SESSION['adresses']); $offset++) : ?>
                                    <option value="<?php echo $_SESSION['adresses'][$offset] ?>"><?php echo $_SESSION['adresses'][$offset] ?></option>
                                <?php endfor; ?>
                                <option value="gerer">Gérer les adresses</option>
                            </select>
                            <script>
                                document.getElementById("address-select").addEventListener("change", function() {
                                    if (this.value === "gerer") {
                                        location = "compte.php?selection=1#ouvrir-adresses";
                                    }
                                });
                            </script>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-warning shadow-lg mx-auto w-2/3">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Vous n'avez pas défini d'adresse. <a class="font-bold ml-1" href="compte.php?ajouteradresse=1&source=recapitulatif">Ajouter</a></span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php foreach ($_SESSION['panier']['items'] as $i) : ?>
                    <div class="w-2/3 flex justify-between h-24 items-center">
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

                <div class="form-control w-2/3">
                    <label class="label">
                        <span class="label-text">Instructions supplémentaires</span>
                    </label>
                    <textarea name="instructions" form="formulaire-payer" class="textarea textarea-bordered" placeholder="Beaucoup de sauce, laissez la commande devant la porte"></textarea>
                </div>

            </div>

            <div class="text-center flex flex-col justify-center w-2/3 md:w-full mx-auto md:mx-0 mt-5 md:mt-0" id="paiement">

                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <tbody>
                            <tr>
                                <th>Sous-total</th>
                                <td class="text-end"><?php echo str_replace(".", ",", $prix_total); ?>€</td>
                            </tr>
                            <?php if (isset($codePromo)) : ?>
                                <tr>
                                    <th><?php echo $codePromo; ?></th>
                                    <td class="text-end">-<?php echo str_replace(".", ",", number_format(floatval($prix_total) * ($pourcentage_remise / 100), 2)); ?>€</td>
                                </tr>
                            <?php else : ?>
                                <?php $pourcentage_remise = 0; ?>
                            <?php endif; ?>
                            <tr>
                                <th>Frais de livraison</th>
                                <?php $frais_livraison = 3.99; ?>
                                <td class="text-end"><?php echo str_replace(".", ",", $frais_livraison); ?>€</td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td class="text-end"><?php echo str_replace(".", ",", number_format(floatval($prix_total) * (1 - $pourcentage_remise / 100) + $frais_livraison, 2)); ?>€</td>
                                <?php $_SESSION['panier']['prix_final'] = number_format(floatval($prix_total) * (1 - $pourcentage_remise / 100) + $frais_livraison, 2); ?>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form method="post">
                    <div class="form-control my-3">
                        <div class="input-group w-full items-center justify-center">
                            <input type="text" name="code" placeholder="Code promo" class="input input-bordered w-full" required />
                            <button class="btn" name="code_promo">
                                Ajouter
                            </button>
                        </div>
                    </div>
                </form>

                <div class="form-control">
                    <label class="label cursor-pointer gap-3">
                        <span class="label-text">J'accepte les conditions générales de vente</span>
                        <input type="checkbox" class="checkbox" form="formulaire-payer" required />
                    </label>
                </div>

                <form id="formulaire-payer" method="post">
                    <button class="btn btn-block mt-2" name="payer" <?php if (!$hasAdresse) echo "disabled"; ?>>Payer <?php echo str_replace(".", ",", number_format(floatval($prix_total) * (1 - $pourcentage_remise / 100) + $frais_livraison, 2)); ?>€</button>
                </form>
            </div>
        </div>

    <?php endif; ?>

    <?php include('footer.php'); ?>

</body>

</html>