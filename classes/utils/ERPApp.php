<?php
class ERPApp {

    protected $routes = [];
    protected $errorHandler;

    public function __construct() {
        $this->routes = include_once ERP_ROOT_DIR . '/config/routes.php';
        $this->errorHandler = new ErrorHandler();
        // set_error_handler([$this->errorHandler, 'handleError']);
        // set_exception_handler([$this->errorHandler, 'handleException']);
        // register_shutdown_function([$this->errorHandler, 'handleShutdown']);
    }

    public function letGo() {
        header('Content-Type: application/json');
        $requestDo = $_REQUEST['_do'];
        $requestData = [];
        if (isset($_REQUEST['data'])) {
            $requestData = $_REQUEST['data'];
        }
        if (isset($this->routes[$requestDo])) {
            $controllerMethod = explode('@', $this->routes[$requestDo]);
            $controlerClass  = $controllerMethod[0] . 'Controller';
            $controlerMethod = $controllerMethod[1];
            $controller = new $controlerClass($requestData);
            return call_user_func([$controller, $controlerMethod]);
        } else {
            http_response_code(404);
        }
    }
}
?>
