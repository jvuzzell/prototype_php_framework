<?php 

namespace Bootstrap\Api_gateway\Library\Classes;

use Symfony\Component\OptionsResolver\OptionsResolver as Options_resolver;
use \Dump_var;
use ErrorException;

class Api_schema {

    Protected $schema; 

    Private const VALIDATION_FUNCTIONS = [
        'bool' => 'is_bool',
        'boolean' => 'is_bool',
        'int' => 'is_int',
        'integer' => 'is_int',
        'long' => 'is_int',
        'float' => 'is_float',
        'double' => 'is_float',
        'real' => 'is_float',
        'numeric' => 'is_numeric',
        'string' => 'is_string',
        'scalar' => 'is_scalar',
        'array' => 'is_array',
        'iterable' => 'is_iterable',
        'countable' => 'is_countable',
        'callable' => 'is_callable',
        'object' => 'is_object',
        'resource' => 'is_resource',
        'multidimensional_array' => 'is_array'
    ];

    Private const COMMON_REGEX_PATTERNS = [
        'alphanumeric' => [
            '/^[a-z0-9]$/'
        ],
        'username' => [
            '/^[a-z0-9_-]{8,24}$/'
        ],
        'email' => [
            '/^[_A-Za-z0-9-]+(\.[_A-Za-z0-9-]+)*@[A-Za-z0-9]+(\.[A-Za-z0-9]+)*(\.[A-Za-z]{2,})$/', 
            '/^([a-z0-9_\.\+-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/'
        ], 
        'phone_number' => [ 
            '/^\b\d{3}[-.]?\d{3}[-.]?\d{4}\b$/'
        ], 
        'ip_address' => [
            '/^([01]?\d\d?|2[0-4]\d|25[0-5])\.([01]?\d\d?|2[0-4]\d|25[0-5])\.([01]?\d\d?|2[0-4]\d|25[0-5])\.([01]?\d\d?|2[0-4]\d|25[0-5])$/'
        ], 
        'date' => [
            /* Date Format YYYY-MM-dd */
            '/^(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]))\1|(?:(?:29|30)(\/|-|\.)(?:0?[1,3-9]|1[0-2])\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)0?2\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9])|(?:1[0-2]))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})$/',  
            /* Date Format dd-MM-YYYY or 
               dd.MM.YYYY or
               dd/MM/YYYY
                with check for leap year */
            '/([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))/', 
            /* Date Format dd-mmm-YYYY or
               dd/mmm/YYYY or
               dd.mmm.YYYY */
            '/^(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]|(?:Jan|Mar|May|Jul|Aug|Oct|Dec)))\1|(?:(?:29|30)(\/|-|\.)(?:0?[1,3-9]|1[0-2]|(?:Jan|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec))\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)(?:0?2|(?:Feb))\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9]|(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep))|(?:1[0-2]|(?:Oct|Nov|Dec)))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})$/'
        ],   
        'time' => [
            '/^(0?[1-9]|1[0-2]):[0-5][0-9]$/', // HH:MM 12-hour without AM or PM
            '/((1[0-2]|0?[1-9]):([0-5][0-9]) ?([AaPp][Mm]))/', // HH:MM 12-hour clock with AM or PM
            '/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/',  // HH:MM 24-hour clock
            '/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', // HH:MM 24 hour
            '/(?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)/' // HH:MM:SS 24-hour
        ],
        'datetime' => [
            '/^(?![+-]?\d{4,5}-?(?:\d{2}|W\d{2})T)(?:|(\d{4}|[+-]\d{5})-?(?:|(0\d|1[0-2])(?:|-?([0-2]\d|3[0-1]))|([0-2]\d{2}|3[0-5]\d|36[0-6])|W([0-4]\d|5[0-3])(?:|-?([1-7])))(?:(?!\d)|T(?=\d)))(?:|([01]\d|2[0-4])(?:|:?([0-5]\d)(?:|:?([0-5]\d)(?:|\.(\d{3})))(?:|[zZ]|([+-](?:[01]\d|2[0-4]))(?:|:?([0-5]\d)))))$/' // ISO-8601
        ],
        'url' => [
            '/^((https?|ftp|file):\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/'
        ], 
        'zip_code' => [
            '/(^\d{5}(-\d{4})?$)|(^[ABCEGHJKLMNPRSTVXY]{1}\d{1}[A-Z]{1} *\d{1}[A-Z]{1}\d{1}$)/', // US, Canada
            '/^[0-9]{5}-[0-9]{3}$/' // Brazillian
        ], 
        'street_address' => [
            '/^$/'
        ], 
        'files' => [ 
            '/((\/|\\|\/\/|https?:\\\\|https?:\/\/)[a-z0-9 _@\-^!#$%&+={}.\/\\\[\]]+)+\.[a-z]+$/' // File path with filename and extension
        ], 
        'hexadecimal_color' => [
            '/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'
        ], 
        'html_tag' => [
            '/^<([a-z1-6]+)([^<]+)*(?:>(.*)<\/\1>| *\/>)$/'
        ], 
        'password' => [
            // Should have 1 lowercase letter, 1 uppercase letter, 1 number, 1 special character and be at least 8 characters long
            '/(?=(.*[0-9]))(?=.*[\!@#$%^&*()\\[\]{}\-_+=~`|:;\"\'<>,./?])(?=.*[a-z])(?=(.*[A-Z]))(?=(.*)).{8,}/', 
            // Should have 1 lowercase letter, 1 uppercase letter, 1 number, and be at least 8 characters long
            '/(?=(.*[0-9]))((?=.*[A-Za-z0-9])(?=.*[A-Z])(?=.*[a-z]))^.{8,}$/'
        ],
        'jwt' => [
            '(^[A-Za-z0-9-_]*\.[A-Za-z0-9-_]*\.[A-Za-z0-9-_]*$)'
        ]
    ];

