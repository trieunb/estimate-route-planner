<?php
class EstimateController extends BaseController {

    public function index() {
        $page = $this->getPageParam();
        $keyword = $this->getKeywordParam();
        $filteredStatus = "";

        if (isset($_REQUEST['status'])) {
            $filteredStatus = $_REQUEST['status'];
        }

        $estimates = ORM::forTable('estimates')
            ->tableAlias('e')
            ->join('customers', ['e.customer_id', '=', 'c.id'], 'c')
            ->join('customers', ['e.job_customer_id', '=', 'jc.id'], 'jc')
            ->selectMany(
                'e.id', 'e.txn_date', 'e.doc_number',
                'e.source', 'e.due_date', 'e.total',
                'e.status', 'e.email'
            )
            ->select('c.display_name', 'customer_display_name')
            ->select('jc.display_name', 'job_customer_display_name')
            ->whereAnyIs([
                ['c.display_name' => "%$keyword%"],
                ['jc.display_name' => "%$keyword%"]], 'LIKE'
            )
            ->whereLike('e.status', "%$filteredStatus%")
            ->orderByDesc('e.id')
            ->limit(self::PAGE_SIZE)
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->findArray();
        $counter = ORM::forTable('estimates')
            ->tableAlias('e')
            ->join('customers', ['e.customer_id', '=', 'c.id'], 'c')
            ->join('customers', ['e.job_customer_id', '=', 'jc.id'], 'jc')
            ->whereAnyIs([
                ['c.display_name' => "%$keyword%"],
                ['jc.display_name' => "%$keyword%"]], 'LIKE'
            )
            ->whereLike('e.status', "%$filteredStatus%")
            ->selectExpr('COUNT(*)', 'count')
            ->findMany();
        $this->renderJson([
            'total' => $counter[0]->count,
            'data'  => $estimates
        ]);
    }

    /**
     * Get all estimates which non-assigned to any routes
     */
    public function unassigned() {
        $estimates = ORM::forTable('estimates')
            ->tableAlias('e')
            ->join('customers', ['e.job_customer_id', '=', 'c.id'], 'c')
            ->selectMany(
                'e.id', 'e.due_date', 'e.job_address', 'e.job_city',
                'e.job_country', 'e.job_state', 'e.job_zip_code',
                'e.total', 'e.job_lat', 'e.job_lng', 'e.status'
            )
            ->select('c.display_name', 'job_customer_display_name')
            ->orderByDesc('e.id')
            ->whereNull('e.estimate_route_id')
            ->whereIn('e.status', ['Pending', 'Accepted'])
            ->findArray();
        $this->renderJson($estimates);
    }

    /**
     * Get all estimates (includes lines data) which assigned to given route
     */
    public function assigned() {
        $routeId = $this->data['id'];
        $estimates = ORM::forTable('estimates')
            ->tableAlias('e')
            ->join('customers', ['e.job_customer_id', '=', 'c.id'], 'c')
            ->selectMany(
                'e.id', 'e.due_date', 'e.job_address', 'e.job_city',
                'e.job_country', 'e.job_state', 'e.job_zip_code',
                'e.location_notes', 'e.doc_number',
                'e.total', 'e.job_lat', 'e.job_lng', 'e.status'
            )
            ->select('c.display_name', 'job_customer_display_name')
            ->orderByDesc('e.id')
            ->where('e.estimate_route_id', $routeId)
            ->findArray();
        # Get lines
        $estimateIds = [];
        foreach ($estimates as $est) {
            $estimateIds[] = $est['id'];
        }
        $lines = ORM::forTable('estimate_lines')
            ->tableAlias('el')
            ->join(
                'products_and_services',
                ['el.product_service_id', '=', 'ps.id'],
                'ps'
            )
            ->whereIn('estimate_id', $estimateIds)
            ->select('el.*')
            ->select('ps.name', 'product_service_name')
            ->orderByAsc('el.line_num')
            ->findArray();
        foreach ($estimates as &$est) {
            $est['lines'] = [];
            foreach ($lines as $line) {
                if ($line['estimate_id'] === $est['id']) {
                    $est['lines'][] = $line;
                }
            }
        }
        $this->renderJson($estimates);
    }

    public function getdata() {
        return PreferenceModel::getQuickbooksCreds();
    }

    private function collectCustomerInfo() {
        $customerInfo = [];
        $customerInfo['display_name']   = trim($this->data['customer_display_name']);
        $customerInfo['bill_address']   = @$this->data['bill_address'];
        $customerInfo['bill_city']      = @$this->data['bill_city'];
        $customerInfo['bill_country']   = @$this->data['bill_country'];
        $customerInfo['bill_state']     = @$this->data['bill_state'];
        $customerInfo['bill_zip_code']  = @$this->data['bill_zip_code'];
        $customerInfo['primary_phone_number']    = @$this->data['primary_phone_number'];
        $customerInfo['alternate_phone_number']  = @$this->data['alternate_phone_number'];
        $customerInfo['email']  = @$this->data['email'];
        return $customerInfo;
    }

