<?php
class AttachmentModel extends BaseModel {

    protected $fillable = [
        'id',
        'estimate_id',
        'size',
        'sync_token',
        'content_type',
        'access_uri',
        'tmp_download_uri',
        'file_name',
        'is_customer_signature',
        'created_at',
        'last_updated_at'
    ];

    public function getTableName() {
        return 'estimate_attachments';
    }

    public function uploadSignature($estimateId, $content) {
        $sync = Asynchronzier::getInstance();
        $attachableObj = $this->_uploadToQB(
            $estimateId,
            $content,
            'customer-signature.png',
            'image/png'
        );
        $attachmentData = $sync->parseAttachment($attachableObj);
        $attachmentData['is_customer_signature'] = true;
        $this->insert($attachmentData);
        return $attachmentData;
    }

    /**
     * Upload attachment to given estimate id
     * @return array
     */
    public function upload($estimateId, $uploadedFile) {
        $sync = Asynchronzier::getInstance();
        $attachableObj = $this->_uploadToQB(
            $estimateId,
            file_get_contents($uploadedFile['tmp_name']),
            $uploadedFile['name'],
            $uploadedFile['type']
        );
        $attachmentData = $sync->parseAttachment($attachableObj);
        $this->insert($attachmentData);
        return $attachmentData;
    }

    private function _uploadToQB($estimateId, $fileContent, $fileName, $mimeType) {
        $sync = Asynchronzier::getInstance();
        $entityRef = new IPPReferenceType(['value' => $estimateId, 'type' => 'Estimate']);
        $attachableRef = new IPPAttachableRef(['EntityRef' => $entityRef]);

        $objAttachable = new IPPAttachable();
        $objAttachable->FileName = $fileName;
        $objAttachable->AttachableRef = $attachableRef;
        $objAttachable->Category = 'Image';

        $resultObj = $sync->Upload(
            $fileContent,
            $objAttachable->FileName,
            $mimeType,
            $objAttachable
        );
        return $resultObj->Attachable;
    }

    public function delete($id) {
        $attachment = ORM::forTable('estimate_attachments')->findOne($id);
        if ($attachment) {
            $objAttachable = new IPPAttachable();
            $objAttachable->Id = $id;
            $sync = new Asynchronzier(PreferenceModel::getQuickbooksCreds());
            try {
                // Delete from quickbooks
                $sync->Delete($objAttachable);
            } catch(IdsException $e) {
                // Do nothing
            } finally {
                // Delete from local database
                $attachment->delete();
                return $attachment;
            }
        } else {
            return null;
        }
    }
}
?>
