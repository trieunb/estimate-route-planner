<?php
require_once 'bootstrap.php';

$serviceType = IntuitServicesType::QBD;

$prefs = ORM::forTable('preferences')->findOne();
// Prep Service Context
$requestValidator = new OAuthRequestValidator(
    $prefs->qbo_oauth_token,
    $prefs->qbo_oauth_secret,
    $prefs->qbo_consumer_key,
    $prefs->qbo_consumer_secret
);
$serviceContext = new ServiceContext($prefs->qbo_company_id, $serviceType, $requestValidator);
if (!$serviceContext)
	exit("Problem while initializing ServiceContext.\n");

// Prep Platform Services
$platformService = new PlatformService($serviceContext);

// Call Reconnect
$xmlObj = $platformService->Reconnect();
var_dump($xmlObj);
echo $xmlObj->ErrorMessage;
?>
