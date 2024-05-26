<?php

header("Cache-Control: no-cache,no-store,max-age=0,must-revalidate");
header("Pragma: no-cache");
header("Expires: -1");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// application page-build-timer start
// $mtime = explode(" ",microtime()); 
// $starttime = $mtime[1] + $mtime[0]; 

// Define BASEPATH as this file's directory
define('BASEPATH', dirname(__FILE__) . '/');

// set BASEPATH to server path to install
// define('BASEPATH', '/var/www/html/application/');
/* set INSTANCE_BASEPATH to server path for this instance  */
// define('INSTANCE_BASEPATH', dirname(__FILE__) . '/');
// pre-load the config file
// require_once(INSTANCE_BASEPATH . 'vce-config.php');

// start application
require_once(BASEPATH . 'vce-application/initiate.php');

