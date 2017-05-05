<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$source = $_POST['source'];
$vm_name = $_POST['vm_name'];
$vm_type = $_POST['vm_type'];
$volume_size = $_POST['volume_size'];
if (empty($volume_size))
    $volume_size = 0;
if (!empty($source) && !empty($vm_name) && !empty($vm_type)){
    $source_info = getVMInfo($source);
    $source = $source_info['server']['os-extended-volumes:volumes_attached'][0]['id'];
    echo createVolume($source, $vm_name, $vm_type, $volume_size);
}
