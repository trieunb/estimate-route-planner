<?php

require_once 'bootstrap.php';

echo "=== Upgrading ERPP to 1.8.1 ... \n";

$capRegister = new ERPCapabilityRegister();
$capRegister->register([
    'erpp_restrict_client_dropdown' => [],
    'erpp_hide_expired_estimates'   => []
]);

echo "=== Upgrade successfully\n";