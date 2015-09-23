<?php
class ProductServiceModel extends BaseModel {

    protected $fillable = [
        'id',
        'sync_token',
        'name',
        'description',
        'rate',
        'active',
        'taxable',
        'created_at',
        'last_updated_at'
    ];

    public function getTableName() {
        return 'products_and_services';
    }

}
?>
