<?php
final class Input {

    /**
     * Get input value by key. Return default value if not exists
     *
     * Usage:
     * Input::get('first_name', 'John');
     * Input::get('email');
     *
     * @return mixed
     */
    public static function get($key, $defaultValue = NULL) {
        if (self::has($key)) {
            return $_REQUEST[$key];
        } else {
            return $defaultValue;
        }
    }

    /**
     * Check for input has key
     *
     * @return boolean
     */
    public static function has($key) {
        return isset($_REQUEST[$key]);
    }

    /**
     * Filter data by given keys
     * Usage:
     * Input::only('first_name', 'last_name')
     *
     * @return boolean
     */
    public static function only() {
        $args = func_get_args();
        if(count($args) == 0) {
            throw new Exception("Must to supply at least one key", 1);

        }
        $filteredInputs = [];
        foreach ($args as $key) {
            if(self::has($key)) {
                $filteredInputs[$key] = $_REQUEST[$key];
            }
        }
        return $filteredInputs;
    }

    /**
     * Get the input file
     * Usage:
     * Input::file('logo')
     *
     * @return boolean
     */
    public static function file($key) {
        return $_FILES[$key];
    }
}
?>
