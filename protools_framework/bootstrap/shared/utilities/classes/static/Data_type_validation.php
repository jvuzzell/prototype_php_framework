<?php 

namespace Bootstrap\Shared\Utilities\Classes\Static; 

class Data_type_validation {

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
        ]
    ];

    Private const ALLOWED_TYPES = [
        'null', 'string', 'bool', 'boolean', 
        'int', 'integer', 'long', 'float', 
        'double', 'real', 'numeric', 
        'array', 'multidimensional_array'
    ];


    public function get_variable_bindings( $attributes = array() ) {

        /**
         * $attributes = array(
         *      'key' => array(
         *          'value' => '', // mixed - int, string, bool, float, 
         *          'data_type' => '' // string - int, integer, 
         *      )
         * )
         */

        

        $variables = array();

        foreach( $attributes as $key => $value ) {

            $variables[]= array(
                'type' => 'input', 
                'name' => ':'.$key, 
                'value' =>$value, 
                'data_type' => PDO::PARAM_INT, 
            );

        }

        $variable_bindings = array(
            'fields' => $variables
        );

        return $variable_bindings;
    }
    
}