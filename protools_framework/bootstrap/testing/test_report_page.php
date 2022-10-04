<?php 

$display_env_variable = ( ENV_NAME !== 'prod' ) ? "(" . strtoupper( ENV_NAME ) . ")" : '';

?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1, maximum-scale=1">
        <title><?php echo $display_env_variable; ?> Testing Report</title>

        <link rel="icon" type="image/png" href="<?php echo SITE_URL . '/favicon.png'; ?>">
        <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL . '/favicon.ico'; ?>">
    </head>
    <body>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-lock">

                <!-- Error Content -->
                <div class="404-error has-mg-top-30">
                    <div class="h-row">
                        <div class="v-col">
                            <h1>Test Report</h1>
                            <?php 
                                Dump_var::print( $response_data[ 'data' ] );
                            ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        <!-- End Main Content -->

    </body>
</html>