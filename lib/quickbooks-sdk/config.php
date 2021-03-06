<?php

/**
 * This file allows custom configuration of paths for XSD2PHP dependencies and
 * POPO classes.  Rarely necessary, but possible.
 *
 * @author Intuit Partner Platform Team
 * @version 1.0
 */

// Determine parent path for SDK
$sdkDir = __DIR__ . DIRECTORY_SEPARATOR;

if (!defined('PATH_SDK_ROOT'))
    define('PATH_SDK_ROOT', $sdkDir);

// Specify POPO class path; typically a direct child of the SDK path
if (!defined('POPO_CLASS_PATH'))
    define('POPO_CLASS_PATH', $sdkDir . 'Data' . DIRECTORY_SEPARATOR);

// Include XSD2PHP dependencies for marshalling and unmarshalling
use com\mikebevz\xsd2php;
require_once(PATH_SDK_ROOT . 'XSD2PHP/src/com/mikebevz/xsd2php/Php2Xml.php');
require_once(PATH_SDK_ROOT . 'XSD2PHP/src/com/mikebevz/xsd2php/Bind.php');

// Includes all POPO classes; these are the source, dest, or both of the marshalling
set_include_path(get_include_path() . PATH_SEPARATOR . POPO_CLASS_PATH);
foreach (glob(POPO_CLASS_PATH.'/*.php') as $filename)
    require_once($filename);

//include som
if (!defined('POPO_CLASS_PATH_REST')) {
    define('POPO_CLASS_PATH_REST',POPO_CLASS_PATH  . 'IntuitRestServiceDef' . DIRECTORY_SEPARATOR);
}

// Specify the prefix pre-pended to POPO class names.  If you modify this value, you
// also need to rebuild the POPO classes, with the same prefix
if (!defined('PHP_CLASS_PREFIX')) {
    define('PHP_CLASS_PREFIX', 'IPP');
}

//TODO: It will be fixed in scope of SDK-229
//It is specified and included manually to avoid double inclusion and ambiguous state
require_once POPO_CLASS_PATH_REST  . PHP_CLASS_PREFIX .'TaxRateDetails.php';
require_once POPO_CLASS_PATH_REST  . PHP_CLASS_PREFIX .'TaxService.php';
require_once POPO_CLASS_PATH_REST  . PHP_CLASS_PREFIX .'Fault.php';

class QuickbooksAPIException extends Exception {

    protected $statusCode;
    protected $responseBody;

    public function __construct($statusCode, $responseBody = null) {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $message = 'Quickbooks Online API Error: ';
        switch ($statusCode) {
            case '401':
                $message .= "Invalid auth/bad request";
                break;
            case '403':
                $message .= "Forbidden";
                break;
            case '400':
                $message .= "Bad request";
                break;
            case '500':
            case '501':
            case '502':
            case '503':
            case '504':
            case '505':
                $message .= "Service error or busy";
                break;
            default:
                $message .= "Unknown";
                break;
        }
        $message .= ". Status code: $statusCode";
        parent::__construct($message);
    }

    public function getStatusCode() {
        return $this->statusCode;
    }

    public function getResponseBody() {
        return $this->responseBody;
    }
}
