<?php
class ReferralRouteController extends BaseController {

    public function recent() {
        $routes = ORM::forTable('referral_routes')
            ->orderByDesc('created_at')
            ->limit(5)
            ->findArray();
        $this->renderJson($routes);
    }

    public function all() {
        $routes = ORM::forTable('referral_routes')
            ->orderByDesc('created_at')
            ->limit(self::PAGE_SIZE)
            ->findArray();
        $this->renderJson($routes);
    }

    public function index() {
        $page = $this->getPageParam();
        $keyword = $this->getKeywordParam();
        $routes = ORM::forTable('referral_routes')
            ->whereLike('title', "%$keyword%")
            ->orderByDesc('created_at')
            ->limit(self::PAGE_SIZE)
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->findArray();
        $counter = ORM::forTable('referral_routes')
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
        $route = ORM::forTable('referral_routes')
            ->findOne($routeId);

        $response = $route->asArray();
        if ($route) {
            $referralM = new ReferralModel;
            // Get assigned referrals
            $response['assigned_referrals'] = $referralM->tableAlias('r')
                ->join('customers', ['r.customer_id', '=', 'c.id'], 'c')
                ->selectMany(
                    'r.id', 'r.address', 'r.city',
                    'r.state', 'r.zip_code', 'r.primary_phone_number',
                    'r.status', 'r.date_requested', 'r.lat', 'r.lng'
                )
                ->select('c.display_name', 'customer_display_name')
                ->where('route_id', $routeId)
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
                $referral->route_id = $route->id;
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
                    ->where('route_id', $routeId)
                    ->findMany();
                foreach ($oldAssignedReferrals as $referral) {
                    if (!in_array($referral->id, $this->data['assigned_referral_ids'])) {
                        $referral->status = 'Pending';
                        $referral->route_id = NULL;
                        $referral->route_order = 0;
                        $referral->save();
                    }
                }

                foreach ($this->data['assigned_referral_ids'] as $index => $id) {
                    $referral = ORM::forTable('referrals')
                        ->findOne($id);
                    $referral->status = 'Assigned';
                    $referral->route_id = $routeId;
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
