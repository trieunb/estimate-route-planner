<?php
class EstimateLineModel extends BaseModel {

    protected $fillable = [
        'line_id',
        'line_num',
        'estimate_id',
        'product_service_id',
        'qty',
        'rate',
        'description'
    ];

    public function getTableName() {
        return 'estimate_lines';
    }

}
?>
