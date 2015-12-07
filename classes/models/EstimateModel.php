<?php
class EstimateModel extends BaseModel {

    protected $fillable = [
        'id',
        'customer_id',
        'route_id',
        'sync_token',
        'doc_number',
        'estimate_footer',
        'disclaimer',
        'due_date',
        'txn_date',
        'expiration_date',
        'class_id',
        'customer_signature',
        'location_notes',
        'date_of_signature',
        'sold_by_1',
        'sold_by_2',

        'job_customer_id',
        'job_address_id',
        'job_address',
        'job_line_1',
        'job_line_2',
        'job_line_3',
        'job_line_4',
        'job_line_5',
        'job_city',
        'job_state',
        'job_zip_code',
        'job_country',
        'job_lat',
        'job_lng',
        'primary_phone_number',
        'alternate_phone_number',
        'mobile_phone_number',
        'email',

        'bill_address_id',
        'bill_address',
        'bill_line_1',
        'bill_line_2',
        'bill_line_3',
        'bill_line_4',
        'bill_line_5',
        'bill_city',
        'bill_state',
        'bill_zip_code',
        'bill_country',
        'status',
        'last_updated_at',
        'created_at',
        'total'
    ];

    public function getTableName() {
        return 'estimates';
    }

    public function updateSyncToken($estimateId) {
        // Update estimate sync token and updated at
        $objEstimate = new IPPEstimate();
        $objEstimate->Id = $estimateId;
        $sync = Asynchronzier::getInstance();
        $estimateEntity = $sync->Retrieve($objEstimate);
        $estimateLocal = ORM::forTable('estimates')->findOne($estimateId);
        $estimateLocal->last_updated_at =
            date("Y-m-d H:i:s", strtotime($estimateEntity->MetaData->LastUpdatedTime));
        $estimateLocal->sync_token = $estimateEntity->SyncToken;
        $estimateLocal->save();
        return $estimateLocal->sync_token;
    }
}
?>
