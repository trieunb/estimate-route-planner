<?php
class SyncHistoryModel extends BaseModel {

    const MAX_WAITING_TIME_FOR_LAST_SYNC = 600; // in seconds == 10 minutes

    public function getTableName() {
        return 'sync_histories';
    }

    public static function isSoLongNotSync() {
        $lastSync = ORM::forTable('sync_histories')
            ->orderByDesc('start_at')
            ->findOne();
        if (!$lastSync) {
            return true;
        } else {
            return (time() - strtotime($lastSync->start_at)) >= self::MAX_WAITING_TIME_FOR_LAST_SYNC;
        }
    }
}
?>
