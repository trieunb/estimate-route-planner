<?php
require_once 'bootstrap.php';
if (ERPConfig::checkQuickbookAuthenticated()) {
    $loger = new ERPLogger('sync_job.log');
    $loger->log("== Start sync at: " . date('Y-m-d H:i:s'));
    $syncHistory = ORM::forTable('sync_histories')->create();
    $syncHistory->start_at = date('Y-m-d H:i:s');
    $syncHistory->status = 'Inprogress';
    $syncHistory->save();
    try {
        $syncService = new Asynchronzier(PreferenceModel::getQuickbooksAPIConnectionInfo());
        $syncService->syncAll();
        $endAt = date('Y-m-d H:i:s');
        $syncHistory->end_at = $endAt;
        $syncHistory->status = 'Done';
        $syncHistory->save();
        $prefs = ORM::forTable('preferences')->findOne();
        if ($prefs) {
            $prefs->last_sync_at = $endAt;
            $prefs->save();
        }
    } catch(Exception $e) {
        $loger->log("Sync job error: " . $e->getMessage());
        $loger->log($e->getTraceAsString());
        $syncHistory->end_at = date('Y-m-d H:i:s');
        $syncHistory->status = 'Error';
        $syncHistory->save();
    }
    $loger->log("== Finish sync at: " . date('Y-m-d H:i:s'));
}
?>
