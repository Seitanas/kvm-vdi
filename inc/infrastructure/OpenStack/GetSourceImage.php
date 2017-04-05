<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$vm_type = $_POST['vm_type'];
if (!empty($vm_type)){
    if ($vm_type == 'images'){
        $images = json_decode(listImages(), TRUE);
        $vm_list=array();
        foreach ($images['images'] as $image){
            array_push($vm_list,array('name' => $image['name'], 'id' => $image['id']));
        }
    }
    else 
        $vm_list = getSQLArray("SELECT name, osInstanceId AS id FROM vms WHERE machine_type='$vm_type'");
    echo json_encode($vm_list);
}