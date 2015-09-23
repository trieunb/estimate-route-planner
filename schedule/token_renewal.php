<?php
require_once 'bootstrap.php';

$loger = new ERPLogger('token_renewal.log');
$loger->log('=== Renewal token started - ' . date('Y-m-d H:i:s'));
if (ERPConfig::isOauthTokensRenewable()) {
    $loger->log('INFO: start getting new tokens');
    $prefs = ORM::forTable('preferences')->findOne();

    // Prepare Service Context
    $requestValidator = new OAuthRequestValidator(
        $prefs->qbo_oauth_token,
        $prefs->qbo_oauth_secret,
        $prefs->qbo_consumer_key,
        $prefs->qbo_consumer_secret
    );

    $serviceContext = new ServiceContext(
        $prefs->qbo_company_id,
        IntuitServicesType::QBO,
        $requestValidator
    );

    if (!$serviceContext) {
        $loger->log('ERROR: problem while initializing ServiceContext.');
        exit;
    }

    $platformService = new PlatformService($serviceContext);

    // Call Reconnect API
    $response = $platformService->Reconnect();
    switch ($response->ErrorCode) {
        case '0': // Success
            $loger->log('INFO: Succeed got new token!.');
            // Update renewed tokens
            $prefs->qbo_oauth_token = $response->OAuthToken;
            $prefs->qbo_oauth_secret = $response->OAuthTokenSecret;
            $prefs->qbo_token_expires_at = date("Y-m-d H:i:s", strtotime("+179 days"));
            if ($prefs->save()) {
                $loger->log('INFO: New tokens updated successfully.');
            } else {
                $loger->log('ERROR: Failed to save new tokens!');
            }
        case '212': // The request is made outside the 30-day window bounds.
            $loger->log('ERROR: The request is made outside the 30-day window bounds.');
            break;
        case '270': // The OAuth access token has expired.
            $loger->log('ERROR: The current token as expired or invalid.');
            // Empty the token to tell admin to re-authenticate
            $prefs->qbo_oauth_token = null;
            $prefs->qbo_oauth_secret = null;
            $prefs->qbo_token_expires_at = null;
            $prefs->save();
            break;
        case '22':
            $loger->log('ERROR: App consumser key invalid!.');
        default:
            break;
    }
} else {
    $loger->log('INFO: current token is not renewalable');
}
$loger->log('=== Done');
?>
