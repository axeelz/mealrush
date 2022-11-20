<?php
if (isset($_SESSION['connecte']) && $_SESSION['connecte'] == true) {
    $isConnecte = true;
} else {
    $isConnecte = false;
}

if ($_SESSION['role'] == 'admin') {
    $isAdmin = true;
} else {
    $isAdmin = false;
}

if ($_SESSION['role'] == 'restaurateur' || $isAdmin) {
    $isRestaurateur = true;
} else {
    $isRestaurateur = false;
}

if (!$_SESSION['panier']) {
    $nb_items = 0;
    $prix_total = 0;
} else {
    $nb_items = $_SESSION['panier']['nb_total'];
    $prix_total = $_SESSION['panier']['prix_total'];
}

// Ajouter un article au panier
if (isset($_POST['ajouter_panier'])) {
    do {
        $id_produit = $_POST['ajouter_panier'];
        $result = mysqli_query($conn, "SELECT plats.nom, plats.prix, plats.image, restaurants.nom AS rn FROM plats JOIN restaurants ON restaurants.id = plats.id_restaurant WHERE plats.id='$id_produit'");
        $row = mysqli_fetch_assoc($result);

        if ($row == false) {
            array_push($erreurs, "Produit inexistant");
            break;
        }

        $plats_dans_panier = array(
            $id_produit => array(
                'nom' => $row['nom'],
                'id' => $id_produit,
                'prix' => $row['prix'],
                'quantite' => 1,
                // pas besoin pour l'instant - 'image' => $row['image'],
                'restaurant' => $row['rn']
            )
        );

        if (empty($_SESSION['panier'])) {
            $_SESSION['panier'] = array(
                'items' => $plats_dans_panier,
                'nb_total' => 1,
                'prix_total' => $row['prix'],
            );
        } else {
            if (in_array($id_produit, array_keys($_SESSION['panier']['items']))) {
                $_SESSION['panier']['items'][$id_produit]['quantite']++;
            } else {
                $_SESSION['panier']['items'] += $plats_dans_panier;
            }
            $_SESSION['panier']['nb_total']++;
            $_SESSION['panier']['prix_total'] += $row['prix'];
        }
        $_SESSION['successMessage'] = $row['nom'] . " ajouté à votre panier";
        header('location: ' . basename($_SERVER['REQUEST_URI']));
        exit();
    } while (0);
}

// Vider le panier
if (isset($_POST['vider_panier'])) {
    unset($_SESSION['panier']);
    $_SESSION['successMessage'] = "Panier vidé";
    header('location: ' . basename($_SERVER['REQUEST_URI']));
    exit();
}

// Ajouter quantité panier
if (isset($_POST['plus1'])) {
    $_SESSION['forcerPanierOuvert'] = true;

    $_SESSION['panier']['prix_total'] += $_SESSION['panier']['items'][$_POST['plus1']]['prix'];
    $_SESSION['panier']['nb_total']++;

    $_SESSION['panier']['items'][$_POST['plus1']]['quantite']++;

    // On utilise REQUEST_URI pour conserver les paramètres d'URL
    header('location: ' . basename($_SERVER['REQUEST_URI']));
    exit();
}

// Retirer quantité panier
if (isset($_POST['moins1'])) {
    $_SESSION['forcerPanierOuvert'] = true;

    $_SESSION['panier']['prix_total'] -= $_SESSION['panier']['items'][$_POST['moins1']]['prix'];
    $_SESSION['panier']['nb_total']--;

    if ($_SESSION['panier']['items'][$_POST['moins1']]['quantite'] > 1) {
        $_SESSION['panier']['items'][$_POST['moins1']]['quantite']--;
    } else {
        unset($_SESSION['panier']['items'][$_POST['moins1']]);
        if (empty($_SESSION['panier']['items']))
            $_SESSION['panier']['prix_total'] = 0;
    }

    // On utilise REQUEST_URI pour conserver les paramètres d'URL
    header('location: ' . basename($_SERVER['REQUEST_URI']));
    exit();
}
?>

