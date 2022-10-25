<?php

namespace ATUS;

use ATUS\libs\ATUS;

define('ATUS', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/ATUS.php';

// check if php version is 5.6 or higher
if (version_compare(PHP_VERSION, '5.6.0', '<')) {
  die('PHP 5.6 or higher is required');
}

ATUS::init();
