<?php
class ProductServiceModel extends BaseModel {

    protected $fillable = [
        'id',
        'name',
        'description',
        'rate',
        'active',
        'taxable',
        'last_updated_at'
    ];

    public function getTableName() {
        return 'products_and_services';
    }

}
?>
