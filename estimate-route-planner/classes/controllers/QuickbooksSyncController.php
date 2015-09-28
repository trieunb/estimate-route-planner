<?php
class QuickbooksSyncController extends BaseController {

    public function getSetting() {
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs) {
            $this->renderJson([
                'qbo_consumer_key'      => $prefs->qbo_consumer_key,
                'qbo_consumer_secret'   => $prefs->qbo_consumer_secret,
                'last_sync_at'          => $prefs->last_sync_at,
            ]);
        } else {
            $this->renderJson([
                'qbo_consumer_key' => '',
                'qbo_consumer_secret' => ''
            ]);
        }
    }

    public function saveSetting() {
        $prefs = ORM::forTable('preferences')->findOne();
        if (!$prefs) {
            $prefs = ORM::forTable('preferences')->create();
        }
        $prefs->qbo_consumer_key = $this->data['qbo_consumer_key'];
        $prefs->qbo_consumer_secret = $this->data['qbo_consumer_secret'];
        if ($prefs->save()) {
            $this->renderJson([
                'success' => true,
                'message' => 'Updated successfully, please reconnect'
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => "Failed to updated synchronize settings"
            ]);
        }
    }

    public function syncAll() {
        try {
            $sync = new Asynchronzier(PreferenceModel::getQuickbooksAPIConnectionInfo());
            $sync->syncAll();
            $prefs = ORM::forTable('preferences')->findOne();
            $syncAt = date('Y-m-d H:i:s');
            if ($prefs) {
                $prefs->last_sync_at = $syncAt;
                $prefs->save();
            }
            $this->renderJson([
                'success' => true,
                'message' => "Sync successfully",
                'data'    => ['last_sync_at' => $syncAt]
            ]);
        } catch(Exception $e) {
            $this->renderJson([
                'success' => false,
                'message' => "An error has occurred while sync data"
            ]);
        }
    }
}
?>
