<?php
class EmployeeController extends BaseController {

    /**
     * Return list of WP users
     */
    public function index() {
        $emps = [];
        foreach (get_users() as $user) {
            $emps[] = [
                'id' => $user->id,
                'name' => ERPWordpress::getNameOfUser($user)
            ];
        }
        $this->renderJson($emps);
    }
}
?>
