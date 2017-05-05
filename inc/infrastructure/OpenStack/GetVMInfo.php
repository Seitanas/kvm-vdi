<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$vm_id = $_POST['vm_id'];
if (!empty ($vm_id))
   echo json_encode(GetVMInfo($vm_id));
