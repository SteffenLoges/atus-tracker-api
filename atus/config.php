<?php

// ------------------------------------------------------------------------------------------------
// -- START OF CONFIGURATION ----------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------

// setting ATUS__DEBUG to true will display all php errors and exceptions and append the stack trace to the error message
// should be set to false in production
define('ATUS__DEBUG', true);

// copy & paste the authentication token from the ATUS "API" page
define('ATUS__AUTHENTICATION_TOKEN', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');

// URL to the reverse proxy configured in your webserver
// by default this is http[s]://[your-tracker-url]/atus-proxy
define('ATUS__REVERSE_PROXY_URL', 'https://[your-tracker-url]/atus-proxy');

// can either be NETVISION or TBDEV
// if you are using a different tracker software, you will most likely have to heavily modify the code
define('ATUS__DERIVED_FROM', 'NETVISION');

// how many entries should be displayed in the upcoming releases table
define('ATUS__UPCOMING_RELEASES__MAX_ENTRIES', 10);

// how often should the upcoming releases table be refreshed (in milliseconds)
define('ATUS__UPCOMING_RELEASES__REFRESH_INTERVAL', 4000);

// MySQL database configuration
define('ATUS__DB_HOST', 'localhost');
define('ATUS__DB_PORT', 3306);
define('ATUS__DB_USER', 'user');
define('ATUS__DB_PASS', 'pass');
define('ATUS__DB_NAME', 'table');
define('ATUS__DB_CHARSET', 'utf8');

// ------------------------------------------------------------------------------------------------
// -- END OF CONFIGURATION ------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------
// -- You dont need to edit anything below this line ----------------------------------------------
// ------------------------------------------------------------------------------------------------

ini_set('display_errors', (int)ATUS__DEBUG);

// there are a ton of notices and warnings in both TBDev and Netvision, 
// so we'll set the error reporting level to E_ALL & ~E_NOTICE & ~E_WARNING
// if you are stuck and don't know why your script is not working, you can set this to E_ALL
error_reporting(ATUS__DEBUG ? E_ALL & ~E_NOTICE & ~E_WARNING : 0);

set_time_limit(60);
ini_set('memory_limit', '16M');
ini_set('max_execution_time', '120');
