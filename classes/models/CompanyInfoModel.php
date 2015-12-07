<?php
class CompanyInfoModel extends BaseModel {

    protected $fillable = [
        'name', 'full_address', 'primary_phone_number', 'fax', 'email',
        'website', 'estimate_footer', 'logo_url', 'mailing_address', 'disclaimer'
    ];

    public function getTableName() {
        return 'company_info';
    }

}
?>
