<?php
// Usage : Nécessite d'être dans un "foreach ($restos as $r)"
?>

<div class="card shadow-md min-h-[380px] w-[310px] min-w-[310px] snap-center animate-in zoom-in-75 duration-300">
    <?php if (basename($_SERVER['PHP_SELF']) == "restaurateur.php") : ?>
        <figure class="h-44 overflow-hidden">
            <img src="<?php echo $r['image']; ?>" alt="Image Restaurant" class="object-cover" onerror="if (this.src != 'img/error.png') this.src = 'img/error.png';" loading="lazy" />
        </figure>
    <?php else : ?>
        <a href="restaurants.php?id=<?php echo $r['id']; ?>">
            <figure class="h-44 overflow-hidden">
                <img src="<?php echo $r['image']; ?>" alt="Image Restaurant" class="object-cover" onerror="if (this.src != 'img/error.png') this.src = 'img/error.png';" loading="lazy" />
            </figure>
        </a>
    <?php endif; ?>
    <div class="card-body">
        <h2 class="card-title">
            <?php echo $r['nom']; ?>
        </h2>
        <div class="card-actions justify-start">
            <?php if (empty($r['tags'])) : ?>
                <div class="badge badge-ghost">Pas de tag</div>
            <?php else : ?>
                <?php foreach ($r['tags'] as $t) : ?>
                    <div class="badge badge-ghost"><?php echo $t; ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (basename($_SERVER['PHP_SELF']) == "index.php") : ?>
            <p>
                Livraison en
                <span class="badge bg-green">
                    <script>
                        document.write(Math.round(Math.random() * (30 - 5) + 5) + " min");
                    </script>
                </span>
            </p>
        <?php elseif (basename($_SERVER['PHP_SELF']) == "admin.php") : ?>
            <p>Par <?php echo $r['prenom_u'] . " " . $r['nom_u']; ?></p>
        <?php endif; ?>
        <div class="card-actions justify-end">
            <?php if (basename($_SERVER['PHP_SELF']) == "admin.php") : ?>
                <form method="post">
                    <?php if ($r['approuve'] == 'true') : ?>
                        <button name="masquer" value="<?php echo $r['id']; ?>" class="btn btn-ghost mt-2">Masquer</button>
                    <?php else : ?>
                        <button name="approuver" value="<?php echo $r['id']; ?>" class="btn btn-success mt-2">Approuver</button>
                    <?php endif; ?>
                    <button name="supprimer" value="<?php echo $r['id']; ?>" class="btn btn-error mt-2" onClick="return confirm('Cette action est irreversible, voulez-vous vraiment supprimer ce restaurant ?');">Supprimer</button>
                </form>
            <?php elseif (basename($_SERVER['PHP_SELF']) == "restaurateur.php") : ?>
                <form method="post">
                    <?php if ($r['approuve'] == 'true') : ?>
                        <button name="gerer" value="<?php echo $r['id']; ?>" class="btn btn-ghost mt-2">Gérer</button>
                        <button name="supprimer" value="<?php echo $r['id']; ?>" class="btn btn-error mt-2" onClick="return confirm('Cette action est irreversible, voulez-vous vraiment supprimer ce restaurant ?');">Supprimer</button>
                    <?php else : ?>
                        <button name="supprimer" value="<?php echo $r['id']; ?>" class="btn btn-ghost mt-2">Annuler</button>
                    <?php endif; ?>
                </form>
            <?php else : ?>
                <a href="restaurants.php?id=<?php echo $r['id']; ?>" class="btn bg-blue text-black border-none hover:text-white mt-2">Voir les plats</a>
            <?php endif; ?>
        </div>
    </div>
</div>