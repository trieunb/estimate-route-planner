<?php
class EstimateController extends BaseController {

    const DATE_EXP_EST = 14;

    /**
     * Listing all estimates
     */
    public function index() {
        $page = $this->getPageParam();
        $keyword = $this->getKeywordParam();
        $filteredStatus = $this->getParam('status');

        $searchQuery = ORM::forTable('estimates')
            ->tableAlias('e')
            ->leftOuterJoin('customers', ['e.customer_id', '=', 'c.id'], 'c')
            ->leftOuterJoin('customers', ['e.job_customer_id', '=', 'jc.id'], 'jc')
            ->leftOuterJoin('erpp_classes', ['e.class_id', '=', 'classes.id'], 'classes')
            ->orderByDesc('e.id');
        if ($filteredStatus) {
            $searchQuery->where('e.status', $filteredStatus);
        }
        if ($keyword) {
            $searchQuery->whereAnyIs([
                ['c.display_name' => "%$keyword%"],
                ['jc.display_name' => "%$keyword%"],
                ['e.doc_number' => "%$keyword%"],
            ], 'LIKE');
        }
        if ($this->currentUserHasCap('erpp_view_sales_estimates')) {
            $currentUserName = $this->getCurrentUserName();
            $searchQuery
                ->whereAnyIs([
                    ['e.sold_by_1' => $currentUserName],
                    ['e.sold_by_2' => $currentUserName]
                ]);
        }
        if ($this->currentUserHasCap('erpp_hide_expired_estimates')) {
            $searchQuery->whereGte(
                'txn_date',
                date('Y-m-d', strtotime('-' . self::DATE_EXP_EST . 'days'))
            );
        }

        $countQuery = clone($searchQuery);
        $estimates = $searchQuery
            ->selectMany(
                'e.id', 'e.txn_date', 'e.doc_number',
                'e.expiration_date', 'e.total',
                'e.status', 'e.email',
                'e.job_address', 'e.job_city', 'e.job_state', 'e.job_zip_code'
            )
            ->select('c.display_name', 'billing_customer_display_name')
            ->select('c.given_name', 'billing_customer_given_name')
            ->select('c.family_name', 'billing_customer_family_name')
            ->select('jc.display_name', 'shipping_customer_display_name')
            ->select('classes.name', 'source_name')
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
     * Get all estimates which non-assigned to any routes and have Accepted status
     */
    public function assignable() {
        $estimates = ORM::forTable('estimates')
            ->tableAlias('e')
            ->join('customers', ['e.job_customer_id', '=', 'c.id'], 'c')
            ->selectMany(
                'e.id', 'e.expiration_date', 'e.txn_date', 'e.doc_number',
                'e.job_address', 'e.job_city', 'e.primary_phone_number',
                'e.job_country', 'e.job_state', 'e.job_zip_code',
                'e.total', 'e.job_lat', 'e.job_lng', 'e.status', 'e.priority'
            )
            ->select('c.display_name', 'job_customer_display_name')
            ->orderByDesc('e.id')
            ->whereNull('e.route_id')
            ->whereNotNull('e.job_lat')
            ->whereNotNull('e.job_lng')
            ->where('e.status', 'Accepted')
            ->findArray();
        $this->renderJson($estimates);
    }

    /**
     * Get all estimates (includes lines data) which assigned to given route
     * This action use for getting data for create work order
     */
    public function assigned() {
        $routeId = $this->data['id'];
        $estimates = ORM::forTable('estimates')
            ->tableAlias('e')
            ->join('customers', ['e.job_customer_id', '=', 'c.id'], 'c')
            ->selectMany(
                'e.id', 'e.expiration_date', 'e.job_address', 'e.job_city',
                'e.job_country', 'e.job_state', 'e.job_zip_code',
                'e.location_notes', 'e.doc_number', 'e.txn_date',
                'e.total', 'e.job_lat', 'e.job_lng', 'e.status',
                'e.sold_by_1', 'e.sold_by_2'
            )
            ->select('c.display_name', 'job_customer_display_name')
            ->orderByAsc('e.route_order')
            ->where('e.route_id', $routeId)
            ->findArray();
        # Get lines
        $estimateIds = [];
        foreach ($estimates as $est) {
            $estimateIds[] = $est['id'];
        }
        if (count($estimateIds) !== 0) {
            $lines = ORM::forTable('estimate_lines')
                ->tableAlias('el')
                ->leftOuterJoin(
                    'products_and_services',
                    ['el.product_service_id', '=', 'ps.id'],
                    'ps'
                )
                ->whereIn('el.estimate_id', $estimateIds)
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
        }
        $this->renderJson($estimates);
    }

    /**
     * Create new estimate
     */
    public function add() {
        $estimateModel = new EstimateModel();
        $sync = Asynchronzier::getInstance();
        $insertData = $this->data;

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
            'route_id', 'date_of_signature', 'accepted_date',
            'expiration_date', 'class_id'
        ];
        foreach ($keepNullColumns as $column) {
            if (!@$insertData[$column]) {
                $insertData[$column] = NULL;
            }
        }
        // Save estimate to QB
        $estimateEntity = $sync->buildEstimateEntity($insertData);
        $resEntity = $sync->saveEntity($estimateEntity);
        $parsedEstimateData = ERPDataParser::parseEstimate($resEntity, $insertData);

        // Parse lines data
        foreach ($resEntity->Line as $line) {
            $parsedLine = ERPDataParser::parseEstimateLine($line);
            if ($parsedLine['line_id'] != null) {
                $parsedLine['estimate_id'] = $parsedEstimateData['id'];
                ORM::forTable('estimate_lines')->create($parsedLine)->save();
            }
        }
        // Save estimate to local DB
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
        $id = $this->data['id'];
        $estimate = null;

        $query = ORM::forTable('estimates')
            ->tableAlias('e')
            ->select('e.*');

        if ($this->currentUserHasCap('erpp_view_sales_estimates')) {
            $currentUserName = $this->getCurrentUserName();
            $estimate = $query->whereAnyIs([
                    ['sold_by_1' => $currentUserName],
                    ['sold_by_2' => $currentUserName]
                ]);
        } else {
            $estimate = $query;
        }
        if ($this->currentUserHasCap('erpp_hide_expired_estimates')) {
            $estimate = $query->whereGte(
                'txn_date',
                date('Y-m-d', strtotime('-' . self::DATE_EXP_EST . 'days'))
            );
        }
        $estimate = $query
            ->leftOuterJoin('customers', ['e.customer_id', '=', 'c.id'], 'c')
            ->leftOuterJoin('customers', ['e.job_customer_id', '=', 'jc.id'], 'jc')
            ->select('c.display_name', 'billing_customer_display_name')
            ->select('c.given_name', 'billing_customer_given_name')
            ->select('c.family_name', 'billing_customer_family_name')
            ->select('jc.display_name', 'shipping_customer_display_name')
            ->findOne($id);
        if ($estimate) {
            $estimate = $estimate->asArray();
            $estimate['lines'] = ORM::forTable('estimate_lines')
                ->where('estimate_id', $id)
                ->findArray();
            $estimate['attachments'] = ORM::forTable('estimate_attachments')
                ->where('estimate_id', $id)
                ->where('is_customer_signature', 0)
                ->findArray();
            $resData = $estimate;
            $resData['customer'] = ORM::forTable('customers')
                ->findOne($estimate['customer_id'])->asArray();
            $resData['job_customer'] = ORM::forTable('customers')
                ->findOne($estimate['job_customer_id'])->asArray();
            $this->renderJson($resData);
        } else {
            $this->render404();
        }
    }

