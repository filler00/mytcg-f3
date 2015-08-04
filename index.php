<?php

// Kickstart the framework
$f3 = require('vendor/f3/lib/base.php');

// Load configuration
$f3->config('config.ini');

require_once('bootstrap.php');

$f3->run();