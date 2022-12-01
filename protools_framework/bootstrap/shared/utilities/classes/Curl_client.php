<?php 

namespace Bootstrap\Shared\Utilities\Classes;

use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;
use Dump_var;
use \Exception;


/**
 * cURL client with basic authorization
 * 
 * Public Methods
 *      @method execute 
 *      @method set_encryption
 *      @method set_client_args
 *      @method 
 * 
 * Private Methods 
 *      @method response
 *      @method parse_results
 *      @method format_results
 *      @method get_client_errors
 */

class Curl_client {

    private $nonce;
    private $key;

    private $client; 
    private $json_response;

    public function __construct( $args = array() ) {

        if( !empty( $args ) ) {
            $this->set_client_args = array( $args );
        }

    }

    /**
     * Set Encryption 
     */

    public function set_encryption( string $nonce, string $key, string $encryption_method, string $decryption_method ) {

        $this->nonce = ( isset( $nonce ) ) ? random_bytes( 24 ) : $nonce; 
        $this->key = ( isset( $key ) ) ? random_bytes( 32 ) : $key; 
     
        if ( isset( $encryption_method ) && function_exists( $encryption_method ) ) {
            $this->encrypt = $encryption_method;
    
        } else {
            throw new Exception( 'Curl client - encryption_method not found' );
        }

        if ( isset( $decryption_method ) && function_exists( $decryption_method ) ) {
            $this->decrypt = $decryption_method;
        } else {
            throw new Exception( 'Curl client - decryption_method not found' );
        }

    }

    /**
     * Set_credentials
     */

    public function set_client_args( $args ) {
        
        // Defaults
        $default_args = array(
            'encryption' => array(
                'nonce'             => NULL, 
                'key'               => NULL, 
                'encryption_method' => NULL, 
                'decryption_method' => NULL
            ),
            'request_url'          => NULL,
            'append_uri'           => FALSE, 
            'bearer_token'         => NULL,
            'request_method'       => 'POST',
            'request_data'         => NULL,
            'return_response'      => TRUE,
            'json_response'        => TRUE, // Represents "do you want to retrieve json response"
            'decode_json_as_array' => FALSE,
            'username'             => NULL,
            'password'             => NULL, 
            'content_type'         => 'json', // This is the content type of the client request
            'additional_headers'   => FALSE, 
            'authorization_type'   => FALSE // possible values (FALSE, "default-agent" or "bearer-token")
        );
    
        $this->args = array_merge( $default_args, $args );

        $this->set_encryption( 
            $this->args[ 'encryption' ][ 'nonce' ], 
            $this->args[ 'encryption' ][ 'key' ], 
            $this->args[ 'encryption' ][ 'encryption_method' ], 
            $this->args[ 'encryption' ][ 'decryption_method' ] 
        );

        if( $this->args[ 'bearer_token' ] !== NULL ) {
            $this->args[ 'bearer_token' ] = call_user_func( $this->encrypt, $this->args[ 'bearer_token' ], $this->nonce, $this->key );
        }

        if( $this->args[ 'username' ] !== NULL && $this->args[ 'password' ] !== NULL ) {
            $this->args[ 'username' ] = call_user_func( $this->encrypt, $this->args[ 'username' ], $this->nonce, $this->key ); 
            $this->args[ 'password' ] = call_user_func( $this->encrypt, $this->args[ 'password' ], $this->nonce, $this->key );
        }
        
    }  

    /**
     * Execute request 
     * 
     * @return mixed response from remote endpoint
     */