    Public function __construct( array $schema ) {

        $this->schema_settings_resolver = new Options_resolver;
        $this->configure_schema_settings( $this->schema_settings_resolver );
        $schema = $this->schema_settings_resolver->resolve( $schema );

        $this->schema_field_resolver = new Options_resolver; 
        $this->configure_field_options( $this->schema_field_resolver ); 

        $this->schema_settings = $schema[ 'settings' ];
        $this->schema_fields = $this->validate_schema( $schema[ 'fields' ] );

    }   

    Public function configure_schema_settings( Options_resolver $schema_settings_resolver ) {


        $schema_settings_resolver->setDefault( 'settings', function( Options_resolver $settings_resolver ) {

            $settings_resolver
                ->setDefaults([
                        'strict_validation' => true, 
                        'resolver_callback' => null, 
                        'version' => "0.1", 
                        'experimental' => true, 
                        'deprecated' => false,
                        'title' => '', 
                        'description' => '', 
                        'reference_url' => '', 
                        'tags' => []
                    ])
                ->setAllowedTypes( 'strict_validation', 'boolean' )
                ->setAllowedTypes( 'resolver_callback', [ 'null', 'string' ] )
                ->setAllowedTypes( 'version', 'string' )
                ->setAllowedTypes( 'experimental', 'boolean' )
                ->setAllowedTypes( 'deprecated', 'boolean' )
                ->setAllowedTypes( 'description', 'string' );

                $settings_resolver->setDefault( 'contact', function( Options_resolver $contact_resolver ) {
                    $contact_resolver
                        ->setDefaults([
                                'name' => '', 
                                'url' => '', 
                                'email' => ''
                            ])
                        ->setRequired([
                                'name', 
                                'email' 
                            ])
                        ->setAllowedTypes( 'name', 'string' )
                        ->setAllowedTypes( 'url', 'string' )
                        ->setAllowedTypes( 'email', 'string' );
                });
        
                $settings_resolver->setDefault( 'response_codes', array() );

        });

        $schema_settings_resolver->setDefault( 'fields', array() );

    }

