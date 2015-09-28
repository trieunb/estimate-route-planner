<?php
final class ERPConfig {

    public static function checkQuickbookAuthenticated() {
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs) {
            return $prefs->qbo_consumer_key
                && $prefs->qbo_consumer_secret
                && $prefs->qbo_oauth_token
                && $prefs->qbo_oauth_secret
                && $prefs->qbo_company_id;
        } else {
            return false;
        }
    }

    public static function checkQuickbookAppConfig() {
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs) {
            return trim($prefs->qbo_consumer_key) && trim($prefs->qbo_consumer_secret);
        } else {
            return false;
        }
    }

    public static function checkQuickbookOauthTokenValid() {
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs) {
            return !!$prefs->qbo_token_expires_at && (strtotime($prefs->qbo_token_expires_at) > strtotime(date("Y-m-d H:i:s")));
        } else {
            return false;
        }
    }
}
?>
