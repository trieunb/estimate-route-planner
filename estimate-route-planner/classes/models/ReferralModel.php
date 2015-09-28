<?php
class ReferralModel extends BaseModel {

    public function getTableName() {
        return 'referrals';
    }

    public function getPrimaryKey() {
        return 'id';
    }
}
?>
