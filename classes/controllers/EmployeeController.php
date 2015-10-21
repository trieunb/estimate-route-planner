<?php
class EmployeeController extends BaseController {

    /**
     * Return list of WP users
     */
    public function index() {
        $emps = [];
        $WPUsers = ORM::forTable('wp_users')
            ->tableAlias('wp_u')
            ->leftOuterJoin(
                'wp_usermeta',
                "wp_u.id = wp_um1.user_id AND wp_um1.meta_key='first_name'",
                'wp_um1'
            )
            ->leftOuterJoin(
                'wp_usermeta',
                "wp_u.id = wp_um2.user_id AND wp_um2.meta_key='last_name'",
                'wp_um2'
            )
            ->selectMany('wp_u.id', 'wp_u.display_name')
            ->select('wp_um1.meta_value', 'first_name')
            ->select('wp_um2.meta_value', 'last_name')
            ->findMany();
        foreach ($WPUsers as $user) {
            $possibleNames = [
                trim($user->first_name . ' ' . $user->last_name),
                $user->display_name
            ];
            $selectName = '';
            foreach ($possibleNames as $name) {
                if (strlen($name) > 0) {
                    $selectName = $name; break;
                }
            }
            $emps[] = [
                'id' => $user->id,
                'name' => $selectName
            ];
        }
        $this->renderJson($emps);
    }
}
?>
