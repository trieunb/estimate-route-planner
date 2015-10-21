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

    private $hiddenParams = ['password', 'customer_signature_encoded'];

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

    private function logRequestParams() {
        if ($this->logging) { // Check here fore skip delay request due to looping recursive
            $request = $_REQUEST;
            $hiddenParams = $this->hiddenParams;
            array_walk_recursive($request, function(&$value, $key) use($hiddenParams) {
                if (array_search($key, $hiddenParams) !== false) {
                    $value = 'FILTERED';
                }
            });
            $this->log('Params: ' . json_encode($request));
        }
    }

    public function letGo() {
        $this->log(
            "\n=== " .
            $_SERVER['REQUEST_METHOD'] . ' ' .
            trim($_SERVER['REQUEST_URI'])
        );
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
            $this->log(
                "Handler: $controlerClass@$controlerMethod"
            );
            $this->logRequestParams();
            call_user_func([$controller, $controlerMethod]);
        } else {
            http_response_code(404);
            $this->log("404 - Not Found");
        }
        $queries = ORM::getQueryLog();
        $this->log(
            'SQLs(' . count($queries) . '):' . "\n" . implode($queries, "\n"));
        exit;
    }
}
?>
