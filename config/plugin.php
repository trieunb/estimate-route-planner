<?php
/**
 * Constants for configurations
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

/* Css and Js minify */
erp_define_if_not('ERP_MINIFY_ASSETS', true);

/* Timezone */
erp_define_if_not('ERP_TIMEZONE', 'UTC');

if (date_default_timezone_get() != ERP_TIMEZONE) {
    date_default_timezone_set(ERP_TIMEZONE);
}
/* PHP session save path */
erp_define_if_not('ERP_SESSION_SAVE_PATH', '/tmp');

/* Max excution time */
set_time_limit(600); // 10 mins

?>
