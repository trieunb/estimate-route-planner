<?php
/**
 * The base model class. A wrapper of ORM library
 * @author Lht
 *
 */
abstract class AbstractModel extends ORM {

    protected $fillable = [];

    protected $tableName;

    public function __construct() {
        parent::__construct($this->getTableName());
    }

    public abstract function getTableName();

    /**
     * Override to restrict only $fillable from mass assignment
     * NOTE: this only filter array inputs, skip if assign a single attribute
    */
    protected function _set_orm_property($key, $value = null, $expr = false) {
        if (is_array($key)) {
            return parent::_set_orm_property($this->_filterAttrs($key), $value, $expr);
        } else {
            return parent::_set_orm_property($key, $value, $expr);
        }
    }

    /**
     * Filter data base on $fillable when call set attributes
     * Note: if the $fillable is empty, all inputs will be accepted
     */
    private function _filterAttrs($inputs) {
        $filteredInputs = [];
        if (count($this->fillable) > 0) {
            foreach ($this->fillable as $attr) {
                if (isset($inputs[$attr])) {
                    $filteredInputs[$attr] = $inputs[$attr];
                }
            }
            return $filteredInputs;
        } else {
            return $inputs;
        }
    }
}
?>
