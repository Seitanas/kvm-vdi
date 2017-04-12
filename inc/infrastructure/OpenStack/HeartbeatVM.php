<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_client_session()){
    exit;
}
slash_vars();
$vm_id=$_POST['vm_id'];
if (empty($vm_id)){
    exit;
}
$userid=$_SESSION['userid'];
$v_reply=get_SQL_array("SELECT * FROM vms WHERE osInstanceId = '$vm_id'");
if ($v_reply[0]['clientid'] != $userid)//allow only clients, which were given current VM to modify heartbeats
    exit;
add_SQL_line("UPDATE vms SET lastused=NOW() WHERE osInstanceId='$vm_id'");
exit;
?>
