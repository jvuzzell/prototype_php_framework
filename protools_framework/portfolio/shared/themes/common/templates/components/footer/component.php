  
    <?php // Hooks - Footer ?> 

    <?php $Hooks->do_action( 'footer_actions' ); ?>
    <?php $Hooks->do_action( 'after_footer_actions', $PAGE_MODEL, $PAGE_CONTROLLER, ENV_NAME ); // Parameters defined in view.php of module  ?>

    </body>

</html>