<?php
class EstimateController extends BaseController {

    public function index() {
        $page = $this->getPageParam();
        $keyword = $this->getKeywordParam();
        $filteredStatus = "";

        if (isset($_REQUEST['status'])) {
            $filteredStatus = $_REQUEST['status'];
        }
        $searchQuery = ORM::forTable('estimates')
            ->tableAlias('e')
            ->leftOuterJoin('customers', ['e.customer_id', '=', 'c.id'], 'c')
            ->leftOuterJoin('customers', ['e.job_customer_id', '=', 'jc.id'], 'jc')
            ->whereAnyIs(
                [
                    ['c.display_name' => "%$keyword%"],
                    ['jc.display_name' => "%$keyword%"],
                    ['e.doc_number' => "%$keyword%"],
                ], 'LIKE'
            )
            ->whereLike('e.status', "%$filteredStatus%")
            ->orderByDesc('e.id');
        $countQuery = clone($searchQuery);
        $estimates = $searchQuery
            ->selectMany(
                'e.id', 'e.txn_date', 'e.doc_number',
                'e.source', 'e.due_date', 'e.total',
                'e.status', 'e.email'
            )
            ->select('c.display_name', 'customer_display_name')
            ->select('jc.display_name', 'job_customer_display_name')
            ->limit(self::PAGE_SIZE)
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->findArray();
        $counter = $countQuery->selectExpr('COUNT(*)', 'count')->findMany();
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

    public function add() {
        $estimateModel = new EstimateModel();
        $sync = Asynchronzier::getInstance();
        $newCustomerData = $this->_checkForCreateNewCustomers();
        $insertData = array_merge($this->data, $newCustomerData);

        // Upload customer signature
        $decodedSignature = null;
        if (isset($insertData['customer_signature_encoded'])) {
            $encodedSignature = $insertData['customer_signature_encoded'];
            if ($encodedSignature) {
                $decodedSignature = Base64Encoder::decode($encodedSignature);
                $signatureFileName = 'customer-signature-' . time() . '.png';
                file_put_contents(ERP_UPLOADS_DIR . '/' . $signatureFileName, $decodedSignature);
                $insertData['customer_signature'] = 'uploads/' . $signatureFileName;
            }
        }
        $keepNullColumns = [
            'estimate_route_id', 'date_of_signature', 'accepted_date',
            'expiration_date'
        ];
        foreach ($keepNullColumns as $column) {
            if (!@$insertData[$column]) {
                $insertData[$column] = NULL;
            }
        }
        // Save estimate to QB
        $params = $sync->decodeEstimate($insertData);
        $result = $sync->Create($params);
        $parsedEstimateData = ERPDataParser::parseEstimate($result, $insertData);

        // Parse lines data
        $estimateLineModel = new EstimateLineModel();
        foreach ($result->Line as $line) {
            $result_line = ERPDataParser::parseEstimateLine($line, $parsedEstimateData['id']);
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
        // Save estimate to local DB
        unset($parsedEstimateData['line']);
        $estimate = ORM::forTable('estimates')->create();
        $estimate->set($parsedEstimateData);
        if ($estimate->save()) {
            // Check to upload signature as estimate attachment
            if ($decodedSignature) {
                $atmModel = new AttachmentModel;
                $atmModel->uploadSignature($parsedEstimateData['id'], $decodedSignature);
                $estimateModel->updateSyncToken($parsedEstimateData['id']);
            }
            $this->renderJson([
                'success' => true,
                'message' => 'Estimate saved successfully',
                'data'    => $estimate->asArray()
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
            ->where('is_customer_signature', 0)
            ->findArray();
        $this->renderJson($estimate);
    }

    public function update() {
        $estimateM = new EstimateModel;
        $updateData = $this->data;
        $id = $updateData['id'];
        $estimate = ORM::forTable('estimates')->findOne($id);
        $sync = Asynchronzier::getInstance();
        $newCustomerData = $this->_checkForCreateNewCustomers();
        $updateData = array_merge($this->data, $newCustomerData);
        $keepNullColumns = [
            'estimate_route_id', 'date_of_signature', 'accepted_date',
            'expiration_date'
        ];
        foreach ($keepNullColumns as $column) {
            if (!@$updateData[$column]) {
                $updateData[$column] = NULL;
            }
        }
        if (isset($updateData['customer_signature_encoded'])) { // This mean the signature has changed
            $encodedSignature = $updateData['customer_signature_encoded'];
            $atmModel = new AttachmentModel;
            $needUpdateSyncToken = false;
            if ($encodedSignature) {
                // Upload to QB
                $decodedSignature = Base64Encoder::decode($encodedSignature);
                $atmModel->uploadSignature($id, $decodedSignature);
                $needUpdateSyncToken = true;
                // Save signature to local-disk
                $signatureFileName = 'customer-signature-' . time() . '.png';
                file_put_contents(
                    ERP_UPLOADS_DIR . '/' . $signatureFileName, $decodedSignature);
                $updateData['customer_signature'] = 'uploads/' . $signatureFileName;
            }
            if ($estimate->customer_signature) { // Check to remove the old
                @unlink(ERP_ROOT_DIR . '/' . $estimate->customer_signature);
                if (!$encodedSignature) {
                    $updateData['customer_signature'] = '';
                }
                // Remove the signature attachments if exists
                $signatureAttachment = ORM::forTable('estimate_attachments')
                    ->where('estimate_id', $id)
                    ->where('is_customer_signature', 1)
                    ->findOne();
                if ($signatureAttachment) {
                    $atmModel->delete($signatureAttachment->id);
                    $needUpdateSyncToken = true;
                }
            }
            if ($needUpdateSyncToken) {
                $newToken = $estimateM->updateSyncToken($id);
                $estimate->sync_token = $newToken;
            }
        } else {
            // Keep the old url
            $updateData['customer_signature'] = $estimate->customer_signature;
        }
        $updateData['sync_token'] = $estimate->sync_token;
        $params = $sync->decodeEstimate($updateData);
        try {
            $result = $sync->Update($params);
        } catch (QuickbooksAPIException $e) {
            if ($e->getStatusCode() == '400') { // Maybe the sync token wrong
                // Try to get update token
                $objEstimate = new IPPEstimate();
                $objEstimate->Id = $id;
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
        $parsedEstimateData = ERPDataParser::parseEstimate($result, $updateData);
        // Start sync lines
        $estimateLineModel = new EstimateLineModel();
        $newEstimateLines = [];
        foreach ($result->Line as $line) {
            $result_line = ERPDataParser::parseEstimateLine($line, $id);
            if (($result_line['line_id'] != null) && ($result_line['estimate_id'] != null)) {
                $lineInfo = [
                    'line_id' => $result_line['line_id'],
                    'estimate_id' => $result_line['estimate_id']
                ];
                array_push($newEstimateLines, $lineInfo);
                $estimate_line = $estimateLineModel->findBy($lineInfo);
                if ($estimate_line == null) {
                    $estimateLineModel->insert($result_line);
                } else {
                    $estimateLineModel->update($result_line, $lineInfo);
                }
            }
        }
        $oldEstimateLine = $estimateLineModel->getAllWithColumns(
            ['line_id', 'estimate_id'], ['estimate_id' => $id]
        );
        $data_delete = $sync->mergeData($newEstimateLines, $oldEstimateLine);
        foreach ($data_delete as $item_line_delete) {
            $estimateLineModel->delete([
              'line_id' => $item_line_delete['line_id'],
              'estimate_id' => $item_line_delete['estimate_id']
            ]);
        }
        // End sync lines

        unset($parsedEstimateData['line']);
        $estimate->set($parsedEstimateData);
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

    /**
     * Return the estimate's attachments
     */
    public function attachments() {
        $id = $this->data['id'];
        $attachments = ORM::forTable('estimate_attachments')
            ->where('estimate_id', $id)
            ->where('is_customer_signature', 0)
            ->findArray();
        $this->renderJson($attachments);
    }

    public function uploadAttachment() {
        if (isset($this->data['id']) && isset($_FILES['file'])) {
            $estAtM = new AttachmentModel;
            $estimateId = $this->data['id'];
            $attachment = $estAtM->upload($estimateId, $_FILES['file']);
            $estimateM = new EstimateModel;
            $estimateM->updateSyncToken($estimateId);
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
        $estimateM = new EstimateModel;
        $attachment = $estAtM->delete($this->data['id']);
        if ($attachment) {
            $estimateId = $attachment->estimate_id;
            if ($attachment->is_customer_signature) {
                $estimate = ORM::forTable('estimates')->findOne($estimateId);
                $estimate->customer_signature = NULL;
                $estimate->save();
            }
            $estimateM->updateSyncToken($estimateId);
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
            ->leftOuterJoin('customers', ['e.customer_id' ,'=', 'cus.id'], 'cus')
            ->leftOuterJoin('customers', ['e.job_customer_id' ,'=', 'jobcus.id'], 'jobcus')
            ->select('e.*')
            ->select('cus.display_name', 'customer_display_name')
            ->select('jobcus.display_name', 'job_customer_display_name')
            ->findOne($id);
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
        $customerRecord->set(ERPDataParser::parseCustomer($qbcustomerObj));
        $customerRecord->save();
        return $customerRecord;
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
}
?>
