<?php
session_start();
if (isset($_SESSION['connecte']) && $_SESSION['connecte'] == true) {
    header("location: index.php");
    exit();
}

include 'ouvrirconnexion.php';
try {
    // On se connecte à la BDD
    $conn = OuvrirConnexion();

    // On récupère les tags des resto pour les afficher
    $tags = array();
    $query = "SELECT * FROM tags";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        array_push($tags, array(
            'nom_tag' => $row["nom_tag"],
            'id_tag' => $row["id"]
        ));
    }
} catch (\Throwable $th) {
    array_push($erreurs, $th->getMessage());
}

// Après avoir cliqué sur création de compte
if (isset($_POST['signup']) && isset($conn)) {
    do {
        // On récupère les valeurs du formulaire
        $prenom = mysqli_real_escape_string($conn, htmlspecialchars($_POST['prenom']));
        $nom = mysqli_real_escape_string($conn, htmlspecialchars($_POST['nom']));
        $email = mysqli_real_escape_string($conn, htmlspecialchars($_POST['email']));
        $mdp = mysqli_real_escape_string($conn, htmlspecialchars($_POST['mdp']));
        $mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);

        if (empty($prenom) || empty($nom) || empty($email) || empty($mdp)) {
            array_push($erreurs, "Un des champs requis est vide");
            break;
        }

        // Récupération du rôle et verif qu'il est un de ces deux choix (car on peut le modifier en inspectant l'élement)
        $role = $_POST['role'];
        if ($role != 'utilisateur' && $role != 'restaurateur') {
            array_push($erreurs, "Le rôle n'est pas correct");
            break;
        }

        // Verification que l'e-mail est pas déjà dans la BDD
        $query = "SELECT * FROM utilisateurs WHERE email='$email'";
        $result = mysqli_query($conn, $query);
        $count = mysqli_num_rows($result);

        // Si le mail est déjà dans la BDD
        if ($count > 0) {
            array_push($erreurs, "Cet e-mail est déjà utilisé");
            break;
        }

        // Insertion d'un nouveau compte
        $query = "INSERT INTO `utilisateurs` (`prenom`, `nom`, `email`, `mdp`, `role`) VALUES ('$prenom', '$nom', '$email', '$mdp_hash', '$role')";
        if (mysqli_query($conn, $query)) {
            // On récupère l'id utilisateur de l'utilisateur qu'on vient d'ajouter
            // car on en a besoin pour lier un restaurateur à un utilisateur
            $id_utilisateur = mysqli_insert_id($conn);
            $creation = date("Y-m-d H:i:s"); // date de création
            // Compté crée !
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }

        // Si restaurateur, on ajoute également un nouveau restaurant
        if ($role == 'restaurateur') {
            // On récupère les valeurs du formulaire
            $nom_resto = mysqli_real_escape_string($conn, htmlspecialchars($_POST['nom_resto']));
            $image_resto = mysqli_real_escape_string($conn, htmlspecialchars($_POST['image_resto']));

            // Si l'insertion du compte utilisateur a pas fonctionné, on stop
            if (!isset($id_utilisateur)) {
                array_push($erreurs, "Erreur lors de la création de l'utilisateur, impossible de créer le restaurant");
                break;
            }

            // Si un des champs est vide, on stop
            if (empty($nom_resto) || empty($image_resto)) {
                array_push($erreurs, "Un des champs requis est vide");
                break;
            }

            // Si tout est bon, on crée un restaurant
            $query = "INSERT INTO `restaurants` (`nom`, `image`, `id_utilisateur`) VALUES ('$nom_resto', '$image_resto', '$id_utilisateur')";
            if (mysqli_query($conn, $query)) {
                $id_restaurant = mysqli_insert_id($conn);
                // Restaurant crée !
            } else {
                array_push($erreurs, mysqli_error($conn));
                break;
            }

            // Ajout des tags correspondants aux restos dans la BDD
            $checkboxes = $_POST['tags'];

            // Pour chaque tag coché
            foreach ($checkboxes as $c) {
                $tag_nom = explode(":", $c)[0];
                $tag_id = explode(":", $c)[1];

                // Vérification que le tag est bien dans la BDD (et qu'il a pas été modifié avec inspecter l'élément)
                $result = mysqli_query($conn, "SELECT * FROM `tags` WHERE nom_tag='$tag_nom' AND id='$tag_id'");
                $count = mysqli_num_rows($result);

                if ($count == 1) {
                    $query = "INSERT INTO `restaurants_tags` (`id_restaurant`, `id_tag`) VALUES ('$id_restaurant', '$tag_id')";
                    if (!mysqli_query($conn, $query)) {
                        array_push($erreurs, "Impossible d'ajouter les tags");
                        break;
                    }
                } else {
                    array_push($erreurs, $tag_nom . " n'est pas un tag reconnu");
                    break;
                }
            }
        }

        FermerConnexion($conn);

        // On prépare l'email à envoyer à l'utilisateur
        $destinataire = $email;
        $sujet_mail = "Bienvenue sur MealRush !";
        $contenu_mail = "<h4>Bienvenue, " . $prenom . "</h4><p>Vous venez de créer un compte chez nous, vous pouvez dès maintenant commander et vous régaler !</p>";

        // On utilise exec pour effectuer la tache en arrière plan afin de ne pas bloquer le chargement de la page pour l'utilisateur
        exec(PHP_BINDIR . "/php " . realpath("email.php") . " '" . $sujet_mail . "' '" . $contenu_mail . "' '" . $destinataire . "' 2>&1 &", $output);

        // On définit les variables de session et on redirige vers une page
        $_SESSION['connecte'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['prenom'] = $prenom;
        $_SESSION['nom'] = $nom;
        $_SESSION['role'] = $role;
        $_SESSION['id_utilisateur'] = $id_utilisateur;
        $_SESSION['creation'] = $creation;

        // Si l'utilisateur se crée un compte au moment de payer sa commande, on le renvoie directement
        // vers sa commande au lieu de vers la page d'accueil.
        if ($_GET['source'] == 'recapitulatif') {
            $_SESSION['successMessage'] = "Compte crée";
            header('location: recapitulatif.php');
            exit();
        }

        // S'il se crée un compte depuis une autre page...
        if ($role == 'restaurateur') {
            $_SESSION['successMessage'] = "Compte et restaurant crées";
            header('location: restaurateur.php');
            exit();
        } else {
            $_SESSION['successMessage'] = "Compte crée";
            header('location: compte.php?ajouteradresse=1&source=creation');
            exit();
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
    <title>Création de compte - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen bg-base-200">

    <?php include('navbar.php'); ?>

    <div class="flex align-middle justify-center">
        <div class="rounded-xl shadow-xl bg-base-100 p-10 m-5 lg:m-10 lg:w-1/3">
            <img src="img/logo-blanc.png" alt="" class="w-64 mx-auto">
            <div class="divider"></div>
            <h1 class="text-xl font-bold md:text-2xl mb-5">
                Créer un compte
            </h1>
            <form class="form-control w-full max-w-xs md:max-w-md" method="post" autocomplete="new-password" id="formulaire-creation">
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div id="row-1">
                        <label for="prenom" class="label">
                            <span class="label-text">Votre prénom</span>
                        </label>
                        <input type="text" name="prenom" id="prenom" placeholder="Meal" class="input input-bordered bg-slate-100 w-full" required />
                    </div>
                    <div id="row-2">
                        <label for="nom" class="label">
                            <span class="label-text">Votre nom</span>
                        </label>
                        <input type="text" name="nom" id="nom" placeholder="Rush" class="input input-bordered bg-slate-100 w-full" required />
                    </div>
                </div>
                <label for="email" class="label">
                    <span class="label-text">Votre adresse e-mail</span>
                </label>
                <input type="email" name="email" id="email" placeholder="nom@domaine.com" class="input input-bordered bg-slate-100 w-full mb-5" required />
                <label for="mdp" class="label">
                    <span class="label-text">Votre mot de passe</span>
                </label>
                <input type="password" name="mdp" id="mdp" placeholder="••••••••" class="input input-bordered bg-slate-100 w-full mb-2" onChange="validationMdp()" required />
                <label for="confirmation" class="label">
                    <span class="label-text">Confirmer le mot de passe</span>
                </label>
                <input type="password" name="confirmation" id="confirmation" placeholder="••••••••" class="input input-bordered bg-slate-100 w-full mb-5" onChange="validationMdp()" required />
                <!-- Vérifier que les mdp match -->
                <script>
                    function validationMdp() {
                        const password = document.querySelector('input[name=mdp]');
                        const confirm = document.querySelector('input[name=confirmation]');
                        if (confirm.value === password.value) {
                            confirm.setCustomValidity('');
                        } else {
                            confirm.setCustomValidity('Les mots de passe sont différents');
                        }
                    }
                </script>
                <!-- Toggle role -->
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">Je suis utilsateur</span>
                        <input type="radio" name="role" value="utilisateur" id="utilisateur" class="radio" checked />
                    </label>
                    <label class="label cursor-pointer">
                        <span class="label-text">Je suis restaurateur</span>
                        <input type="radio" name="role" value="restaurateur" id="restaurateur" class="radio" />
                    </label>
                </div>
                <!-- Formulaire restaurateur -->
                <div id="formulaire_restaurateur" class="hidden">
                    <h2 class="text-xl font-bold md:text-2xl my-5">
                        Informations sur le restaurant
                    </h2>
                    <label for="nom_resto" class="label">
                        <span class="label-text">Nom du restaurant</span>
                    </label>
                    <input type="text" name="nom_resto" id="nom_resto" placeholder="McDonald's" class="input input-bordered bg-slate-100 w-full mb-5" />
                    <label for="image_resto" class="label">
                        <span class="label-text">URL d'une image du restaurant</span>
                    </label>
                    <input type="url" name="image_resto" id="image_resto" placeholder="https://google.com/image.jpg" class="input input-bordered bg-slate-100 w-full mb-5" />
                    <div tabindex="0" class="collapse collapse-arrow border border-base-300 rounded-box">
                        <input type="checkbox" />
                        <div class="collapse-title">
                            A quelles catégories correspond votre restaurant&nbsp;?
                        </div>
                        <div class="collapse-content">
                            <div class="form-control">
                                <?php foreach ($tags as $t) : ?>
                                    <label class="label cursor-pointer">
                                        <span class="label-text"><?php echo $t['nom_tag']; ?></span>
                                        <input type="checkbox" class="checkbox" name="tags[]" value="<?php echo $t['nom_tag'] . ":" . $t['id_tag'] ?>" />
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-5">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current flex-shrink-0 w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Il y aura une vérification de votre restaurant avant sa publication</span>
                        </div>
                    </div>
                </div>
                <script>
                    const formulaire_restaurateur = document.getElementById('formulaire_restaurateur');

                    function gererClickRadio() {
                        if (document.getElementById('restaurateur').checked) {
                            formulaire_restaurateur.style.display = 'block';
                            document.getElementById("nom_resto").required = true;
                            document.getElementById("image_resto").required = true;
                        } else {
                            formulaire_restaurateur.style.display = 'none';
                            document.getElementById("nom_resto").required = false;
                            document.getElementById("image_resto").required = false;
                        }
                    }

                    const boutonsRadio = document.querySelectorAll('input[name="role"]');
                    boutonsRadio.forEach(radio => {
                        radio.addEventListener('click', gererClickRadio);
                    });
                </script>
                <button class="btn btn-block bg-primary border-none hover:text-white text-black my-5" name="signup">Créer mon compte</button>
                <script>
                    const boutonConnexion = document.getElementsByName("signup")[0];

                    boutonConnexion.addEventListener("click", () => {
                        if (document.getElementById("formulaire-creation").checkValidity()) {
                            boutonConnexion.classList.toggle("loading");
                        }
                    });
                </script>
                <p>Vous avez déjà un compte ?
                    <a href="connexion.php" class="link font-bold">Connectez vous !</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include('footer.php'); ?>

</body>

</html>