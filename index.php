<?php

// Kickstart the framework
$f3 = require('vendor/f3/lib/base.php');
require_once 'vendor/swiftmailer/lib/swift_required.php';

// Load configuration
$f3->config('config.ini');

require_once('bootstrap.php');

$f3->run();