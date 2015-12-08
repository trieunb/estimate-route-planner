<?php
/**
 * Plugin configurations constants
 */

/**
 * Define a constant if not exists
 */
function erp_define_if_not($name, $val) {
    if (!defined($name)) {
        define($name, $val);
    }
}

/* Debugging flag */
erp_define_if_not('ERP_DEBUG', false);

/* Quickbooks sanbox mode flag */
erp_define_if_not('QB_SANDBOX_MODE', ERP_DEBUG);

/* Timezone */
erp_define_if_not('ERP_TIMEZONE', 'UTC');

if (date_default_timezone_get() != ERP_TIMEZONE) {
    date_default_timezone_set(ERP_TIMEZONE);
}

erp_define_if_not('ERP_MEMCACHED_HOST', '127.0.0.1');

erp_define_if_not('ERP_MEMCACHED_PORT', 11211);

erp_define_if_not('ERP_CACHE_PREFIX', 'erpp');

/* Max excution time */
set_time_limit(600); // 10 mins

?>
