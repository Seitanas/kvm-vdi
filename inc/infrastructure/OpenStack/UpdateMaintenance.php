<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$vm_id = $_POST['vm_id'];
$state = $_POST['state'];
if (!empty ($vm_id) && !empty ($state))
    add_SQL_line("UPDATE vms SET maintenance = '$state' WHERE osInstanceId = '$vm_id'");