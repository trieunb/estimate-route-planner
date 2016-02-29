<?php
class CompanyInfoController extends BaseController {

    public function update() {
        $companyInfo = ORM::forTable('company_info')->findOne();
        if (!$companyInfo) {
            $companyInfo = ORM::forTable('company_info')->create();
        }

        $fillableCols = [
            'name', 'full_address', 'primary_phone_number', 'fax', 'email',
            'website', 'email_template', 'estimate_footer', 'mailing_address', 'disclaimer'
        ];
        foreach ($fillableCols as $col) {
            if (isset($this->data[$col])) {
                $companyInfo->set([$col => $this->data[$col]]);
            }
        }
        if ($companyInfo->save()) {
            $companyInfo = $companyInfo->asArray();
            if (!$companyInfo['logo_url']) {
                $companyInfo['logo_url'] = 'images/default-logo.png';
            }
            $result = [
                'success' => true,
                'message' => 'Company info updated successfully',
                'data'  => $companyInfo
            ];
        } else {
            $result = [
                'success' => false,
                'message' => 'Failed to update company info'
            ];
        }
        $this->renderJson($result);
    }

    public function get() {
        $companyInfo = ORM::forTable('company_info')->findOne();
        if ($companyInfo) {
            $companyInfo = $companyInfo->asArray();
            if (!$companyInfo['logo_url']) {
                $companyInfo['logo_url'] = 'images/default-logo.png';
            }
        } else {
            $companyInfo = [];
        }
        $this->renderJson($companyInfo);
    }

    public function uploadLogo() {
        $uploadedFile = Input::file('file');
        $validFileTypes = ['image/jpeg', 'image/png', 'image/gif' ];
        if (in_array($uploadedFile['type'], $validFileTypes)) {
            if ($uploadedFile['size'] > 3072000) {
                $result = [
                    'success' => false,
                    'message' => 'Image size too large!'
                ];
                $this->renderJson($result);
            } else {
                $tmp_name = $uploadedFile['tmp_name'];
                $name = $uploadedFile['name'];
                $sanitizedFileName = md5(time() . $name);
                move_uploaded_file($tmp_name, ERP_UPLOADS_DIR . $sanitizedFileName);
                $companyInfo = ORM::forTable('company_info')->findOne();
                $companyInfo->logo_url = 'uploads/' . $sanitizedFileName;
                if ($companyInfo->save()) {
                    $result = array(
                        'success' => true,
                        'message' => 'Logo updated successfully',
                        'data'    => $companyInfo->asArray()
                    );
                }else {
                    $result = array(
                        'success' => false,
                        'message' => 'Failed to update logo'
                    );
                }
                $this->renderJson($result);
           }
       } else {
           $result = [
               'success' => false,
               'message' => 'The file is not an image.'
           ];
           $this->renderJson($result);
       }
    }
}
?>