    public function execute( $args ) {
      
        $args = array_merge( $this->args, $args );
   
        $this->json_response = $args[ 'json_response' ]; 

        $request_data = $args[ 'request_data' ]; 

        switch( $args[ 'content_type' ] ) {
            case 'json' : 
                $contentType = 'application/json';
                break; 
            case 'multipart-form' : 
                $contentType = 'multipart/form-data';
                break;
            case 'form-urlencoded' :
                $contentType = 'application/x-www-form-urlencoded';
                break; 
            default : 
                $contentType = null; 
                break;
        }

        // Construct Request URL
        $requestUrl = $args[ 'request_url' ]; 

        // Append URI 
        if ( $args[ 'append_uri' ] !== FALSE ) {
            // TODO: trim leading and trailing slashes
            $requestUrl = $requestUrl . '/' . $args[ 'append_uri' ];
        } 

        // APPEND $_GET Parameters to Request Url  
        if( strtoupper( $args[ 'request_method' ] ) == 'GET' ) {

            $requestUrl .= '?';
            $params = array();
            if ($args[ 'request_data' ]){
                foreach( $args[ 'request_data' ] as $key => $value ) {
                    $params[] = "$key=". urlencode( $args[ 'request_data' ][$key] );
                }
            }

            $requestUrl .= implode( '&', $params );

            // Clean up
            $request_data = NULL;

        } 
        
        $this->client = curl_init(); 

        if( is_array( $request_data ) ) {
            $request_data = json_encode( $request_data );
        }

        curl_setopt( $this->client, CURLOPT_URL            , $requestUrl );
        curl_setopt( $this->client, CURLOPT_RETURNTRANSFER , TRUE );
        curl_setopt( $this->client, CURLOPT_VERBOSE        , 1 );
        curl_setopt( $this->client, CURLOPT_HEADER         , 1 );
        curl_setopt( $this->client, CURLOPT_MAXREDIRS      , 10 );
        curl_setopt( $this->client, CURLOPT_TIMEOUT        , 30 ); // seconds
        curl_setopt( $this->client, CURLOPT_CUSTOMREQUEST  , $args[ 'request_method' ] ); 
        curl_setopt( $this->client, CURLOPT_POSTFIELDS     , $request_data );
        curl_setopt( $this->client, CURLOPT_SSL_VERIFYHOST , FALSE ); // TODO: Add SSL to server and remove these 
        curl_setopt( $this->client, CURLOPT_SSL_VERIFYPEER , FALSE ); // TODO: Add SSL to server and remove these 
        curl_setopt( $this->client, CURLOPT_HTTPAUTH       , CURLAUTH_ANY );
        curl_setopt( $this->client, CURLINFO_HEADER_OUT    , TRUE ); 
        
        /**
         * Customize HTTP Headers
         */

        $httpHeaders = array();
        array_push( $httpHeaders, 'Content-Type:' . $contentType ); 
    
        // Set authorization header
        if( 
            ( $args[ 'authorization_type' ] !== FALSE ) &&
            ( $args[ 'authorization_type' ] == 'basic' ||
              $args[ 'authorization_type' ] == 'bearer-token' )
        ) {

            $authConfigs = array(
                'bearer-token'  => '', 
                'default-agent' => '', 
                'basic'         => '',
            );

            if( 
                ( $args[ 'authorization_type' ] == 'basic' ) && 
                isset( $args[ 'username' ] ) && 
                isset( $args[ 'password' ] ) 
            ) {
           
                curl_setopt( $this->client, CURLOPT_USERNAME, call_user_func( $this->decrypt, $args[ 'username' ], $this->nonce, $this->key ) );
                curl_setopt( $this->client, CURLOPT_PASSWORD, call_user_func( $this->decrypt, $args[ 'password' ], $this->nonce, $this->key ) );

                $authConfigs[ 'basic' ] = "Authorization: Basic " . base64_encode( 
                    call_user_func( $this->decrypt, $args[ 'username' ], $this->nonce, $this->key ) . ":" . 
                    call_user_func( $this->decrypt, $args[ 'password' ], $this->nonce, $this->key )
                );
            }
   
            if( 
                ( $args[ 'authorization_type' ] == 'bearer-token' ) && 
                isset( $args[ 'bearer_token' ] )
            ) {
                $authConfigs[ 'bearer-token' ] = "Authorization: Bearer " . call_user_func( $this->decrypt, $args[ 'bearer_token' ], $this->nonce, $this->key );
            }

            array_push( $httpHeaders, $authConfigs[ $args[ 'authorization_type' ] ] );

        }
        
        // Set additional headers
        if( $args[ 'additional_headers' ] !== FALSE ) {
            foreach( $args[ 'additional_headers' ] as $header_clause ) {
                array_push( $httpHeaders, $header_clause );
            }
        }

        // Add request length to client request if PUT method used
        if( strtoupper( $args[ 'request_method' ] ) === 'PUT' ) {

            array_push( $httpHeaders, 'Content-Length: ' . $args[ 'request_data' ] );  
             
        }

        // Set header 
        curl_setopt( $this->client, CURLOPT_HTTPHEADER, $httpHeaders );

        /**
         * Execute and process curl call 
         */

        $results = curl_exec( $this->client ); 
        $response = $this->format_results( $results );  
        curl_close( $this->client ); 

        // Return formatted response
        return $response; 
    
    }
    
    /**
     * Format Response 
     * 
     * @param string $results curl response
     * 
     * @return array results in standardized API response format
     */

    private function format_results( string $results ) {

        $header_size = curl_getinfo( $this->client, CURLINFO_HEADER_SIZE );
        $header = substr( $results, 0, $header_size );
        $body = substr( $results, $header_size ); 
        $status_code = curl_getinfo($this->client, CURLINFO_HTTP_CODE);
        $error = false; 
        $message = 'cURL call successful';

        /**
         * @todo parse non-json responses
         */        

        if( $this->json_response && ( json_decode( $body ) !== NULL ) ) {
            $results = json_decode( $body, true );
        } else {
            $results = $body;
        }

        if(
            $status_code !== 200 && 
            $status_code !== 201 && 
            $status_code !== 202 &&
            $status_code !== 203 &&
            $status_code !== 204 &&
            $status_code !== 205 &&
            $status_code !== 206 
        ) { 
            $error = true;
  
            if( curl_error( $this->client ) ) {
                $message = curl_error( $this->client ); 
            } else { 
                $message = $results[ 'message' ];
            }

        } 

  
        $response = Api_response::format_response( array(
            'status'  => $status_code, // Success
            'error'   => $error, 
            'issue_id' => 'curl_client_001', 
            'source' => get_class(),
            'message' => $message, 
            'data' => $results
        ));
        
        return $response; 

    }

}