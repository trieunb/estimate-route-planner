<?php
class EstimateRouteController extends BaseController {

    public function recent() {
        $routes = ORM::forTable('estimate_routes')
            ->orderByDesc('created_at')
            ->limit(5)
            ->findArray();
        $this->renderJson($routes);
    }

    public function index() {
        $pageSize = 10;
        if (isset($_REQUEST['page'])) {
            $page = (int) $_REQUEST['page'];
        } else {
            $page = 1;
        }
        $keyword = "";
        if (isset($_REQUEST['keyword'])) {
            $keyword = $_REQUEST['keyword'];
        }
        $routes = ORM::forTable('estimate_routes')
            ->whereLike('title', "%$keyword%")
            ->orderByDesc('created_at')
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->findArray();
        $counter = ORM::forTable('estimate_routes')
            ->whereLike('title', "%$keyword%")
            ->selectExpr('COUNT(*)', 'count')
            ->findMany();
        $this->renderJson([
            'keyword' => $keyword,
            'routes' => $routes,
            'total' => $counter[0]->count
        ]);
    }

    public function show() {
        $routeId = $this->data['id'];
        $route = ORM::forTable('estimate_routes')
            ->findOne($routeId);
        $response = $route->asArray();
        if ($route) {
            // Get assigned estimates
            $response['assigned_estimates'] = ORM::forTable('estimates')
                ->tableAlias('e')
                ->join('customers', ['e.job_customer_id', '=', 'c.id'], 'c')
                ->where('e.estimate_route_id', $routeId)
                ->selectMany(
                    'e.id', 'e.doc_number', 'e.status', 'e.txn_date',
                    'e.due_date', 'e.job_address', 'e.job_city',
                    'e.job_state', 'e.job_zip_code', 'e.total', 'e.job_lat',
                    'e.route_order', 'e.job_lng', 'e.status'
                )
                ->select('c.display_name', 'job_customer_display_name')
                ->orderByAsc('e.route_order')
                ->findArray();
        }
        $this->renderJson($response);
    }

    public function save() {
        $route = ORM::forTable('estimate_routes')->create();
        $route->title = $this->data['title'];
        $route->created_at = date('Y-m-d H:i:s');
        $route->status = 'Pending';
        if ($route->save()) {
            foreach ($this->data['assigned_estimate_ids'] as $index => $estimateId) {
                $estimate = ORM::forTable('estimates')
                    ->findOne($estimateId);
                $estimate->estimate_route_id = $route->id;
                $estimate->route_order = $index;
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
        $route = ORM::forTable('estimate_routes')->findOne($routeId);
        $route->title = $this->data['title'];
        $route->status = $this->data['status'];

        if ($route->save()) {
            if (isset($this->data['assigned_estimate_ids'])) {
                $oldAssignedEstimates = ORM::forTable('estimates')
                    ->where('estimate_route_id', $routeId)
                    ->findMany();

                foreach ($oldAssignedEstimates as $estimate) {
                    if (!in_array($estimate->id, $this->data['assigned_estimate_ids'])) {
                        // Un-assign to the route
                        $estimate->estimate_route_id = NULL;
                        $estimate->route_order = 0;
                        $estimate->save();
                    }
                }

                foreach ($this->data['assigned_estimate_ids'] as $index => $id) {
                    $estimate = ORM::forTable('estimates')
                        ->findOne($id);
                    $estimate->estimate_route_id = $routeId;
                    $estimate->route_order = $index;
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

}
?>
