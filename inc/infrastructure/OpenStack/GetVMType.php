<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
/*
    returns JSON: {"machine_type":"type"} e.g, {"machine_type":"vdimachine"}
*/
$password = $_POST['password'];
$vmname = $_POST['vmname'];
$vmArray = array();
if ($password == $backend_pass && $vmname){
    $vmArray = getSQLarray("SELECT machine_type FROM vms WHERE osInstanceName = '$vmname'");
    if ($vmArray[0]){
        if (!$vmArray[0]['machine_type'])
            $vmArray[0]['machine_type'] = 'simplemachine';
        echo json_encode($vmArray[0]);
    }
    else
        echo json_encode(array('machine_type' => 'none'));
}



