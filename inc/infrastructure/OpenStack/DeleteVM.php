<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$vm_id = $_POST['vm_id'];
if (!empty($vm_id))
    $reply = deleteVM($vm_id);
if ($reply){
    $reply = json_decode($reply, TRUE);
    if (isset($reply['itemNotFound'])){ //if machine is already deleted, just delete it from db
        add_SQL_line("DELETE FROM vms WHERE osInstanceId = '$vm_id' LIMIT 1");
        echo json_encode($reply);
        exit;
    }
}
add_SQL_line("DELETE FROM vms WHERE osInstanceId = '$vm_id' LIMIT 1");
echo json_encode(array('delete' => 'success'));
