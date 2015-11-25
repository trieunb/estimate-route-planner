<?php
class ReferralModel extends AbstractModel {

    protected $fillable = [
        'customer_id',
        'route_id',
        'route_order',
        'estimator_id',
        'date_requested',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'email',
        'primary_phone_number',
        'mobile_phone_number',
        'date_service',
        'how_find_us',
        'type_of_service_description',
        'status',
        'lat',
        'lng',
    ];

    public function getTableName() {
        return 'referrals';
    }
}
?>
