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

    /**
     * Sync all customers
     * @param $lastSyncedTime the time in ISO 8601 format
     */
    public function syncCustomer($lastSyncedTime = null) {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("== Sync customer started");
        $maxResults = 1000;
        $startPos = 1;
        $localCus = ORM::forTable('customers')->select('id')->findArray();
        $localCusIds = [];
        foreach ($localCus as $cus) { $localCusIds[] = $cus['id']; }
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = "SELECT * FROM Customer WHERE Active IN (true, false)"
                    . " AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = "SELECT * FROM Customer WHERE Active IN (true, false)";
            }
            $query .= " startPosition $startPos maxResults $maxResults";
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records.");
                ORM::getDB()->beginTransaction();
                foreach ($res as $cusObj) {
                    $parsedCus = $this->parseCustomer($cusObj);
                    if (array_search($parsedCus['id'], $localCusIds) !== false) {
                        // The customer is already exists in local DB
                        $cusRecord = ORM::forTable('customers')->hydrate();
                    } else {
                        $cusRecord = ORM::forTable('customers')->create();
                    }
                    $cusRecord->set($parsedCus)->save();
                }
                ORM::getDB()->commit();
            } else {
                $loger->log("End of data.");
                break;
            }
            $startPos += $maxResults;
        }
        $loger->log("== Sync customer done, taken: " . (time() - $startedAt) . " secs\n");
    }

    /**
     * Sync all employees
     * @param $lastSyncedTime the time in ISO 8601 format
     */
    public function syncEmployee($lastSyncedTime = null) {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync employee started");
        $maxResults = 1000;
        $startPos = 1;
        $localEmps = ORM::forTable('employees')->select('id')->findArray();
        $localEmpIds = [];
        foreach ($localEmps as $emp) { $localEmpIds[] = $emp['id']; }
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = "SELECT * FROM Employee WHERE Active IN (true, false)"
                    . " AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = "SELECT * FROM Employee WHERE Active IN (true, false)";
            }
            $query .= " startPosition $startPos maxResults $maxResults";
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $empObj) {
                    $parsedEmpData = $this->parseEmployee($empObj);
                    if (array_search($parsedEmpData['id'], $localEmpIds) !== false) {
                        // The employee is already exists in local DB
                        $empRecord = ORM::forTable('employees')->hydrate();
                    } else {
                        $empRecord = ORM::forTable('employees')->create();
                    }
                    $empRecord->set($parsedEmpData)->save();
                }
                ORM::getDB()->commit();
            } else {
                $loger->log("End of data.");
                break;
            }
            $startPos += $maxResults;
        }
        $endAt = time();
        $loger->log("= Sync employee done, taken: " . ($endAt - $startedAt) . " secs\n");
    }

    public function parseEmployee($data) {
        $primary_address
            = $primary_city
            = $primary_state
            = $primary_zip_code
            = $primary_country
            = null;
        $primaryAddress = $data->PrimaryAddr;
        if (null != $primaryAddress) {
            $primary_address = $primaryAddress->Line1;
            $primary_city = $primaryAddress->City;
            $primary_state = $primaryAddress->CountrySubDivisionCode;
            $primary_zip_code = $primaryAddress->PostalCode;
            $primary_country = $primaryAddress->Country;
        }
        $primary_phone_number = $email = null;
        if (null != $data->PrimaryPhone) {
            $primary_phone_number = $data->PrimaryPhone->FreeFormNumber;
        }
        if (null != $data->PrimaryEmailAddr) {
            $email = $data->PrimaryEmailAddr->Address;
        }

        $last_updated_at = date("Y-m-d H:i:s",  strtotime($data->MetaData->LastUpdatedTime));
        $created_at = date("Y-m-d H:i:s", strtotime($data->MetaData->CreateTime));
        $active  = $data->Active == 'true';
        return [
            'id'                    => $data->Id,
            'sync_token'            => $data->SyncToken,
            'primary_address'       => $primary_address,
            'primary_city'          => $primary_city,
            'primary_state'         => $primary_state,
            'primary_zip_code'      => $primary_zip_code,
            'primary_country'       => $primary_country,
            'given_name'            => $data->GivenName,
            'middle_name'           => $data->MiddleName,
            'family_name'           => $data->FamilyName,
            'suffix'                => $data->Suffix,
            'display_name'          => $data->DisplayName,
            'print_name'            => $data->PrintOnCheckName,
            'email'                 => $email,
            'primary_phone_number'  => $primary_phone_number,
            'ssn'                   => $data->SSN,
            'active'                => $active,
            'created_at'            => $created_at,
            'last_updated_at'       => $last_updated_at
        ];
    }

    public function syncEstimate($lastSyncedTime = null) {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync estimate started");
        $maxResults = 1000;
        $startPos = 1;
        $localEstimates = ORM::forTable('estimates')->findMany();
        $localLines = ORM::forTable('estimate_lines')->findMany();
        $loger->log("Local count: " . count($localEstimates));
        $updateCount = $createCount = 0;
        while (true) {
            $query = "SELECT * FROM Estimate";
            if ($lastSyncedTime) {
                $query .= " WHERE MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            }
            $query .= " startPosition $startPos maxResults $maxResults";
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $estimateObj) {
                    $estRecord = ORM::forTable('estimates')->create();
                    $localEstimateData = null;
                    $createCount++;
                    // Estimate data
                    foreach ($localEstimates as $index => $estimate) {
                        if ($estimateObj->Id == $estimate->id) {
                            if ($estimateObj->SyncToken != $estimate->sync_token) {
                                $localEstimateData = $estimate->asArray();
                                $estRecord = ORM::forTable('estimates')->hydrate();
                                $updateCount++;
                            } else {
                                $estRecord = null;
                            }
                            unset($localEstimates[$index]);
                            $createCount--;
                            break;
                        }
                    }
                    if ($estRecord) {
                        $parsedEstimateData = $this->parseEstimate($estimateObj, $localEstimateData);
                        $estRecord->set($parsedEstimateData)->save();

                        // Sync lines
                        $data_line_sync = [];
                        $estimateLineModel = new EstimateLineModel;
                        $localEstLines = $remoteLines = [];
                        foreach ($localLines as $index => $localLine) {
                            if ($localLine->estimate_id == $estimateObj->Id) {
                                $localEstLines[] = $localLine;
                                unset($localLines[$index]);
                            }
                        }
                        foreach ($estimateObj->Line as $lineObj) {
                            $parsedLine = $this->parseEstimateLine($lineObj, $estimateObj->Id);
                            if ($parsedLine) {
                                $exists = false;
                                foreach ($localEstLines as $index => $localLine) {
                                    if ($localLine->line_id == $parsedLine['line_id']) {
                                        $localLine->set($parsedLine);
                                        $localLine->save();
                                        $exists = true;
                                        unset($localEstLines[$index]);
                                    }
                                }
                                if (!$exists) {
                                    $lineRecord = ORM::forTable('estimate_lines')->create();
                                    $lineRecord->set($parsedLine);
                                    $lineRecord->save();
                                }
                            }
                        }
                        // Delete lines
                        foreach ($localEstLines as $line) {
                            $line->delete();
                        }
                    }
                }
                ORM::getDB()->commit();
            } else {
                $loger->log("End of data.");
                break;
            }
            $startPos += $maxResults;
        }
        $loger->log("Update: $updateCount");
        $loger->log("Create: $createCount");
        // $loger->log("Delete: " . count($localEstimates));
        // Delete removed estiamtes form local DB
        // ORM::getDB()->beginTransaction();
        // foreach ($localEstimates as $estimate) {
        //     ORM::forTable('estimate_lines')
        //         ->where('estimate_id', $estimate->id)
        //         ->findResultSet()
        //         ->delete();
        //     ORM::forTable('estimate_attachments')
        //         ->where('estimate_id', $estimate->id)
        //         ->findResultSet()
        //         ->delete();
        //     $estimate->delete();
        // }
        // ORM::getDB()->commit();
        $endAt = time();
        $loger->log("= Sync estimate done, taken: " . ($endAt - $startedAt) . " secs\n");
    }

    public function parseEstimate($data, $data_local = null) {
        $txn_date = $expiration_date = null;
        if ($data->TxnDate) {
            $txn_date = $data->TxnDate;
        }
        $expiration_date = $data_local['expiration_date'];
        if ($data->ExpirationDate) {
            $expiration_date = date("Y-m-d H:i:s",  strtotime($data->ExpirationDate));
        }
        $billAddress = $data->BillAddr;
        $bill_address_id
            = $bill_address
            = $bill_city
            = $bill_state
            = $bill_zip_code
            = $bill_country
            = null;
        if (null != $billAddress) {
            $bill_address_id = $billAddress->Id;
            $bill_address = $billAddress->Line1;
            $bill_city = $billAddress->City;
            $bill_state = $billAddress->CountrySubDivisionCode;
            $bill_zip_code = $billAddress->PostalCode;
            $bill_country = $billAddress->Country;
        }
        $email = null;
        if (null != ($data->BillEmail)) {
            $email = $data->BillEmail->Address;
        }
        $estimate_footer = $data->CustomerMemo;
        $last_updated_at = date("Y-m-d H:i:s",  strtotime($data->MetaData->LastUpdatedTime));
        $created_at = date("Y-m-d H:i:s", strtotime($data->MetaData->CreateTime));

        if (($data_local != null) && ($data->TxnStatus == 'Accepted') && ($data_local['status'] == 'Completed')) {
            $status = 'Completed';
        } else {
            $status = $data->TxnStatus;
        }

        return [
            'id'                    => $data->Id,
            'customer_id'           => $data->CustomerRef,
            'sync_token'            => $data->SyncToken,
            'doc_number'            => $data->DocNumber,
            'estimate_footer'       => $estimate_footer,
            'txn_date'              => $txn_date,
            'expiration_date'       => $expiration_date,
            'email'                 => $email,
            'bill_address_id'       => $bill_address_id,
            'bill_address'          => $bill_address,
            'bill_city'             => $bill_city,
            'bill_state'            => $bill_state,
            'bill_zip_code'         => $bill_zip_code,
            'bill_country'          => $bill_country,
            'status'                => $status,
            'created_at'            => $created_at,
            'last_updated_at'       => $last_updated_at,
            'total'                 => $data->TotalAmt,
            'primary_phone_number'  => $data_local['primary_phone_number'],
            'alternate_phone_number'=> $data_local['alternate_phone_number'],
            'due_date'              => $data_local['due_date'],
            'estimate_route_id'     => $data_local['estimate_route_id'],
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
    /**
     * Sync all employees
     * @param $lastSyncedTime the time in ISO 8601 format
     */
    public function syncProductService($lastSyncedTime = null) {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync product/services started");
        $maxResults = 1000;
        $startPos = 1;
        $localPDs = ORM::forTable('products_and_services')->select('id')->findArray();
        $localPDIds = [];
        foreach ($localPDs as $item) { $localPDIds[] = $item['id']; }
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = "SELECT * FROM Item WHERE Active IN (true, false)"
                    . " AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = "SELECT * FROM Item WHERE Active IN (true, false)";
            }
            $query .= " startPosition $startPos maxResults $maxResults";
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $PDObj) {
                    $parsedPDData = $this->parseProductService($PDObj);
                    if (array_search($parsedPDData['id'], $localPDIds) !== false) {
                        // The employee is already exists in local DB
                        $PDRecord = ORM::forTable('products_and_services')->hydrate();
                    } else {
                        $PDRecord = ORM::forTable('products_and_services')->create();
                    }
                    $PDRecord->set($parsedPDData)->save();
                }
                ORM::getDB()->commit();
            } else {
                $loger->log("End of data.");
                break;
            }
            $startPos += $maxResults;
        }
        $endAt = time();
        $loger->log("= Sync product/services done, taken: " . ($endAt - $startedAt) . " secs\n");
    }

    public function syncAttachment($lastSyncedTime = null) {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync attachments started");
        $maxResults = 1000;
        $startPos = 1;
        $localAts = ORM::forTable('estimate_attachments')->select('id')->findArray();
        $localAtIds = [];
        foreach ($localAts as $at) { $localAtIds[] = $at['id']; }
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = "SELECT * FROM Attachable WHERE ContentType LIKE '%image%'"
                    . " AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = "SELECT * FROM Attachable WHERE ContentType LIKE '%image%'";
            }
            $query .= " startPosition $startPos maxResults $maxResults";
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $attachableObj) {
                    $parsedAtData = $this->parseAttachment($attachableObj);
                    if ($parsedAtData) {
                        if (array_search($parsedAtData['id'], $localAtIds) !== false) {
                            // The employee is already exists in local DB
                            $atRecord = ORM::forTable('estimate_attachments')->hydrate();
                        } else {
                            $atRecord = ORM::forTable('estimate_attachments')->create();
                        }
                        $atRecord->set($parsedAtData)->save();
                    }
                }
                ORM::getDB()->commit();
            } else {
                $loger->log("End of data.");
                break;
            }
            $startPos += $maxResults;
        }
        $endAt = time();
        $loger->log("= Sync attachments done, taken: " . ($endAt - $startedAt) . " secs\n");
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
        Ex:
        $entity = array(
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
                } else {
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
        // Parse billing address
        $billAddress = $data->BillAddr;
        $bill_address_id
            = $bill_address
            = $bill_city
            = $bill_state
            = $bill_zip_code
            = $bill_country
            = null;
        if (null != $billAddress) {
            $bill_address_id = $billAddress->Id;
            $bill_address = $billAddress->Line1;
            $bill_city = $billAddress->City;
            $bill_state = $billAddress->CountrySubDivisionCode;
            $bill_zip_code = $billAddress->PostalCode;
            $bill_country = $billAddress->Country;
        }
        // Parse shipping address
        $shipAddr = $data->ShipAddr;
        $shipAddressId
            = $shipAddress
            = $shipCity
            = $shipState
            = $shipZipCode
            = $shipCountry
            = null;
        if (null != $shipAddr) {
            $shipAddressId = $shipAddr->Id;
            $shipAddress = $shipAddr->Line1;
            $shipCity = $shipAddr->City;
            $shipState = $shipAddr->CountrySubDivisionCode;
            $shipZipCode = $shipAddr->PostalCode;
            $shipCountry = $shipAddr->Country;
        }

        $primary_phone_number
            = $mobile_phone_number
            = $alternate_phone_number
            = $fax
            = $email
            = $parentId
            = null;
        if (null != $data->PrimaryPhone) {
            $primary_phone_number = $data->PrimaryPhone->FreeFormNumber;
        }
        if (null != $data->Mobile) {
            $mobile_phone_number = $data->Mobile->FreeFormNumber;
        }
        if (null != $data->AlternatePhone) {
            $alternate_phone_number = $data->AlternatePhone->FreeFormNumber;
        }
        if (null != $data->Fax) {
            $fax = $data->Fax->FreeFormNumber;
        }
        if (null != $data->PrimaryEmailAddr) {
            $email = $data->PrimaryEmailAddr->Address;
        }
        if (null != $data->ParentRef) {
            $parentId = $data->ParentRef->value;
        }
        $last_updated_at = date("Y-m-d H:i:s", strtotime($data->MetaData->LastUpdatedTime));
        $created_at = date("Y-m-d H:i:s", strtotime($data->MetaData->CreateTime));
        $active = $data->Active == 'true';
        return [
            'id'                    => $data->Id,
            'sync_token'            => $data->SyncToken,
            'parent_id'             => $parentId,
            'title'                 => $data->Title,
            'given_name'            => $data->GivenName,
            'middle_name'           => $data->MiddleName,
            'family_name'           => $data->FamilyName,
            'suffix'                => $data->Suffix,
            'display_name'          => $data->DisplayName,
            'print_name'            => $data->PrintOnCheckName,
            'company_name'          => $data->CompanyName,
            'email'                 => $email,
            'primary_phone_number'  => $primary_phone_number,
            'mobile_phone_number'   => $mobile_phone_number,
            'alternate_phone_number'=> $alternate_phone_number,
            'fax'                   => $fax,

            'bill_address_id'       => $bill_address_id,
            'bill_address'          => $bill_address,
            'bill_city'             => $bill_city,
            'bill_state'            => $bill_state,
            'bill_zip_code'         => $bill_zip_code,
            'bill_country'          => $bill_country,

            'ship_address_id'       => $shipAddressId,
            'ship_address'          => $shipAddress,
            'ship_city'             => $shipCity,
            'ship_state'            => $shipState,
            'ship_zip_code'         => $shipZipCode,
            'ship_country'          => $shipCountry,

            'active'                => $active,
            'created_at'            => $created_at,
            'last_updated_at'       => $last_updated_at
        ];
    }


    public function parseEstimateLine($data, $estimateId) {
        if (null != $data->SalesItemLineDetail) {
            $qty = $rate = 0;
            if ($data->SalesItemLineDetail->Qty) {
                $qty = $data->SalesItemLineDetail->Qty;
            }
            if ($data->SalesItemLineDetail->UnitPrice) {
                $rate = $data->SalesItemLineDetail->UnitPrice;
            }
            $product_service_id = $data->SalesItemLineDetail->ItemRef;

            return [
                'line_id'               => $data->Id,
                'line_num'              => $data->LineNum,
                'estimate_id'           => $estimateId,
                'product_service_id'    => $product_service_id,
                'qty'                   => $qty,
                'rate'                  => $rate,
                'description'           => $data->Description
            ];
        } else {
            return null;
        }
    }

    public function parseAttachment($data) {
        if (null != $data->AttachableRef) {
            $estimate_id = $data->AttachableRef->EntityRef;
            $last_updated_at = date("Y-m-d H:i:s",  strtotime($data->MetaData->LastUpdatedTime));
            $created_at = date("Y-m-d H:i:s", strtotime($data->MetaData->CreateTime));
            return [
                'id'                    => $data->Id,
                'sync_token'            => $data->SyncToken,
                'estimate_id'           => $estimate_id,
                'size'                  => $data->Size,
                'content_type'          => $data->ContentType,
                'access_uri'            => $data->FileAccessUri,
                'tmp_download_uri'      => $data->TempDownloadUri,
                'file_name'             => $data->FileName,
                'created_at'            => $created_at,
                'last_updated_at'       => $last_updated_at
            ];
        } else {
            return null;
        }
    }

    public function parseProductService($data) {
        $active = $data->Active == 'true';
        $taxable = $data->Taxable == 'true';
        $last_updated_at = date("Y-m-d H:i:s", strtotime($data->MetaData->LastUpdatedTime));
        $created_at = date("Y-m-d H:i:s", strtotime($data->MetaData->CreateTime));
        return [
            'id'                    => $data->Id,
            'sync_token'            => $data->SyncToken,
            'name'                  => $data->Name,
            'description'           => $data->Description,
            'rate'                  => $data->UnitPrice,
            'active'                => $active,
            'taxable'               => $taxable,
            'created_at'            => $created_at,
            'last_updated_at'       => $last_updated_at
        ];
    }

    public function createCustomer($data) {
        $customerObj = new IPPCustomer();
        $customerObj->DisplayName = $data['display_name'];
        if (isset($data['bill_address'])) {
            $billAddr = new IPPPhysicalAddress();
            $billAddr->Line1 = $data['bill_address'];
            $billAddr->City = $data['bill_city'];
            $billAddr->Country = $data['bill_country'];
            $billAddr->CountrySubDivisionCode = $data['bill_state'];
            $billAddr->PostalCode = $data['bill_zip_code'];
            $customerObj->BillAddr = $billAddr;
        }

        if (isset($data['primary_phone_number'])) {
            $primaryPhone = new IPPTelephoneNumber();
            $primaryPhone->FreeFormNumber = $data['primary_phone_number'];
            $customerObj->PrimaryPhone = $primaryPhone;
        }
        if (isset($data['alternate_phone_number'])) {
            $alternatePhone = new IPPTelephoneNumber();
            $alternatePhone->FreeFormNumber = $data['alternate_phone_number'];
            $customerObj->AlternatePhone = $alternatePhone;
        }
        if (isset($data['email'])) {
            $primaryEmail = new IPPEmailAddress();
            $primaryEmail->Address = $data['email'];
            $customerObj->PrimaryEmailAddr = $primaryEmail;
        }
        return $this->dataService->Add($customerObj);
    }

    public function decodeCustomer($data) {
        $value = [
            'name'                                      => 'IPPCustomer',
            'attributes' => [
                'DisplayName'                           => $data['display_name'],
                'BillAddr' => [
                    'name'                                  => 'IPPPhysicalAddress',
                    'attributes' => [
                        'Line1'                             => $data['bill_address'],
                        'City'                              => $data['bill_city'],
                        'Country'                           => $data['bill_country'],
                        'CountrySubDivisionCode'            => $data['bill_state'],
                        'PostalCode'                        => $data['bill_zip_code']
                    ]
                ],
                'PrimaryPhone' => [
                    'name'  => 'IPPTelephoneNumber',
                    'attributes' => [
                        'FreeFormNumber'                    => $data['primary_phone_number']
                    ]
                ],
                'PrimaryEmailAddr' => [
                    'name' => 'IPPEmailAddress',
                    'attributes' => [
                        'Address'                           => $data['email']
                    ]
                ]
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
        } else {
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

    public function Add($entity) {
        return $this->dataService->Add($entity);
    }

    public function upload($fileContent, $fileName, $mimeType, $objAttachable) {
        return $this->dataService->Upload($fileContent, $fileName, $mimeType, $objAttachable);
    }
}
?>
