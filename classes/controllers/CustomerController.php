<?php
class CustomerController extends BaseController {

    public function index() {
        $customers = ORM::forTable('customers')
            ->tableAlias('c')
            ->leftOuterJoin('customers', ['c.parent_id', '=', 'pc.id'], 'pc')
            ->selectMany(
                'c.id', 'c.display_name',
                'c.parent_id', 'c.sub_level', 'c.email',
                'c.primary_phone_number', 'c.alternate_phone_number',
                'c.mobile_phone_number',
                'c.bill_address', 'c.bill_city', 'c.bill_state', 'c.bill_zip_code',
                'c.ship_address', 'c.ship_city', 'c.ship_state', 'c.ship_zip_code'
            )
            ->select('pc.display_name', 'parent_display_name')
            ->where('c.active', true)
            ->orderByAsc('c.display_name')
            ->findArray();
        $this->renderJson(
            $this->_sortCustomers($customers)
        );
    }

    public function show() {
        $customer = ORM::forTable('customers')->findOne($this->data['id']);
        $this->renderJson($customer->asArray());
    }

    private function _buildTree($itemList, $parentId) {
        $result = [];
        foreach ($itemList as $index => $item) {
            if ($item['parent_id'] == $parentId) {
                $newItem = $item;
                $newItem['childs'] = $this->_buildTree($itemList, $newItem['id']);
                $result[] = $newItem;
                unset($itemList[$index]);
            }
        }

        if (count($result) > 0) {
            return $result;
        }
        return null;
    }

    private function _flatternCustomer(&$item) {
        $results = [];
        $results[0] = $item;
        if (is_array($item['childs']) && count($item['childs']) > 0) {
            $childs = $item['childs'];
            usort($childs, function($a, $b) {
                return strcmp($a['display_name'], $b['display_name']);
            });
            foreach($childs as $child) {
                $results = array_merge($results, $this->_flatternCustomer($child));
            }
        }
        return $results;
    }

    private function _sortCustomers($customers) {
        $customersTree = array_values($this->_buildTree($customers, null));
        $results = [];
        foreach ($customersTree as $node) {
            $results = array_merge($results, $this->_flatternCustomer($node));
        }
        foreach ($results as $index => &$node) {
            $node['order'] = $index;
            unset($node['childs']);
        }
        return $results;
    }
}
?>
