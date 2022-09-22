<?php 

$display_env_variable = ( ENV_NAME !== 'prod' ) ? "(" . strtoupper( ENV_NAME ) . ")" : '';

?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1, maximum-scale=1">
        <title><?php echo $display_env_variable; ?> Testing - Error Page</title>

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
                            <h1>Oops! System error has occured.</h1>
                            <h2><?php echo $response_data[ 'status' ]; ?> - <?php echo $response_data[ 'message' ]; ?></h3>
                            <p>If this screen persists, please contact your account manager.</p>
                            <?php 
                                if( isset( $response_data[ 'system' ] ) && isset( $response_data[ 'system' ][ 'private' ] ) ) {
                                    $private = $response_data[ 'system' ][ 'private' ]; 
                                } else {
                                    $private = true; 
                                }

                                if( !$private || ENV_NAME == 'dev' ) :
                            ?>
                            <form>
                                <textarea name="" id="" cols="30" rows="10" style="min-height: 500px;"><?php echo( json_encode( $response_data, JSON_PRETTY_PRINT ) ); ?></textarea>
                            </form>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        <!-- End Main Content -->

    </body>
</html>