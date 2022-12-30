<?php 

return array(
    'dev' => array(
        'databases' => array(
            'cms_database' => array(
                'dsn'         => '', // Required for ODBC
                'host'       => 'localhost', 
                'username'   => 'my', // Required
                'password'   => 'chip', // Required
                'port'       => '3306', 
                'database'   => 'pro_tools_cms', 
                'charset'    => 'utf8', 
                'pdo_driver' => 'mysql'
            )
        ),
        'curl' => array(
            'protools_api' => array(
                'request_url'        => 'http://api.prototypes.dv',
                'bearer_token'       => '',
                'authorization_type' => 'bearer-token'
            )
        ),
        'email_server' => array(
            'default_headers'  => "MIME-Version: 1.0" . "\r\n" . "Content-type: text/html; charset=UTF-8", 
            'smtp_user'        => 'admin', 
            'smtp_pass'        => '', 
            'smtp_host'        => 'localhost', 
            'smtp_secure'      => 'none', 
            'smtp_port'        => '25', 
            'sender_address'   => 'josh.uzzell@gmail.com', 
            'sender_name'      => 'Joshua Uzzell', 
            'protocol'         => 'smtp',
        ), 
        'system_mail_list' => array(
            'josh.uzzell@gmail.com'
        ),
        'database_log' => array(
            'pro_tools_cms' => 'rsc_api_logs'
        )
    )
);
