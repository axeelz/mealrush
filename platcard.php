<?php
// Usage : Nécessite d'être dans un "foreach ($plats as $p)"
?>

<div class="card card-side border-2 border-base-200 md:w-1/2 lg:w-full md:min-w-[400px] mx-auto">
    <figure class="w-36 overflow-hidden p-3 md:p-0 md:ml-3">
        <img src="<?php echo $p['image']; ?>" alt="Image Plat" class="object-cover rounded-lg" onerror="if (this.src != 'img/error.png') this.src = 'img/error.png';" />
    </figure>
    <div class="card-body">
        <h2 class="card-title">
            <?php echo $p['nom']; ?>
        </h2>
        <div class="badge badge-lg"><?php echo str_replace(".", ",", number_format($p['prix'], 2)); ?> €</div>
        <div class="card-actions justify-end">
            <?php if (basename($_SERVER['PHP_SELF']) == "restaurants.php") : ?>
                <form method="post">
                    <button name="ajouter_panier" value="<?php echo $p['id']; ?>" class="btn bg-blue text-black border-none hover:text-white mt-2">Ajouter au panier</button>
                </form>
            <?php elseif (basename($_SERVER['PHP_SELF']) == "restaurateur.php") : ?>
                <form method="post">
                    <!-- <button name="modifier" class="btn btn-ghost mt-2">Modifier</button> -->
                    <button name="supprimer_plat" value="<?php echo $p['id']; ?>" class="btn btn-error mt-2" onClick="return confirm('Cette action est irreversible, voulez-vous vraiment supprimer ce plat ?');">Supprimer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>