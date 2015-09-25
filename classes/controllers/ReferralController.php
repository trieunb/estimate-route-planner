<?php
class ReferralController extends BaseController {

    public function index() {
        $page = $this->Page('page');
        $keyword = $this->Keyword('keyword');
        $refs = ORM::forTable('referrals')
            ->selectMany(
                'id', 'name', 'address', 'primary_phone',
                'date_service', 'status', 'date_requested'
            )
            ->whereLike('name', "%$keyword%")
            ->orderByDesc('date_requested')
            ->limit(self::PAGE_SIZE)
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->findArray();
        $counter = ORM::forTable('referrals')
            ->whereLike('name', "%$keyword%")
            ->selectExpr('COUNT(*)', 'count')
            ->findMany();
        $this->renderJson([
            'total' => $counter[0]->count,
            'data' => $refs,
            'keyword' => $keyword
        ]);
    }

    public function pending() {
        $refs = ORM::forTable('referrals')
            ->selectMany(
                'id', 'name', 'address', 'primary_phone', 'address', 'city',
                'state', 'zip_code',
                'status', 'date_requested', 'lat', 'lng'
            )
            ->where('status', 'Pending')
            ->whereNull('referral_route_id')
            ->orderByDesc('date_requested')
            ->findArray();
        $this->renderJson($refs);
    }

    public function add() {
        $ref = ORM::forTable('referrals')->create();
        $ref->set($this->data);
        $ref->save();
        $this->renderJson([
            'success'  => true,
            'message'  => 'Job request created successfully',
            'data'     => $ref->asArray()
        ]);
    }

    public function show() {
        $ref = ORM::forTable('referrals')
            ->findOne($this->data['id']);
        if ($ref) {
            $this->renderJson($ref->asArray());
        } else {
            $this->render404();
        }
    }

    public function update() {
        $ref = ORM::forTable('referrals')
            ->findOne($this->data['id']);
        if ($ref) {
            $ref->set($this->data);
            if (!$this->data['referral_route_id']) {
                $ref->referral_route_id = NULL;
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

    public function updateStatus() {
        $ref = ORM::forTable('referrals')
            ->findOne($this->data['id']);
        if ($this->data['status'] == 'Assigned' && $this->data['referral_route_id']) {
            $route = ORM::forTable('referral_routes')->findOne($this->data['referral_route_id']);
            $assignedReferralsCount = ORM::forTable('referrals')
                ->select('id')
                ->where('referral_route_id', $route->id)
                ->count();
            $ref->referral_route_id = $this->data['referral_route_id'];
            $ref->status = 'Assigned';
            $ref->route_order = $assignedReferralsCount;

        } elseif($this->data['status'] == 'Pending') {
            $ref->status = 'Pending';
            $ref->route_order = 0;
            $ref->referral_route_id = NULL;
        } else {
            $ref->status = $this->data['status'];
        }
        $ref->save();
    }

    public function printPDF() {
        header("Content-Type: text/html");
        $companyInfo = ORM::forTable('company_info')->findOne();
        $referral = ORM::forTable('referrals')->findOne($_REQUEST['id']);
        require TEMPLATES_DIR . '/print/referral.php';
        exit;
    }

}

?>
