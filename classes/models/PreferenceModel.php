<?php
class PreferenceModel extends BaseModel {

    public function getTableName() {
        return 'preferences';
    }

    /**
     * Load preferences for email
     * @return SMTPSetting
    */
    public static function getSMTPSetting() {
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs && $prefs->gmail_username
            && $prefs->gmail_password && $prefs->gmail_server
                && $prefs->gmail_port) {
            return new SMTPSetting(
                $prefs->gmail_username,
                $prefs->gmail_password,
                $prefs->gmail_server,
                $prefs->gmail_port
            );
        } else {
            return null;
        }
    }

    public static function getQuickbooksCreds() {
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs) {
            return [
                'access_token'        => $prefs->qbo_oauth_token,
                'access_token_secret' => $prefs->qbo_oauth_secret,
                'consumer_key'        => $prefs->qbo_consumer_key,
                'consumer_secret'     => $prefs->qbo_consumer_secret,
                'realmId'             => $prefs->qbo_company_id
            ];
        } else {
            return [];
        }
    }

    public static function isValidMinimumConfiguration() {
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
}
?>