    private function collectJobCustomerInfo() {
        $customerInfo = [];
        $customerInfo['display_name']   = trim(@$this->data['job_customer_display_name']);
        $customerInfo['ship_address']   = @$this->data['job_address'];
        $customerInfo['ship_city']      = @$this->data['job_city'];
        $customerInfo['ship_country']   = @$this->data['job_country'];
        $customerInfo['ship_state']     = @$this->data['job_state'];
        $customerInfo['ship_zip_code']  = @$this->data['job_zip_code'];
        return $customerInfo;
    }

    /**
     * Create new customer, push to QB and return the local record
     */
    private function _createCustomer($attrs) {
        $sync = Asynchronzier::getInstance();
        $qbcustomerObj = $sync->createCustomer($attrs);
        $customerRecord = ORM::forTable('customers')->create();
        $customerRecord->set($sync->parseCustomer($qbcustomerObj));
        $customerRecord->save();
        return $customerRecord;
    }

    public function add() {
        $estimateModel = new EstimateModel();
        $estimateLineModel = new EstimateLineModel();
        $sync = Asynchronzier::getInstance();
        $newCustomerData = $this->_checkForCreateNewCustomers();
        $insertData = array_merge($this->data, $newCustomerData);

        if (isset($insertData['customer_signature_encoded'])) {
            $dataPieces = explode(",", $insertData['customer_signature_encoded']);
            $encodedImage = $dataPieces[1];
            $decodedImage = base64_decode($encodedImage);
            $signatureFileName = 'signature-' . time() . '.png';
            file_put_contents(ERP_UPLOADS_DIR . '/' . $signatureFileName, $decodedImage);
            $insertData['customer_signature'] = 'uploads/' . $signatureFileName;
        }
        if (!@$insertData['date_of_signature']) {
            $insertData['date_of_signature'] = NULL;
        }
        if (!@$insertData['accepted_date']) {
            $insertData['accepted_date'] = NULL;
        }
        if (!@$insertData['expiration_date']) {
            $insertData['expiration_date'] = NULL;
        }

        $params = $sync->decodeEstimate($insertData);
        $result = $sync->Create($params);
        $data_result = $sync->parseEstimate($result, $insertData);
        foreach ($result->Line as $line) {
            $result_line = $sync->parseEstimateLine($line, $data_result['id']);
            if (($result_line['line_id'] != null) && ($result_line['estimate_id'] != null)) {
                $estimate_line = $estimateLineModel->findBy([
                    'line_id' => $result_line['line_id'],
                    'estimate_id' => $result_line['estimate_id']
                ]);
                if ($estimate_line == null) {
                    $estimateLineModel->insert($result_line);
                }
            }
        }

        if ($estimateModel->insert($data_result)) {
            $this->renderJson([
                'success' => true,
                'message' => 'Estimate saved successfully',
                'data'    => $data_result
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'Error while saving estimate'
            ]);
        }
    }

    public function show() {
        // TODO: add check for Sales Rep permission
        $id = $this->data['id'];
        $estimate = ORM::forTable('estimates')->findOne($id);
        $estimate = $estimate->asArray();
        $estimate['lines'] = ORM::forTable('estimate_lines')
            ->where('estimate_id', $estimate['id'])
            ->findArray();
        $estimate['attachments'] = ORM::forTable('estimate_attachments')
            ->where('estimate_id', $estimate['id'])
            ->findArray();
        $this->renderJson($estimate);
    }

    private function _checkForCreateNewCustomers() {
        $return = [];
        $newCustomerAttrs = $newJobCustomerAttrs = [];
        if (($this->data['customer_id'] == 0) && // Has new billing customer
            isset($this->data['customer_display_name']) &&
            trim($this->data['customer_display_name'])) {
            $newCustomerAttrs = $this->collectCustomerInfo();
        }

        // Check for new job customer
        if (($this->data['job_customer_id'] == 0) && // Has new job customer
            isset($this->data['job_customer_display_name']) &&
            trim($this->data['job_customer_display_name'])) {
            $newJobCustomerAttrs = $this->collectJobCustomerInfo();
        }

        try {
            // Check if the new job customer is same with new billing customer
            if ($newCustomerAttrs && $newJobCustomerAttrs &&
                    ($newCustomerAttrs['display_name'] ===
                        $newJobCustomerAttrs['display_name'])) {
                $newCustomer = $this->_createCustomer(
                    array_merge($newCustomerAttrs, $newJobCustomerAttrs)
                );
                $return['customer_id'] =
                    $return['job_customer_id'] = $newCustomer->id;
            } else {
                if ($newCustomerAttrs) {
                    $newCustomer = $this->_createCustomer($newCustomerAttrs);
                    $return['customer_id'] = $newCustomer->id;
                }
                if ($newJobCustomerAttrs) {
                    $newCustomer = $this->_createCustomer($newJobCustomerAttrs);
                    $return['job_customer_id'] = $newCustomer->id;
                }
            }
        } catch (IdsException $e) {
            $this->renderJson([
                'success' => false,
                'message' => 'Failed to create new customer'
            ]);
        }
        return $return;
    }

