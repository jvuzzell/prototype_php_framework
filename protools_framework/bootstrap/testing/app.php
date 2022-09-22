<?php 

// @TODO Kill direct access

/**
 * @TODO
 * 1. Route request to test plan
 * 2. Publish Test Plan Results to client 
 * 3. Commit Test Plan Summary to DB
 * 4. Add meta to complete context of test plan execution
 * 
 */

$request = Test_router::get_test_plan( $Environment_config->get_env_config()[ 'app_path' ] );

// if( $request[ 'error' ] ) {
    
//     if( IS_CLI ) {
        
//         // Instantiate Test Plan based 
//         Api_response::print_json( $request[ 'status' ], $request, 'dev' );  

//     } else {

//         Api_response::route_to_custom_page( 404, $request, ERROR_PAGE, 'dev' );

//     }
    
// }

// $request_route = $request[ 'data' ][ 'route' ]; 

// //Dump_var::dump( Test_router::get_test_plan( $request_route ) );

// $Assertions = new Assertions();
// $Test_case = new Test_case( $Assertions );


// $files_to_find = array(
//     '*.php'
// );

// $search_args = array(
//     'starting_directory' => SHARED_LIBRARY_DIR,
//     'search_direction' => 'children'
// );

// Dump_var::print( $Directory_search->cwd() );

// Dump_var::print( $Directory_search->search( $files_to_find, $search_args ) );



