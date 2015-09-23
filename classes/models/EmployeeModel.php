<?php
class EmployeeModel extends BaseModel {

    protected $fillable = [
        'id',
        'sync_token',
        'primary_address',
        'primary_city',
        'primary_state',
        'primary_zip_code',
        'primary_country',
        'given_name',
        'middle_name',
        'family_name',
        'suffix',
        'display_name',
        'print_name',
        'email',
        'primary_phone_number',
        'ssn',
        'company_name',
        'active',
        'created_at',
        'last_updated_at'
    ];

    public function getTableName() {
        return 'employees';
    }

}
?>
