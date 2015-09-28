<?php
define('ERP_ROOT_DIR', __DIR__);

if (!defined('ERP_ENABLE_DEBUG')) {
    define('ERP_ENABLE_DEBUG', true);
}
if (ERP_ENABLE_DEBUG) {
    ini_set('display_errors', '1');
}

define('QBO_SDK_ROOT', __DIR__ . '/lib/quickbooks-sdk/');
define('LOG_STORAGE_PATH', __DIR__ . '/log');

define('ERP_UPLOADS_DIR', __DIR__ . '/uploads/');
define('ERP_IMAGES_DIR', __DIR__ . '/images/');
define('TEMPLATES_DIR', __DIR__ . '/templates/');

// Increase max excution time
set_time_limit(300); // 5 mins
// PHP Session
if (!session_id()) {
    session_save_path(ERP_ROOT_DIR . '/tmp');
    @session_start();
}

require_once(__DIR__ . '/lib/helper.php');

// ORM library
require_once(__DIR__ . '/lib/idiorm.php');
ORM::configure('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST);
ORM::configure('username', DB_USER);
ORM::configure('password', DB_PASSWORD);
ORM::configure('logging', true);

// Autoload plugin classes
spl_autoload_register(function($class) {
    $posibleLocations = [
        __DIR__ . '/classes/models',
        __DIR__ . '/classes/utils',
        __DIR__ . '/classes/controllers',
        __DIR__ . '/schedule',
        __DIR__ . '/classes/services'
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

define('ERP_PHPMAILER_ROOT', __DIR__ . '/lib/phpmailer/');
require_once(ERP_PHPMAILER_ROOT . 'class.phpmailer.php');
require_once(ERP_PHPMAILER_ROOT . 'class.pop3.php');
require_once(ERP_PHPMAILER_ROOT . 'class.smtp.php');

define('ERP_DOMPDF', __DIR__ . '/lib/dompdf/');
require_once(ERP_DOMPDF . 'dompdf_config.inc.php');

?>
