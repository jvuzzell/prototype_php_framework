<?php 

namespace Bootstrap\Api_gateway\Library\Classes;

use Bootstrap\Shared\Utilities\Classes\Static\Api_response;
use Firebase\JWT\JWT as Firebase_jwt;
use Firebase\JWT\Key;
use \Dump_var;

class Jwt_management {

    Protected $env_var; 

    Public function __construct( Array $envrionment_variables, Api_response $api_response ) {

        $this->env_var = $envrionment_variables; 
        $this->api_response = $api_response; 

        $this->keys = $this->get_key_pair();

    }

    Public function get_key_pair() {

        $env_var_dir = $this->env_var[ 'directories' ][ 'environment_variables' ][ 'shared' ]; 

        $public_key_filename = $env_var_dir . "keys/" . 'public_ed25519.key';
        $private_key_filename = $env_var_dir . "keys/" . 'private_ed25519.key';

        if( 
            file_exists( $public_key_filename ) &&
            file_exists( $private_key_filename )
        ) {
            
            $private_key_file = fopen( $private_key_filename , 'r' );
            $keys[ 'private' ] = fread( $private_key_file, filesize( $private_key_filename ) );
            fclose( $private_key_file );

            $public_key_file = fopen( $public_key_filename , 'r' );
            $keys[ 'public' ] = fread( $public_key_file, filesize( $public_key_filename ) );
            fclose( $public_key_file );

        } else { 

            $this->make_keys( $public_key_filename, $private_key_filename );
            $keys = $this->get_key_pair(); // Try again

        }

        return [ 
            'private' => $keys[ 'private' ], 
            'public' => $keys[ 'public' ]
        ];

    }

    Public function make_keys ( string $public_key_filename, string $private_key_filename ) {

        $key_pair = sodium_crypto_sign_keypair();
        $private_key = base64_encode(sodium_crypto_sign_secretkey($key_pair));
        $public_key = base64_encode(sodium_crypto_sign_publickey($key_pair));

        $private_key_file = fopen( $private_key_filename , 'w' );
        fwrite( $private_key_file, $private_key);
        fclose( $private_key_file );

        $public_key_file = fopen( $public_key_filename , 'w' );
        fwrite( $public_key_file, $public_key );
        fclose( $public_key_file );

    }

    Public function encode_jwt( $claims ) {

        return Firebase_jwt::encode( $claims, $this->keys[ 'private' ], 'EdDSA' );

    }

    Public function get_claims( string $jwt ) {
         
        $keys = $this->get_key_pair();
        $response = array();

        try {

            $firebase_response = Firebase_jwt::decode( $jwt, new Key( $keys[ 'public' ], 'EdDSA') );

            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'error' => false,
                'message' => 'Claims retrieved', 
                'source' => get_class(),
                'issue_id' => 'jwt_management_002', 
                'data' => $firebase_response
            ));

        } catch( \Exception $e ) {

            $response = $this->api_response->format_response(array(
                'status' => 500, 
                'message' => $e->getMessage(), 
                'source' => get_class(), 
                'issue_id' => 'jwt_management_001'
            ));

        }

        return $response; 

    }

}