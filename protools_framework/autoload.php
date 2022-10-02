<?php 

function protools_specific_autoloader($className) {

    $fileName = '';
    $namespace = '';

    // Sets the include path as the "src" directory
    $includePath = dirname(__FILE__);

    if (false !== ($lastNsPos = strripos($className, '\\'))) {
        $namespace = strtolower( substr($className, 0, $lastNsPos) );
        $className = substr($className, $lastNsPos + 1);
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    
    $fileName .= $className . '.php';
    $fullFileName = $includePath . DIRECTORY_SEPARATOR . $fileName;

    Dump_var::print( $fullFileName ); exit;
    if (file_exists($fullFileName)) {
        require $fullFileName;
    }
    
}

spl_autoload_register( 'protools_specific_autoloader' ); // Registers the autoloader