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

        $lastSyncAt = null;
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs && $prefs->last_sync_at) {
            $lastSyncAt = strtotime($prefs->last_sync_at);
        }
        $this->renderJson(
            compact('companyInfo', 'userData', 'lastSyncAt')
        );
    }

    public function productServices() {
        $productServices = ORM::forTable('products_and_services')
            ->selectMany('id', 'name', 'description', 'active', 'rate')
            ->orderByAsc('name')
            ->findArray();
        $this->renderJson($productServices);
    }
}
?>
