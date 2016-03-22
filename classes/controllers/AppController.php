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

        $this->renderJson(
            compact('companyInfo', 'userData')
        );
    }

    public function productServices() {
        $productServices = ERPCacheManager::fetch('products_services', function() {
            return ORM::forTable('products_and_services')
                ->selectMany('id', 'name', 'description', 'active', 'rate')
                ->orderByAsc('name')
                ->findArray();
        });
        $this->renderJson($productServices);
    }

    public function classes() {
        $classes = ERPCacheManager::fetch('classes', function() {
            return ORM::forTable('erpp_classes')
            ->selectMany('id', 'name', 'parent_id', 'active')
            ->where('active', true)
            ->orderByAsc('name')
            ->findArray();
        });
        $this->renderJson($classes);
    }
}
?>
