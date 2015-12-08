<?php
class CrewRouteModel extends BaseModel {

    protected $fillable = ['title', 'created_at'];

    public function getTableName() {
        return 'crew_routes';
    }

}
?>