    Public function configure_field_options( Options_resolver $field_options_resolvers ) {

        $field_options_resolvers->setDefault( 'field_meta', 
            function (Options_resolver $field_meta_resolver) {
                $field_meta_resolver
                    ->setDefaults([
                            'response_field_name' => null, 
                            'resolver_callback' => null, 
                            'description' => ""
                        ])
                    ->setAllowedTypes( 'response_field_name', [ 'null', 'string' ] )
                    ->setAllowedTypes( 'resolver_callback', [ 'null', 'string' ] )
                    ->setAllowedTypes( 'description', 'string' );
            }); 

        $field_options_resolvers->setDefault( 'field_validation', 
            function (Options_resolver $field_validation_resolver) {
                $field_validation_resolver
                    ->setDefaults([
                            'required' => false, 
                            'default_value' => '', 
                            'data_type' => 'string',
                            'types_in_array' => 'string', 
                            'content_length' => null, 
                            'max_content_length' => 256, 
                            'min_content_length' => 0, 
                            'regex_expression' => '', 
                            'children_share_schema' => false, 
                            'strict_validation' => true
                        ])
                    ->setAllowedTypes( 'required', 'boolean' )
                    ->setAllowedTypes( 'data_type', 'string' )
                    ->setAllowedTypes( 'types_in_array', [ 'string' ])
                    ->setAllowedTypes( 'content_length', [ 'null', 'int' ] )
                    ->setAllowedTypes( 'min_content_length', [ 'null', 'int' ] )
                    ->setAllowedTypes( 'max_content_length', [ 'null', 'int' ] )
                    ->setAllowedTypes( 'regex_expression', 'string' )
                    ->setAllowedTypes( 'children_share_schema', 'boolean' )
                    ->setAllowedTypes( 'default_value', [
                        'null', 'string', 'bool', 'boolean', 
                        'int', 'integer', 'long', 'float', 
                        'double', 'real', 'numeric', 
                        'array'
                    ])
                    ->setAllowedValues( 'data_type', [ 
                            'null', 'string', 'bool', 'boolean', 
                            'int', 'integer', 'long', 'float', 
                            'double', 'real', 'numeric', 
                            'array', 'multidimensional_array'
                        ])
                    ->setAllowedValues( 'types_in_array', [
                            'string', 'bool', 'boolean', 
                            'int', 'integer', 'long', 'float', 
                            'double', 'real', 'numeric', 'multidimensional_array', 'mixed'
                        ])
                    ;
            });

        $field_options_resolvers->setDefault( 'children', array() );

    }

    Public function validate_schema( $schema ) {

        $tmp_result = array();
        $results = array();

        foreach( $schema as $field_key => $options ) {

            $child_count = ( isset( $schema[ $field_key ][ 'children' ] ) ) ? count( $schema[ $field_key ][ 'children' ] ) : 0; 

            if( $child_count > 0 ) {

                if( 
                    $child_count > 1 && 
                    isset( $schema[ $field_key ][ 'field_validation' ][ 'children_share_schema' ] ) &&
                    $schema[ $field_key ][ 'field_validation' ][ 'children_share_schema' ] 
                ) {

                    $response = $this->api_response->format_response(array(
                        'status' => 406, 
                        'message' => "Children can only share one schema - $field_key", 
                        'source' => get_class( $this ), 
                        'issue_id' => 'api_schema_009'
                    ));

                    $this->api_response->print_json_to_screen( $response[ 'status' ], $response );

                }

                $tmp_result = $this->validate_schema( $schema[ $field_key][ 'children' ] ); 
                $results[ $field_key ] = $this->schema_field_resolver->resolve( $options );

            } else { 

                $results[ $field_key ] = $this->schema_field_resolver->resolve( $options );
                continue;

            }

            $results[ $field_key ][ 'children' ] = $tmp_result;
            $tmp_result = array();

        }

        return $results;

    }

