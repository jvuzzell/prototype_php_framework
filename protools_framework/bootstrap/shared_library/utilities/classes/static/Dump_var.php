<?php 

class Dump_var {

    Public static function dump( $output ) {
        echo "<pre style='font-size: 12px;'>";
        var_dump( $output );
        echo "</pre>\n";
    }

    Public static function print( $output ) {
        echo "<pre style='font-size: 12px;'>";
        print_r( $output );
        echo "</pre>\n";
    }

    Public static function echo( $output ) {
        echo $output;
        echo "<br>\n";
    }

}