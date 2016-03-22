<?php
final class ERPConfig {

    const RENEW_TOKEN_WITHIN = 15; // Maximum by 30

    public static function isOauthTokensRenewable() {
        $prefs = ORM::forTable('preferences')->findOne();
        $hasToken = $prefs->qbo_consumer_key
            && $prefs->qbo_consumer_secret
            && $prefs->qbo_oauth_token
            && $prefs->qbo_oauth_secret
            && $prefs->qbo_company_id
            && $prefs->qbo_token_expires_at;
        $tokenValid = strtotime($prefs->qbo_token_expires_at) > time();
        $diffToExpireDate = round(abs(strtotime($prefs->qbo_token_expires_at) - time()) / 86400);
        return $hasToken && $tokenValid && ($diffToExpireDate <= self::RENEW_TOKEN_WITHIN);
    }

    public function isOAuthTokenValid() {
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs) {
            return $prefs->qbo_consumer_key
                && $prefs->qbo_consumer_secret
                && $prefs->qbo_oauth_token
                && $prefs->qbo_oauth_secret
                && $prefs->qbo_company_id
                && $prefs->qbo_token_expires_at
                && (strtotime($prefs->qbo_token_expires_at) > time());
        } else {
            return false;
        }
    }
}
?>
