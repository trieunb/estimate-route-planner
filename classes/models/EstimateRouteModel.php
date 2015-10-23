<?php
class EstimateRouteModel extends BaseModel {

    protected $fillable = ['title', 'created_at', 'status', 'estimator_id'];

    public function getTableName() {
        return 'estimate_routes';
    }

}
?>
