<?php
define('ERP_ROOT_DIR', __DIR__);
define('ERP_LOG_STORAGE_DIR', ERP_ROOT_DIR . '/log');
define('ERP_UPLOADS_DIR', ERP_ROOT_DIR . '/uploads/');
define('ERP_IMAGES_DIR', ERP_ROOT_DIR . '/images/');
define('ERP_TEMPLATES_DIR', ERP_ROOT_DIR . '/templates/');
define('ERP_TMP_DIR', ERP_ROOT_DIR . '/tmp/');

require_once(ERP_ROOT_DIR . '/config/plugin.php');
require_once(ERP_ROOT_DIR . '/config/autoload.php');

// Some global functions use in plugin
require_once(ERP_ROOT_DIR . '/lib/helper.php');

// ORM library
require_once(ERP_ROOT_DIR . '/lib/idiorm.php');
ORM::configure('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST);
ORM::configure('username', DB_USER);
ORM::configure('password', DB_PASSWORD);
ORM::configure('logging', ERP_DEBUG);

// Autoload QBO SDK
define('QBO_SDK_ROOT', ERP_ROOT_DIR . '/lib/quickbooks-sdk/');
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