    Public function validate_request_body( array $request_data, array $schema_fields = array() ) {
       
        $response = array(); 
        $regex_and_content_length_accepted_types = array(
            'string', 
            'integer', 
            'int',
            'real', 
            'numeric', 
            'float', 
            'long', 
            'double'
        );

        if( count( $schema_fields ) == 0 ) {
            $schema_fields = $this->schema_fields;
        }

        if( $this->schema_settings[ 'strict_validation' ] ) {

            foreach( $request_data as $request_field_key => $request_field_value ) { 

                if( !key_exists( $request_field_key, $schema_fields ) ) { 
    
                    $this->api_response->print_json_to_screen( 
                        406, 
                        $this->api_response->format_response( 
                            array(
                                'status' => 406, 
                                'message' => "Invalid request field; not present in schema - $request_field_key", 
                                'issue_id' => 'api_schema_011',
                                'source' => get_class( $this )
                            )
                        ) 
                    );

                }

            }   

        }

        // Validate request fields against schema constraints
        foreach( $schema_fields as $schema_field_key => $field_constraints ) {

            $field_required = $field_constraints[ 'field_validation' ][ 'required' ];
            $expected_data_type = $field_constraints[ 'field_validation' ][ 'data_type' ];
            $regex_expression = $field_constraints[ 'field_validation' ][ 'regex_expression' ]; 
            $content_length = $field_constraints[ 'field_validation' ][ 'content_length' ]; 
            $min_content_length = $field_constraints[ 'field_validation' ][ 'min_content_length' ]; 
            $max_content_length = $field_constraints[ 'field_validation' ][ 'max_content_length' ];

            // Validate field exists
            $field_found_in_request = $this->api_response->on_error(
                'print_json_to_screen',
                $this->assert_field_in_request( $schema_field_key, $request_data, $field_required )
            );
            
            if( $field_found_in_request[ 'found' ] == false ) {
                // @todo - Is there a default??
                continue; // Go to the next field without processing this one further
            }

            // Validate data type
            $request_field_data_type = $this->api_response->on_error(
                'print_json_to_screen', 
                $this->assert_data_type( $expected_data_type, $schema_field_key, $request_data[ $schema_field_key ] )
            );

            // Validate content length 
            if (
                    $content_length !== null && 
                    in_array( $expected_data_type, $regex_and_content_length_accepted_types )
            ) {

                $this->api_response->on_error( 
                    'print_json_to_screen',
                    $this->assert_content_length( 
                        $content_length, 
                        $schema_field_key, 
                        $request_data[ $schema_field_key ] 
                    ) 
                );

            }

            // Validate content length 
            if (
                    $content_length === null && 
                    $min_content_length !== null && 
                    in_array( $expected_data_type, $regex_and_content_length_accepted_types )
            ) {

                $this->api_response->on_error( 
                    'print_json_to_screen',
                    $this->assert_min_content_length( 
                        $min_content_length, 
                        $schema_field_key, 
                        $request_data[ $schema_field_key ] 
                    ) 
                );

            }

            // Validate content length 
            if (
                    $content_length === null && 
                    $max_content_length !== null && 
                    in_array( $expected_data_type, $regex_and_content_length_accepted_types )
            ) {
      
                $this->api_response->on_error( 
                    'print_json_to_screen',
                    $this->assert_max_content_length( 
                        $max_content_length, 
                        $schema_field_key, 
                        $request_data[ $schema_field_key ] 
                    ) 
                );

            }

            // Validate regular expressions as needed
            if ( (
                    $regex_expression !== null &&
                    $regex_expression !== '' 
                ) &&  
                ( 
                    in_array( $expected_data_type, $regex_and_content_length_accepted_types )
                ) 
            ) {

                $this->api_response->on_error( 
                    'print_json_to_screen',
                    $this->assert_regex_match( $regex_expression, $schema_field_key, $request_data[ $schema_field_key ] ) 
                );

            }

            // Validate multidimensional array
            if( 
                $expected_data_type === 'multidimensional_array' && 
                $request_field_data_type[ 'type' ] === 'array' 
            ) {

                $this->filter_multidimensional_array( $schema_field_key, $field_constraints, $request_data ); 

            }   

            // Validate simple array
            if( 
                $expected_data_type !== 'multidimensional_array' && 
                $request_field_data_type[ 'type' ] === 'array' 
            ) { 

                $this->api_response->on_error(
                    'print_json_to_screen', 
                    $this->assert_type_of_array( $field_constraints[ 'field_validation' ][ 'types_in_array' ], $schema_field_key, $request_data[ $schema_field_key ] )
                );

            } 

        } // end loop

        $response = $this->api_response->format_response( array(
            'status' => 200, 
            'error' => false, 
            'message' => 'Valid request', 
            'issue_id' => 'api_schema_001',
            'source' => get_class( $this ), 
            'data' => $request_data
        ));

        return $response; 

    }


    /**
     * Assert Content Length
     */

    Public function assert_content_length ( $content_length, $request_field_key, $request_field_value ) {

        $num_length = strlen( (string) $request_field_value );

        if( $num_length == $content_length ) {
          
            $response = $this->api_response->format_response( array(
                'status' => 200, 
                'error' => false,
                'message' => "Content ($request_field_key) equal to character length $content_length", 
                'issue_id' => 'api_schema_014',
                'source' => get_class( $this ), 
                'data' => array( 'request_field' => $request_field_key )
            ));

        } else { 

            $response = $this->api_response->format_response( array(
                'status' => 500, 
                'message' => "Content ($request_field_key) NOT equal to character length $content_length", 
                'issue_id' => 'api_schema_015',
                'source' => get_class( $this ), 
                'data' => array( 'request_field' => $request_field_key )
            ));
        
        }

        return $response;

    }

