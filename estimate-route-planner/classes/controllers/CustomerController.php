<?php
class CustomerController extends BaseController {

    public function index() {
        $customerModel = new CustomerModel();
        $this->renderJson(
            $customerModel->all()
        );
    }
    public function show() {
        $customerModel = new CustomerModel();
        $cons = [
            'id' => $this->data['id']
        ];
        //var_dump($customerModel->findBy($cons));die();
        $this->renderJson($customerModel->findBy($cons));
    }

    /*
        Param Entity of function add is array
        Ex: $data = array(
            'name' => 'IPPCustomer',
            'attributes' = array(
                'Name' => 'SFR-SOFTWARE',
                'DisplayName' => 'SFR Company'
                ....
            )
        );
    */
    public function add() {
        $sync = new Asynchronzier(PreferenceModel::getQuickbooksAPIConnectionInfo());
        $params = $sync->decodeCustomer($this->data);
        $result = $sync->Create($params);
        $customer = ORM::forTable('customers')->create();
        $customer->set($sync->parseCustomer($result));
        if ($customer->save()) {
            $this->renderJson([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => $customer->asArray()
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'Failed to create customer'
            ]);
        }
    }
}
?>