    public function update() {
        $estimateM = new EstimateModel;
        $updateData = $this->data;
        $id = $updateData['id'];
        $estimate = ORM::forTable('estimates')->findOne($id);
        $sync = Asynchronzier::getInstance();
        $updateData = $this->data;
        $keepNullColumns = [
            'route_id', 'date_of_signature', 'accepted_date',
            'expiration_date', 'class_id'
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
            } else {
                // Change status to Accepted when the signature is added(once)
                $updateData['status'] = 'Accepted';
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
        $estimateEntity = $sync->buildEstimateEntity($updateData);
        try {
            $resEntity = $sync->saveEntity($estimateEntity);
        } catch (QuickbooksAPIException $e) {
            if ($e->getStatusCode() == '400') { // Maybe the sync token wrong
                // Try to get update token
                $objEstimate = new IPPEstimate();
                $objEstimate->Id = $id;
                $responseEstimate = $sync->Retrieve($objEstimate);
                if ($estimateEntity->SyncToken != $responseEstimate->SyncToken) {
                    $estimateEntity->SyncToken = $responseEstimate->SyncToken;
                    $resEntity = $sync->saveEntity($estimateEntity);
                } else {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }
        $parsedEstimateData = ERPDataParser::parseEstimate($resEntity, $updateData);
        // Start sync lines
        $currentLineIds = [];
        foreach ($resEntity->Line as $line) {
            $parsedLine = ERPDataParser::parseEstimateLine($line);
            if ($parsedLine['line_id'] != null) {
                $parsedLine['estimate_id'] = $id;
                $currentLineIds[] = $parsedLine['line_id'];
                $localLine = ORM::forTable('estimate_lines')
                    ->where('estimate_id', $id)
                    ->where('line_id', $parsedLine['line_id'])
                    ->findOne();
                if ($localLine) {
                    $localLine->set($parsedLine);
                } else {
                    $localLine = ORM::forTable('estimate_lines')->create($parsedLine);
                }
                $localLine->save();
            }
        }
        ORM::forTable('estimate_lines')
            ->where('estimate_id', $id)
            ->whereNotIn('line_id', $currentLineIds)
            ->deleteMany();
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
        if ($estimate) {
            $lines = $this->getEstimateLines($estimateId);
            require ERP_TEMPLATES_DIR . '/print/estimate.php';
        } else {
            $this->render404();
        }
    }

    public function sendEstimate() {
        $companyInfo = ORM::forTable('company_info')->findOne();
        $estimateId = $this->data['id'];
        $estimate = $this->getEstimateDataForPrint($estimateId);
        if ($estimate) {
            $lines = $this->getEstimateLines($estimateId);
            ob_start();
            require ERP_TEMPLATES_DIR . '/print/estimate.php';
            $html = ob_get_clean();
            $dompdf = new DOMPDF();
            $dompdf->load_html($html);
            $dompdf->set_paper('legal');
            $dompdf->set_base_path(ERP_ROOT_DIR); // For load local images
            $dompdf->render();
            $pdfPath = ERP_TMP_DIR . 'estimate-' . $estimateId . '-' . time() . '.pdf';
            file_put_contents($pdfPath, $dompdf->output());
            $STMPSetting = PreferenceModel::getSMTPSetting();
            if (is_null($STMPSetting)) {
                $this->renderJson([
                    'success' => false,
                    'message' => 'Error: SMTP setting is not configured properly or missing'
                ]);
            } else {
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
        } else {
            $this->render404();
        }
    }

    public function previewPdf() {
        $companyInfo = ORM::forTable('company_info')->findOne();
        $estimateId = $_REQUEST['id'];
        $estimate = $this->getEstimateDataForPrint($estimateId);
        if ($estimate) {
            $lines = $this->getEstimateLines($estimateId);
            ob_start();
            require ERP_TEMPLATES_DIR . '/print/estimate.php';
            $html = ob_get_clean();
            $dompdf = new DOMPDF();
            $dompdf->load_html($html);
            $dompdf->set_paper('legal');
            $dompdf->set_base_path(ERP_ROOT_DIR); // For load local images
            $dompdf->render();
            $dompdf->stream('estimate-'. $estimate['doc_number'] .'.pdf', ['Attachment' => 0]);
        } else {
            $this->render404();
        }
    }

    private function getEstimateLines($estimateId) {
        return ORM::forTable('estimate_lines')
                ->tableAlias('el')
                ->leftOuterJoin(
                    'products_and_services',
                    ['el.product_service_id', '=', 'ps.id'],
                    'ps')
                ->where('el.estimate_id', $estimateId)
                ->select('el.*')
                ->select('ps.name', 'product_service_name')
                ->orderByAsc('el.line_num')
                ->findArray();
    }

    private function getEstimateDataForPrint($id) {
        $query = ORM::forTable('estimates')
            ->tableAlias('e')
            ->leftOuterJoin('customers', ['e.customer_id' ,'=', 'cus.id'], 'cus')
            ->leftOuterJoin('customers', ['e.job_customer_id' ,'=', 'jobcus.id'], 'jobcus')
            ->select('e.*')
            ->select('cus.display_name', 'customer_display_name')
            ->select('jobcus.display_name', 'job_customer_display_name');
        if ($this->currentUserHasCap('erpp_view_sales_estimates')) {
            $currentUserName = $this->getCurrentUserName();
            $query = $query->whereAnyIs([
                    ['e.sold_by_1' => $currentUserName],
                    ['e.sold_by_2' => $currentUserName]
                ]);
        }
        return $query->findOne($id);
    }
}
?>
