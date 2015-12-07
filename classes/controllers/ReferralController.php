<?php
class ReferralController extends BaseController {

    /**
     * Return "Incomplete" job requests (status is Pending or Assigned)
     */
    public function index() {
        $page = $this->getPageParam();
        $keyword = $this->getKeywordParam();
        $searchQuery = ORM::forTable('referrals')
            ->tableAlias('r')
            ->join('customers', ['r.customer_id', '=', 'c.id'], 'c')
            ->whereIn('r.status', ["Pending", "Assigned"]);
        if ($keyword) {
            $searchQuery->whereLike('c.display_name', "%$keyword%");
        }
        $countQuery = clone($searchQuery);
        $refs = $searchQuery
            ->selectMany(
                'r.id', 'r.address', 'r.primary_phone_number',
                'r.date_service', 'r.status', 'r.date_requested',
                'r.estimator_id'
            )
            ->select('c.display_name', 'customer_display_name')
            // Join to get employee name
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
            ->selectExpr("CONCAT_WS(' ',wpum1.meta_value,wpum2.meta_value)", 'estimator_full_name')
            ->groupBy('r.id')
            ->orderByDesc('r.id')
            ->limit(self::PAGE_SIZE)
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->findArray();
        $counter = $countQuery->selectExpr('COUNT(*)', 'count')->findMany();
        $this->renderJson([
            'total' => $counter[0]->count,
            'data' => $refs
        ]);
    }

    /**
     * Get pending job requests for assigning to route
     */
    public function pending() {
        $model = new ReferralModel;
        $refs = $model
            ->tableAlias('r')
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
            ->selectMany(
                'r.id', 'r.customer_id', 'r.address', 'r.city',
                'r.state', 'r.zip_code', 'r.primary_phone_number',
                'r.status', 'r.date_requested', 'r.lat', 'r.lng'
            )
            ->selectExpr("CONCAT_WS(' ',wpum1.meta_value, wpum2.meta_value)", 'estimator_full_name')
            ->select('c.display_name', 'customer_display_name')
            ->where('r.status', 'Pending')
            ->whereNull('r.route_id')
            ->orderByDesc('r.date_requested')
            ->findArray();
        $this->renderJson($refs);
    }

    /**
     * Create new job request
     */
    public function add() {
        $customerData = $this->_checkForCreateNewCustomer();
        $insertData = array_merge($this->data, $customerData);
        $keepNullFields = [
            'estimator_id', 'date_requested', 'date_service', 'class_id'
        ];
        foreach ($keepNullFields as $field) {
            if (!$insertData[$field]) {
                $insertData[$field] = null;
            }
        }
        $model = new ReferralModel;
        $ref = $model->create();
        $ref->set($insertData);
        $ref->save();
        $this->renderJson([
            'success'  => true,
            'message'  => 'Job request created successfully',
            'data'     => $ref->asArray()
        ]);
    }

    /**
     * Get a job request details
     */
    public function show() {
        $ref = ORM::forTable('referrals')
            ->tableAlias('r')
            ->leftOuterJoin('customers', ['r.customer_id', '=', 'c.id'], 'c')
            ->select('r.*')
            ->select('c.display_name', 'customer_display_name')
            ->select('c.active', 'customer_active')
            ->findOne($this->data['id']);
        if ($ref) {
            $this->renderJson($ref->asArray());
        } else {
            $this->render404();
        }
    }

    /**
     * Update a job request
     */
    public function update() {
        $customerData = $this->_checkForCreateNewCustomer();
        $updateData = array_merge($this->data, $customerData);
        $model = new ReferralModel;
        $ref = $model->findOne($updateData['id']);
        if ($ref) {
            $ref->set($updateData);
            $keepNullFields = [
                'route_id', 'estimator_id', 'date_requested',
                'date_service', 'class_id'
            ];
            foreach ($keepNullFields as $field) {
                if (!$updateData[$field]) {
                    $ref->set($field, null);
                }
            }
            $ref->save();
            $this->renderJson([
                'success' => true,
                'message' => 'Job request updated successfully',
                'data'    => $ref->asArray()
            ]);
        } else {
            http_response_code(404);
            $this->renderJson([
                'success' => false,
                'message' => 'Resource not found'
            ]);
        }
    }

    /**
     * Update status for a job request
     */
    public function updateStatus() {
        $ref = ORM::forTable('referrals')
            ->findOne($this->data['id']);
        if ($this->data['status'] == 'Assigned' && $this->data['route_id']) {
            $route = ORM::forTable('estimate_routes')
                ->findOne($this->data['route_id']);
            $assignedReferralsCount = ORM::forTable('referrals')
                ->select('id')
                ->where('route_id', $route->id)
                ->count();
            $ref->route_id = $this->data['route_id'];
            $ref->status = 'Assigned';
            $ref->route_order = $assignedReferralsCount;

        } elseif ($this->data['status'] == 'Pending') {
            $ref->status = 'Pending';
            $ref->route_order = 0;
            $ref->route_id = NULL;
        } else {
            $ref->status = $this->data['status'];
        }
        if ($ref->save()) {
            $this->renderJson([
                'success' => true,
                'message' => 'Job request status updated successfully'
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'An error has occurred while saving job request'
            ]);
        }
    }

    public function printPDF() {
        header("Content-Type: text/html");
        $companyInfo = ORM::forTable('company_info')->findOne();
        $referral = ORM::forTable('referrals')
            ->tableAlias('r')
            ->join('customers', ['r.customer_id', '=', 'c.id'], 'c')
            ->select('r.*')
            ->select('c.display_name', 'customer_display_name')
            ->findOne($_REQUEST['id']);
        require ERP_TEMPLATES_DIR . '/print/referral.php';
    }

    /**
     * Collect referral field as customer' shipping address
    */
    private function _collectCustomerInfo() {
        $customerInfo = [];
        $customerInfo['display_name']   = trim(@$this->data['customer_display_name']);
        $customerInfo['ship_address']   = @$this->data['address'];
        $customerInfo['ship_city']      = @$this->data['city'];
        $customerInfo['ship_state']     = @$this->data['state'];
        $customerInfo['ship_zip_code']  = @$this->data['zip_code'];
        $customerInfo['ship_country']   = @$this->data['country'];
        $customerInfo['email']          = @$this->data['email'];
        $customerInfo['primary_phone_number'] = @$this->data['primary_phone_number'];
        $customerInfo['mobile_phone_number'] = @$this->data['mobile_phone_number'];
        return $customerInfo;
    }

    private function _checkForCreateNewCustomer() {
        $return = [];
        if (($this->data['customer_id'] == 0) && // Has new customer
            isset($this->data['customer_display_name']) &&
            trim($this->data['customer_display_name'])) {
            $sync = Asynchronzier::getInstance();
            $qbcustomerObj = $sync->createCustomer($this->_collectCustomerInfo());
            $customerRecord = ORM::forTable('customers')->create();
            $customerRecord->set(ERPDataParser::parseCustomer($qbcustomerObj));
            $customerRecord->save();
            $return['customer_id'] = $customerRecord->id;
        }
        return $return;
    }
}

?>
