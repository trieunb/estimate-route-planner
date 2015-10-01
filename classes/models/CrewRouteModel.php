<?php
class CrewRouteModel extends BaseModel {

    protected $fillable = ['title', 'created_at', 'status'];

    public function getTableName() {
        return 'crew_routes';
    }

}
?>
