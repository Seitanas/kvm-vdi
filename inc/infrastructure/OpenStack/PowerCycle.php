<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    exit;
}
$vm_id = $_POST['vm_id'];
$power_state = $_POST['power_state'];
if (!empty ($vm_id) && !empty ($power_state))
   echo vmPowerCycle($vm_id, $power_state);