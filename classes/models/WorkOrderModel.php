<?php
class WorkOrderModel extends AbstractModel {

    protected $fillable = [
        'route_id',
        'equipment_list',
        'start_time'
    ];

    public function getTableName() {
        return 'erpp_work_orders';
    }
}
?>
