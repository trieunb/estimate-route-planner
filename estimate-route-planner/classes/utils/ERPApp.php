<?php
class ERPApp {

    protected $routes = [];

    public function __construct() {
        $this->routes = include_once ERP_ROOT_DIR . '/config/routes.php';
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
            // var_dump($controlerClass); var_dump($controlerMethod); exit();
            $controller = new $controlerClass($requestData);
            // var_dump($controller); exit();
            return call_user_func([$controller, $controlerMethod]);
        } else {
            http_response_code(404);
        }
    }
}
?>
