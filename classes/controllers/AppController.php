<?php
class AppController extends BaseController {

    public function sharedData() {
        $userData = [];
        if ($this->currentUser) {
            $userData['capabilities'] = $this->currentUser->allcaps;
            $userData['roles'] = $this->currentUser->roles;
            $userData['name'] = $this->getCurrentUserName();
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
            ->selectMany('id', 'name', 'description', 'active', 'rate')
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
