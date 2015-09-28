<?php
/**
* Execute the submitted code, then return output and SQL queries in JSON
* @author: Lht
*/

header("content-type: application/json");
require 'bootstrap.php';
ob_get_clean();
ob_start();

$code = stripslashes($_REQUEST['code']);
$output = '';
try {
    // Execute the code
    eval($code);
    // Collect outputs and errors
    $output = ob_get_clean();
    if(error_get_last()) {
        $e = error_get_last();
        $output = $e['message'];
    }
} catch(Exception $e) {
    $output = $e->getTraceAsString();
}
$queries = ORM::get_query_log();
echo json_encode([
    'output' => $output,
    'sqls'   => $queries
]);
?>
