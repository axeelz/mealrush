<?php
session_start();
if ($_SESSION["connecte"]) {
    header("location: index.php");
    exit;
}

include 'ouvrirconnexion.php';
try {
    $conn = OuvrirConnexion();
} catch (Exception $th) {
    array_push($erreurs, $th->getMessage());
}

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, htmlspecialchars($_POST['email']));
    $mdp = mysqli_real_escape_string($conn, htmlspecialchars($_POST['mdp']));
    $mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);

    $query = "SELECT * FROM utilisateurs WHERE email='$email' AND mdp='$mdp_hash'";
    $result = mysqli_query($conn, $query);
    // $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $count = mysqli_num_rows($result);

    if ($count == 1) {
        array_push($succes, "Connecté");
        // session start
    } else {
        array_push($erreurs, "Nom d'utilisateur ou mot de passe invalide");
    }
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
    <title>Connexion - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen bg-base-200">

    <?php include('navbar.php'); ?>

    <div class="flex align-middle justify-center">
        <div class="rounded-xl shadow-xl bg-base-100 p-10 m-5 lg:m-10 lg:w-1/3">
            <img src="img/logo-blanc.png" alt="" class="w-64 mx-auto">
            <div class="divider"></div>
            <h1 class="text-xl font-bold text-stale-900 md:text-2xl mb-5">
                Connectez vous à votre compte
            </h1>
            <form class="form-control w-full max-w-xs md:max-w-md" method="POST" action="#">
                <label for="email" class="label">
                    <span class="label-text">Votre adresse e-mail</span>
                </label>
                <input type="email" name="email" id="email" placeholder="nom@domaine.com" class="input input-bordered bg-slate-100 w-full mb-5" required />
                <label for="mdp" class="label">
                    <span class="label-text">Votre mot de passe</span>
                </label>
                <input type="password" name="mdp" id="mdp" placeholder="••••••••" class="input input-bordered bg-slate-100 w-full mb-5" required />
                <a href="" class="link link-hover text-center">Mot de passe oublié ?</a>
                <button class="btn btn-block bg-primary border-none hover:text-white text-black my-5" name="login">Se connecter</button>
                <script>
                    const boutonConnexion = document.getElementsByName("login")[0];

                    boutonConnexion.addEventListener("click", () => {
                        if (document.getElementById("email").value.trim().length != 0 && document.getElementById("mdp").value.trim().length != 0 && document.getElementById("email").checkValidity()) {
                            boutonConnexion.classList.toggle("loading");
                        }
                    });
                </script>
                <p>Pas encore de compte ?
                    <a href="creation.php" class="link font-bold">Créez en un !</a>
                </p>
            </form>
        </div>
    </div>

    <?php include('footer.php'); ?>

</body>

</html>