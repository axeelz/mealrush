<?php
session_start();
if (!isset($_SESSION['connecte']) || $_SESSION['connecte'] == false) {
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
    <title>Commandes - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <div class="hero bg-green min-h-[12rem] text-center">
        <div class="hero-content">
            <div class="max-w-md">
                <h1 class="text-5xl font-bold text-white">Vos commandes</h1>
            </div>
        </div>
    </div>

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
                    </tr>
                </thead>
                <tbody>
                    <!-- row 1 -->
                    <tr>
                        <th>1</th>
                        <td>
                            <div class="flex items-center space-x-3">
                                <div class="avatar">
                                    <div class="mask mask-squircle w-12 h-12">
                                        <img src="" alt="Image du restaurant" />
                                    </div>
                                </div>
                                <div>
                                    <div class="font-bold">McDonald's</div>
                                    <div class="text-sm opacity-60">3 articles</div>
                                </div>
                            </div>
                        </td>
                        <td class="font-bold">16,99€</td>
                        <td class="opacity-60">05/11/2022 à 11:22</td>
                        <td><span class="badge badge-ghost">Livraison dans 12mn</span></td>
                    </tr>
                    <!-- row 2 -->
                    <tr>
                        <th>2</th>
                        <td>
                            <div class="flex items-center space-x-3">
                                <div class="avatar">
                                    <div class="mask mask-squircle w-12 h-12">
                                        <img src="" alt="Image du restaurant" />
                                    </div>
                                </div>
                                <div>
                                    <div class="font-bold">Pizza Hut</div>
                                    <div class="text-sm opacity-60">6 articles</div>
                                </div>
                            </div>
                        </td>
                        <td class="font-bold">59,31€</td>
                        <td class="opacity-60">05/11/2022 à 22:11</td>
                        <td><span class="badge badge-success">Réceptionnée</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>


    <!-- Footer -->
    <?php include('footer.php'); ?>

</body>

</html>