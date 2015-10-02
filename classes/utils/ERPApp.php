<?php
class ERPApp {
    /* @var array */
    private $routes = [];

    /* @var ERPErrorHandler */
    private $errorHandler;

    /* @var ERPLogger */
    private $logger;

    /* @var boolean */
    private $logging;

    public function __construct() {
        $this->routes = include_once ERP_ROOT_DIR . '/config/routes.php';
        $this->errorHandler = new ERPErrorHandler();
        $this->logger = new ERPLogger('app.log');
        $this->logging = ERP_DEBUG;
        // set_error_handler([$this->errorHandler, 'handleError']);
        // set_exception_handler([$this->errorHandler, 'handleException']);
        // register_shutdown_function([$this->errorHandler, 'handleShutdown']);
    }

    public function enableLog() {
        $this->logging = true;
    }

    public function log($data) {
        if ($this->logging) {
            $this->logger->log($data);
        }
    }

    public function letGo() {
        $this->log(
            "[=== " .
            $_SERVER['REQUEST_METHOD'] . ' ' .
            trim($_SERVER['REQUEST_URI'])
        );
        $this->log('Params: ' . json_encode($_REQUEST));
        $requestDo = $_REQUEST['_do'];
        $requestData = [];
        if (isset($_REQUEST['data'])) {
            $requestData = $_REQUEST['data'];
        }
        if (isset($this->routes[$requestDo])) {
            $controllerMethod = explode('@', $this->routes[$requestDo]);
            $controlerClass  = $controllerMethod[0] . 'Controller';
            $controlerMethod = $controllerMethod[1];
            $controller = new $controlerClass($requestData, $this->logger);
            call_user_func([$controller, $controlerMethod]);
        } else {
            http_response_code(404);
            $this->log("404 - Not Found");
        }
        $queries = ORM::getQueryLog();
        $this->log(
            'SQLs(' . count($queries) . ')' . "\n" . implode($queries, "\n"));
        exit;
    }
}
?>
