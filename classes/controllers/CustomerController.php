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
                    'c.mobile_phone_number', 'c.notes',
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

    /**
     * Note: this action for both create and update function
     */
    public function create() {
        // Create customer entity and push to QB
        $customerInfo = $this->data;
        if (isset($customerInfo['id'])) {
            $customer = ORM::forTable('customers')->findOne($customerInfo['id']);
            // Set to sync token for update
            $customerInfo['sync_token'] = $customer->sync_token;
        } else {
            $customer = ORM::forTable('customers')->create();
        }
        $sync = Asynchronzier::getInstance();
        try {
            $qbCustomerObj = $sync->createCustomer($customerInfo);
            $customer->set(ERPDataParser::parseCustomer($qbCustomerObj));
            $customer->save();
        } catch (QuickbooksAPIException $e) {
            // Maybe the sync token wrong when update
            if ($e->getStatusCode() == '400' && $customerInfo['id']) {
                // Try to get update token
                $qbCustomerEntity = new IPPCustomer();
                $qbCustomerEntity->Id = $customerInfo['id'];
                $returnCustomerObj = $sync->Retrieve($qbCustomerEntity);
                if ($qbCustomerObj->SyncToken != $returnCustomerObj->SyncToken) {
                    $customerInfo['sync_token'] = $returnCustomerObj->SyncToken;
                    $qbCustomerObj = $sync->createCustomer($customerInfo);
                    $customer->set(ERPDataParser::parseCustomer($qbCustomerObj));
                    $customer->save();
                } else {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        ERPCacheManager::clear('customers');
        $customerData = $customer->asArray();
        if ($customerData['parent_id']) {
            $parentCus = ORM::forTable('customers')
                ->select('display_name')
                ->findOne($customerData['parent_id']);
            $customerData['parent_display_name'] = $parentCus->display_name;
        }
        if ($customer->save()) {
            $this->renderJson([
                'success' => true,
                'message' => 'Customer saved successfully',
                'customer' => $customerData
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'Failed to save customer'
            ]);
        }
    }
}
?>
