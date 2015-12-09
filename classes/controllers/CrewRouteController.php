<?php
class CrewRouteController extends BaseController {

    public function recent() {
        $routes = ORM::forTable('crew_routes')
            ->orderByDesc('created_at')
            ->limit(5)
            ->findArray();
        $this->renderJson($routes);
    }

    public function index() {
        $page = $this->getPageParam();
        $keyword = $this->getKeywordParam();
        $routes = ORM::forTable('crew_routes')
            ->whereLike('title', "%$keyword%")
            ->orderByDesc('created_at')
            ->limit(self::PAGE_SIZE)
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->findArray();
        $counter = ORM::forTable('crew_routes')
            ->whereLike('title', "%$keyword%")
            ->selectExpr('COUNT(*)', 'count')
            ->findMany();
        $this->renderJson([
            'routes' => $routes,
            'total' => $counter[0]->count
        ]);
    }

    public function show() {
        $routeId = $this->data['id'];
        $route = ORM::forTable('crew_routes')
            ->findOne($routeId);
        $response = $route->asArray();
        if ($route) {
            // Get assigned estimates
            $response['assigned_estimates'] = ORM::forTable('estimates')
                ->tableAlias('e')
                ->join('customers', ['e.job_customer_id', '=', 'c.id'], 'c')
                ->where('e.route_id', $routeId)
                ->selectMany(
                    'e.id', 'e.doc_number', 'e.status', 'e.txn_date',
                    'e.expiration_date', 'e.job_address', 'e.job_city',
                    'e.job_state', 'e.job_zip_code', 'e.total', 'e.job_lat',
                    'e.route_order', 'e.job_lng', 'e.status',
                    'e.primary_phone_number'
                )
                ->select('c.display_name', 'job_customer_display_name')
                ->orderByAsc('e.route_order')
                ->findArray();
        }
        $this->renderJson($response);
    }

    public function save() {
        $route = ORM::forTable('crew_routes')->create();
        $route->title = $this->data['title'];
        $route->created_at = date('Y-m-d H:i:s');
        if ($route->save()) {
            foreach ($this->data['assigned_estimate_ids'] as $index => $estimateId) {
                $estimate = ORM::forTable('estimates')
                    ->findOne($estimateId);
                $estimate->route_id = $route->id;
                $estimate->route_order = $index;
                if ($estimate->status == 'Accepted') {
                    $estimate->status = 'Routed';
                }
                $estimate->save();
            }
            $this->renderJson([
                'success' => true,
                'message' => 'Route created successfully',
                'data'    => $route->asArray()
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'An error has occurred while saving route'
            ]);
        }
    }

    public function update() {
        $routeId = $this->data['id'];
        $route = ORM::forTable('crew_routes')->findOne($routeId);
        $route->title = $this->data['title'];

        if ($route->save()) {
            $oldAssignedEstimates = ORM::forTable('estimates')
                ->where('route_id', $routeId)
                ->findMany();
            if (isset($this->data['assigned_estimate_ids']) &&
                is_array($this->data['assigned_estimate_ids'])) {
                foreach ($oldAssignedEstimates as $estimate) {
                    if (!in_array($estimate->id, $this->data['assigned_estimate_ids'])) {
                        // Un-assign to the route
                        $estimate->route_id = NULL;
                        $estimate->route_order = 0;
                        if ($estimate->status == 'Routed') {
                            $estimate->status = 'Accepted';
                        }
                        $estimate->save();
                    }
                }

                foreach ($this->data['assigned_estimate_ids'] as $index => $id) {
                    $estimate = ORM::forTable('estimates')
                        ->findOne($id);
                    $estimate->route_id = $routeId;
                    $estimate->route_order = $index;
                    if ($estimate->status == 'Accepted') {
                        $estimate->status = 'Routed';
                    }
                    $estimate->save();
                }
            } else {
                foreach ($oldAssignedEstimates as $estimate) {
                    // Un-assign to the route
                    $estimate->route_id = NULL;
                    $estimate->route_order = 0;
                    if ($estimate->status == 'Routed') {
                        $estimate->status = 'Accepted';
                    }
                    $estimate->save();
                }
            }
            $this->renderJson([
                'success' => true,
                'message' => 'Route updated successfully'
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'An error has occurred while saving route'
            ]);
        }
    }

    /**
     * Get the work order info
     */
    public function showWorkOrder() {

    }

    public function saveWorkOrder() {
        $workOrder = ORM::forTable('erpp_work_orders')
            ->where('route_id', $this->data['route_id'])
            ->findOne();
        if (null == $workOrder) {
            $workOrder = ORM::forTable('erpp_work_orders')->create();
        }
        $workOrder->route_id = $this->data['route_id'];
        $workOrder->equipment_list = $this->data['equipment_list'];
        $workOrder->start_time = $this->data['start_time'];
        $workOrder->save();
    }
}
?>
