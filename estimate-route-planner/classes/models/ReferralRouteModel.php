<?php
class ReferralRouteModel extends BaseModel {

    protected $fillable = ['title', 'created_at', 'status'];

    public function getTableName() {
        return 'referral_routes';
    }

}
?>
