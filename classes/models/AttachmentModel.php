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
        'created_at',
        'last_updated_at'
    ];

    public function getTableName() {
        return 'estimate_attachments';
    }
    /**
     * Upload attachment to given estimate id
     * @return array
     */
    public function upload($estimateId, $file) {
        $sync = new Asynchronzier(PreferenceModel::getQuickbooksCreds());
        $fileContent = file_get_contents($file['tmp_name']);
        $fileBase64Encoded = base64_encode($fileContent);
        $mimeType = $file['type'];

        $entityRef = new IPPReferenceType(['value' => $estimateId, 'type' => 'Estimate']);
        $attachableRef = new IPPAttachableRef(['EntityRef' => $entityRef]);

        $objAttachable = new IPPAttachable();
        $objAttachable->FileName = $file['name'];
        $objAttachable->AttachableRef = $attachableRef;
        $objAttachable->Category = 'Image';

        $resultObj = $sync->Upload(
            base64_decode($fileBase64Encoded),
            $objAttachable->FileName,
            $mimeType,
            $objAttachable
        );
        $attachmentData = $sync->parseAttachment($resultObj->Attachable);
        $this->insert($attachmentData);

        // Update estimate sync token and updated at
        $objEstimate = new IPPEstimate();
        $objEstimate->Id = $estimateId;
        $estimateEntity = $sync->Retrieve($objEstimate);
        $estimateLocal = ORM::forTable('estimates')->findOne($estimateId);
        if ($estimateLocal) {
            $estimateLocal->last_updated_at =
                date("Y-m-d H:i:s", strtotime($estimateEntity->MetaData->LastUpdatedTime));
            $estimateLocal->sync_token = $estimateEntity->SyncToken;
            $estimateLocal->save();
        }
        return $attachmentData;
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

                // Update estimate sync token and updated at
                $objEstimate = new IPPEstimate();
                $objEstimate->Id = $attachment->estimate_id;
                $estimateEntity = $sync->Retrieve($objEstimate);
                $estimateLocal = ORM::forTable('estimates')->findOne($attachment->estimate_id);
                if ($estimateLocal) {
                    $estimateLocal->last_updated_at =
                        date("Y-m-d H:i:s", strtotime($estimateEntity->MetaData->LastUpdatedTime));
                    $estimateLocal->sync_token = $estimateEntity->SyncToken;
                    $estimateLocal->save();
                }
            } catch(IdsException $e) {
                // Do nothing
            } finally {
                // Delete from local database
                $attachment->delete();
                return true;
            }
        } else {
            return false;
        }

    }

}
?>
