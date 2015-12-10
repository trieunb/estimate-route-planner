<?php
class CustomerController extends BaseController {

    public function index() {
        $customers = ERPCacheManager::fetch('customers', function() {
            return ORM::forTable('customers')
                ->tableAlias('c')
                ->leftOuterJoin('customers', ['c.parent_id', '=', 'pc.id'], 'pc')
                ->selectMany(
                    'c.id', 'c.display_name', 'c.parent_id', 'c.sub_level', 'c.email',
                    'c.primary_phone_number', 'c.alternate_phone_number',
                    'c.mobile_phone_number',
                    'c.bill_address', 'c.bill_city', 'c.bill_state', 'c.bill_zip_code',
                    'c.bill_country',
                    'c.ship_address', 'c.ship_city', 'c.ship_state', 'c.ship_zip_code',
                    'c.ship_country'
                )
                ->select('pc.display_name', 'parent_display_name')
                ->where('c.active', true)
                ->orderByAsc('c.display_name')
                ->findArray();
        });
        $this->renderJson($customers);
    }

    public function show() {
        $customer = ORM::forTable('customers')
            ->findOne($this->data['id']);
        $this->renderJson($customer->asArray());
    }

    public function create() {
        // Create customer entity and push to QB
        $customerInfo = $this->data;
        $sync = Asynchronzier::getInstance();
        $qbcustomerObj = $sync->createCustomer($customerInfo);
        $customer = ORM::forTable('customers')->create();
        $customer->set(ERPDataParser::parseCustomer($qbcustomerObj));
        $customer->save();
        ERPCacheManager::clear('customers');
        $this->renderJson([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer->asArray()
        ]);
    }
}
?>
