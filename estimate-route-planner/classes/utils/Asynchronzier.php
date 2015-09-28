<?php
class Asynchronzier {
    public $access_token;
    public $access_token_secret;
    public $consumer_key;
    public $consumer_secret;
    public $realmId;

    public $serviceType = IntuitServicesType::QBO;

    public $requestValidator;
    public $serviceContext;
    public $dataService;

    /*
        param $data of function construct is array
        $data = [
            'access_token'=> 'value',
            'access_token_secret' = 'value',
            'consumer_key' => 'value',
            'consumer_secret' => 'value'
            'realmId' => 'value'
            ]
    */
    public function __construct($data) {
        $this->access_token = $data['access_token'];
        $this->access_token_secret = $data['access_token_secret'];
        $this->consumer_key = $data['consumer_key'];
        $this->consumer_secret = $data['consumer_secret'];
        $this->realmId = $data['realmId'];

        $this->requestValidator = new OAuthRequestValidator(
                $this->access_token,
                $this->access_token_secret,
                $this->consumer_key,
                $this->consumer_secret
            );

        $this->serviceContext = new ServiceContext(
                $this->realmId,
                $this->serviceType,
                $this->requestValidator
            );
        if (!$this->serviceContext) exit("Problem while initializing ServiceContext.\n");

        $this->dataService = new DataService($this->serviceContext);
        if (!$this->dataService) exit("Problem while initializing ServiceContext.\n");
    }

    public function syncCustomer() {
        $data = $this->Query("SELECT * FROM Customer");
        $customerModel = new CustomerModel;
        $data_sync = [];
        if (!is_null($data)) {
            foreach ($data as $item) {
                $result = $this->parseCustomer($item);
                array_push($data_sync, array('id' => $result['id']));
                $customer = $customerModel->findBy(['id'=>$result['id']]);
                if ($customer != null) {
                    if (strtotime($result['last_updated_at']) > strtotime($customer['last_updated_at'])) {
                        $customerModel->update($result,['id' => $customer['id']]);
                    }
                } else {
                    $customerModel->insert($result);
                }
            }
            $data_customer = $customerModel->getAllWithColumns(['id'], array());
            $data_delete = $this->mergeData($data_sync, $data_customer);
            foreach ($data_delete as $item) {
                $customerModel->delete(['id' => $item['id']]);
            }
        }
    }

    public function syncEmployee() {
        $data = $this->Query("SELECT * FROM Employee");
        $employeeModel = new EmployeeModel;
        $data_sync = [];
        if (!is_null($data)) {
            foreach ($data as $item) {
                $result = $this->parseEmployee($item);
                array_push($data_sync, array('id' => $result['id']));
                $employee = $employeeModel->findBy(['id' => $result['id']]);
                if ($employee != null) {
                    if (strtotime($result['last_updated_at']) > strtotime($employee['last_updated_at'])) {
                        $employeeModel->update($result, ['id' => $employee['id']]);
                    }
                }else {
                    $employeeModel->insert($result);
                }
            }
            $data_employee = $employeeModel->getAllWithColumns(['id'], array());
            $data_delete = $this->mergeData($data_sync, $data_employee);
            foreach ($data_delete as $item) {
                $employeeModel->delete(['id' => $item['id']]);
            }
        }
    }

