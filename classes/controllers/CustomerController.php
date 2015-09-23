<?php
class CustomerController extends BaseController {

    public function index() {
        $customers = ORM::forTable('customers')
            ->selectMany(
                'id', 'display_name', 'email',
                'primary_phone_number', 'alternate_phone_number',
                'mobile_phone_number',
                'bill_address', 'bill_city', 'bill_state', 'bill_zip_code',
                'bill_country',
                'ship_address', 'ship_city', 'ship_state', 'ship_zip_code',
                'ship_country'
            )->findArray();
        $this->renderJson($customers);
    }

    public function show() {
        $customer = ORM::forTable('customers')->findOne($this->data['id']);
        $this->renderJson($customer->asArray());
    }
}
?>
