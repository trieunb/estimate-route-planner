<?php
class EstimateController extends BaseController {

    public function index() {
        $pageSize = 30;
        if (isset($_REQUEST['page'])) {
            $page = (int) $_REQUEST['page'];
        } else {
            $page = 1;
        }
        $filteredStatus = "";

        if (isset($_REQUEST['status'])) {
            $filteredStatus = $_REQUEST['status'];
        }

        $estimates = ORM::forTable('estimates')
            ->tableAlias('e')
            ->join('customers', ['e.customer_id', '=', 'c.id'], 'c')
            ->selectMany(
                'e.id', 'e.txn_date', 'e.doc_number',
                'e.source', 'e.due_date', 'e.total',
                'e.status', 'e.email'
            )
            ->select('c.display_name', 'customer_display_name')
            ->whereLike('e.status', "%$filteredStatus%")
            ->orderByDesc('e.id')
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->findArray();
        $counter = ORM::for_table('estimates')
            ->whereLike('estimates.status', "%$filteredStatus%")
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
                'e.job_state', 'e.job_zip_code', 'e.total', 'e.job_lat',
                'e.job_lng', 'e.status'
            )
            ->select('c.display_name', 'job_customer_display_name')
            ->orderByDesc('e.id')
            ->whereNull('e.estimate_route_id')
            ->whereIn('e.status', ['Pending', 'Accepted'])
            ->findArray();
        $this->renderJson($estimates);
    }

    public function getdata() {
        return PreferenceModel::getQuickbooksCreds();
    }

    private function collectCustomerInfo() {
        $customerInfo = [];
        $customerInfo['display_name']   = $this->data['customer_display_name'];
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
        $customerInfo['display_name']   = $this->data['job_customer_display_name'];
        $customerInfo['bill_address']   = @$this->data['job_address'];
        $customerInfo['bill_city']      = @$this->data['job_city'];
        $customerInfo['bill_country']   = @$this->data['job_country'];
        $customerInfo['bill_state']     = @$this->data['job_state'];
        $customerInfo['bill_zip_code']  = @$this->data['job_zip_code'];
        return $customerInfo;
    }

    public function add() {
        $estimateModel = new EstimateModel();
        $estimateLineModel = new EstimateLineModel();
        $insertData = $this->data;
        $sync = new Asynchronzier(PreferenceModel::getQuickbooksCreds());
        // Check for new customer
        if (($this->data['customer_id'] == 0) && @$this->data['customer_display_name']) {
            // Push to Quickbooks
            try {
                $qbcustomerObj = $sync->createCustomer($this->collectCustomerInfo());
                // Save to local DB
                $customer = ORM::forTable('customers')->create();
                $customer->set($sync->parseCustomer($qbcustomerObj));
                $customer->save();
                $insertData['customer_id'] = $customer->id;
            } catch (IdsException $e) {
                $this->renderJson([
                    'success' => false,
                    'message' => 'Failed to create new customer for billing information'
                ]);
                exit;
            }
        }

        // Check for new job customer
        if (($this->data['job_customer_id'] == 0) && @$this->data['job_customer_display_name']) {
            if ($this->data['job_customer_display_name'] == @$this->data['customer_display_name']) {
                $this->data['job_customer_id'] = $insertData['customer_id'];
            } else {
                try {
                    // Push to Quickbooks
                    $qbcustomerObj = $sync->createCustomer($this->collectJobCustomerInfo());
                    // Save to local DB
                    $jobCustomer = ORM::forTable('customers')->create();
                    $jobCustomer->set($sync->parseCustomer($qbcustomerObj));
                    $jobCustomer->save();
                    $insertData['job_customer_id'] = $jobCustomer->id;
                } catch (IdsException $e) {
                    $this->renderJson([
                        'success' => false,
                        'message' => 'Failed to create new customer for job information'
                    ]);
                    exit;
                }
            }
        }

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
        $cons = [
            'id' => $this->data['id']
        ];
        $estimateModel = new EstimateModel();
        $estimate = $estimateModel->findBy($cons);
        $estimateLineM = new EstimateLineModel;
        $estimateAttachmentM = new AttachmentModel;
        $lines = $estimateLineM->where(['estimate_id' => $estimate['id']]);
        $attachments = $estimateAttachmentM->where(['estimate_id' => $estimate['id']]);
        $estimate['lines'] = $lines;
        $estimate['attachments'] = $attachments;
        $this->renderJson($estimate);
    }

    public function update() {
        $estimateModel = new EstimateModel();
        $estimateLineModel = new EstimateLineModel();
        $updateData = $this->data;
        $estimate = ORM::forTable('estimates')->findOne($updateData['id']);

        $sync = new Asynchronzier(PreferenceModel::getQuickbooksCreds());
        // Check for new customer
        if (($this->data['customer_id'] == 0) && @$this->data['customer_display_name']) {
            // Push to Quickbooks
            try {
                $qbcustomerObj = $sync->createCustomer($this->collectCustomerInfo());
                // Save to local DB
                $customer = ORM::forTable('customers')->create();
                $customer->set($sync->parseCustomer($qbcustomerObj));
                $customer->save();
                $updateData['customer_id'] = $customer->id;
            } catch (IdsException $e) {
                $this->renderJson([
                    'success' => false,
                    'message' => 'Failed to create new customer for billing information'
                ]);
                exit;
            }
        }

        // Check for new job customer
        if (($this->data['job_customer_id'] == 0) && @$this->data['job_customer_display_name']) {
            if (($this->data['customer_id'] == 0)
                    && ($this->data['job_customer_display_name'] == @$this->data['customer_display_name'])) {
                $updateData['job_customer_id'] = $updateData['customer_id'];
            } else {
                try {
                    // Push to Quickbooks
                    $qbcustomerObj = $sync->createCustomer($this->collectJobCustomerInfo());
                    // Save to local DB
                    $jobCustomer = ORM::forTable('customers')->create();
                    $jobCustomer->set($sync->parseCustomer($qbcustomerObj));
                    $jobCustomer->save();
                    $updateData['job_customer_id'] = $jobCustomer->id;
                } catch (IdsException $e) {
                    $this->renderJson([
                        'success' => false,
                        'message' => 'Failed to create new customer for job information'
                    ]);
                    exit;
                }
            }
        }

        if (!$updateData['estimate_route_id']) {
            $updateData['estimate_route_id'] = NULL;
        }
        if (!$updateData['date_of_signature']) {
            $updateData['date_of_signature'] = NULL;
        }
        if (!$updateData['accepted_date']) {
            $updateData['accepted_date'] = NULL;
        }
        if (!$updateData['expiration_date']) {
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
