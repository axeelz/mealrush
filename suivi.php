<?php
session_start();

if (!(isset($_SESSION['connecte']) && $_SESSION['connecte'] == true)) {
    header("location: index.php");
    exit();
}

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    $id_utilisateur = $_SESSION['id_utilisateur'];

    // Si on veut afficher les détails d'une commande en particulier (dont l'id est donné en paramètre d'URL)
    if (isset($_GET['id'])) {
        $id_commande = (int)$_GET['id'];
        $query = "SELECT * FROM commandes WHERE id_utilisateur='$id_utilisateur' AND id='$id_commande' ORDER BY id DESC";
    } else {
        // Sinon, on récupère la dernière commande de l'utilisateur
        $query = "SELECT * FROM commandes WHERE id_utilisateur='$id_utilisateur' ORDER BY id DESC";
    }

    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    // Si aucune commande correspondante trouvée
    if ($row == false) {
        header("location: commandes.php");
        exit();
    }

    // On récupère le panier de l'utilisateur
    $panier = json_decode($row['panier'], true);

    // On vérifie si la commande a été livrée
    $livraison = new DateTime($row['livraison']);
    $maintenant = new DateTime();
    if ($livraison < $maintenant) {
        $terminee = true;
    } else {
        $terminee = false;
    }

    // On récupère le jour et l'heure de l'enregistrement de la commande
    $date_commande = explode(" ", $row['enregistrement'])[0];
    $heure_commande = explode(" ", $row['enregistrement'])[1];

    // On récupère l'heure de livraison estimée
    $dateheure_livraison = $row['livraison'];
    // On récupère le jour et l'heure de la livraison
    $date_livraison = explode(" ", $dateheure_livraison)[0];
    $heure_livraison = explode(" ", $dateheure_livraison)[1];

    $itemsPayes = $panier['items'];
    $prixFinal = $panier['prix_final'];
    $prixTotal = $panier['prix_total'];
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
    <title>Suivi de votre commande - MealRush</title>
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
            <li class="step step-primary">Paiement</li>
            <li class="step step-primary">Livraison</li>
        </ul>
    </div>

    <div class="divider"></div>

    <div>

        <h1 class="text-2xl text-center">Merci d'avoir commandé chez MealRush&nbsp;!</h1>

        <div class="p-2 mx-auto w-fit grid grid-flow-col gap-5 text-center auto-cols-max items-center" id="delivery-container">
            <?php if (!$terminee) : ?>
                <span>Livraison estimée dans</span>
                <div class="flex gap-5 opacity-0 transition-all duration-500" id="countdown-container">
                    <div>
                        <span class="countdown text-3xl">
                            <span style="--value: 30;" id="min"></span>
                        </span>
                        min
                    </div>
                    <div>
                        <span class="countdown text-3xl">
                            <span style="--value: 00;" id="sec"></span>
                        </span>
                        sec
                    </div>
                </div>
                <script>
                    // On génère un temps de livraison aléatoire entre 10 et 30 minutes dans la base de données,
                    // on fait ici le compte à rebours entre maintenant et cette date

                    var countDownDate = new Date("<?php echo $dateheure_livraison; ?>").getTime();
                    const jsConfetti = new JSConfetti();

                    var x = setInterval(function() {
                        if (document.getElementById("countdown-container").classList.contains("opacity-0")) {
                            document.getElementById("countdown-container").classList.remove("opacity-0");
                            document.getElementById("countdown-container").classList.add("opacity-100");
                        }

                        var now = new Date().getTime();
                        var distance = countDownDate - now;

                        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        document.getElementById("min").style.setProperty('--value', minutes);
                        document.getElementById("sec").style.setProperty('--value', seconds);

                        if (distance < 0) {
                            clearInterval(x);
                            document.getElementById("delivery-container").innerHTML = "Rendez vous à votre porte";
                            jsConfetti.addConfetti();
                        }
                    }, 1000);
                </script>
            <?php else : ?>
                <span>Commande livrée le <?php echo date("d/m/Y", strtotime($date_livraison)) . " à " . date('H:i', strtotime($heure_livraison)); ?></span>
            <?php endif; ?>
        </div>

        <div class="min-h-[50vh] max-w-xs mx-auto shadow-lg p-5 my-10 flex flex-1 flex-col rounded-lg">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto stroke-success flex-shrink-0 h-12 w-12" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="mt-3 mb-5 text-center">
                <h2 class="text-xl font-bold">Commande confirmée&nbsp;!</h2>
                <p>Le <?php echo date("d/m/Y", strtotime($date_commande)) . " à " . date('H:i', strtotime($heure_commande)); ?></p>
            </div>
            <?php foreach ($itemsPayes as $i) : ?>
                <div class="flex justify-between p-3">
                    <div>
                        <span class="badge badge-xl badge-outline"><?php echo $i['quantite']; ?></span>
                        <?php echo $i['nom']; ?>
                    </div>
                    <div>
                        <?php echo str_replace(".", ",", $i['prix'] * $i['quantite']); ?>€
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="flex justify-between p-3">
                <div>
                    Frais
                </div>
                <div>
                    <?php echo str_replace(".", ",", number_format($prixFinal - $prixTotal, 2)); ?>€
                </div>
            </div>
            <div class="flex justify-between p-3 mt-auto text-xl">
                <div class="">
                    Total
                </div>
                <div>
                    <?php echo str_replace(".", ",", $prixFinal); ?>€
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <?php include('footer.php'); ?>

</body>

</html>