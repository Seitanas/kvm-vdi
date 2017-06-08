<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$vm = $_POST['vm'];
reload_vm_info();
if (isset($_POST['is_parent'])){
    if ($_POST['is_parent'] == 1){
        $vm_info = getSQLArray("SELECT id, state FROM vms WHERE source_volume = '$vm'");
        $x=0;
        $vm_child = array();
        while ($x < sizeof($vm_info)){
            $vm_child[$x] = array('id' => $vm_info[$x]['id'], 'state' => $vm_info[$x]['state'], 'state_html' => drawStateInfo($vm_info[$x]['state']));
            ++$x;
        }
        echo json_encode($vm_child);
    }
    else{
        $vm_info = getSQLArray("SELECT id,state FROM vms WHERE id = '$vm' LIMIT 1");
        echo json_encode(array('id' => $vm_info[0]['id'], 'state' => $vm_info[0]['state'], 'state_html' => drawStateInfo($vm_info[0]['state'])));
    }
}

