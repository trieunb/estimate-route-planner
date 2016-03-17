<?php
/**
 * CLI agruments
 *   -m, --mode The sync mode: normal(default), reset(ignore SyncToken, just sync from scratch)
 */
// define('NORMAL_SYNC_MODE', 'normal');
// define('RESET_SYNC_MODE', 'reset');
//
// $args = getopt('m:f:');
// var_dump($args);
//
// function erp_get_agrument($key, $defaultValue) {
//     if (isset($args['m'])) {
//         return $args['m'];
//     } else {
//         return $defaultValue;
//     }
// }
//
// $syncOpts['mode'] = erp_get_agrument('m', NORMAL_SYNC_MODE);
// $syncOpts['force'] = erp_get_agrument('m', false);
// var_dump($syncOpts);

require_once 'bootstrap.php';

$prefs = ORM::forTable('preferences')->findOne();
if (!$prefs) {
    exit;
}
if (ERPConfig::isOAuthTokenValid()) {
    $startSyncAt = date('Y-m-d H:i:s');
    $syncFromTime = null;
    if ($prefs->last_sync_at) {
        $syncFromTime = date("c", strtotime($prefs->last_sync_at));
    }
    try {
        Asynchronzier::getInstance()->start($syncFromTime);
        $prefs->last_sync_at = $startSyncAt;
        $prefs->save();
    } catch(\Exception $e) {
        // Do nothing
    }
}
?>
