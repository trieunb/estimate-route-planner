<?php
class EstimateModel extends BaseModel {

    protected $fillable = [
        'id',
        'customer_id',
        'estimate_route_id',
        'sync_token',
        'doc_number',
        'estimate_footer',
        'due_date',
        'txn_date',
        'ship_date',
        'expiration_date',
        'accepted_date',
        'source',
        'customer_signature',
        'location_notes',
        'date_of_signature',
        'sold_by_1',
        'sold_by_2',
        'job_customer_id',
        'job_address',
        'job_city',
        'job_state',
        'job_zip_code',
        'job_lat',
        'job_lng',
        'primary_phone_number',
        'alternate_phone_number',
        'email',
        'bill_address_id',
        'bill_address',
        'bill_city',
        'bill_state',
        'bill_zip_code',
        'bill_country',
        'ship_address_id',
        'ship_address',
        'ship_city',
        'ship_state',
        'ship_zip_code',
        'ship_country',
        'status',
        'last_updated_at',
        'total'
    ];

    public function getTableName() {
        return 'estimates';
    }

}
?>
