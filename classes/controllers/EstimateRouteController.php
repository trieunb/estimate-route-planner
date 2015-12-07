<?php
class EstimateRouteController extends BaseController {

    public function recent() {
        $routes = ORM::forTable('estimate_routes')
            ->tableAlias('er')
            // Join to get employee full name
            ->leftOuterJoin('wp_users', ['er.estimator_id', '=', 'wpu.id'], 'wpu')
            ->leftOuterJoin(
                'wp_usermeta',
                "wpu.id = wpum1.user_id AND wpum1.meta_key='first_name'",
                'wpum1'
            )
            ->leftOuterJoin(
                'wp_usermeta',
                "wpu.id = wpum2.user_id AND wpum2.meta_key='last_name'",
                'wpum2'
            )
            ->select('er.*')
            ->selectExpr("CONCAT_WS(' ',wpum1.meta_value, wpum2.meta_value)", 'estimator_full_name')
            ->orderByDesc('er.created_at')
            ->limit(5)
            ->findArray();
        $this->renderJson($routes);
    }

    /**
     * Return routes for quick assignement in job request routes
     */
    public function all() {
        $query = ORM::forTable('estimate_routes')->tableAlias('er');
        if ($this->currentUserHasCap('erpp_estimator_only_routes')) {
            $query->where('er.estimator_id', $this->currentUser->id);
        }
        $routes = $query->orderByDesc('er.created_at')
            ->limit(self::PAGE_SIZE)
            ->findArray();
        $this->renderJson($routes);
    }

    public function index() {
        $page = $this->getPageParam();
        $keyword = $this->getKeywordParam();

        $filterQuery = ORM::forTable('estimate_routes')->tableAlias('er');
        if ($keyword) {
            $filterQuery->whereLike('er.title', "%$keyword%");
        }

        if ($this->currentUserHasCap('erpp_estimator_only_routes')) {
            $filterQuery->where('er.estimator_id', $this->currentUser->id);
        }

        $countQuery = clone($filterQuery);
        $routes = $filterQuery
            ->leftOuterJoin('wp_users', ['er.estimator_id', '=', 'wpu.id'], 'wpu')
            ->leftOuterJoin(
                'wp_usermeta',
                "wpu.id = wpum1.user_id AND wpum1.meta_key='first_name'",
                'wpum1'
            )
            ->leftOuterJoin(
                'wp_usermeta',
                "wpu.id = wpum2.user_id AND wpum2.meta_key='last_name'",
                'wpum2'
            )
            ->select('er.*')
            ->selectExpr("CONCAT_WS(' ',wpum1.meta_value,wpum2.meta_value)", 'estimator_full_name')
            ->orderByDesc('created_at')
            ->limit(self::PAGE_SIZE)
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->findArray();

        $counter = $countQuery->selectExpr('COUNT(*)', 'count')->findMany();
        $this->renderJson([
            'routes' => $routes,
            'total' => $counter[0]->count
        ]);
    }

    /**
     * Get data of a estimate route
     * Inclues assigned job requests are not incompleted yet.
     */
    public function show() {
        $routeId = $this->data['id'];
        $route = ORM::forTable('estimate_routes')
            ->findOne($routeId);

        $response = $route->asArray();
        if ($route) {
            $referralM = new ReferralModel;
            // Get assigned referrals
            $response['assigned_referrals'] = $referralM->tableAlias('r')
                ->join('customers', ['r.customer_id', '=', 'c.id'], 'c')
                // Join to get employee full name
                ->leftOuterJoin('wp_users', ['r.estimator_id', '=', 'wpu.id'], 'wpu')
                ->leftOuterJoin(
                    'wp_usermeta',
                    "wpu.id = wpum1.user_id AND wpum1.meta_key='first_name'",
                    'wpum1'
                )
                ->leftOuterJoin(
                    'wp_usermeta',
                    "wpu.id = wpum2.user_id AND wpum2.meta_key='last_name'",
                    'wpum2'
                )
                ->selectMany(
                    'r.id', 'r.address', 'r.city',
                    'r.state', 'r.zip_code', 'r.primary_phone_number',
                    'r.status', 'r.date_requested', 'r.lat', 'r.lng'
                )
                ->selectExpr("CONCAT_WS(' ',wpum1.meta_value, wpum2.meta_value)", 'estimator_full_name')
                ->select('c.display_name', 'customer_display_name')
                ->where('r.route_id', $routeId)
                ->whereIn('r.status', ['Pending', 'Assigned'])
                ->orderByAsc('route_order')
                ->findArray();
        }
        $this->renderJson($response);
    }

    public function save() {
        $route = ORM::forTable('estimate_routes')->create();
        $route->title = $this->data['title'];
        if (isset($this->data['estimator_id'])) {
            if ($this->data['estimator_id']) {
                $route->estimator_id = $this->data['estimator_id'];
            } else {
                $route->estimator_id = NULL;
            }
        }
        $route->created_at = date('Y-m-d H:i:s');
        $route->status = 'Pending';
        if ($route->save()) {
            // Set status of current assigned referrals to `Assigned`
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

    public function updateStatus() {
        $routeId = $this->data['id'];
        $route = ORM::forTable('estimate_routes')->findOne($routeId);
        $route->status = $this->data['status'];
        if ($route->save()) {
            $this->renderJson([
                'success' => true,
                'message' => 'Route status updated successfully'
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
        if (isset($this->data['estimator_id'])) {
            if ($this->data['estimator_id']) {
                $route->estimator_id = $this->data['estimator_id'];
            } else {
                $route->estimator_id = NULL;
            }
        }
        if ($route->save()) {
            // Remove old assigned referrals from route
            // Except `Completed` referrals
            $oldAssignedReferrals = ORM::forTable('referrals')
                ->where('route_id', $routeId)
                ->whereIn('status', ['Pending', 'Assigned'])
                ->findMany();
            if (isset($this->data['assigned_referral_ids']) &&
                is_array($this->data['assigned_referral_ids'])) {
                foreach ($oldAssignedReferrals as $referral) {
                    if (!in_array($referral->id, $this->data['assigned_referral_ids'])) {
                        $referral->status = 'Pending';
                        $referral->route_id = NULL;
                        $referral->route_order = 0;
                        $referral->save();
                    }
                }
                // Update current assigned referrals: change status to Assigned
                foreach ($this->data['assigned_referral_ids'] as $index => $id) {
                    $referral = ORM::forTable('referrals')->findOne($id);
                    $referral->status = 'Assigned';
                    $referral->route_id = $routeId;
                    $referral->route_order = $index;
                    $referral->save();
                }
            } else {
                foreach ($oldAssignedReferrals as $referral) {
                    $referral->status = 'Pending';
                    $referral->route_id = NULL;
                    $referral->route_order = 0;
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
