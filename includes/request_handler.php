<?php

function erp_ajax_handler() {
    require_once ERP_PLUGIN_DIR . 'init.php';
    $app = new ERPApp();
    // Start app
    $app->letGo();
    wp_die();
}
/**
 * Add custom query in url
 */
function erp_query_vars($vars) {
    $vars[] = '_do';
    return $vars;
}

function erp_parse_request($wp) {
    require_once ERP_PLUGIN_DIR . 'init.php';
    if (array_key_exists('_do', $wp->query_vars)) {
        // Handler Quickbooks authentication
        if (current_user_is_admin()) {
            $authService = new QuickbooksAuth();
            if ($wp->query_vars['_do'] == 'startQuickbooksAuthenticate') {
                // PHP Session
                erp_start_session();
                $result = $authService->start(site_url() . '?_do=verifyQuickbooksAuthenticate');
                $_SESSION['oauth_token_secret'] = null;
                if ($result['success']) {
                    $_SESSION['oauth_token_secret'] = $result['oauth_token_secret'];
                    wp_redirect($result['redirect_url']); exit;
                } else {
                    wp_die(
                        "Error while authenticating with Quickbooks: " . $result['message'] . '<br>'.
                        'Please make sure the app consumer keys are correct.'
                    );
                }
            } elseif ($wp->query_vars['_do'] == 'verifyQuickbooksAuthenticate') {
                erp_start_session();
                $result = $authService->getOauthAccessToken(
                    $_GET['oauth_token'], $_SESSION['oauth_token_secret']
                );
                if ($result['success']) {
                    // Insert to perferences table
                    $data = [];
                    $data['qbo_oauth_token']      = $result['oauth_token'];
                    $data['qbo_oauth_secret']     = $result['oauth_token_secret'];
                    $data['qbo_company_id']       = $_GET['realmId'];
                    $data['qbo_token_expires_at'] = date("Y-m-d H:i:s", strtotime("+179 days"));
                    $prefs = ORM::forTable('preferences')->findOne();
                    if (!$prefs) {
                        $prefs = ORM::forTable('preferences')->create();
                    }
                    $prefs->set($data);
                    if ($prefs->save()) {
                        require_once TEMPLATES_DIR . 'plugin/quickbooks-authenticate-success.php';
                    } else {
                        wp_die("Error while saving user OAuth tokens to database!");
                    }
                } else {
                    wp_die("Error while get access token from QBO!");
                }
            }
        } else {
            wp_die("You are not authorized to access this page!");
        }
    }
}
?>
