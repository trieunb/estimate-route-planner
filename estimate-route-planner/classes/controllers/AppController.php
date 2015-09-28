<?php
class AppController extends BaseController {

    public function sessionData() {
        // Company Info
        $companyInfo = ORM::forTable('company_info')->findOne();
        if ($companyInfo) {
            $companyInfo = $companyInfo->asArray();
            if (!$companyInfo['logo_url']) {
                $companyInfo['logo_url'] = 'images/default-logo.png';
            }
        } else {
            $companyInfo = [];
        }
        // Current User
        $currentUser = wp_get_current_user();

        // All product services
        $productServices = ORM::forTable('products_and_services')->findArray();
        $result = [
            'companyInfo'       => $companyInfo,
            'currentUser'       => $currentUser,
            'productServices'   => $productServices
        ];
        $this->renderJson($result);
    }
}
?>
