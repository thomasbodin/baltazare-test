        </section>

        <footer class="">
            <!-- your code HTML footer here -->
        </footer>

        <div class="cookie-cnil" id="cookieCnil">
            En poursuivant votre navigation sur ce site, vous acceptez l’utilisation de Cookies pour réaliser des statistiques de visites anonymes. <a href="<?= get_permalink(113); ?>" class="cookie-cnil__link">En savoir plus.</a>
            <div class="cookie-cnil__btn" id="cookie_btn_ok">Ok</div>
        </div>

        <!-- Scripts -->
        <?php wp_footer(); ?>
        <script src="<?php bloginfo( 'template_directory' ); ?>/script/build/bootstrap.min.js"></script>
        <script src="<?php bloginfo( 'template_directory' ); ?>/script/build/main.js"></script>
    </body>
</html>
