<?php
session_start();
if (isset($_SESSION['connecte']) && $_SESSION['connecte'] == true) {
    header("location: index.php");
    exit;
}

include 'ouvrirconnexion.php';
try {
    $conn = OuvrirConnexion();
} catch (\Throwable $th) {
    array_push($erreurs, $th->getMessage());
}

if (isset($_POST['login'])) {
    do {
        // On récupère les valeurs du formulaire
        $email = mysqli_real_escape_string($conn, htmlspecialchars($_POST['email']));
        $mdp = mysqli_real_escape_string($conn, htmlspecialchars($_POST['mdp']));

        // On cherche dans la base de données une entrée correspondante à l'utilisateur
        $query = "SELECT * FROM utilisateurs WHERE email='$email'";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);

        // Soit on a bien trouvé une valeur, soit on renvoie false
        $mdpBDD = $row['mdp'] ?? false;

        if ($mdpBDD == false) {
            // Aucun compte avec cette adresse e-mail
            array_push($erreurs, "E-mail ou mot de passe invalide");
            break;
        }

        $prenom = $row['prenom'];
        $nom = $row['nom'];
        $role = $row['role'];
        $id_utilisateur = $row['id'];
        $creation = $row['creation'];

        // Puis on vérifie que le mot de passe hashé contenu dans la base de données
        // correspond bien avec ce que l'utilisateur a entré
        if (password_verify($mdp, $mdpBDD)) {
            FermerConnexion($conn);
            // On définit les variables de session et on redirige vers la page d'accueil
            $_SESSION['connecte'] = true;
            $_SESSION['email'] = $email;
            $_SESSION['prenom'] = $prenom;
            $_SESSION['nom'] = $nom;
            $_SESSION['role'] = $role;
            $_SESSION['id_utilisateur'] = $id_utilisateur;
            $_SESSION['creation'] = $creation;
            // On ajoute un message en variable de session pour qu'il puisse être affiché sur la page suivante
            $_SESSION['successMessage'] = "Bienvenue, " . $_SESSION['prenom'] . " " . $_SESSION['nom'];
            header('location: index.php');
            exit();
        } else {
            // Mot de passe invalide (mais on le dit pas)
            array_push($erreurs, "E-mail ou mot de passe invalide");
        }
    } while (0);
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
            <h1 class="text-xl font-bold md:text-2xl mb-5">
                Connectez vous à votre compte
            </h1>
            <form class="form-control w-full max-w-xs md:max-w-md" method="post">
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