    public function syncEstimate() {
        $data = $this->Query("SELECT * FROM Estimate");
        $estimateModel = new EstimateModel;
        $data_sync = [];
        $estimateLineModel = new EstimateLineModel;
        if (!is_null($data)) {
            foreach ($data as $item) {
                $result = $this->parseEstimate($item);
                array_push($data_sync, array('id' => $result['id']));
                $estimate = $estimateModel->findBy(['id' => $result['id']]);
                if ($estimate != null) {
                    if (strtotime($result['last_updated_at']) > strtotime($estimate['last_updated_at'])) {
                        $estimateModel->update($result,['id' => $estimate['id']]);
                    }
                } else {
                    $estimateModel->insert($result);
                }
                $data_line_sync = [];
                foreach ($result['line'] as $line) {
                    $result_line = $this->parseEstimate_line($line, $result['id']);
                    if (($result_line['line_id'] != null) && ($result_line['estimate_id'] != null)) {
                        array_push($data_line_sync, array('line_id' => $result_line['line_id'], 'estimate_id' => $result_line['estimate_id']));
                        $estimate_line = $estimateLineModel->findBy(['line_id' => $result_line['line_id'], 'estimate_id' => $result_line['estimate_id']]);
                        if ($estimate_line == null) {
                            $estimateLineModel->insert($result_line);
                        } else {
                            $estimateLineModel->update($result_line, ['line_id' => $result_line['line_id'], 'estimate_id' => $result_line['estimate_id']]);
                        }
                    }
                }
                $data_estimate_line = $estimateLineModel->getAllWithColumns(['line_id','estimate_id'], ['estimate_id' => $result['id']]);
                $data_delete = $this->mergeData($data_line_sync, $data_estimate_line);
                foreach ($data_delete as $item_line_delete) {
                    $estimateLineModel->delete(['line_id' => $item_line_delete['line_id'], 'estimate_id' => $item_line_delete['estimate_id']]);
                }
            }
            $data_estimate = $estimateModel->getAllWithColumns(['id'], array());
            $data_delete = $this->mergeData($data_sync, $data_estimate);
            foreach ($data_delete as $item_delete) {
                $estimateModel->delete(['id' => $item_delete['id']]);
            }
        }
    }

    public function syncProductService() {
        $data = $this->Query("SELECT * FROM Item");
        $productserviceModel = new ProductServiceModel;
        $data_sync = [];
        if (!is_null($data)) {
            foreach ($data as $item) {
                $result = $this->parseProductService($item);
                array_push($data_sync, array('id' => $result['id']));
                $productservice = $productserviceModel->findBy(['id'=>$result['id']]);
                if ($productservice != null) {
                    if (strtotime($result['last_updated_at']) > strtotime($productservice['last_updated_at'])) {
                        $productserviceModel->update($result,['id' => $productservice['id']]);
                    }
                } else {
                    $productserviceModel->insert($result);
                }
            }
            $data_productservice = $productserviceModel->getAllWithColumns(['id'], array());
            $data_delete = $this->mergeData($data_sync, $data_productservice);
            foreach ($data_delete as $item_delete) {
                $productserviceModel->delete(['id' => $item_delete['id']]);
            }
        }
    }

    public function syncAttachment() {
        $data = $this->Query("SELECT * FROM Attachable");
        $estimateAttachmentModel = new EstimateAttachmentModel;
        $data_sync = [];
        if (!is_null($data)) {
            foreach ($data as $item) {
                $result = $this->parseEstimate_attach($item);
                array_push($data_sync, array('id' => $result['id']));
                $estimateAttachment = $estimateAttachmentModel->findBy(['id'=>$result['id']]);
                if ($estimateAttachment != null) {
                    if (strtotime($result['last_updated_at']) > strtotime($estimateAttachment['last_updated_at'])) {
                        $estimateAttachmentModel->update($result,['id' => $estimateAttachment['id']]);
                    }
                } else {
                    $estimateAttachmentModel->insert($result);
                }
            }
            $data_attachment = $estimateAttachmentModel->getAllWithColumns(['id'], array());
            $data_delete = $this->mergeData($data_sync, $data_attachment);
            foreach ($data_delete as $item_delete) {
                $estimateAttachmentModel->delete(['id' => $item_delete['id']]);
            }
        }
    }

    public function syncAll() {
        $this->syncCustomer();
        $this->syncEmployee();
        $this->syncEstimate();
        $this->syncProductService();
        $this->syncAttachment();
    }

    public function mergeData($dataGet, $dataLocal) {
        $diff = [];
        foreach ($dataLocal as $l) {
            $exist = false;
            foreach ($dataGet as $g) {
                if ($g == $l) {
                    $exist = true;
                }
            }
            if (!$exist) {
                $diff[] = $l;
            }
        }

        return $diff;
    }

    /*
        $name is String,
        $positionStart is Integer,
        $maxResult is Integer
    */
    public function findAll($name, $postionStart, $maxResult) {
        return $this->dataService->FindAll($name, $postionStart, $maxResult);
    }

