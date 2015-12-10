<?php

class Asynchronzier
{
    const QB_QUERY_SIZE = 1000;
    public $access_token;
    public $access_token_secret;
    public $consumer_key;
    public $consumer_secret;
    public $realmId;

    public $serviceType = IntuitServicesType::QBO;

    public $requestValidator;
    public $serviceContext;
    public $dataService;

    static $instance;
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
    public function __construct($data)
    {
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
        if (!$this->serviceContext) {
            exit("Problem while initializing ServiceContext.\n");
        }

        $this->dataService = new DataService($this->serviceContext);
        if (!$this->dataService) {
            exit("Problem while initializing ServiceContext.\n");
        }
    }

    public static function getInstance() {
        if (!$instance) {
            self::$instance = new self(PreferenceModel::getQuickbooksCreds());
        }
        return self::$instance;
    }
    /**
     * Sync all customers.
     *
     * @param $lastSyncedTime the time in ISO 8601 format
     */
    public function syncCustomer($lastSyncedTime = null)
    {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log('== Sync customer started');
        $startPos = 1;
        $localCus = ORM::forTable('customers')
            ->selectMany('id', 'sync_token')
            ->findMany();
        $loger->log('Local count: ' . count($localCus));
        $updateCount = $createCount = 0;
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = 'SELECT * FROM Customer WHERE Active IN (true, false)'
                    ." AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = 'SELECT * FROM Customer WHERE Active IN (true, false)';
            }
            $query .= " startPosition $startPos maxResults " . self::QB_QUERY_SIZE;
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records.");
                ORM::getDB()->beginTransaction();
                foreach ($res as $cusObj) {
                    $cusRecord = null;
                    $exists = false;
                    foreach ($localCus as $index => $cus) {
                        // The customer is already exists in local DB
                        if ($cusObj->Id == $cus->id) {
                            $exists = true;
                            if ($cusObj->SyncToken !== $cus->sync_token) {
                                ++$updateCount;
                                $cusRecord = ORM::forTable('customers')->hydrate();
                            }
                            unset($localCus[$index]); break;
                        }
                    }
                    if (!$exists) {
                        ++$createCount;
                        $cusRecord = ORM::forTable('customers')->create();
                    }
                    if (null != $cusRecord) {
                        $parsedCus = ERPDataParser::parseCustomer($cusObj);
                        $cusRecord->set($parsedCus)->save();
                    }
                }
                ORM::getDB()->commit();
            } else {
                $loger->log('End of data.');
                break;
            }
            $startPos += self::QB_QUERY_SIZE;
        }
        $loger->log("Update: $updateCount");
        $loger->log("Create: $createCount");
        if ($updateCount > 0 || $createCount > 0) {
            ERPCacheManager::clear('customers');
        }
        $loger->log('== Sync customer done, taken: ' . (time() - $startedAt)." secs\n");
    }

    /**
     * Sync all employees.
     *
     * @param $lastSyncedTime the time in ISO 8601 format
     */
    public function syncEmployee($lastSyncedTime = null)
    {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync employee started");
        $startPos = 1;
        $localEmps = ORM::forTable('employees')
            ->selectMany('id', 'sync_token')
            ->findMany();
        $loger->log('Local count: ' . count($localEmps));
        $updateCount = $createCount = 0;
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = 'SELECT * FROM Employee WHERE Active IN (true, false)'
                    ." AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = 'SELECT * FROM Employee WHERE Active IN (true, false)';
            }
            $query .= " startPosition $startPos maxResults " . self::QB_QUERY_SIZE;
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $empObj) {
                    $empRecord = null;
                    $exists = false;
                    foreach ($localEmps as $index => $emp) {
                        // The employee is already exists in local DB
                        if ($empObj->Id == $emp->id) {
                            $exists = true;
                            if ($empObj->SyncToken !== $emp->sync_token) {
                                ++$updateCount;
                                $empRecord = ORM::forTable('employees')->hydrate();
                            }
                            unset($localEmps[$index]); break;
                        }
                    }
                    if (!$exists) {
                        ++$createCount;
                        $empRecord = ORM::forTable('employees')->create();
                    }
                    if (null != $empRecord) {
                        $parsedEmpData = ERPDataParser::parseEmployee($empObj);
                        $empRecord->set($parsedEmpData)->save();
                    }
                }
                ORM::getDB()->commit();
            } else {
                $loger->log('End of data.');
                break;
            }
            $startPos += self::QB_QUERY_SIZE;
        }
        $endAt = time();
        $loger->log("Update: $updateCount");
        $loger->log("Create: $createCount");
        $loger->log('= Sync employee done, taken: '.($endAt - $startedAt)." secs\n");
    }

    public function syncEstimate($lastSyncedTime = null)
    {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync estimate started");
        $startPos = 1;
        $localEstimates = ORM::forTable('estimates')
            ->selectMany('id', 'sync_token')
            ->findMany();
        $localLines = ORM::forTable('estimate_lines')
            ->selectMany('id', 'estimate_id', 'line_id')
            ->findMany();
        $loger->log('Local count: ' . count($localEstimates));
        $updateCount = $createCount = 0;
        while (true) {
            $query = 'SELECT * FROM Estimate';
            if ($lastSyncedTime) {
                $query .= " WHERE MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            }
            $query .= " startPosition $startPos maxResults " . self::QB_QUERY_SIZE;
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $estimateObj) {
                    if ($estimateObj->Id == '29791') { continue; }
                    $estRecord = ORM::forTable('estimates')->create();
                    $localEstimateData = null;
                    ++$createCount;
                    // Estimate data
                    foreach ($localEstimates as $index => $estimate) {
                        if ($estimateObj->Id == $estimate->id) {
                            if ($estimateObj->SyncToken != $estimate->sync_token) {
                                $localEstimateData = ORM::forTable('estimates')
                                    ->findOne($estimate->id)
                                    ->asArray();
                                $estRecord = ORM::forTable('estimates')->hydrate();
                                ++$updateCount;
                            } else {
                                $estRecord = null;
                            }
                            unset($localEstimates[$index]);
                            --$createCount;
                            break;
                        }
                    }
                    if ($estRecord) {
                        $parsedEstimateData = ERPDataParser::parseEstimate($estimateObj, $localEstimateData);
                        $estRecord->set($parsedEstimateData)->save();

                        // Sync lines
                        $estimateLineModel = new EstimateLineModel();
                        $localEstLines = [];
                        foreach ($localLines as $index => $localLine) {
                            if ($localLine->estimate_id == $estimateObj->Id) {
                                $localEstLines[] = $localLine;
                                unset($localLines[$index]);
                            }
                        }
                        foreach ($estimateObj->Line as $lineObj) {
                            $parsedLine = ERPDataParser::parseEstimateLine($lineObj, $estimateObj->Id);
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
                $loger->log('End of data.');
                break;
            }
            $startPos += self::QB_QUERY_SIZE;
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
        $loger->log('= Sync estimate done, taken: '.($endAt - $startedAt)." secs\n");
    }


    /**
     * Sync all employees.
     *
     * @param $lastSyncedTime the time in ISO 8601 format
     */
    public function syncProductService($lastSyncedTime = null)
    {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync product/services started");
        $startPos = 1;
        $localPDs = ORM::forTable('products_and_services')
            ->select('id')
            ->findArray();
        $localPDIds = [];
        foreach ($localPDs as $item) {
            $localPDIds[] = $item['id'];
        }
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = 'SELECT * FROM Item WHERE Active IN (true, false)'
                    ." AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = 'SELECT * FROM Item WHERE Active IN (true, false)';
            }
            $query .= " startPosition $startPos maxResults " . self::QB_QUERY_SIZE;
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                if ($startPos === 1) {
                    ERPCacheManager::clear('products_services');
                }
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $PDObj) {
                    $parsedPDData = ERPDataParser::parseProductService($PDObj);
                    if (array_search($parsedPDData['id'], $localPDIds) !== false) {
                        // The entry is already exists in local DB
                        $PDRecord = ORM::forTable('products_and_services')->hydrate();
                    } else {
                        $PDRecord = ORM::forTable('products_and_services')->create();
                    }
                    $PDRecord->set($parsedPDData)->save();
                }
                ORM::getDB()->commit();
            } else {
                $loger->log('End of data.');
                break;
            }
            $startPos += self::QB_QUERY_SIZE;
        }
        $endAt = time();
        $loger->log('= Sync product/services done, taken: '.($endAt - $startedAt)." secs\n");
    }

    public function syncAttachment($lastSyncedTime = null)
    {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync attachments started");
        $startPos = 1;
        $localAts = ORM::forTable('estimate_attachments')->select('id')->findArray();
        $localAtIds = [];
        foreach ($localAts as $at) {
            $localAtIds[] = $at['id'];
        }
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = "SELECT * FROM Attachable WHERE ContentType LIKE '%image%'"
                    ." AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = "SELECT * FROM Attachable WHERE ContentType LIKE '%image%'";
            }
            $query .= " startPosition $startPos maxResults " . self::QB_QUERY_SIZE;
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $attachableObj) {
                    $parsedAtData = ERPDataParser::parseAttachment($attachableObj);
                    if (array_search($parsedAtData['id'], $localAtIds) !== false) {
                        // The attachment is already exists in local DB
                        if ($parsedAtData['estimate_id']) {
                            $atRecord = ORM::forTable('estimate_attachments')->hydrate();
                            $atRecord->set($parsedAtData);
                            $atRecord->save();
                        } else { // Detach from estimate
                            $localAt = ORM::forTable('estimate_attachments')
                                ->findOne($parsedAtData['id']);
                            if ($localAt) {
                                // Check to remove customer signature from estimate
                                if ($localAt->is_customer_signature) {
                                    $est = ORM::forTable('estimates')
                                        ->findOne($localAt->estimate_id);
                                        if ($est) {
                                            $est->customer_signature = null;
                                            $est->save();
                                            $loger->log("\n= Remove customer signature from estimate #" . $est->id);
                                        }
                                }
                                $localAt->delete();
                                $loger->log("\n= Delete attachment: " . $parsedAtData['id']);
                            }
                        }
                    } else {
                        if ($parsedAtData['estimate_id']) { // Only save if it attach to estimate
                            $atRecord = ORM::forTable('estimate_attachments')->create();
                            $atRecord->set($parsedAtData)->save();
                        }
                    }
                }
                ORM::getDB()->commit();
            } else {
                $loger->log('End of data.');
                break;
            }
            $startPos += self::QB_QUERY_SIZE;
        }
        $endAt = time();
        $loger->log('= Sync attachments done, taken: '.($endAt - $startedAt)." secs\n");
    }

    public function syncClass($lastSyncedTime = null)
    {
        $loger = new ERPLogger('sync.log');
        $startedAt = time();
        $loger->log("\n= Sync classes started");
        $startPos = 1;
        $localItems = ORM::forTable('erpp_classes')->select('id')->findArray();
        $localItemIds = [];
        foreach ($localItems as $item) {
            $localItemIds[] = $item['id'];
        }
        while (true) {
            if ($lastSyncedTime) {
                $query
                    = 'SELECT * FROM Class WHERE Active IN (true, false)'
                    ." AND MetaData.LastUpdatedTime >= '$lastSyncedTime'";
            } else {
                $query = 'SELECT * FROM Class WHERE Active IN (true, false)';
            }
            $query .= " startPosition $startPos maxResults " . self::QB_QUERY_SIZE;
            $res = $this->Query($query);
            $resCount = count($res);
            if ($resCount !== 0) {
                if ($startPos === 1) {
                    ERPCacheManager::clear('classes');
                }
                $loger->log("Got $resCount records");
                ORM::getDB()->beginTransaction();
                foreach ($res as $classEntity) {
                    $parsedClassData = ERPDataParser::parseClass($classEntity);
                    if (array_search($parsedClassData['id'], $localItemIds) !== false) {
                        // The entry is already exists in local DB
                        $classRecord = ORM::forTable('erpp_classes')->hydrate();
                    } else {
                        $classRecord = ORM::forTable('erpp_classes')->create();
                    }
                    $classRecord->set($parsedClassData)->save();
                }
                ORM::getDB()->commit();
            } else {
                $loger->log('End of data.');
                break;
            }
            $startPos += self::QB_QUERY_SIZE;
        }
        $endAt = time();
        $loger->log('= Sync classes done, taken: '.($endAt - $startedAt)." secs\n");
    }

    public function mergeData($dataGet, $dataLocal)
    {
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
    public function findAll($name, $postionStart, $maxResult)
    {
        return $this->dataService->FindAll($name, $postionStart, $maxResult);
    }

    /*
        $entity is Object
        Ex: $entiry = Customer;
        Customer = {
            'IPPCustomer': [{Taxable: true},{IPPPhysicalAddress: [{id:2, }]}]
        }
    */
    public function findById($entity)
    {
        return $this->dataService->FindById($entity);
    }

    /*
        $Query is String
        Ex: "SECLECT * FROM Customer"
    */
    public function Query($query)
    {
        return $this->dataService->Query($query);
    }

    public function Create($entity)
    {
        $object = new $entity['name']();
        foreach ($entity['attributes'] as $key => $value) {
            if (!is_array($entity['attributes'][$key])) {
                $object->$key = $value;
            } else {
                if ($key == 'Line' || $key == 'CustomField') {
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
    public function Update($entity)
    {
        $object = new $entity['name']();
        foreach ($entity['attributes'] as $key => $value) {
            if (!is_array($entity['attributes'][$key])) {
                $object->$key = $value;
            } else {
                if ($key == 'Line' || $key == 'CustomField') {
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
    public function Delete($object)
    {
        return $this->dataService->Delete($object);
    }
    public function Edit($object)
    {
        return $this->dataService->Add($object);
    }

    public function createCustomer($data) {
        $customerObj = new IPPCustomer();
        if (@$data['display_name']) {
          $customerObj->DisplayName = @$data['display_name'];
        }
        $customerObj->GivenName = @$data['given_name'];
        $customerObj->FamilyName = @$data['family_name'];
        if (isset($data['bill_address'])) {
            $billAddr = new IPPPhysicalAddress();
            $billAddr->Line1                    = @$data['bill_address'];
            $billAddr->City                     = @$data['bill_city'];
            if (@$data['bill_country']) {
                $billAddr->Country              = $data['bill_country'];
            }
            $billAddr->CountrySubDivisionCode   = @$data['bill_state'];
            $billAddr->PostalCode               = @$data['bill_zip_code'];
            $customerObj->BillAddr = $billAddr;
        }
        if (isset($data['ship_address'])) {
            $shipAddr = new IPPPhysicalAddress();
            $shipAddr->Line1                    = @$data['ship_address'];
            $shipAddr->City                     = @$data['ship_city'];
            if (@$data['ship_country']) {
                $shipAddr->Country              = @$data['ship_country'];
            }
            $shipAddr->CountrySubDivisionCode   = @$data['ship_state'];
            $shipAddr->PostalCode               = @$data['ship_zip_code'];

            $customerObj->ShipAddr = $shipAddr;
            // Set billing same with shipping if billing address is not set
            if (!isset($data['bill_address'])) {
                $customerObj->BillAddr = $shipAddr;
            }
        }

        if (isset($data['primary_phone_number'])) {
            $primaryPhone = new IPPTelephoneNumber();
            $primaryPhone->FreeFormNumber = $data['primary_phone_number'];
            $customerObj->PrimaryPhone = $primaryPhone;
        }
        if (isset($data['mobile_phone_number'])) {
            $mobilePhone = new IPPTelephoneNumber();
            $mobilePhone->FreeFormNumber = $data['mobile_phone_number'];
            $customerObj->Mobile = $mobilePhone;
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
        if (isset($data['parent_id'])) {
            $customerObj->Job = 'true';
            $customerObj->ParentRef = $data['parent_id'];
        }
        return $this->dataService->Add($customerObj);
    }

    public function buildEstimateBillAddress($localData) {
        $billAddr = new IPPPhysicalAddress;
        $customer = ORM::forTable('customers')->findOne($localData['customer_id']);
        $isSame = true;
        $addressAttrs = ['bill_address', 'bill_city', 'bill_state', 'bill_zip_code', 'bill_country'];
        foreach ($addressAttrs as $attr) {
            $isSame &= $customer[$attr] == $localData[$attr];
        }
        if ($isSame) {
            $billAddr->Id = $customer->bill_address_id;
        } else {
            if ($localData['bill_address_id']) {
                $billAddr->Id = $localData['bill_address_id'];
            }
            $billAddr->Line1 = $customer->display_name;
            $billAddr->Line2 = $localData['bill_address'];
            $billAddr->City = $localData['bill_city'];
            $billAddr->CountrySubDivisionCode = $localData['bill_state'];
            $billAddr->PostalCode = $localData['bill_zip_code'];
            $billAddr->Country = $localData['bill_country'];
        }
        return $billAddr;
    }


    public function buildEstimateShipAddress($localData) {
        $shipAddr = new IPPPhysicalAddress;
        $jobCustomer = ORM::forTable('customers')->findOne($localData['job_customer_id']);

        $isSame = true;
        $isSame &= $localData['job_customer_id'] == $localData['customer_id'];
        $addressAttrsMap = [ // from estimate to customer
            'job_address' => 'ship_address',
            'job_city'  => 'ship_city',
            'job_state' => 'ship_state',
            'job_zip_code' => 'ship_zip_code',
            'job_country' => 'ship_country'
        ];
        foreach ($addressAttrsMap as $eAttr => $cAttr) {
            $isSame &= $jobCustomer[$cAttr] == $localData[$eAttr];
        }
        if ($isSame) { // use customer ship address is
            $shipAddr->Id = $jobCustomer->ship_address_id;
        } else {
            if ($localData['job_address_id']) {
                $shipAddr->Id = $localData['job_address_id'];
            }
            $shipAddr->Line1 = $jobCustomer->display_name;
            $shipAddr->Line2 = $localData['job_address'];
            $shipAddr->City = $localData['job_city'];
            $shipAddr->CountrySubDivisionCode = $localData['job_state'];
            $shipAddr->PostalCode = $localData['job_zip_code'];
            $shipAddr->Country = $localData['job_country'];
        }
        return $shipAddr;
    }

    public function buildEstimateLines(array $lines) {
        $lineEntities = [];
        foreach ($lines as $index => $line) {
            $lineObj = new IPPLine;
            $lineObj->Id = $line['line_id'];
            $lineObj->LineNum = $index;
            $lineObj->Description = $line['description'];
            if ($line['product_service_id']) {
                $lineObj->DetailType = 'SalesItemLineDetail';
                $lineObj->Amount = (float) $line['qty'] * (float) $line['rate'];
                $lineObj->SalesItemLineDetail = new IPPSalesItemLineDetail;
                $lineObj->SalesItemLineDetail->ItemRef = $line['product_service_id'];
                $lineObj->SalesItemLineDetail->Qty = $line['qty'];
                $lineObj->SalesItemLineDetail->UnitPrice = $line['rate'];
            } else {
                // Consider lines without product service are DescriptionOnly
                $lineObj->DetailType = 'DescriptionOnly';
            }
            $lineEntities[] = $lineObj;
        }
        return $lineEntities;
    }

    public function saveEstimate(IPPEstimate $estimateEntity) {
        return $this->dataService->Add($estimateEntity);
    }

    public function buildEstimateEntity($localData) {
        $estimateObj = new IPPEstimate;
        $estimateObj->CustomerRef = $localData['customer_id'];
        $estimateObj->ClassRef = $localData['class_id'];
        $estimateObj->TxnDate = $localData['txn_date'];
        $estimateObj->ExpirationDate = $localData['expiration_date'];
        $estimateObj->CustomerMemo = $localData['estimate_footer'];
        $estimateObj->CustomField = [];

        $salesRep1 = new IPPCustomField;
        $salesRep1->DefinitionId = '2';
        $salesRep1->Type = 'StringType';
        $salesRep1->Name = 'Sales Rep';
        $salesRep1->StringValue = @$localData['sold_by_1'] . '';

        $salesRep2 = new IPPCustomField;
        $salesRep2->DefinitionId = '3';
        $salesRep2->Type = 'StringType';
        $salesRep2->Name = 'Sales Rep';
        $salesRep2->StringValue = @$localData['sold_by_2'] . '';

        $estimateObj->CustomField[] = $salesRep1;
        $estimateObj->CustomField[] = $salesRep2;

        $estimateObj->BillAddr = $this->buildEstimateBillAddress($localData);
        $estimateObj->ShipAddr = $this->buildEstimateShipAddress($localData);

        $billEmail = new IPPEmailAddress;
        $billEmail->Address = $localData['email'];
        $estimateObj->BillEmail = $billEmail;

        $estimateObj->Line = $this->buildEstimateLines($localData['lines']);

        if (isset($localData['id'])) {
            $estimateObj->Id = $localData['id'];
        }
        if ($localData['status'] === 'Completed' || $localData['status'] === 'Routed') {
            $estimateObj->TxnStatus = 'Accepted';
        } else {
            $estimateObj->TxnStatus = $localData['status'];
        }
        if ($localData['sync_token']) {
            $estimateObj->SyncToken = $localData['sync_token'];
        }
        return $estimateObj;
    }
    /**
     * Create Entity object by given raw array of attributes
     */
    public function decodeEstimate($localData)
    {
        $value = [
            'name' => 'IPPEstimate',
            'attributes' => [
                'CustomerRef'   => $localData['customer_id'],
                'ClassRef'      => $localData['class_id'],
                'SyncToken'     => $localData['sync_token'],
                'DocNumber'     => $localData['doc_number'],
                'TxnDate'       => $localData['txn_date'],
                'ExpirationDate'=> $localData['expiration_date'],
                'CustomerMemo'  => $localData['estimate_footer'],
                'CustomField'   => [
                    // Sold by 1
                    [
                        'name' => 'IPPCustomField',
                        'attributes' => [
                            'DefinitionId' => '2',
                            'Type' => 'StringType',
                            'Name' => 'Sales Rep',
                            'StringValue' => @$localData['sold_by_1'] . ''
                        ]
                    ],
                    // Sold by 2
                    [
                        'name' => 'IPPCustomField',
                        'attributes' => [
                            'DefinitionId' => '3',
                            'Type' => 'StringType',
                            'Name' => 'Sales Rep',
                            'StringValue' => @$localData['sold_by_2'] . ''
                        ]
                    ]
                ],
                'BillAddr' => [
                    [
                        'name' => 'IPPPhysicalAddress',
                        'attributes' => [
                            'Id' => $localData['bill_address_id'],
                            'Line1' => $localData['bill_address'],
                            'City' => $localData['bill_city'],
                            'CountrySubDivisionCode' => $localData['bill_state'],
                            'PostalCode' => $localData['bill_zip_code']
                        ],
                    ],
                ],
                'ShipAddr' => [
                    [
                        'name' => 'IPPPhysicalAddress',
                        'attributes' => [
                            'Id' => $localData['job_address_id'],
                            'Line1' => $localData['job_address'],
                            'City' => $localData['job_city'],
                            'CountrySubDivisionCode' => $localData['job_state'],
                            'PostalCode' => $localData['job_zip_code']
                        ],
                    ],
                ],
                'BillEmail' => [
                    [
                        'name' => 'IPPEmailAddress',
                        'attributes' => [
                            'Address' => $localData['email'],
                        ],
                    ],
                ],
            ],
        ];

        $value['attributes']['Line'] = [];
        foreach ($localData['lines'] as $index => $line) {
            $value_line = [
                'name' => 'IPPLine',
                'attributes' => [
                    'Id' => $line['line_id'],
                    'LineNum' => $index,
                    'Description' => $line['description']
                ],
            ];
            if ($line['product_service_id']) {
                $value_line['attributes']['DetailType'] = 'SalesItemLineDetail';
                $value_line['attributes']['Amount'] = (float) $line['qty'] * (float) $line['rate'];
                $value_line['attributes']['SalesItemLineDetail'] = [
                    [
                        'name' => 'IPPSalesItemLineDetail',
                        'attributes' => [
                            'ItemRef' => $line['product_service_id'],
                            'Qty' => $line['qty'],
                            'UnitPrice' => $line['rate'],
                        ],
                    ]
                ];
            } else { // Consider lines without product service are DescriptionOnly
                $value_line['attributes']['DetailType'] = 'DescriptionOnly';
            }
            array_push($value['attributes']['Line'], $value_line);
        }
        if (isset($localData['id'])) {
            $value['attributes']['Id'] = $localData['id'];
        }
        if ($localData['status'] == 'Completed' || $localData['status'] == 'Routed') {
            $value['attributes']['TxnStatus'] = 'Accepted';
        } else {
            $value['attributes']['TxnStatus'] = $localData['status'];
        }
        return $value;
    }

    /**
     * Returns an entity under the specified realm. The realm must be set in the context.
     *
     * @param object $entity Entity to Find
     *
     * @return IPPIntuitEntity Returns an entity of specified Id.
     */
    public function Retrieve($entity)
    {
        return $this->dataService->Retrieve($entity);
    }

    public function Add($entity)
    {
        return $this->dataService->Add($entity);
    }

    public function upload($fileContent, $fileName, $mimeType, $objAttachable)
    {
        return $this->dataService->Upload($fileContent, $fileName, $mimeType, $objAttachable);
    }
}
