<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$vm_id = $_POST['vm_id'];
$power_state = $_POST['power_state'];
if (!empty ($vm_id) && !empty ($power_state))
   echo vmPowerCycle($vm_id, $power_state);