<?php
class AppController extends BaseController {

    public function sessionData() {
        $currentUser = wp_get_current_user();
        $userData = [];
        if ($currentUser) {
            $userData['capabilities'] = $currentUser->allcaps;
            $userData['roles'] = $currentUser->roles;
        }
        $this->renderJson([
            'currentUser' => $userData
        ]);
    }

    public function sharedData() {
        $currentUser = wp_get_current_user();
        $userData = [];
        if ($currentUser) {
            $userData['capabilities'] = $currentUser->allcaps;
            $userData['roles'] = $currentUser->roles;
        }

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

        // All product services
        $productServices = ORM::forTable('products_and_services')
            ->selectMany('id', 'name', 'description', 'rate')
            ->where('active', '1')
            ->orderByAsc('name')
            ->findArray();
        $this->renderJson([
            'companyInfo'       => $companyInfo,
            'productServices'   => $productServices,
            'currentUser'       => $userData
        ]);
    }
}
?>
