<footer class="footer footer-center p-10 bg-primary text-primary-content sticky top-[100vh]">
    <div>
        <h2 class="text-xl">MealRush Inc.</h2>
        <p class="font-bold">
            Votre festin livré en quelques clics. <br />
            Pour votre santé, mangez au moins cinq fruits et légumes par jour.
        </p>
        <p>Copyright © 2022 - Tous droits réservés</p>
    </div>

    <div class="toast toast-end">
        <?php foreach ($erreurs as $v) : ?>
            <div class="temp alert alert-error shadow-lg">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Erreur ! <?php echo $v ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        <?php foreach ($succes as $s) : ?>
            <div class="temp alert alert-success shadow-lg">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?php echo $s ?> !</span>
                </div>
            </div>
        <?php endforeach; ?>
        <!-- Afficher message provenant d'une autre page -->
        <?php if (isset($_SESSION['successMessage'])) : ?>
            <div class="temp alert alert-success shadow-lg">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?php echo $_SESSION['successMessage'] ?> !</span>
                </div>
            </div>
            <?php unset($_SESSION['successMessage']); ?>
        <?php endif; ?>
    </div>
</footer>
<script>
    setTimeout(() => {
        const alert = document.getElementsByClassName('temp');
        for (let notif of alert) {
            notif.style.display = 'none';
        }
    }, 4000);
</script>