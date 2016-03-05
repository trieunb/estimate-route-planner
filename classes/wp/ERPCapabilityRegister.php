<?php

class ERPCapabilityRegister
{
    static $ADMIN_ROLES = ['administrator', 'erppadmin', 'erpp_admin'];

    public function register($capabilities)
    {
        // Skip these caps for admin role
        // TODO: should use 'admin' flag as convention
        $exceptAdminCaps = [
            'erpp_view_sales_estimates',
            'erpp_estimator_only_routes',
            'erpp_hide_estimate_pending_list',
            'erpp_restrict_client_dropdown',
            'erpp_hide_expired_estimates'
        ];

        foreach ($this->getWPRoles()->get_names() as $roleName => $label) {
            $role = get_role($roleName);
            if ($role) {
                foreach ($capabilities as $cap => $options) {
                    if (in_array($roleName, self::$ADMIN_ROLES) && in_array($cap, $exceptAdminCaps)) {
                        continue;
                    }
                    $role->add_cap($cap);
                }
            }
        }
    }

    public function unregister($capabilities)
    {
        foreach ($this->getWPRoles()->get_names() as $roleName => $label) {
            $role = get_role($roleName);
            if ($role) {
                foreach ($capabilities as $cap => $options) {
                    $role->remove_cap($cap);
                }
            }
        }
    }

    private function getWPRoles()
    {
        global $wp_roles;
        return $wp_roles;
    }
}