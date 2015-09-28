<?php
class QuickbooksAuth {
    const OAUTH_REQUEST_URL     = 'https://oauth.intuit.com/oauth/v1/get_request_token';
    const OAUTH_ACCESS_URL      = 'https://oauth.intuit.com/oauth/v1/get_access_token';
    const OAUTH_AUTHORISE_URL   = 'https://appcenter.intuit.com/Connect/Begin';

    protected $oauth;

    public function __construct() {
        $prefs = ORM::forTable('preferences')->findOne();
        $this->oauth = new OAuth(
            $prefs->qbo_consumer_key,
            $prefs->qbo_consumer_secret,
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
        if (ERP_ENABLE_DEBUG) {
            $this->oauth->enableDebug();
        }
        $this->oauth->disableSSLChecks();
    }

    public function getOauthAccessToken($auth_token, $oauth_token_secret) {
        try {
            $this->oauth->setToken($auth_token, $oauth_token_secret);
            $response = $this->oauth->getAccessToken(self::OAUTH_ACCESS_URL);
            return [
                'success'            => true,
                'oauth_token'        => $response['oauth_token'],
                'oauth_token_secret' => $response['oauth_token_secret']
            ];
        } catch(OAuthException $e) {
            return [
                'success' => false
            ];
        }
    }

    public function start($callbackURL) {
        // Get request token from Intuit
        try {
            $response = $this->oauth->getRequestToken(self::OAUTH_REQUEST_URL, $callbackURL);
            return [
                'success' => true,
                'oauth_token_secret' => $response['oauth_token_secret'],
                'redirect_url' => self::OAUTH_AUTHORISE_URL .'?oauth_token=' . $response['oauth_token']
            ];
        } catch(OAuthException $e) {
            return [
                'success' => false
            ];
        }
    }
}
?>
