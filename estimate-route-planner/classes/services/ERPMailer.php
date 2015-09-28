<?php
class ERPMailer {

    protected $SMTPSetting;

    protected $lastError;

    public function __construct(SMTPSetting $setting) {
        $this->SMTPSetting = $setting;
    }

    public function getLastError() {
        return $lastError;
    }

    public function sendmail($subject, $body, $to, $options = []) {
        $preferenceModel = new PreferenceModel;
        $preference = $preferenceModel->first();
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host     = $this->SMTPSetting->server;
        $mail->Username = $this->SMTPSetting->username;
        $mail->Password = $this->SMTPSetting->password;
        $mail->Port     = $this->SMTPSetting->port;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';

        if (isset($options['fromName'])) {
            $mail->FromName = $options['fromName'];
        }
        $mail->addAddress($to);

        if (isset($options['cc']) && is_array($options['cc'])) {
            foreach ($options['cc'] as $ccmail) {
                $mail->addCC($ccmail);
            }
        }
        if (isset($options['bcc']) && is_array($options['bcc'])) {
            foreach ($options['bcc'] as $bccmail) {
                $mail->addBCC($bccmail);
            }
        }
        if (isset($options['html'])) {
            $mail->isHTML(!!$options['html']);
        }
        if (isset($options['attachments']) && is_array($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                $mail->addAttachment($attachment);
            }
        }

        $mail->Subject = $subject;
        $mail->Body    = $body;
        if ($mail->send()) {
            return true;
        } else {
            $this->lastError = $mail->ErrorInfo;
            return false;
        }
    }
}
