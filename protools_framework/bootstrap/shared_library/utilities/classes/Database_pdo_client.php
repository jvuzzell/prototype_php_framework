<?php 

/**
 * 
 * @method construct
 * @method close
 * @method connect
 * @method execute
 *
 */

class Database_pdo_client {

    private $nonce;
    private $key;
    private $dsn;
    private $pdo_options;
    private $client;

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
            throw new Exception( 'Database client - encryption_method not found' );
        }

        if ( isset( $decryption_method ) && function_exists( $decryption_method ) ) {
            $this->decrypt = $decryption_method;
        } else {
            throw new Exception( 'Database client - decryption_method not found' );
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

        $this->args[ 'username' ] = call_user_func( $this->encrypt, $this->args[ 'username' ], $this->nonce, $this->key ); 
        $this->args[ 'password' ] = call_user_func( $this->encrypt, $this->args[ 'password' ], $this->nonce, $this->key );

        // Data Source Name 
        if( $args[ 'pdo_driver' ] == 'mysql' ) {
            $this->dsn = 'mysql:host='. $args[ 'host' ] .';port=' . $args[ 'port' ]  . ';dbname='. $args[ 'database' ] .';charset=' . $args[ 'charset' ];
        } else if( $args[ 'pdo_driver' ] == 'dblib' ) {
            $this->dsn = 'dblib:host=' . $args[ 'dsn' ] . ';dbname=' . $args[ 'database' ];
        } else if( $args[ 'pdo_driver' ] == 'odbc' ) {
            $this->dsn = "odbc:" . $args[ 'dsn' ];
        } else {
            $this->dsn = 'sqlsrv:Server=' . $args[ 'host' ] . ',' . $args[ 'port' ] . ';Database=' . $args[ 'database' ] .';charset=utf8mb4';
        }

        // Set PDO options 
        $default_options = [ 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]; 

        $pdo_options = ( isset( $args[ 'pdo_options' ] ) ) ? $args[ 'pdo_options' ] : [];
        $this->pdo_options = array_replace( $default_options, $pdo_options );

    }  


    public function connect() : array {

        try {

            $this->client = new PDO(
                $this->dsn,
                call_user_func( $this->decrypt, $this->args[ 'username' ], $this->nonce, $this->key ),
                call_user_func( $this->decrypt, $this->args[ 'password' ], $this->nonce, $this->key ),
                $this->pdo_options
            );

        } catch ( PDOException $e ) {
        
            return array( 
                'status'  => 500,
                'error'   => TRUE,
                'system'  => array(
                    'issue_id' => 'database_pdo_client_001',
                    'log'      => FALSE, 
                    'private'  => FALSE, 
                    'continue' => FALSE, 
                    'email'    => FALSE
                ),
                'source' => get_class(),
                'message' => "Failed to get DB handle: " . $e->getMessage() . "\n",
                'data'  => array(
                    'exception' => array( 
                        'message' => $e->getMessage(), 
                        'code' => $e->getCode(),
                        'file' => $e->getFile(), 
                        'line' => $e->getLine()
                    )
                )
            );

        }

        return array( 
            'status'  => 200,
            'error'   => FALSE,
            'system'  => array(
                'issue_id' => 'database_pdo_client_002',
                'log'      => FALSE, 
                'private'  => FALSE, 
                'continue' => FALSE, 
                'email'    => FALSE
            ),
            'source' => get_class(),
            'message' => 'Connection successful, database: ' . $this->dsn,
            'data'    => null
        ); 

    }

    public function close(){
        $this->client = null;
    }

    /**
     * Execute SQL statement 
     * 
     * @param string $args 
     * 
     * @return object returns data object containing query results 
     */

    public function execute( array $args ) {
        
        if( $this->connect()[ 'error' ] ) {

            return array( 
                'status'  => 500,
                'error'   => TRUE,
                'system'  => array(
                    'issue_id' => 'database_pdo_client_003',
                    'log'      => FALSE, 
                    'private'  => FALSE, 
                    'continue' => FALSE, 
                    'email'    => FALSE
                ),
                'source' => get_class(),
                'message' => 'Failed to open database connection', 
                'data'    => array(
                   'connection_response' => $this->connect()
                )
            );

        }

        // Manage input parameters
        $default_args = array(
            'sql'                 => '', // string, sql statement
            'bindings'            => '', 
            'fetch_style'         => PDO::FETCH_ASSOC, 
            'return_stmt_obj'     => false, 
            'debug_dump_params'   => false, 
            'execution_type'      => 'query', // Possible values: 'query' or 'exec'
            'return_response'     => true
        );

        $args = array_merge( $default_args, $args );

        // Simplify variable names 
        $sql              = $args[ 'sql' ];
        $bindValues       = $args[ 'bindings' ];
        $fetch_style      = $args[ 'fetch_style' ];
        $return_stmt_obj  = $args[ 'return_stmt_obj' ];
        $execution_type   = $args[ 'execution_type' ];
        $return_response  = $args[ 'return_response' ];
        $query_results    = array(); 

        // Run simple query
        if( !$bindValues && $execution_type == 'query' ) {
            
            try {
                $stmt = $this->client->query( $sql );
            } catch( Exception $e ) {
                return array( 
                    'status'  => 500,
                    'error'   => TRUE,
                    'system'  => array(
                        'issue_id' => 'database_pdo_client_005',
                        'log'      => FALSE, 
                        'private'  => FALSE, 
                        'continue' => FALSE, 
                        'email'    => FALSE
                    ),
                    'source' => get_class(),
                    'message' => $e->getMessage(), 
                    'data'    => $args
                );
            }

        } else if( !$bindValues && $execution_type == 'exec' ) {
            
            try {
                $stmt = $this->client->exec( $sql ); 
            } catch( Exception $e ) {
                return array( 
                    'status'  => 500,
                    'error'   => TRUE,
                    'system'  => array(
                        'issue_id' => 'database_pdo_client_004',
                        'log'      => FALSE, 
                        'private'  => FALSE, 
                        'continue' => FALSE, 
                        'email'    => FALSE
                    ),
                    'source' => get_class(),
                    'message' => $e->getMessage(), 
                    'data'    => $args
                );
            }

        } else {

            // Run prepared statements
            $stmt = $this->client->prepare( $sql );

            // Bind variables
            if ( isset( $bindValues['fields'] ) && is_array( $bindValues['fields'] ) ) {
     
                $arg_cnt = 1;
                for ($i = 0; $i < count( $bindValues['fields'] ); $i++ ) {
                    $bindValue  = $bindValues['fields'][$i]["value"];
                    $param_type = $bindValues['fields'][$i]["type"];
                    $data_type  = $bindValues['fields'][$i]["data_type"];

                    if ( $param_type == 'input' ) {

                        $stmt->bindValue( $arg_cnt++, $bindValue, $data_type );

                    } elseif ( $param_type == 'output' ) {

                        $stmt->bindParam( $arg_cnt++, $proc_pass_val, $data_type );

                    }

                }

            }

            try {
                
                // Execute SQL statement
                $stmt->execute();

            } catch( Exception $e ) {

                return array( 
                    'status'  => 500,
                    'error'   => TRUE,
                    'system'  => array(
                        'issue_id' => 'database_pdo_client_006',
                        'log'      => TRUE, 
                        'private'  => FALSE, 
                        'continue' => FALSE, 
                        'email'    => FALSE
                    ),
                    'source' => get_class(),
                    'message' => $e->getMessage(), 
                    'data'    => $args
                );

            }
            
        }

        // Return results or SQL statements 
        if( $return_response && !$return_stmt_obj && $execution_type == 'query' ) {

            // var_dump( $stmt );
            $results     = $stmt->fetchAll( $fetch_style );
            $error_array = $stmt->errorInfo();

            // '00000' Represents PDOstatements success response
            if( $error_array[0] == '00000' ) {
                $query_results = $results; 
            } else { 
                array( 
                    'status'  => 500,
                    'error'   => TRUE,
                    'system'  => array(
                        'issue_id' => 'database_pdo_client_007',
                        'log'      => TRUE, 
                        'private'  => FALSE, 
                        'continue' => FALSE, 
                        'email'    => FALSE
                    ),
                    'source' => get_class(),
                    'message' => 'Failed to execute SQL statement.', 
                    'data'    => $error_array
                );
            }
        } 

        if( $args[ 'debug_dump_params' ] && $execution_type == 'query' ) {
            var_dump( $stmt->debugDumpParams());
        }

        // Clear last query
        if( $execution_type == 'query' ) {
            $stmt->closeCursor();
        }

        // Return array or null
        return array( 
            'status'  => 200,
            'error'   => FALSE,
            'source' => get_class(),
            'message' => 'Statement ran successfully',
            'system'  => array(
                'issue_id' => 'database_pdo_client_008',
                'log'      => FALSE, 
                'private'  => FALSE, 
                'continue' => FALSE, 
                'email'    => FALSE
            ),
            'data'    => ( !$return_stmt_obj && isset( $query_results ) ) ? $query_results : $stmt, // array of queried rows or integer representing the number of affected rows
            'stmt_obj'     => ( $return_stmt_obj ) ? $stmt : null, // PDOStatement
            'affected_rows' => ( $execution_type == 'exec' ) ? $stmt : null, // integer,
        ); 

    } 

    public function getLastInsertId() {
        return intval( $this->client->lastInsertId() );
    }

    public function getErrorInfo() {
        return $this->client->errorInfo();
    }

    public function call( $db_client_request_args, $source = 'Database_odbc_client', $issue_id = 'database_pdo_client_009' ) {

        try {

            $db_request_client_response = $this->execute( $db_client_request_args ); 
            
            if( $db_request_client_response[ 'error' ] ) {
                throw new Exception( $db_request_client_response[ 'message' ], 1 );
            }

            $db_response = $db_request_client_response;

        } catch( Exception $e ) {

            $db_response = array( 
                'status'  => 500,   // int
                'error'   => TRUE, // bool
                'system'  => array(
                    'issue_id' => $issue_id, 
                    'log'      => TRUE,    
                    'private'  => TRUE,    
                    'continue' => TRUE,    
                    'email'    => FALSE
                ),
                'source'  => $source,     
                'message' => $e->getMessage(),    
                'data'    => array() 
            );

        }
        
        return $db_response; 

    }

}
