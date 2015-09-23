<?php
class CustomerModel extends BaseModel {

    protected $fillable = [
        'id',
        'sync_token',
        'parent_id',
        'title',
        'given_name',
        'middle_name',
        'family_name',
        'suffix',
        'display_name',
        'print_name',
        'email',
        'primary_phone_number',
        'mobile_phone_number',
        'alternate_phone_number',
        'fax',
        'company_name',
        'bill_address',
        'bill_city',
        'bill_state',
        'bill_zip_code',
        'bill_country',
        'active',
        'created_at',
        'last_updated_at'
    ];

    public function getTableName() {
        return 'customers';
    }

}
?>