<?php
/**
 * A wrapp class for Wordpress APIs
 */
final class ERPWordpress {

    public static function getNameOfUser(WP_User $user) {
        $possibleNames = [
            trim($user->first_name . ' ' . $user->last_name),
            $user->display_name,
            $user->nick_name
        ];
        $selectName = '';
        foreach ($possibleNames as $name) {
            if (strlen($name) > 0) {
                $selectName = $name; break;
            }
        }
        return $selectName;
    }
}
?>
