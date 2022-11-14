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

    $id_utilisateur = $_SESSION['id_utilisateur'];

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

        $query = "SELECT adresses.rue, adresses.numero, adresses.code_postal, adresses.ville, adresses.pays, adresses.id FROM utilisateurs_adresses JOIN adresses ON utilisateurs_adresses.id_adresse = adresses.id WHERE utilisateurs_adresses.id_utilisateur = '$id_utilisateur'";
        $result = mysqli_query($conn, $query);
        $count = mysqli_num_rows($result);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            array_push(
                $_SESSION['adresses'],
                array(
                    "id" => $row["id"],
                    "format" => $row["numero"] . " " . $row["rue"] . ", " . $row["code_postal"] . ", " . $row["ville"] . ", " . $row["pays"]
                )
            );
        }
    }
} catch (\Throwable $th) {
    array_push($erreurs, $th->getMessage());
}

// Définition de l'adresse de l'utilisateur 
if (isset($_POST['setadress']) && isset($conn)) {
    do {
        // On récupère les valeurs du formulaire
        $rue = mysqli_real_escape_string($conn, htmlspecialchars(lcfirst($_POST['rue']))); // on met la première lettre en minuscule (rue)
        $numero = mysqli_real_escape_string($conn, htmlspecialchars($_POST['numero']));
        $ville = mysqli_real_escape_string($conn, htmlspecialchars($_POST['ville']));
        $code_postal = mysqli_real_escape_string($conn, htmlspecialchars($_POST['postal']));
        // $pays = mysqli_real_escape_string($conn, htmlspecialchars($_POST['pays']));

        if (empty($rue) || empty($numero) || empty($ville) || empty($code_postal)) {
            array_push($erreurs, "Un des champs requis est vide");
            break;
        }

        // Insertion d'une nouvelle adresse
        // On vérifie si l'adresse existe pas déjà et si oui, on récupere simplement son id
        $query = "SELECT id FROM `adresses` WHERE `rue` = '$rue' AND `numero` = '$numero' AND `ville` = '$ville' AND `code_postal` = '$code_postal' LIMIT 1";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);

        // Soit on a bien trouvé une valeur, soit on renvoie false
        $id_adresse = $row['id'] ?? false;

        // Si cette adresse n'a jamais été ajoutée par un utilisateur, on l'ajoute
        if ($id_adresse == false) {
            $query = "INSERT INTO `adresses` (`rue`, `numero`, `ville`, `code_postal`, `pays`) VALUES ('$rue', '$numero', '$ville', '$code_postal', 'France')";
            if (mysqli_query($conn, $query)) {
                $id_adresse = mysqli_insert_id($conn);
            } else {
                array_push($erreurs, mysqli_error($conn));
                break;
            }

            // Si l'insertion de l'adresse a pas fonctionné, on stop
            if (!isset($id_adresse)) {
                array_push($erreurs, "Erreur lors de la création de l'adresse, impossible de la lier à l'utilisateur");
                break;
            }
        }

        // Si tout est bon, on lie l'adresse à l'utilisateur
        $query = "INSERT INTO `utilisateurs_adresses` (`id_utilisateur`, `id_adresse`) VALUES ('$id_utilisateur', '$id_adresse')";
        if (mysqli_query($conn, $query)) {
            // On ajoute un message en variable de session pour qu'il puisse être affiché sur la page suivante
            $_SESSION['successMessage'] = "Adresse ajoutée à votre compte";
            if ($_GET['source'] == 'recapitulatif') {
                FermerConnexion($conn);
                header('location: recapitulatif.php');
                exit();
            } else {
                header('location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }
    } while (0);
}

// Après avoir cliqué sur supprimer user
if (isset($_POST['supprimer_user']) && isset($conn)) {
    do {
        $query = "DELETE FROM utilisateurs_adresses WHERE id_utilisateur='$id_utilisateur'";
        $query2 = "DELETE rt FROM restaurants_tags AS rt JOIN restaurants ON rt.id_restaurant = restaurants.id WHERE restaurants.id_utilisateur='$id_utilisateur'";
        $query3 = "DELETE FROM restaurants WHERE id_utilisateur='$id_utilisateur'";
        $query4 = "DELETE FROM utilisateurs WHERE id='$id_utilisateur'";

        if (mysqli_query($conn, $query) && mysqli_query($conn, $query2) && mysqli_query($conn, $query3) && mysqli_query($conn, $query4)) {
            FermerConnexion($conn);
            // On ajoute un message en variable de session pour qu'il puisse être affiché après le reload
            $_SESSION['successMessage'] = "Votre compte a bien été supprimé";
            session_unset();
            session_destroy();
            header("location: index.php");
            exit();
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }
    } while (0);
}

// Modification infos utilisateur 
if (isset($_POST['modifier']) && isset($conn)) {
    do {
        // On récupère les valeurs du formulaire
        $prenom_updated = mysqli_real_escape_string($conn, htmlspecialchars($_POST['update_prenom']));
        $nom_updated = mysqli_real_escape_string($conn, htmlspecialchars($_POST['update_nom']));
        $email_updated = mysqli_real_escape_string($conn, htmlspecialchars($_POST['update_email']));

        if ($prenom_updated != $_SESSION['prenom'] || $nom_updated != $_SESSION['nom'] || $email_updated != $_SESSION['email']) {

            if (empty($email_updated)) {
                array_push($erreurs, "Vous devez avoir une adresse e-mail associée à votre compte");
                break;
            }

            $query = "UPDATE utilisateurs SET prenom='$prenom_updated', nom='$nom_updated', email='$email_updated' WHERE id='$id_utilisateur'";

            if (mysqli_query($conn, $query)) {
                $_SESSION['prenom'] = $prenom_updated;
                $_SESSION['nom'] = $nom_updated;
                $_SESSION['email'] = $email_updated;
                array_push($succes, "Informations modifiées");
            } else {
                array_push($erreurs, mysqli_error($conn));
                break;
            }
        }
    } while (0);
}

// Devenir restaurateur
if (isset($_POST['devenir_restaurateur']) && isset($conn)) {
    $query = "UPDATE utilisateurs SET role='restaurateur' WHERE id='$id_utilisateur'";

    if (mysqli_query($conn, $query)) {
        FermerConnexion($conn);
        $_SESSION['role'] = 'restaurateur';
        $_SESSION['successMessage'] = "Vous êtes maintenant restaurateur";
        header("location: restaurateur.php?nouveaurestaurateur=1");
        exit();
    } else {
        array_push($erreurs, mysqli_error($conn));
    }
}

// Devenir utilisateur lambda
if (isset($_POST['devenir_user']) && isset($conn)) {
    $query = "UPDATE utilisateurs SET role='utilisateur' WHERE id='$id_utilisateur'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['role'] = 'utilisateur';
        array_push($succes, "Vous êtes maintenant utilisateur");
    } else {
        array_push($erreurs, mysqli_error($conn));
    }
}

// Après avoir cliqué sur supprimer une adresse
if (isset($_POST['supprimer_adresse']) && isset($conn)) {
    do {
        $id_adresse_a_suppr = $_POST['supprimer_adresse'];

        // On vérifie que l'adresse que l'utilisateur souhaite supprimer
        // existe bien et appartient bien à cet utilisateur,
        // pour éviter qu'il puisse supprimer une autre adresse en inspectant l'élément
        // et en modifiant la valeur du bouton.
        $result = mysqli_query($conn, "SELECT * FROM `utilisateurs_adresses` WHERE id_adresse='$id_adresse_a_suppr' AND id_utilisateur='$id_utilisateur'");
        $count = mysqli_num_rows($result);

        if (!($count == 1)) {
            array_push($erreurs, "Cette adresse n'est pas enregistrée dans votre compte");
            break;
        }

        // On supprime le lien entre l'utilisateur et l'adresse
        $query = "DELETE FROM utilisateurs_adresses WHERE id_adresse='$id_adresse_a_suppr' AND id_utilisateur='$id_utilisateur'";

        if (mysqli_query($conn, $query)) {
            for ($i = 0; $i < count($_SESSION['adresses']); $i++) {
                if ($_SESSION['adresses'][$i]['id'] == $id_adresse_a_suppr)
                    unset($_SESSION['adresses'][$i]);
            }
        } else {
            array_push($erreurs, mysqli_error($conn));
            break;
        }

        // On vérifie si l'adresse n'est pas aussi liée à un autre utilisateur, dans ce cas on ne la supprime pas
        $verif = "SELECT * FROM utilisateurs_adresses WHERE id_adresse='$id_adresse_a_suppr'";
        $result = mysqli_query($conn, $verif);
        $count = mysqli_num_rows($result);

        // Si l'adresse appartient à personne d'autre, on peut la supprimer
        if ($count == 0) {
            $query2 = "DELETE FROM adresses WHERE id='$id_adresse_a_suppr'";
            if (!mysqli_query($conn, $query2)) {
                array_push($erreurs, mysqli_error($conn));
                break;
            }
        }

        // On ajoute un message en variable de session pour qu'il puisse être affiché après le reload
        $_SESSION['successMessage'] = "Adresse supprimée";
        header('location: ' . $_SERVER['PHP_SELF'] . "#ouvrir-adresses");
        exit();
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
    <title>Compte - MealRush</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>

<body class="min-h-screen">

    <!-- Navigation -->
    <?php include('navbar.php'); ?>

    <!-- Formulaire d'ajoutr d'adresse -->
    <?php if ($veutAjouterAdresse) : ?>

        <div class="flex align-middle justify-center">
            <div class="rounded-xl shadow-xl bg-base-100 p-10 m-5 lg:m-10 lg:w-1/3">
                <img src="img/logo-blanc.png" alt="" class="w-64 mx-auto">
                <div class="divider"></div>
                <h1 class="text-xl font-bold md:text-2xl mb-5">
                    Ajouter une adresse de livraison
                </h1>
                <form class="form-control w-full max-w-xs md:max-w-md gap-5" method="post">
                    <div class="grid grid-cols-2 gap-4">
                        <div id="row-1">
                            <label for="numero" class="label">
                                <span class="label-text">Numéro de rue</span>
                            </label>
                            <input type="text" name="numero" id="numero" placeholder="5" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                        <div id="row-2">
                            <label for="rue" class="label">
                                <span class="label-text">Rue</span>
                            </label>
                            <input type="text" name="rue" id="rue" placeholder="rue de Rivoli" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div id="row-1">
                            <label for="ville" class="label">
                                <span class="label-text">Ville</span>
                            </label>
                            <input type="text" name="ville" id="ville" placeholder="Paris" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                        <div id="row-2">
                            <label for="postal" class="label">
                                <span class="label-text">Code postal</span>
                            </label>
                            <input type="text" name="postal" id="postal" placeholder="75001" class="input input-bordered bg-slate-100 w-full" required />
                        </div>
                    </div>
                    <p class="opacity-60">Nous ne livrons qu'en France pour l'instant</p>
                    <select disabled class="select select-bordered w-full" name="pays">
                        <option value="Afghanistan">Afghanistan</option>
                        <option value="Åland Islands">Åland Islands</option>
                        <option value="Albania">Albania</option>
                        <option value="Algeria">Algeria</option>
                        <option value="American Samoa">American Samoa</option>
                        <option value="Andorra">Andorra</option>
                        <option value="Angola">Angola</option>
                        <option value="Anguilla">Anguilla</option>
                        <option value="Antarctica">Antarctica</option>
                        <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                        <option value="Argentina">Argentina</option>
                        <option value="Armenia">Armenia</option>
                        <option value="Aruba">Aruba</option>
                        <option value="Australia">Australia</option>
                        <option value="Austria">Austria</option>
                        <option value="Azerbaijan">Azerbaijan</option>
                        <option value="Bahamas">Bahamas</option>
                        <option value="Bahrain">Bahrain</option>
                        <option value="Bangladesh">Bangladesh</option>
                        <option value="Barbados">Barbados</option>
                        <option value="Belarus">Belarus</option>
                        <option value="Belgium">Belgium</option>
                        <option value="Belize">Belize</option>
                        <option value="Benin">Benin</option>
                        <option value="Bermuda">Bermuda</option>
                        <option value="Bhutan">Bhutan</option>
                        <option value="Bolivia">Bolivia</option>
                        <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                        <option value="Botswana">Botswana</option>
                        <option value="Bouvet Island">Bouvet Island</option>
                        <option value="Brazil">Brazil</option>
                        <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                        <option value="Brunei Darussalam">Brunei Darussalam</option>
                        <option value="Bulgaria">Bulgaria</option>
                        <option value="Burkina Faso">Burkina Faso</option>
                        <option value="Burundi">Burundi</option>
                        <option value="Cambodia">Cambodia</option>
                        <option value="Cameroon">Cameroon</option>
                        <option value="Canada">Canada</option>
                        <option value="Cape Verde">Cape Verde</option>
                        <option value="Cayman Islands">Cayman Islands</option>
                        <option value="Central African Republic">Central African Republic</option>
                        <option value="Chad">Chad</option>
                        <option value="Chile">Chile</option>
                        <option value="China">China</option>
                        <option value="Christmas Island">Christmas Island</option>
                        <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
                        <option value="Colombia">Colombia</option>
                        <option value="Comoros">Comoros</option>
                        <option value="Congo">Congo</option>
                        <option value="Congo, The Democratic Republic of The">Congo, The Democratic Republic of The</option>
                        <option value="Cook Islands">Cook Islands</option>
                        <option value="Costa Rica">Costa Rica</option>
                        <option value="Cote D'ivoire">Cote D'ivoire</option>
                        <option value="Croatia">Croatia</option>
                        <option value="Cuba">Cuba</option>
                        <option value="Cyprus">Cyprus</option>
                        <option value="Czech Republic">Czech Republic</option>
                        <option value="Denmark">Denmark</option>
                        <option value="Djibouti">Djibouti</option>
                        <option value="Dominica">Dominica</option>
                        <option value="Dominican Republic">Dominican Republic</option>
                        <option value="Ecuador">Ecuador</option>
                        <option value="Egypt">Egypt</option>
                        <option value="El Salvador">El Salvador</option>
                        <option value="Equatorial Guinea">Equatorial Guinea</option>
                        <option value="Eritrea">Eritrea</option>
                        <option value="Estonia">Estonia</option>
                        <option value="Ethiopia">Ethiopia</option>
                        <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>
                        <option value="Faroe Islands">Faroe Islands</option>
                        <option value="Fiji">Fiji</option>
                        <option value="Finland">Finland</option>
                        <option value="France" selected>France</option>
                        <option value="French Guiana">French Guiana</option>
                        <option value="French Polynesia">French Polynesia</option>
                        <option value="French Southern Territories">French Southern Territories</option>
                        <option value="Gabon">Gabon</option>
                        <option value="Gambia">Gambia</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Germany">Germany</option>
                        <option value="Ghana">Ghana</option>
                        <option value="Gibraltar">Gibraltar</option>
                        <option value="Greece">Greece</option>
                        <option value="Greenland">Greenland</option>
                        <option value="Grenada">Grenada</option>
                        <option value="Guadeloupe">Guadeloupe</option>
                        <option value="Guam">Guam</option>
                        <option value="Guatemala">Guatemala</option>
                        <option value="Guernsey">Guernsey</option>
                        <option value="Guinea">Guinea</option>
                        <option value="Guinea-bissau">Guinea-bissau</option>
                        <option value="Guyana">Guyana</option>
                        <option value="Haiti">Haiti</option>
                        <option value="Heard Island and Mcdonald Islands">Heard Island and Mcdonald Islands</option>
                        <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>
                        <option value="Honduras">Honduras</option>
                        <option value="Hong Kong">Hong Kong</option>
                        <option value="Hungary">Hungary</option>
                        <option value="Iceland">Iceland</option>
                        <option value="India">India</option>
                        <option value="Indonesia">Indonesia</option>
                        <option value="Iran, Islamic Republic of">Iran, Islamic Republic of</option>
                        <option value="Iraq">Iraq</option>
                        <option value="Ireland">Ireland</option>
                        <option value="Isle of Man">Isle of Man</option>
                        <option value="Israel">Israel</option>
                        <option value="Italy">Italy</option>
                        <option value="Jamaica">Jamaica</option>
                        <option value="Japan">Japan</option>
                        <option value="Jersey">Jersey</option>
                        <option value="Jordan">Jordan</option>
                        <option value="Kazakhstan">Kazakhstan</option>
                        <option value="Kenya">Kenya</option>
                        <option value="Kiribati">Kiribati</option>
                        <option value="Korea, Democratic People's Republic of">Korea, Democratic People's Republic of</option>
                        <option value="Korea, Republic of">Korea, Republic of</option>
                        <option value="Kuwait">Kuwait</option>
                        <option value="Kyrgyzstan">Kyrgyzstan</option>
                        <option value="Lao People's Democratic Republic">Lao People's Democratic Republic</option>
                        <option value="Latvia">Latvia</option>
                        <option value="Lebanon">Lebanon</option>
                        <option value="Lesotho">Lesotho</option>
                        <option value="Liberia">Liberia</option>
                        <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
                        <option value="Liechtenstein">Liechtenstein</option>
                        <option value="Lithuania">Lithuania</option>
                        <option value="Luxembourg">Luxembourg</option>
                        <option value="Macao">Macao</option>
                        <option value="Macedonia, The Former Yugoslav Republic of">Macedonia, The Former Yugoslav Republic of</option>
                        <option value="Madagascar">Madagascar</option>
                        <option value="Malawi">Malawi</option>
                        <option value="Malaysia">Malaysia</option>
                        <option value="Maldives">Maldives</option>
                        <option value="Mali">Mali</option>
                        <option value="Malta">Malta</option>
                        <option value="Marshall Islands">Marshall Islands</option>
                        <option value="Martinique">Martinique</option>
                        <option value="Mauritania">Mauritania</option>
                        <option value="Mauritius">Mauritius</option>
                        <option value="Mayotte">Mayotte</option>
                        <option value="Mexico">Mexico</option>
                        <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>
                        <option value="Moldova, Republic of">Moldova, Republic of</option>
                        <option value="Monaco">Monaco</option>
                        <option value="Mongolia">Mongolia</option>
                        <option value="Montenegro">Montenegro</option>
                        <option value="Montserrat">Montserrat</option>
                        <option value="Morocco">Morocco</option>
                        <option value="Mozambique">Mozambique</option>
                        <option value="Myanmar">Myanmar</option>
                        <option value="Namibia">Namibia</option>
                        <option value="Nauru">Nauru</option>
                        <option value="Nepal">Nepal</option>
                        <option value="Netherlands">Netherlands</option>
                        <option value="Netherlands Antilles">Netherlands Antilles</option>
                        <option value="New Caledonia">New Caledonia</option>
                        <option value="New Zealand">New Zealand</option>
                        <option value="Nicaragua">Nicaragua</option>
                        <option value="Niger">Niger</option>
                        <option value="Nigeria">Nigeria</option>
                        <option value="Niue">Niue</option>
                        <option value="Norfolk Island">Norfolk Island</option>
                        <option value="Northern Mariana Islands">Northern Mariana Islands</option>
                        <option value="Norway">Norway</option>
                        <option value="Oman">Oman</option>
                        <option value="Pakistan">Pakistan</option>
                        <option value="Palau">Palau</option>
                        <option value="Palestinian Territory, Occupied">Palestinian Territory, Occupied</option>
                        <option value="Panama">Panama</option>
                        <option value="Papua New Guinea">Papua New Guinea</option>
                        <option value="Paraguay">Paraguay</option>
                        <option value="Peru">Peru</option>
                        <option value="Philippines">Philippines</option>
                        <option value="Pitcairn">Pitcairn</option>
                        <option value="Poland">Poland</option>
                        <option value="Portugal">Portugal</option>
                        <option value="Puerto Rico">Puerto Rico</option>
                        <option value="Qatar">Qatar</option>
                        <option value="Reunion">Reunion</option>
                        <option value="Romania">Romania</option>
                        <option value="Russian Federation">Russian Federation</option>
                        <option value="Rwanda">Rwanda</option>
                        <option value="Saint Helena">Saint Helena</option>
                        <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                        <option value="Saint Lucia">Saint Lucia</option>
                        <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
                        <option value="Saint Vincent and The Grenadines">Saint Vincent and The Grenadines</option>
                        <option value="Samoa">Samoa</option>
                        <option value="San Marino">San Marino</option>
                        <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                        <option value="Saudi Arabia">Saudi Arabia</option>
                        <option value="Senegal">Senegal</option>
                        <option value="Serbia">Serbia</option>
                        <option value="Seychelles">Seychelles</option>
                        <option value="Sierra Leone">Sierra Leone</option>
                        <option value="Singapore">Singapore</option>
                        <option value="Slovakia">Slovakia</option>
                        <option value="Slovenia">Slovenia</option>
                        <option value="Solomon Islands">Solomon Islands</option>
                        <option value="Somalia">Somalia</option>
                        <option value="South Africa">South Africa</option>
                        <option value="South Georgia and The South Sandwich Islands">South Georgia and The South Sandwich Islands</option>
                        <option value="Spain">Spain</option>
                        <option value="Sri Lanka">Sri Lanka</option>
                        <option value="Sudan">Sudan</option>
                        <option value="Suriname">Suriname</option>
                        <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
                        <option value="Swaziland">Swaziland</option>
                        <option value="Sweden">Sweden</option>
                        <option value="Switzerland">Switzerland</option>
                        <option value="Syrian Arab Republic">Syrian Arab Republic</option>
                        <option value="Taiwan">Taiwan</option>
                        <option value="Tajikistan">Tajikistan</option>
                        <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>
                        <option value="Thailand">Thailand</option>
                        <option value="Timor-leste">Timor-leste</option>
                        <option value="Togo">Togo</option>
                        <option value="Tokelau">Tokelau</option>
                        <option value="Tonga">Tonga</option>
                        <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                        <option value="Tunisia">Tunisia</option>
                        <option value="Turkey">Turkey</option>
                        <option value="Turkmenistan">Turkmenistan</option>
                        <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>
                        <option value="Tuvalu">Tuvalu</option>
                        <option value="Uganda">Uganda</option>
                        <option value="Ukraine">Ukraine</option>
                        <option value="United Arab Emirates">United Arab Emirates</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="United States">United States</option>
                        <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
                        <option value="Uruguay">Uruguay</option>
                        <option value="Uzbekistan">Uzbekistan</option>
                        <option value="Vanuatu">Vanuatu</option>
                        <option value="Venezuela">Venezuela</option>
                        <option value="Viet Nam">Viet Nam</option>
                        <option value="Virgin Islands, British">Virgin Islands, British</option>
                        <option value="Virgin Islands, U.S.">Virgin Islands, U.S.</option>
                        <option value="Wallis and Futuna">Wallis and Futuna</option>
                        <option value="Western Sahara">Western Sahara</option>
                        <option value="Yemen">Yemen</option>
                        <option value="Zambia">Zambia</option>
                        <option value="Zimbabwe">Zimbabwe</option>
                    </select>
                    <div class="gap-0">
                        <!-- Si on provient de la page de création de compte, on propose de passer et non d'annuler -->
                        <?php if ($_GET['source'] == 'creation') : ?>
                            <a class="btn btn-block btn-ghost border-black mt-5" href="index.php">Plus tard</a>
                        <?php elseif ($_GET['source'] == 'recapitulatif') : ?>
                            <a class="btn btn-block btn-ghost border-black mt-5" href="recapitulatif.php">Annuler</a>
                        <?php else : ?>
                            <a class="btn btn-block btn-ghost border-black mt-5" href="compte.php">Annuler</a>
                        <?php endif; ?>

                        <button class="btn btn-block btn-neutral mt-5" name="setadress">Valider</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else : ?>


        <div class="mx-auto p-10">
            <h1 class="text-2xl font-bold md:text-3xl text-center">Ravis de vous voir, <?php echo $_SESSION['prenom']; ?>&nbsp;!</h1>

            <?php if (empty($_GET['modification'])) : ?>

                <div class="card w-fit min-w-[40%] shadow-md my-10 mx-auto">

                    <div class="avatar placeholder mx-auto mt-2">
                        <div class="bg-neutral-focus text-neutral-content rounded-full w-24">
                            <!-- On récupère la première lettre du prenom pour l'avatar -->
                            <span class="text-3xl uppercase"><?php echo mb_substr($_SESSION['prenom'], 0, 1); ?></span>
                        </div>
                    </div>
                    <div class="card-body items-center text-center p-3">
                        <h2 class="card-title"><?php echo $_SESSION['prenom'] . " " . $_SESSION['nom']; ?></h2>
                        <p><?php echo $_SESSION['email'] ?></p>
                        <div class="w-full p-5 flex flex-col gap-3">
                            <div class="card-actions justify-center">
                                <a class="btn btn-wide" href="?modification=1">Modifier mes informations</a>
                            </div>
                            <div>
                                <div class="card-actions justify-center">
                                    <a class="btn btn-ghost border-gray" href="#ouvrir-adresses">Mes adresses</a>
                                    <a class="btn btn-ghost border-gray" href="#ouvrir-moyens-de-paiement">Moyens de paiement</a>
                                </div>
                            </div>
                            <div class="divider mb-0"></div>
                            <form method="post">
                                <div class="card-actions justify-center">
                                    <?php if ($isRestaurateur) : ?>
                                        <button class="btn btn-ghost" name="devenir_user" onClick="return confirm('Voulez-vous vraiment ne plus être restaurateur ?');">Ne plus être restaurateur</button>
                                    <?php else : ?>
                                        <button class="btn btn-ghost" name="devenir_restaurateur" onClick="return confirm('Voulez-vous vraiment devenir restaurateur ?');">Devenir restaurateur</button>
                                    <?php endif; ?>
                                    <button class="btn btn-ghost text-error" name="supprimer_user" onClick="return confirm('Cette action est irreversible, voulez-vous vraiment supprimer votre compte ?');">Supprimer mon compte</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="stats">

                        <div class="stat place-items-center">
                            <div class="stat-title">Activité</div>
                            <div class="stat-value">0</div>
                            <div class="stat-desc">commandes</div>
                        </div>

                        <div class="stat place-items-center">
                            <div class="stat-title">Ancienneté</div>
                            <div class="stat-value">
                                <?php
                                $maintenant = new DateTime();
                                $creation = new DateTime($_SESSION['creation']);

                                $jours_anciennete = $creation->diff($maintenant)->format("%a");

                                echo $jours_anciennete;
                                ?>
                            </div>
                            <div class="stat-desc">jours</div>
                        </div>

                    </div>
                </div>

            <?php else : ?>
                <!-- Mode modification -->
                <div class="card w-fit shadow-md my-10 mx-auto">
                    <div class="avatar placeholder mx-auto mt-5">
                        <div class="bg-neutral-focus text-neutral-content rounded-full w-24">
                            <!-- On récupère la première lettre du prenom pour l'avatar -->
                            <span class="text-3xl uppercase"><?php echo mb_substr($_SESSION['prenom'], 0, 1); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form class="form-control items-center gap-2" method="post" action="compte.php">
                            <div class="grid grid-cols-2 gap-4">
                                <div id="row-1">
                                    <input type="text" value="<?php echo $_SESSION['prenom'] ?>" name="update_prenom" class="card-title text-center input input-bordered bg-slate-100 w-full" />
                                </div>
                                <div id="row-2">
                                    <input type="text" value="<?php echo $_SESSION['nom'] ?>" name="update_nom" class="card-title text-center input input-bordered bg-slate-100 w-full" />
                                </div>
                            </div>
                            <input type="text" value="<?php echo $_SESSION['email'] ?>" name="update_email" class="text-center input input-bordered bg-slate-100 w-full" />
                            <div class="card-actions">
                                <button name="modifier" class="btn btn-success btn-wide mt-5 gap-2">
                                    Enregistrer
                                    <svg xmlns=" http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 stroke-current">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    <?php endif; ?>

    <!-- Footer -->
    <?php include('footer.php'); ?>

    <div class="modal" id="ouvrir-adresses">
        <div class="modal-box text-center">
            <h3 class="font-bold text-lg mb-5">Adresses enregistrées</h3>
            <?php foreach ($_SESSION['adresses'] as $a) : ?>
                <?php $auMoinsUneAdresse = true; ?>
                <div class="flex justify-between items-center gap-4">
                    <span class="flex items-center h-12 font-semibold text-sm whitespace-nowrap overflow-scroll max-w-full">
                        <?php echo $a['format']; ?>
                    </span>
                    <form method="post">
                        <button class="btn btn-circle btn-outline btn-sm" name="supprimer_adresse" value="<?php echo $a['id']; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if (!isset($auMoinsUneAdresse)) : ?>
                <p class="text-start">Aucune adresse enregistrée</p>
            <?php endif; ?>

            <div class="modal-action">
                <a class="btn btn-ghost" href="?ajouteradresse=1">Ajouter une adresse</a>
                <!-- Si on vient ici depuis le sélecteur d'adresse, on renvoie vers la page confirmation au lieu de rester sur la page de compte -->
                <?php if (empty($_GET['selection'])) : ?>
                    <a href="#" class="btn">Terminé</a>
                <?php else : ?>
                    <a href="recapitulatif.php" class="btn">Terminé</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal" id="ouvrir-moyens-de-paiement">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Moyens de paiement enregistrés</h3>
            <p class="py-4"></p>
            <div class="modal-action">
                <a href="#" class="btn">Terminé</a>
            </div>
        </div>
    </div>

</body>

</html>