    /*
        $entity is Object
        Ex: $entiry = Customer;
        Customer = {
            'IPPCustomer': [{Taxable: true},{IPPPhysicalAddress: [{id:2, }]}]
        }
    */
    public function findById($entity) {
        return $this->dataService->FindById($entity);
    }

    /*
        $Query is String
        Ex: "SECLECT * FROM Customer"
    */
    public function Query($query) {
        return $this->dataService->Query($query);
    }

    /*
        Param Entity of function Create is array
        Ex: $entity = array(
            'name' => 'IPPCustomer',
            'attributes' => array(
                'Name' => 'SFR-SOFTWARE',
                'Display' => 'SFR Company'
                'BillAddr' => array(
                    array(
                        'name' => 'IPPPhysicalAddress'
                        'attributes' => array(
                            'City' => 'DaNang'
                            )
                        )
                    )
                ),
                'Line'  => array(
                    array(
                        'name' => 'IPPLine',
                        'attributes' => array(
                            'SalesItemLineDetail' => array(
                                array(
                                    'name' => 'IPPSalesItemLineDetail',
                                    'attributes' => array(
                                        'ItemRef' => '1'
                                    )
                                )
                            )
                        )
                    ),
                    array(
                        'name' => 'IPPLine',
                        'attributes' => array(
                            'SalesItemLineDetail' => array(
                                array(
                                    'name' => 'IPPSalesItemLineDetail',
                                    'attributes' => array(
                                        'ItemRef' => '1'
                                    )
                                )
                            )
                        )
                    ),
                )
                ....
            )
        );
    */
    public function Create($entity) {
        $object = new $entity['name']();
        foreach ($entity['attributes'] as $key => $value) {
            if (!is_array($entity['attributes'][$key])) {
                $object->$key = $value;
            } else {
                if ($key == 'Line') {
                    $object->$key = [];
                    foreach ($entity['attributes'][$key] as $sub) {
                        $sub_object = new $sub['name']();
                        foreach ($sub['attributes'] as $sub_key => $sub_value) {
                            if (!is_array($sub['attributes'][$sub_key])) {
                                $sub_object->$sub_key = $sub_value;
                            } else {
                                foreach ($sub['attributes'][$sub_key] as $sub_sub) {
                                    $sub_sub_object = new $sub_sub['name']();
                                    $sub_object->$sub_key = $sub_sub_object;
                                    foreach ($sub_sub['attributes'] as $sub_sub_key => $sub_sub_value) {
                                        $sub_object->$sub_key->$sub_sub_key = $sub_sub_value;
                                    }
                                }
                            }
                        }
                        array_push($object->$key, $sub_object);
                    }
                }else {
                    foreach ($entity['attributes'][$key] as $sub) {
                        $sub_object = new $sub['name']();
                        $object->$key = $sub_object;
                        foreach ($sub['attributes'] as $sub_key => $sub_value) {
                            if (!is_array($sub['attributes'][$sub_key])) {
                                $object->$key->$sub_key = $sub_value;
                            } else {
                                foreach ($sub['attributes'][$sub_key] as $sub_sub) {
                                    $sub_sub_object = new $sub_sub['name']();
                                    $object->$key->$sub_key = $sub_sub_object;
                                    foreach ($sub_sub['attributes'] as $sub_sub_key => $sub_sub_value) {
                                        $object->$key->$sub_key->$sub_sub_key = $sub_sub_value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->dataService->Add($object);
    }

    /*
        Param $object of function Update is object
        Ex: $entity = Customer
    */
    public function Update($entity) {
        $object = new $entity['name']();
        foreach ($entity['attributes'] as $key => $value) {
            if (!is_array($entity['attributes'][$key])) {
                $object->$key = $value;
            } else {
                if ($key == 'Line') {
                    $object->$key = [];
                    foreach ($entity['attributes'][$key] as $sub) {
                        $sub_object = new $sub['name']();
                        foreach ($sub['attributes'] as $sub_key => $sub_value) {
                            if (!is_array($sub['attributes'][$sub_key])) {
                                $sub_object->$sub_key = $sub_value;
                            } else {
                                foreach ($sub['attributes'][$sub_key] as $sub_sub) {
                                    $sub_sub_object = new $sub_sub['name']();
                                    $sub_object->$sub_key = $sub_sub_object;
                                    foreach ($sub_sub['attributes'] as $sub_sub_key => $sub_sub_value) {
                                        $sub_object->$sub_key->$sub_sub_key = $sub_sub_value;
                                    }
                                }
                            }
                        }
                        array_push($object->$key, $sub_object);
                    }
                }else {
                    foreach ($entity['attributes'][$key] as $sub) {
                        $sub_object = new $sub['name']();
                        $object->$key = $sub_object;
                        foreach ($sub['attributes'] as $sub_key => $sub_value) {
                            if (!is_array($sub['attributes'][$sub_key])) {
                                $object->$key->$sub_key = $sub_value;
                            } else {
                                foreach ($sub['attributes'][$sub_key] as $sub_sub) {
                                    $sub_sub_object = new $sub_sub['name']();
                                    $object->$key->$sub_key = $sub_sub_object;
                                    foreach ($sub_sub['attributes'] as $sub_sub_key => $sub_sub_value) {
                                        $object->$key->$sub_key->$sub_sub_key = $sub_sub_value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->dataService->Add($object);
    }

    /*
        Param $object of function Update is object
        Ex: $entity = Customer
    */
    public function Delete($object) {
        return $this->dataService->Delete($object);
    }
    public function Edit($object){
        return $this->dataService->Add($object);
    }

    public function parseCustomer($data) {
        $billAddress = $data->BillAddr;
        if (null != ($billAddress)) {
            $bill_address = $billAddress->Line1;
            $bill_city = $billAddress->City;
            $bill_state = $billAddress->CountrySubDivisionCode;
            $bill_zip_code = $billAddress->PostalCode;
            $bill_country = $billAddress->Country;
        }

        $shipAddress = $data->ShipAddr;
        if (null != ($shipAddress)) {
            $ship_address = $shipAddress->Line1;
            $ship_city = $shipAddress->City;
            $ship_state = $shipAddress->CountrySubDivisionCode;
            $ship_zip_code = $shipAddress->PostalCode;
            $ship_country = $shipAddress->Country;
        }

        $given_name = $data->GivenName;
        $middle_name = $data->MiddleName;
        $family_name = $data->FamilyName;
        $suffix = $data->Suffix;
        $company_name = $data->CompanyName;
        $display_name = $data->DisplayName;
        $print_name = $data->PrintOnCheckName;

        if (null != ($data->PrimaryPhone)) {
            $primary_phone_number = $data->PrimaryPhone->FreeFormNumber;
        }

        if (null != ($data->Mobile)) {
            $mobile_phone_number = $data->Mobile->FreeFormNumber;
        }

        if (null != ($data->AlternatePhone)) {
            $alternate_phone_number = $data->AlternatePhone->FreeFormNumber;
        }

        if (null != ($data->Fax)) {
            $fax = $data->Fax->FreeFormNumber;
        }

        if (null != ($data->PrimaryEmailAddr)) {
            $email = $data->PrimaryEmailAddr->Address;
        }

        if (null != ($data->WebAddr)) {
            $website = $data->WebAddr->URI;
        }

        if (null != ($data->MetaData)) {
            $time = strtotime($data->MetaData->LastUpdatedTime);
            $last_updated_at = date("Y-m-d h:i:s",$time);
        }

        $id = $data->Id;
        $active = $data->Active == 'true';
        $taxable = $data->Taxable == 'true';
        $title = $data->Title;
        $parentId = NULL;
        if (null != $data->ParentRef) {
            $parentId = $data->ParentRef->value;
        }

        return [
            'id'                    => $id,
            'parent_id'             => $parentId,
            'title'                 => $title,
            'given_name'            => $given_name,
            'middle_name'           => $middle_name,
            'family_name'           => $family_name,
            'suffix'                => $suffix,
            'display_name'          => $display_name,
            'print_name'            => $print_name,
            'email'                 => $email,
            'primary_phone_number'  => $primary_phone_number,
            'mobile_phone_number'   => $mobile_phone_number,
            'alternate_phone_number'=> $alternate_phone_number,
            'fax'                   => $fax,
            'company_name'          => $company_name,
            'website'               => $website,
            'bill_address'          => $bill_address,
            'bill_city'             => $bill_city,
            'bill_state'            => $bill_state,
            'bill_zip_code'         => $bill_zip_code,
            'bill_country'          => $bill_country,
            'ship_address'          => $ship_address,
            'ship_city'             => $ship_city,
            'ship_state'            => $ship_state,
            'ship_zip_code'         => $ship_zip_code,
            'ship_country'          => $ship_country,
            'active'                => $active,
            'taxable'               => $taxable,
            'last_updated_at'       => $last_updated_at
        ];
    }

    public function parseEmployee($data) {
        $primaryAddress = $data->PrimaryAddr;
        if (null != ($primaryAddress)) {
            $primary_address = $primaryAddress->Line1;
            $primary_city = $primaryAddress->City;
            $primary_state = $primaryAddress->CountrySubDivisionCode;
            $primary_zip_code = $primaryAddress->PostalCode;
            $primary_country = $primaryAddress->Country;
        }
        $given_name = $data->GivenName;
        $middle_name = $data->MiddleName;
        $family_name = $data->FamilyName;
        $suffix = $data->Suffix;
        $company_name = $data->CompanyName;
        $display_name = $data->DisplayName;
        $print_name = $data->PrintOnCheckName;

        if (null != ($data->PrimaryPhone)) {
            $primary_phone_number = $data->PrimaryPhone->FreeFormNumber;
        }
        if (null != ($data->PrimaryEmailAddr)) {
            $email = $data->PrimaryEmailAddr->Address;
        }
        if (null != ($data->MetaData)) {
            $time = strtotime($data->MetaData->LastUpdatedTime);
            $last_updated_at = date("Y-m-d h:i:s",$time);
        }
        $ssn = $data->SSN;
        $id = $data->Id;
        $active = $data->Active == 'true';

        return [
            'id'                    => $id,
            'primary_address'       => $primary_address,
            'primary_city'          => $primary_city,
            'primary_state'         => $primary_state,
            'primary_zip_code'      => $primary_zip_code,
            'primary_country'       => $primary_country,
            'given_name'            => $given_name,
            'middle_name'           => $middle_name,
            'family_name'           => $family_name,
            'suffix'                => $suffix,
            'display_name'          => $display_name,
            'print_name'            => $print_name,
            'email'                 => $email,
            'primary_phone_number'  => $primary_phone_number,
            'ssn'                   => $ssn,
            'company_name'          => $company_name,
            'active'                => $active,
            'last_updated_at'       => $last_updated_at
        ];
    }

    public function parseEstimate($data, $data_local = null) {

        $id = $data->Id;
        $customer_id = $data->CustomerRef;
        $sync_token = $data->SyncToken;
        $doc_number = $data->DocNumber;
        $txn_date = $data->TxnDate;

        $billAddress = $data->BillAddr;
        if (null != ($billAddress)) {
            $bill_address_id = $billAddress->Id;
            $bill_address = $billAddress->Line1;
            $bill_city = $billAddress->City;
            $bill_state = $billAddress->CountrySubDivisionCode;
            $bill_zip_code = $billAddress->PostalCode;
            $bill_country = $billAddress->Country;
        }

        $shipAddress = $data->ShipAddr;
        if (null != ($shipAddress)) {
            $ship_address_id = $shipAddress->Id;
            $ship_address = $shipAddress->Line1;
            $ship_city = $shipAddress->City;
            $ship_state = $shipAddress->CountrySubDivisionCode;
            $ship_zip_code = $shipAddress->PostalCode;
            $ship_country = $shipAddress->Country;
        }
        if (null != ($data->BillEmail)) {
            $email = $data->BillEmail->Address;
        }
        $estimate_footer = $data->CustomerMemo;

        if (null != ($data->MetaData)) {
            $time = strtotime($data->MetaData->LastUpdatedTime);
            $last_updated_at = date("Y-m-d h:i:s",$time);
        }
        if (($data_local != null) && ($data_local['status'] == 'Completed')) {
            $status = 'Completed';
        }else {
            $status = $data->TxnStatus;
        }

        return [
            'id'                    => $id,
            'customer_id'           => $customer_id,
            'sync_token'            => $sync_token,
            'doc_number'            => $doc_number,
            'estimate_footer'       => $estimate_footer,
            'due_date'              => $data_local['due_date'],
            'txn_date'              => $txn_date,
            'ship_date'             => $ship_date,
            'expiration_date'       => $expiration_date,
            'accepted_date'         => $accepted_date,
            'primary_phone_number'  => $data_local['primary_phone_number'],
            'alternate_phone_number'=> $data_local['alternate_phone_number'],
            'email'                 => $email,
            'bill_address_id'       => $bill_address_id,
            'bill_address'          => $bill_address,
            'bill_city'             => $bill_city,
            'bill_state'            => $bill_state,
            'bill_zip_code'         => $bill_zip_code,
            'bill_country'          => $bill_country,
            'ship_address_id'       => $ship_address_id,
            'ship_address'          => $ship_address,
            'ship_city'             => $ship_city,
            'ship_state'            => $ship_state,
            'ship_zip_code'         => $ship_zip_code,
            'ship_country'          => $ship_country,
            'status'                => $status,
            'last_updated_at'       => $last_updated_at,
            'line'                  => $data->Line,
            'total'                 => $data->TotalAmt,

            'estimate_route_id'     => $data_local['estimate_route_id'],
            'ship_date'             => $data_local['ship_date'],
            'expiration_date'       => $data_local['expiration_date'],
            'accepted_date'         => $data_local['accepted_date'],
            'source'                => $data_local['source'],
            'customer_signature'    => $data_local['customer_signature'],
            'location_notes'        => $data_local['location_notes'],
            'date_of_signature'     => $data_local['date_of_signature'],
            'sold_by_1'             => $data_local['sold_by_1'],
            'sold_by_2'             => $data_local['sold_by_2'],
            'job_customer_id'       => $data_local['job_customer_id'],
            'job_address'           => $data_local['job_address'],
            'job_city'              => $data_local['job_city'],
            'job_state'             => $data_local['job_state'],
            'job_zip_code'          => $data_local['job_zip_code'],
            'job_lat'               => $data_local['job_lat'],
            'job_lng'               => $data_local['job_lng']
        ];
    }

    public function parseEstimate_line($data, $parent_id) {

        $line_id = $data->Id;
        $line_num = $data->LineNum;
        $estimate_id = $parent_id;
        $description = $data->Description;
        if (null != $data->SalesItemLineDetail) {
            $qty = 0;
            if ($data->SalesItemLineDetail->Qty) {
                $qty = $data->SalesItemLineDetail->Qty;
            }
            $rate = 0;
            if ($data->SalesItemLineDetail->UnitPrice) {
                $rate = $data->SalesItemLineDetail->UnitPrice;
            }
            $product_service_id = $data->SalesItemLineDetail->ItemRef;
        }

        return [
            'line_id'               => $line_id,
            'line_num'              => $line_num,
            'estimate_id'           => $estimate_id,
            'product_service_id'    => $product_service_id,
            'qty'                   => $qty,
            'rate'                  => $rate,
            'description'           => $description
        ];
    }

    public function parseEstimate_attach($data) {
        $id = $data->Id;
        if(null != ($data->AttachableRef)){
            $estimate_id = $data->AttachableRef->EntityRef;
        }
        $access_uri = $data->FileAccessUri;
        $tmp_download_uri = $data->TempDownloadUri;
        $content_type = $data->ContentType;
        $file_name = $data->FileName;
        if (null != ($data->MetaData)) {
            $time = strtotime($data->MetaData->LastUpdatedTime);
            $last_updated_at = date("Y-m-d h:i:s",$time);
        }

        return [
            'id'                    => $id,
            'estimate_id'           => $estimate_id,
            'content_type'          => $content_type,
            'access_uri'            => $access_uri,
            'tmp_download_uri'      => $tmp_download_uri,
            'file_name'             => $file_name,
            'last_updated_at'       => $last_updated_at
        ];
    }

    public function parseProductService($data) {
        $id = $data->Id;
        $name = $data->Name;
        $description = $data->Description;
        $rate = $data->UnitPrice;
        $active = $data->Active == 'true';
        $taxable = $data->Taxable == 'true';
        if (null != ($data->MetaData)) {
            $time = strtotime($data->MetaData->LastUpdatedTime);
            $last_updated_at = date("Y-m-d h:i:s",$time);
        }

        return array(
            'id'                    => $id,
            'name'                  => $name,
            'description'           => $description,
            'rate'                  => $rate,
            'active'                => $active,
            'taxable'               => $taxable,
            'last_updated_at'       => $last_updated_at
        );
    }

    public function decodeCustomer($data) {
        $value = [
            'name'                                      => 'IPPCustomer',
            'attributes'                                => [
                'Name'                                  => $data['name'],
                'DisplayName'                           => $data['display_name']
            ]
        ];
        return $value;
    }

    public function decodeEstimate($data) {
        $value = array(
            'name' => 'IPPEstimate',
            'attributes'                                => array(
                'CustomerRef'                           => $data['customer_id'],
                'SyncToken'                             => $data['sync_token'],
                'DocNumber'                             => $data['doc_number'],
                'TxnDate'                               => $data['txn_date'],
                'DueDate'                               => $data['due_date'],
                'CustomerMemo'                          => $data['estimate_footer'],
                'BillAddr'                              => array(
                    [
                        'name'                          => 'IPPPhysicalAddress',
                        'attributes'                    => array(
                            'Id'                        => $data['bill_address_id'],
                            'Line1'                     => $data['bill_address'],
                            'City'                      => $data['bill_city'],
                            'CountrySubDivisionCode'    => $data['bill_state'],
                            'PostalCode'                => $data['bill_zip_code']
                        )
                    ]
                ),
                'ShipAddr'                              => array(
                    [
                        'name'                          => 'IPPPhysicalAddress',
                        'attributes'                    => array(
                            'Id'                        => $data['ship_address_id'],
                            'Line1'                     => $data['job_address'],
                            'City'                      => $data['job_city'],
                            'CountrySubDivisionCode'    => $data['']
                        )
                    ]
                ),
                'BillEmail'                             => array(
                    [
                        'name'                          => 'IPPEmailAddress',
                        'attributes'                    => array(
                            'Address'                   => $data['email']
                        )
                    ]
                )
            )
        );
        $value['attributes']['Line'] = array();
        foreach ($data['lines'] as $line) {
            $value_line = array(
                'name'                              => 'IPPLine',
                'attributes'                        => array(
                    'Id'                                    => $line['line_id'],
                    'Description'                           => $line['description'],
                    'DetailType'                            => 'SalesItemLineDetail',
                    'Amount'                                => (float)$line['qty'] * (float)$line['rate'],
                    'SalesItemLineDetail'                   => array(
                        [
                            'name'                          => 'IPPSalesItemLineDetail',
                            'attributes'                    => array(
                                'ItemRef'                   => $line['product_service_id'],
                                'Qty'                       => $line['qty'],
                                'UnitPrice'                 => $line['rate']
                            )
                        ]
                    )
                )
            );
            array_push($value['attributes']['Line'], $value_line);
        }
        if (isset($data['id'])) {
            $value['attributes']['Id'] = $data['id'];
        }
        if ($data['status'] == 'Completed') {
            $value['attributes']['TxnStatus'] = 'Accepted';
        }else {
            $value['attributes']['TxnStatus'] = $data['status'];
        }
        return $value;
    }

    /**
     * Returns an entity under the specified realm. The realm must be set in the context.
     *
     * @param object $entity Entity to Find
     * @return IPPIntuitEntity Returns an entity of specified Id.
     */
    public function Retrieve($entity) {
        return $this->dataService->Retrieve($entity);
    }

    public function upload($fileContent, $fileName, $mimeType, $objAttachable) {
        return $this->dataService->Upload($fileContent, $fileName, $mimeType, $objAttachable);
    }
}
?>
