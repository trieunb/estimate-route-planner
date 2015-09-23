<?php
class EmployeeController extends BaseController {

    public function index() {
        $emps = ORM::forTable('employees')
            ->selectMany('id', 'display_name')
            ->findArray();
        $this->renderJson($emps);
    }
}
?>