<div class="navbar max-w-full bg-white">
    <div class="flex-1">
        <a class="btn btn-ghost normal-case text-xl" href="index.php">
            MealRush
        </a>
        <a class="btn btn-ghost md:text-lg opacity-60" href="index.php">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="hidden md:block">Restos</span>
        </a>
    </div>
    <div class="flex-none">
        <div class="form-control hidden md:block">
            <input type="text" placeholder="Chercher un restaurant" class="input input-bordered h-10" />
        </div>
        <button class="btn btn-ghost btn-circle ml-2 hidden md:flex">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>
        <!-- Lorsque on vient de modifier la quantité d'un article dans le panier,
        on a besoin d'actualiser la page pour metre a jour les informations du panier.
        Pour pas que l'utilisateur ait à rouvrir le panier, on l'ouvre par défaut
        avec la classe dropdown-open -->
        <div class="dropdown dropdown-end <?php if ($_SESSION['forcerPanierOuvert'] && basename($_SERVER['PHP_SELF']) != "recapitulatif.php") echo "dropdown-open"; ?>" id="dropdown-panier">
            <?php unset($_SESSION['forcerPanierOuvert']); ?>
            <script>
                // Si le panier a été ouvert par défaut au chargement de la page
                // on fait en sorte de le refermer quand l'utilisateur clique
                // en dehors du menu dépliant avec sa souris.
                const panier = document.getElementById('dropdown-panier');
                if (panier.classList.contains("dropdown-open")) {
                    window.addEventListener('click', function(e) {
                        if (!panier.contains(e.target))
                            panier.classList.remove("dropdown-open");
                    })
                };
            </script>
            <label tabindex="0" class="btn btn-ghost btn-circle">
                <div class="indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="badge badge-sm indicator-item bg-blue text-black"><?php echo $nb_items ?></span>
                </div>
            </label>
            <div tabindex="0" class="mt-3 card card-compact dropdown-content w-72 bg-base-100 shadow">
                <div class="card-body">

                    <span class="text-xl font-bold">Panier</span>

                    <?php foreach ($_SESSION['panier']['items'] as $i) : ?>
                        <div class="w-full flex justify-between h-12 items-center">
                            <div class="w-2/3 flex justify-between">
                                <div><?php echo $i['nom']; ?></div>
                                <div class="flex items-center justify-center">
                                    <span class="badge badge-md badge-outline"><?php echo $i['quantite']; ?></span>
                                </div>
                            </div>
                            <form method="post" class="w-1/3 flex items-center justify-center">
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

                    <span class="text-xl font-bold my-2"><?php echo str_replace(".", ",", $prix_total); ?>€</span>

                    <div class="card-actions">
                        <?php if ($nb_items > 0) : ?>
                            <form method="post" class="w-full">
                                <button name="vider_panier" class="btn btn-ghost text-error border-error btn-block">Vider le panier</button>
                            </form>
                            <a href="recapitulatif.php" class="btn bg-blue text-black btn-block hover:text-white border-none">Commander</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($isConnecte) : ?>
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                    <div class="w-10 rounded-full">
                        <img src="img/avatar.png" />
                    </div>
                </label>
                <ul tabindex="0" class="menu menu-compact dropdown-content mt-3 p-2 shadow bg-base-100 rounded-box w-52">
                    <li>
                        <a class="justify-between" href="compte.php">
                            Mon compte
                        </a>
                    </li>
                    <li><a href="commandes.php">Mes commandes</a></li>
                    <?php if ($isRestaurateur) : ?>
                        <li><a href="restaurateur.php">Gestion restaurants</a></li>
                    <?php endif; ?>
                    <?php if ($isAdmin) : ?>
                        <li><a href="admin.php">Gestion admin</a></li>
                    <?php endif; ?>
                    <li><a href="deconnexion.php">Se déconnecter</a></li>
                </ul>
            </div>
        <?php else : ?>
            <ul class="menu menu-horizontal p-0">
                <li><a class="btn btn-ghost" href="connexion.php">Se connecter</a></li>
            </ul>
        <?php endif; ?>
    </div>
</div>
<!-- Barre de recherche en dessous de la navbar pour les petits écrans -->
<div class="flex md:hidden justify-center bg-white">
    <div class="form-control w-2/3 mb-3">
        <input type="text" placeholder="Chercher un restaurant" class="input input-bordered" />
    </div>
    <button class="btn btn-ghost btn-circle ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </button>
</div>