    /**
     * Assert Min Content Length
     */

    Public function assert_min_content_length ( $min_content_length, $request_field_key, $request_field_value ) {
         
        $num_length = strlen( (string) $request_field_value );

        if( $num_length >= $min_content_length ) {
          
            $response = $this->api_response->format_response( array(
                'status' => 200, 
                'error' => false,
                'message' => "Content ($request_field_key) greater than or equal to minimum character length $min_content_length", 
                'issue_id' => 'api_schema_016',
                'source' => get_class( $this ), 
                'data' => array( 'request_field' => $request_field_key )
            ));

        } else { 

            $response = $this->api_response->format_response( array(
                'status' => 500, 
                'message' => "Content ($request_field_key) is less than minimum character length $min_content_length", 
                'issue_id' => 'api_schema_017',
                'source' => get_class( $this ), 
                'data' => array( 'request_field' => $request_field_key )
            ));
        
        }

        return $response;

    }

    /**
     * Assert Max Content Length
     */

    Public function assert_max_content_length ( $max_content_length, $request_field_key, $request_field_value ) {
         
        $num_length = strlen( (string) $request_field_value );

        if( $num_length <= $max_content_length ) {
          
            $response = $this->api_response->format_response( array(
                'status' => 200, 
                'error' => false,
                'message' => "Content ($request_field_key) less than or equal to maximum character length $max_content_length", 
                'issue_id' => 'api_schema_018',
                'source' => get_class( $this ), 
                'data' => array( 'request_field' => $request_field_key )
            ));

        } else { 

            $response = $this->api_response->format_response( array(
                'status' => 500, 
                'message' => "Content ($request_field_key) is greater than maximum character length $max_content_length", 
                'issue_id' => 'api_schema_019',
                'source' => get_class( $this ), 
                'data' => array( 'request_field' => $request_field_key )
            ));
        
        }

        return $response;

    }

    /**
     * assert_field_exists
     */

    Public function assert_field_in_request( $request_field_key = '', $request_data = array(), $is_required = false ) {

        $response = array();

        $field_found = array_key_exists( $request_field_key, $request_data ); 

        switch( true ) {
             
            case ( $is_required && !$field_found ) :

                $response = $this->api_response->format_response( array(
                    'status' => 500, 
                    'message' => "Missing required field - $request_field_key", 
                    'issue_id' => 'api_schema_003',
                    'source' => get_class( $this )
                ));

                break; 

            case ( !$field_found ) : 

                $response = $this->api_response->format_response( array(
                    'status' => 200, 
                    'error' => false, 
                    'message' => "Missing field not required - $request_field_key", 
                    'issue_id' => 'api_schema_004',
                    'source' => get_class( $this ), 
                    'data' => array( 'found' => false )
                ));

                break;

            case ( $field_found ) : 

                $response = $this->api_response->format_response( array(
                    'status' => 200, 
                    'error' => false,
                    'message' => "Found field - $request_field_key", 
                    'issue_id' => 'api_schema_005',
                    'source' => get_class( $this ), 
                    'data' => array( 'found' => true )
                ));

                break;

        }

        return $response;
    }

    /**
     * Assert data type
     * 
     * $request_field_value is mixed
     */

    Public function assert_data_type( $expected_data_type = '', $request_field_key = '', $request_field_value = null ) {
        
        $response = array();

        $is_valid_data_type = call_user_func( 
            self::VALIDATION_FUNCTIONS[ $expected_data_type ], 
            $request_field_value
        );

        $request_field_type = gettype( $request_field_value ); 

        if( $is_valid_data_type ) { 

            $response = $this->api_response->format_response( array(
                'status' => 200, 
                'error' => false,
                'message' => "Valid data type - $request_field_key", 
                'issue_id' => 'api_schema_006',
                'source' => get_class( $this ), 
                'data' => array( 'type' => $request_field_type )
            ));

        } else { 

            $response = $this->api_response->format_response( array(
                'status' => 406, 
                'message' => "Invalid data type - $request_field_key. Expected $expected_data_type; $request_field_type given.", 
                'issue_id' => 'api_schema_007',
                'source' => get_class( $this ), 
                'data' => array( 'type' => $request_field_type )
            ));

        }

        return $response; 

    }

