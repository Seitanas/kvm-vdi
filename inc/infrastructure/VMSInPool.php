<?php
include ('../../functions/config.php');
require_once('../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$side=$_GET['side'];
$poolid=$_GET['poolid'];
if(!empty($poolid))
    $client_array=get_SQL_array("SELECT vms.id, vms.name FROM vms LEFT JOIN poolmap_vm ON vms.id=poolmap_vm.vmid WHERE poolmap_vm.poolid='$poolid' ORDER BY id");
if ($side=='from'){
    if (isset($_GET['list_non_vdi_vms'])){
        if ($_GET['list_non_vdi_vms']==1)
            $client_array_full=get_SQL_array("SELECT vms.id,vms.name FROM vms LEFT JOIN poolmap_vm ON vms.id=poolmap_vm.vmid WHERE vms.id NOT IN (SELECT poolmap_vm.vmid FROM (poolmap_vm)) AND vms.machine_type<>'vdimachine' ORDER BY id");
        else 
            $client_array_full=get_SQL_array("SELECT vms.id,vms.name FROM vms LEFT JOIN poolmap_vm ON vms.id=poolmap_vm.vmid WHERE vms.id NOT IN (SELECT poolmap_vm.vmid FROM (poolmap_vm)) AND vms.machine_type='vdimachine' ORDER BY id");
        }
    if (!empty ($client_array)){
    $clients= array_diff ($client_array_full,$client_array);
    foreach($client_array_full as $aV){
            $aTmp1[] = $aV['id'];
            $aTmp1[] = $aV['name'];
    }
    foreach($client_array as $aV){
            $aTmp2[] = $aV['id'];
            $aTmp2[] = $aV['name'];
    }
    $tmp_array = array_diff($aTmp1,$aTmp2);
    $tmp=$mode = current($tmp_array);
    $x=0;
    $client_array=array();
    while ($tmp){
            $client_array[$x]['id']=$tmp;
            $tmp = next($tmp_array);
            $client_array[$x]['name']=$tmp;
            $tmp = next($tmp_array);
            ++$x;
        }
    }
    else{
        unset($client_array);
        $client_array=$client_array_full;
    }
}
echo json_encode($client_array);
?>
