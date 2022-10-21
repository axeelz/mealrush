<?php
session_start();

if (!$_SESSION["connecte"]) {
    $isConnecte = false;
}

if (!$_SESSION["item_panier"]) {
    $nb_items = 0;
    $prix_total = 0;
}
?>

<div class="navbar max-w-full bg-white">
    <div class="flex-1">
        <a class="btn btn-ghost normal-case text-xl" href="index.php">
            MealRush
        </a>
        <a class="btn btn-ghost md:text-lg text-slate-600" href="index.php">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="hidden md:block">Restos</span>
        </a>
    </div>
    <div class="flex-none">
        <div class="form-control hidden md:block">
            <input type="text" placeholder="Chercher un restaurant" class="input input-bordered" />
        </div>
        <button class="btn btn-ghost btn-circle ml-2 hidden md:flex">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </button>
        <div class="dropdown dropdown-end">
            <label tabindex="0" class="btn btn-ghost btn-circle">
                <div class="indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="badge badge-sm indicator-item bg-blue text-black"><?php echo $nb_items ?></span>
                </div>
            </label>
            <div tabindex="0" class="mt-3 card card-compact dropdown-content w-52 bg-base-100 shadow">
                <div class="card-body">
                    <span class="font-bold text-lg"><?php echo $nb_items ?> plats</span>
                    <span class="">Total: <?php echo $prix_total ?>€</span>
                    <div class="card-actions">
                        <button class="btn bg-blue text-black btn-block hover:text-white">Panier</button>
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
                        <a class="justify-between">
                            Mon compte
                        </a>
                    </li>
                    <li><a>Paramètres</a></li>
                    <li><a href="deconnexion.php">Se déconnecter</a></li>
                </ul>
            </div>
        <?php else : ?>
            <ul class="menu menu-horizontal p-0">
                <li><a class="btn bg-white hover:text-white border-none" href="connexion.php">Se connecter</a></li>
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