<?php
class EstimateRouteModel extends BaseModel {

    protected $fillable = ['title', 'created_at', 'status'];

    public function getTableName() {
        return 'estimate_routes';
    }

}
?>
