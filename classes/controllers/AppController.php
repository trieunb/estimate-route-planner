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
            ->where('active', true)
            ->orderByAsc('name')
            ->findArray();
        $this->renderJson($productServices);
    }

    public function classes() {
        $classes = ORM::forTable('erpp_classes')
            ->selectMany('id', 'name', 'parent_id', 'active')
            ->orderByAsc('name')
            ->findArray();
        $this->renderJson($classes);
    }
}
?>