    public function update() {
        $estimateModel = new EstimateModel();
        $estimateLineModel = new EstimateLineModel();
        $updateData = $this->data;
        $estimate = ORM::forTable('estimates')->findOne($updateData['id']);
        $sync = Asynchronzier::getInstance();
        $newCustomerData = $this->_checkForCreateNewCustomers();
        $updateData = array_merge($this->data, $newCustomerData);
        if (!$updateData['estimate_route_id']) {
            $updateData['estimate_route_id'] = NULL;
        }
        if (!@$updateData['date_of_signature']) {
            $updateData['date_of_signature'] = NULL;
        }
        if (!@$updateData['accepted_date']) {
            $updateData['accepted_date'] = NULL;
        }
        if (!@$updateData['expiration_date']) {
            $updateData['expiration_date'] = NULL;
        }
        if (isset($updateData['customer_signature_encoded'])) { // Hash customer signature
            if ($updateData['customer_signature_encoded']) {
                $dataPieces = explode(",", $updateData['customer_signature_encoded']);
                $encodedImage = $dataPieces[1];
                $decodedImage = base64_decode($encodedImage);
                $signatureFileName = 'signature-' . time() . '.png';
                file_put_contents(ERP_UPLOADS_DIR . '/' . $signatureFileName, $decodedImage);
                $updateData['customer_signature'] = 'uploads/' . $signatureFileName;
            } elseif ($estimate->customer_signature) {
                // Check exists to remove old signature
                @unlink(ERP_ROOT_DIR . '/' . $estimate->customer_signature);
                $updateData['customer_signature'] = '';
            }
        }
        $updateData['sync_token'] = $estimate->sync_token;
        $params = $sync->decodeEstimate($updateData);
        try {
            $result = $sync->Update($params);
        } catch (QuickbooksAPIException $e) {
            if ($e->getStatusCode() == '400') { // Maybe the sync token wrong
                // Try to get update token
                $objEstimate = new IPPEstimate();
                $objEstimate->Id = $updateData['id'];
                $responseEstimate = $sync->Retrieve($objEstimate);
                if ($params['attributes']['SyncToken'] != $responseEstimate->SyncToken) {
                    $params['attributes']['SyncToken'] = $responseEstimate->SyncToken;
                    $result = $sync->Update($params);
                } else {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }
        $data_result = $sync->parseEstimate($result, $updateData);

        // Start sync lines
        $data_line_sync = [];
        foreach ($result->Line as $line) {
            $result_line = $sync->parseEstimateLine($line, $data_result['id']);
            if (($result_line['line_id'] != null) && ($result_line['estimate_id'] != null)) {
                $lineInfo = [
                    'line_id' => $result_line['line_id'],
                    'estimate_id' => $result_line['estimate_id']
                ];
                array_push($data_line_sync, $lineInfo);
                $estimate_line = $estimateLineModel->findBy($lineInfo);
                if ($estimate_line == null) {
                    $estimateLineModel->insert($result_line);
                } else {
                  $estimateLineModel->update($result_line, $lineInfo);
                }
            }
        }
        $data_estimate_line = $estimateLineModel->getAllWithColumns(
            ['line_id', 'estimate_id'], ['estimate_id' => $data_result['id']]
        );
        $data_delete = $sync->mergeData($data_line_sync, $data_estimate_line);
        foreach ($data_delete as $item_line_delete) {
            $estimateLineModel->delete([
              'line_id' => $item_line_delete['line_id'],
              'estimate_id' => $item_line_delete['estimate_id']
            ]);
        }
        // End sync lines
        unset($data_result['line']);
        $estimate->set($data_result);
        if ($estimate->save()) {
            $this->renderJson([
                'success' => true,
                'message' => 'Estimate saved successfully',
                'data'    => $estimate->asArray()
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'Error while updating estimate'
            ]);
        }
    }

    public function uploadAttachment() {
        if (isset($this->data['id']) && isset($_FILES['file'])) {
            $uploadedFile = $_FILES['file'];
            $estAtM = new AttachmentModel;
            $attachment = $estAtM->upload($this->data['id'], $uploadedFile);
            $this->renderJson([
                'success' => true,
                'attachment' => $attachment
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'Request is invalid'
            ]);
        }
    }

    public function deleteAttachment() {
        $estAtM = new AttachmentModel;
        if ($estAtM->delete($this->data['id'])) {
            $this->renderJson([
                'success' => true,
                'message' => 'An attachment has been deleted'
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => 'An error has occurred while deleting attachment'
            ]);
        }
    }

    public function printPDF() {
        header("Content-Type: text/html");
        $companyInfo = ORM::forTable('company_info')->findOne();
        $estimateId = $_REQUEST['id'];
        $estimate = $this->getEstimateDataForPrint($estimateId);
        $lines = ORM::forTable('estimate_lines')
                ->tableAlias('el')
                ->join(
                    'products_and_services',
                    ['el.product_service_id', '=', 'ps.id'],
                    'ps'
                )
                ->where('el.estimate_id', $estimateId)
                ->select('el.*')
                ->select('ps.name', 'product_service_name')
                ->findArray();
        require TEMPLATES_DIR . '/print/estimate.php';
        exit();
    }

    public function sendEstimate() {
        $companyInfo = ORM::forTable('company_info')->findOne();
        $estimateId = $this->data['id'];
        $estimate = $estimate = $this->getEstimateDataForPrint($estimateId);
        $lines = ORM::forTable('estimate_lines')
                ->tableAlias('el')
                ->join(
                    'products_and_services',
                    ['el.product_service_id', '=', 'ps.id'],
                    'ps'
                )
                ->where('el.estimate_id', $estimateId)
                ->select('el.*')
                ->select('ps.name', 'product_service_name')
                ->findArray();
        ob_start();
        require TEMPLATES_DIR . '/print/estimate.dompdf.php';
        $html = ob_get_clean();
        $dompdf = new DOMPDF();
        $dompdf->load_html($html);
        $dompdf->set_paper('legal');
        $dompdf->set_base_path(ERP_ROOT_DIR); // For load local images
        $dompdf->render();
        $pdfPath = TMP_DIR . 'estimate-' . $estimateId . '-' . time() . '.pdf';
        file_put_contents($pdfPath, $dompdf->output());
        $STMPSetting = PreferenceModel::getSMTPSetting();
        if (is_null($STMPSetting)) {
            $this->renderJson([
                'success' => false,
                'message' => 'Error: SMTP setting is not configured properly or missing'
            ]);
        }
        $mailer = new ERPMailer($STMPSetting);
        $cc = [];
        if (strpos($this->data['to'], ',')) {
            $recipients = explode(',' , $this->data['to']);
            $to = trim($recipients[0]);
            for($i = 1; $i < count($recipients); $i++) {
                $cc[] = trim($recipients[$i]);
            }
        } else {
            $to = $this->data['to'];
        }

        if (isset($this->data['subject'])) {
            $subject = $this->data['subject'];
        } else {
            $subject = $companyInfo['name'];
        }

        if (isset($this->data['body'])) {
            $body = $this->data['body'];
        } else {
            $body = $this->data['estimate_footer'];
        }

        $options = [
            'fromName' => $companyInfo['name'],
            'attachments' => [
                $pdfPath
            ],
            'cc' => $cc
        ];

        if ($mailer->sendmail($subject, $body, $to, $options)) {
            @unlink($pdfPath);
            $this->renderJson([
                'success' => true,
                'message' => 'Email was send successfully'
            ]);
        } else {
            @unlink($pdfPath);
            $this->renderJson([
                'success' => false,
                'message' => 'Error occurred while sending mail'
            ]);
        }
    }

    private function getEstimateDataForPrint($id) {
        return
            ORM::forTable('estimates')
            ->tableAlias('e')
            ->left_outer_join('customers', ['e.customer_id' ,'=', 'cus.id'], 'cus')
            ->left_outer_join('customers', ['e.job_customer_id' ,'=', 'jobcus.id'], 'jobcus')
            ->left_outer_join('employees', ['e.sold_by_1' ,'=', 'emp1.id'], 'emp1')
            ->left_outer_join('employees', ['e.sold_by_2' ,'=', 'emp2.id'], 'emp2')
            ->select('e.*')
            ->select('cus.display_name', 'customer_display_name')
            ->select('jobcus.display_name', 'job_customer_display_name')
            ->select('emp1.display_name', 'sold_by_1_display_name')
            ->select('emp2.display_name', 'sold_by_2_display_name')
            ->findOne($id);
    }
}
?>
