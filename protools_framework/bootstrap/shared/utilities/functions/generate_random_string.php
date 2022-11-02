<?php 

function generate_random_string( $charLength = 10, $charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789" ) {

    $arr = str_split( $charset ); // get all the characters into an array
    shuffle( $arr ); // randomize the array
    $arr = array_slice( $arr, 0, $charLength ); // get the first six (random) characters out
    $str = implode( '', $arr ); // smush them back into a string
    return $str;

}