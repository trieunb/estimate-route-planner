<?php
class QuickbooksSyncController extends BaseController {

    public function getInfo() {
        $prefs = ORM::forTable('preferences')->findOne();
        $syncHistories = ORM::forTable('sync_histories')
            ->orderByDesc('id')
            ->limit(5)
            ->findArray();
        if ($prefs) {
            $this->renderJson([
                'qbo_consumer_key'      => $prefs->qbo_consumer_key,
                'qbo_consumer_secret'   => $prefs->qbo_consumer_secret,
                'qbo_company_id'        => $prefs->qbo_company_id,
                'last_sync_at'          => $prefs->last_sync_at,
                'qbo_token_expires_at'  => $prefs->qbo_token_expires_at,
                'sync_histories'        => $syncHistories
            ]);
        } else {
            $this->renderJson(json_decode("{}"));
        }
    }                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     

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
        if (!ERPConfig::isOAuthTokenValid()) {
            $this->renderJson([
                'success' => false,
                'message' => "OAuth tokens was not configured or expires!",
            ]);
        }
        $prefs = ORM::forTable('preferences')->findOne();
        try {
            $jobStartAt = time();
            $loger = new ERPLogger('sync.log');
            $loger->log("\n\n=== Sync job started at: " . date('Y-m-d H:i:s') . " ===");
            if (!$prefs->is_synchronizing || SyncHistoryModel::isSoLongNotSync()) {
                $startSyncAt = date('Y-m-d H:i:s');
                $syncFromTime = null;
                try {
                    if ($prefs->last_sync_at) {
                        $syncFromTime = date("c", strtotime($prefs->last_sync_at));
                    }
                    $prefs->is_synchronizing = true;
                    $prefs->save();
                    $syncHistory = ORM::forTable('sync_histories')->create();
                    $syncHistory->start_at = date('Y-m-d H:i:s');
                    $syncHistory->status = 'In-progress';
                    $syncHistory->save();

                    $syncService = Asynchronzier::getInstance();
                    // $syncService->syncCustomer($syncFromTime);
                    // $syncService->syncEmployee($syncFromTime);
                    $syncService->syncEstimate($syncFromTime);
                    // $syncService->syncProductService($syncFromTime);
                    // $syncService->syncAttachment($syncFromTime);

                    // $prefs->last_sync_at = $startSyncAt;
                    $prefs->is_synchronizing = false;
                    $prefs->save();

                    $syncHistory->end_at = date('Y-m-d H:i:s');
                    $syncHistory->status = 'Success';
                    $syncHistory->save();
                    $this->renderJson([
                        'success' => true,
                        'message' => "Sync successfully",
                        'data'    => [
                            'last_sync_at' => $startSyncAt
                        ]
                    ]);
                } catch(Exception $e) {
                    $prefs->is_synchronizing = false;
                    $prefs->save();
                    $loger->log("Sync job error: " . $e->getMessage());
                    $loger->log($e->getTraceAsString());
                    $syncHistory->end_at = date('Y-m-d H:i:s');
                    $syncHistory->status = 'Error';
                    $syncHistory->note = $e->getMessage();
                    $syncHistory->save();
                    $this->renderJson([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ]);
                } finally {
                    $loger->log("=== Finished sync job. Taken: " . ( time() - $jobStartAt) . " secs ===");
                }
            } else {
                $this->renderJson([
                    'success' => false,
                    'message' => "The synchronize process is running."
                ]);
            }
        } catch(Exception $e) {
            $this->renderJson([
                'success' => false,
                'message' => "An error has occurred while sync data"
            ]);
        }
    }

    public function reconnect() {
        $currentUser = wp_get_current_user();
        if ($currentUser && in_array('administrator', $currentUser->roles)) {
            $prefs = ORM::forTable('preferences')->findOne();
            $prefs->last_sync_at = null;
            $prefs->qbo_token_expires_at = null;
            $prefs->qbo_company_id = null;
            $prefs->qbo_oauth_token = null;
            $prefs->qbo_oauth_secret = null;
            $prefs->is_synchronizing = false;
            $prefs->save();
            $this->renderJson([
                'success' => true
            ]);
        } else {
            $this->renderJson([
                'success' => false,
                'message' => "You dont have permission to perform this action!"
            ]);
        }
    }
}
?>
