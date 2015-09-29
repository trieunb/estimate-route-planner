<?php

class AttachmentController extends BaseController {

    public function show() {
        $id = $_REQUEST['id'];
        $sync = Asynchronzier::getInstance();
        $objAttachable = new IPPAttachable();
        $objAttachable->Id = $id;
        try {
            $attachmentObj = $sync->Retrieve($objAttachable);
            $tmpDownloadURI = $attachmentObj->TempDownloadUri;
            $this->redirect($tmpDownloadURI);
        } catch (Exception $e) {
            $this->render('Attachment not found or has been deleted', 404);
        }
    }
}

?>
