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
        $routes = ORM::forTable('referral_routes')
            ->orderByDesc('created_at')
            ->findArray();
        $this->renderJson($routes);
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

    public function printRoute() {
        header('Content-Type: text/html');

        $referral_route_id = $_REQUEST['id'];
        $referral_route = ORM::forTable('referral_routes')
                          ->join('referrals',
                                ['referral_routes.id','=','referrals.referral_route_id'])
                          ->where('referral_routes.id',$referral_route_id)
                          ->select('referral_routes.title')
                          ->select('referrals.*')
                          ->order_by_asc('route_order')
                          ->findArray();
        
       $points = [];
       $referral_title;
        foreach ($referral_route as $value) {
            $points[] = [
                'lat' => $value['lat'],
                'lng' => $value['lng']
            ];

            $referral_title  = $value['title'];
        }

        $start = [
            'lat' => $points[0]['lat'],
            'lng' => $points[0]['lng']
        ];
        $end = [
            'lat' => $points[count($points)-1]['lat'],
            'lng' => $points[count($points)-1]['lng']
        ];
        
        require_once TEMPLATES_DIR . '/print/referral-route.php';
    }
}
?>
