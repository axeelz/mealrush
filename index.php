<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="dist/output.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <title>MealRush - Vos plats livrés rapidement</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <div class="hero min-h-fit" style="background-image: url(img/main-hero.jpeg);">
        <div class="hero-overlay bg-opacity-70"></div>
        <div class="hero-content text-center text-neutral-content flex-col">
            <img src="img/logo.png" class="w-72 rounded-lg shadow-2xl mb-3" />
            <div class="max-w-md">
                <h1 class="text-5xl font-bold">Votre repas préféré livré vite fait !</h1>
                <p class="py-6">
                    Recevez votre plat sur le pas de votre porte en un rien de temps avec MealRush.
                </p>
                <a class="btn bg-white text-black hover:text-white" href="#tags-container">On mange quoi ?</a>
                <!-- <a class="btn bg-blue text-black hover:text-white ml-1" href="connexion.php">Se connecter</a> -->
            </div>
        </div>
    </div>
    <div class="" id="tags-container"></div>
    <div class="my-5 ml-5 max-w-full" id="cards-container">
        <div class="card w-1/2 shadow-xl">
            <figure><img src="https://business.ladn.eu/wp-content/uploads/2021/11/Sans-titre-3-1.jpg" alt="McDonald's" /></figure>
            <div class="card-body">
                <h2 class="card-title">McDonald's</h2>
                <p>On ne les présente plus.</p>
                <div class="card-actions justify-end">
                    <button class="btn bg-primary border-none text-black hover:text-white">Commander</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

</body>

</html>