<?php
class PreferenceController extends BaseController {

    public function updateSetting() {
        $preferenceModel = new PreferenceModel;
        $prefs = $preferenceModel->first();
        if ($prefs) {
            $success = $preferenceModel->update($this->data, []);
        } else {
            $success = $preferenceModel->insert($this->data);
        }
        if ($success) {
            $this->renderJson([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'Failed to update settings'
            ]);
        }
    }

    public function getSetting() {
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs) {
            $settings = [];
            $settings['gmap_api_key'] = $prefs->gmap_api_key;
            $settings['gmail_username'] = $prefs->gmail_username;
            $settings['gmail_password'] = $prefs->gmail_password;
            $settings['gmail_server'] = $prefs->gmail_server;
            $settings['gmail_port'] = $prefs->gmail_port;
            $this->renderJson($settings);
        } else {
            $this->renderJson(json_decode("{}"));
        }
    }

    public function sendTestEmail() {
        $companyInfoModel = new CompanyInfoModel;
        $companyInfo = $companyInfoModel->first();
        try {
            $STMPSetting = new SMTPSetting(
                $this->data['setting']['gmail_username'],
                $this->data['setting']['gmail_password'],
                $this->data['setting']['gmail_server'],
                $this->data['setting']['gmail_port']
            );
            $mailer = new ERPMailer($STMPSetting);
            $to = $this->data['to'];
            $subject = 'Test email';
            $body = 'This is the test email';
            if ($mailer->sendmail($subject, $body, $to, ['fromName' => $companyInfo['name']])) {
                $this->renderJson([
                    'success' => true,
                    'message' => 'Email was sent successfully'
                ]);
            } else {
                $this->renderJson([
                    'success' => false,
                    'message' => 'Error occurred while sending email'
                ]);
            }
        } catch(Exception $e) {
            $this->renderJson([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    public function saveAppConfig() {
        $prefs = ORM::forTable('preferences')->findOne();
        if (!$prefs) {
            $prefs = ORM::forTable('preferences')->create();
        }
        $prefs->qbo_consumer_key = $this->data['qbo_consumer_key'];
        $prefs->qbo_consumer_secret = $this->data['qbo_consumer_secret'];

        if ($prefs->save()) {
            $this->renderJson([
                'success' => true,
                'message' => 'App consumer keys saved successfully'
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'Failed to save consumer keys'
            ]);
        }
    }

    public function getAppConfig() {
        $prefs = ORM::forTable('preferences')->findOne();
        $appConfig = [];
        if ($prefs) {
            $appConfig['qbo_consumer_key'] = $prefs->qbo_consumer_key;
            $appConfig['qbo_consumer_secret'] = $prefs->qbo_consumer_secret;
        }
        if ($appConfig) {
            $this->renderJson($appConfig);
        } else {
            $this->renderJson(json_decode("{}"));
        }

    }
}
?>