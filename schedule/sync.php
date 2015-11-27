<?php
require_once 'bootstrap.php';

$prefs = ORM::forTable('preferences')->findOne();
if (!$prefs) {
    exit;
}
if (ERPConfig::isOAuthTokenValid()) {
    $loger = new ERPLogger('sync.log');
    $loger->log("\n\n=== Sync job started at: " . date('Y-m-d H:i:s') . " ===");
    $jobStartAt = time();
    // Check to force to sync even is_synchronizing flag is true
    // If the lastest sync was so long
    if (!$prefs->is_synchronizing || SyncHistoryModel::isSoLongNotSync()) {
        $prefs->is_synchronizing = true; // Lock
        $prefs->save();
        $syncHistory = ORM::forTable('sync_histories')->create();
        $syncHistory->start_at = date('Y-m-d H:i:s');
        $syncHistory->status = 'In-progress';
        $syncHistory->save();

        $now = date("Y-m-d H:i:s");
        $syncFromTime = null;
        if ($prefs->last_sync_at) {
            $syncFromTime = date("c", strtotime($prefs->last_sync_at));
        }
        try {
            $syncService = Asynchronzier::getInstance();
            $syncService->syncProductService($syncFromTime);
            $syncService->syncClass($syncFromTime);
            $syncService->syncEmployee($syncFromTime);
            $syncService->syncCustomer($syncFromTime);
            $syncService->syncAttachment($syncFromTime);
            $syncService->syncEstimate($syncFromTime);

            $syncHistory->end_at = date('Y-m-d H:i:s');
            $syncHistory->status = 'Success';
            $prefs->last_sync_at = $now;
        } catch(\Exception $e) {
            $loger->log("Sync job error: " . $e->getMessage());
            $loger->log($e->getTraceAsString());
            $syncHistory->end_at = date('Y-m-d H:i:s');
            $syncHistory->status = 'Error';
            $syncHistory->note = $e->getMessage();
        } finally {
            if (ORM::getDB()->inTransaction()) {
                ORM::getDB()->rollBack();
            }
            $prefs->is_synchronizing = false;
            $prefs->save();
            $syncHistory->save();
        }
    } else {
        $loger->log("Cancelled! Another instance is running");
    }
    $loger->log("=== Finished sync job. Taken: " . ( time() - $jobStartAt) . " secs ===");
}
?>
