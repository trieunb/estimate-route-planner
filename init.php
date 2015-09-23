<?php
define('ERP_ROOT_DIR', __DIR__);

if (!defined('ERP_TIMEZONE')) {
    define('ERP_TIMEZONE', 'UTC');
}
if (date_default_timezone_get() != ERP_TIMEZONE) {
    date_default_timezone_set(ERP_TIMEZONE);
}
// Increase max excution time
set_time_limit(600); // 10 mins

define('QBO_SDK_ROOT', ERP_ROOT_DIR . '/lib/quickbooks-sdk/');
define('LOG_STORAGE_PATH', ERP_ROOT_DIR . '/log');
define('ERP_UPLOADS_DIR', ERP_ROOT_DIR . '/uploads/');
define('ERP_IMAGES_DIR', ERP_ROOT_DIR . '/images/');
define('TEMPLATES_DIR', ERP_ROOT_DIR . '/templates/');
define('TMP_DIR', ERP_ROOT_DIR . '/tmp/');

// Some global functions use in plugin
require_once(ERP_ROOT_DIR . '/lib/helper.php');

// ORM library
require_once(ERP_ROOT_DIR . '/lib/idiorm.php');
ORM::configure('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST);
ORM::configure('username', DB_USER);
ORM::configure('password', DB_PASSWORD);
ORM::configure('logging', false);

// Autoload plugin classes
spl_autoload_register(function($class) {
    $posibleLocations = [
        ERP_ROOT_DIR . '/classes/models',
        ERP_ROOT_DIR . '/classes/utils',
        ERP_ROOT_DIR . '/classes/controllers',
        ERP_ROOT_DIR . '/schedule',
        ERP_ROOT_DIR . '/classes/services'
    ];
    foreach ($posibleLocations as $location) {
        if (file_exists($location . '/' . $class . '.php')) {
            require_once $location . '/' . $class . '.php';
            return true;
        }
    }
});

// Autoload QBO SDK

require_once(QBO_SDK_ROOT . 'config.php');
require_once(QBO_SDK_ROOT . 'Security/OAuthRequestValidator.php');
require_once(QBO_SDK_ROOT . 'Core/ServiceContext.php');
require_once(QBO_SDK_ROOT . 'DataService/DataService.php');
require_once(QBO_SDK_ROOT . 'PlatformService/PlatformService.php');
require_once(QBO_SDK_ROOT . 'Utility/Configuration/ConfigurationManager.php');

define('ERP_PHPMAILER_ROOT', ERP_ROOT_DIR . '/lib/phpmailer/');
require_once(ERP_PHPMAILER_ROOT . 'class.phpmailer.php');
require_once(ERP_PHPMAILER_ROOT . 'class.pop3.php');
require_once(ERP_PHPMAILER_ROOT . 'class.smtp.php');

define('ERP_DOMPDF', ERP_ROOT_DIR . '/lib/dompdf/');
require_once(ERP_DOMPDF . 'dompdf_config.inc.php');
?>