    /**
     * Validate Type of Array
     */

    Public function assert_type_of_array( $type_of_array = '', $request_field_key = '', $request_field_value = array() ) {

        $is_valid_array = true; 

        if( count( $request_field_value ) !== count( $request_field_value, COUNT_RECURSIVE ) ) {

            $response = $this->api_response->format_response( array(
                'status' => 500, 
                'message' => "Invalid type of array; multidimensional array given - $request_field_key.", 
                'issue_id' => 'api_schema_009',
                'source' => get_class( $this )
            ));

            return $response;

        }

        for( $i = 0; $i < count( $request_field_value ); $i++ ) {
             
            $is_valid_data_type = call_user_func( 
                self::VALIDATION_FUNCTIONS[ $type_of_array ], 
                $request_field_value[ $i ]
            );

            if( !$is_valid_data_type ) {
                $is_valid_array = false; 
                break;
            }

        }

        if( $is_valid_array ) { 
            
            $response = $this->api_response->format_response( array(
                'status' => 200, 
                'error' => false,
                'message' => "Valid type of array - $request_field_key", 
                'issue_id' => 'api_schema_008',
                'source' => get_class( $this )
            ));

        } else { 

            $response = $this->api_response->format_response( array(
                'status' => 500, 
                'message' => "Invalid type of array - $request_field_key. Expected all values to be data type $type_of_array.", 
                'issue_id' => 'api_schema_009',
                'source' => get_class( $this )
            ));

        }

        return $response;

    }

    Public function assert_regex_match( $regex_expression = '', $request_field_key = '', $request_field_value = '' ) {

        $found = false; 

        if( isset( self::COMMON_REGEX_PATTERNS[ $regex_expression ] ) ) { 

            $expressions = self::COMMON_REGEX_PATTERNS[ $regex_expression ]; 

            for( $i = 0; $i < count( $expressions ); $i++ ) { 
                
                if( preg_match( $expressions[ $i ], $request_field_value ) ) { 

                    $found = true; 
                    break; 
        
                } 
        
            }

        } else {
             
            if( preg_match( $regex_expression, $request_field_value ) ) { 

                $found = true; 

            }

        }

        if( $found ) { 

            $response = $this->api_response->format_response( array(
                'status' => 200,
                'error' => false, 
                'message' => "Field matches expression - $request_field_key", 
                'issue_id' => 'api_schema_011',
                'source' => get_class( $this )
            ));

        } else {

            $response = $this->api_response->format_response( array(
                'status' => 406, 
                'message' => "Invalid request field; must match schema format for field - $request_field_key", 
                'issue_id' => 'api_schema_012',
                'source' => get_class( $this )
            ));
            
        }

        return $response; 

    }

    Public function filter_multidimensional_array( $schema_field_key = '', $field_constraints = array(), $request_data = array() ) {

        if( $field_constraints[ 'field_validation' ][ 'strict_validation' ] ) {

            foreach( $request_data[ $schema_field_key ] as $child_key => $child_value ) { 

                if( !key_exists( $child_key, $field_constraints[ 'children' ] ) ) { 
                    
                    $response = $this->api_response->format_response( array(
                        'status' => 406, 
                        'message' => "Invalid request field; not present in schema - $child_key", 
                        'issue_id' => 'api_schema_010',
                        'source' => get_class( $this )
                    ));
    
                    $this->api_response->print_json_to_screen( $response[ 'status' ], $response );

                }

            }   

        }

        if( count( $field_constraints[ 'children' ] ) > 0 ) {

            if( $field_constraints[ 'field_validation' ][ 'children_share_schema' ] ) {

                $child_archetype_key = array_key_first( $field_constraints[ 'children' ] );

                foreach( $request_data[ $schema_field_key ] as $child_key => $child_value ) {

                    // Add new elements to schema based on the archetype for every child detected in request 
                    $field_constraints[ 'children' ][ $child_key ] = $field_constraints[ 'children' ][ $child_archetype_key ];

                    // Validate children against parent
                    $this->validate_request_body( $request_data[ $schema_field_key ], $field_constraints[ 'children' ] );
                    
                }
                
            } else {

                $this->validate_request_body( $request_data[ $schema_field_key ], $field_constraints[ 'children' ] );

            }

        }

    }

}