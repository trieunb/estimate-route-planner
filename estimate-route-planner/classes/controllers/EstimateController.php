<?php
class EstimateController extends BaseController {

    public function index() {
        $estimates = ORM::forTable('estimates')
            ->join('customers', ['estimates.customer_id', '=', 'customers.id'])
            ->select('estimates.*')
            ->select('customers.display_name', 'customer_display_name')
            ->orderByDesc('estimates.id')
            ->findArray();
        $this->renderJson($estimates);
    }

    /**
     * Get all estimates which non-assigned to any routes
     */
    public function unassigned() {
        $estimates = ORM::forTable('estimates')
            ->join('customers', ['estimates.job_customer_id', '=', 'customers.id'])
            ->select('estimates.*')
            ->select('customers.display_name', 'job_customer_display_name')
            ->orderByDesc('estimates.id')
            ->whereNull('estimate_route_id')
            ->findArray();
        $this->renderJson($estimates);
    }

    public function getdata() {
        return PreferenceModel::getQuickbooksAPIConnectionInfo();
    }

    public function add() {
        $estimateModel = new EstimateModel();
        $estimateLineModel = new EstimateLineModel();
        $insertData = $this->data;
        if(isset($insertData['customer_signature_encoded'])) {
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
        $data_connect = $this->getdata();
        $sync = new Asynchronzier($data_connect);
        $params = $sync->decodeEstimate($insertData);
        $result = $sync->Create($params);
        $data_result = $sync->parseEstimate($result, $insertData);
        foreach ($data_result['line'] as $line) {
            $result_line = $sync->parseEstimate_line($line, $data_result['id']);
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
        $estimateAttachmentM = new EstimateAttachmentModel;
        $lines = $estimateLineM->where(['estimate_id' => $estimate['id']]);
        $attachments = $estimateAttachmentM->where(['estimate_id' => $estimate['id']]);
        $estimate['lines'] = $lines;
        $estimate['attachments'] = $attachments;
        $this->renderJson($estimate);
    }

    public function update() {
        $cons = [
            'id' => $this->data['id']
        ];
        $estimateModel = new EstimateModel();
        $estimateLineModel = new EstimateLineModel();
        $updateData = $this->data;
        $estimate = ORM::forTable('estimates')->findOne($updateData['id']);

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
        $data_connect = $this->getdata();
        $sync = new Asynchronzier($data_connect);
        $params = $sync->decodeEstimate($updateData);
        $result = $sync->Update($params);
        $data_result = $sync->parseEstimate($result, $updateData);

        // Start sync lines
        $data_line_sync = [];
        foreach ($data_result['line'] as $line) {
            $result_line = $sync->parseEstimate_line($line, $data_result['id']);
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
                'message' => 'Error while saving estimate'
            ]);
        }
    }

    public function uploadAttachment() {
        if (isset($this->data['id']) && isset($_FILES['file'])) {
            $uploadedFile = $_FILES['file'];
            $estAtM = new EstimateAttachmentModel;
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
        $estAtM = new EstimateAttachmentModel;
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
        $estimate = ORM::forTable('estimates')
            ->findOne($estimateId);

        $customer = ORM::forTable('customers')
            ->findOne($estimate->customer_id);

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
        $estimate = ORM::forTable('estimates')
            ->findOne($estimateId);
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
        $pdfPath = ERP_ROOT_DIR . '/tmp/' . 'estimate-' . $estimateId . time() . '.pdf';
        file_put_contents($pdfPath, $dompdf->output());
        $preference = ORM::forTable('preferences')->findOne();
        $STMPSetting = new SMTPSetting(
            $preference['gmail_username'],
            $preference['gmail_password'],
            $preference['gmail_server'],
            $preference['gmail_port']
        );
        $mailer = new ERPMailer($STMPSetting);

        if(isset($this->data['to'])) {
            $to = $this->data['to'];
        } else {
            $to = $this->data['email'];
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
            ]
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
}
?>
