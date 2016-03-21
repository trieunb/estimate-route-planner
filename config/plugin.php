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

/* Check override configuration in local */
if (file_exists(__DIR__ . '/plugin.local.php')) {
    include __DIR__ . '/plugin.local.php';
} else {
    /* Debugging flag */
    erp_define_if_not('ERP_DEBUG', false);

    /* Quickbooks sandbox mode flag */
    erp_define_if_not('QB_SANDBOX_MODE', false);

    /* Plugin timezone */
    erp_define_if_not('ERP_TIMEZONE', 'UTC');

    /* Memcached server */
    erp_define_if_not('ERP_MEMCACHED_HOST', '127.0.0.1');
    erp_define_if_not('ERP_MEMCACHED_PORT', 11211);
    erp_define_if_not('ERP_CACHE_PREFIX', 'erpp');
}

/* Set timezone for all php date time functions */
if (date_default_timezone_get() != ERP_TIMEZONE) {
    date_default_timezone_set(ERP_TIMEZONE);
}

/* Max execution time */
set_time_limit(600); // 10 mins

?>
