<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once 'core/functions.php';
include_once 'core/jwt.php';

PerformInitialAuthChecks();

echo ToJSON([
    'IsValid' => true,
]);