<?php
class ReferralRouteController extends BaseController {

    public function recent() {
        $routes = ORM::forTable('referral_routes')
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
        $routes = ORM::forTable('referral_routes')
            ->whereLike('title', "%$keyword%")
            ->orderByDesc('created_at')
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->findArray();
        $counter = ORM::forTable('referral_routes')
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
        $route = ORM::forTable('referral_routes')
            ->findOne($routeId);

        $response = $route->asArray();
        if ($route) {
            // Get assigned referrals
            $response['assigned_referrals'] = ORM::forTable('referrals')
                ->where('referral_route_id', $routeId)
                ->orderByAsc('route_order')
                ->findArray();
        }
        $this->renderJson($response);
    }

    public function save() {
        $route = ORM::forTable('referral_routes')->create();
        $route->title = $this->data['title'];
        $route->created_at = date('Y-m-d H:i:s');
        $route->status = 'Pending';
        if ($route->save()) {
            foreach ($this->data['assigned_referral_ids'] as $index => $referralId) {
                $referral = ORM::forTable('referrals')
                    ->findOne($referralId);
                $referral->referral_route_id = $route->id;
                $referral->status = 'Assigned';
                $referral->route_order = $index;
                $referral->save();
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
        $route = ORM::forTable('referral_routes')->findOne($routeId);
        $route->title = $this->data['title'];
        $route->status = $this->data['status'];

        if ($route->save()) {
            if (isset($this->data['assigned_referral_ids'])) {
                $oldAssignedReferrals = ORM::forTable('referrals')
                    ->where('referral_route_id', $routeId)
                    ->findMany();
                foreach ($oldAssignedReferrals as $referral) {
                    if (!in_array($referral->id, $this->data['assigned_referral_ids'])) {
                        $referral->status = 'Pending';
                        $referral->referral_route_id = NULL;
                        $referral->route_order = 0;
                        $referral->save();
                    }
                }

                foreach ($this->data['assigned_referral_ids'] as $index => $id) {
                    $referral = ORM::forTable('referrals')
                        ->findOne($id);
                    $referral->status = 'Assigned';
                    $referral->referral_route_id = $routeId;
                    $referral->route_order = $index;
                    $referral->save();
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
