<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    exit;
}
slash_vars();
$source = $_POST['source'];
$vm_name = $_POST['vm_name'];
$vm_type = $_POST['vm_type'];
/*
$source = '1102a9d4-6f41-4694-a9bd-f1b3b9b796b7';
$vm_name = 'testukas';
$vm_type = 'initialmachina';
*/
if (!empty($source) && !empty($vm_name) && !empty($vm_type)){
    $source_info=json_decode(getVMInfo($source), TRUE);
    echo createVolume($source_info['server']['os-extended-volumes:volumes_attached'][0]['id'], $vm_name, $vm_type);
}
