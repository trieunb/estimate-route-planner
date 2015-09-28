<?php
class EmployeeController extends BaseController {
    
    public function index() {
        $mEmployee = new EmployeeModel();
        $this->renderJson($mEmployee->all());
    }
}
?>
