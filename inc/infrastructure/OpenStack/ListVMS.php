<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
$err = openStackConnect();
if ($err){
    echo json_encode($err);
    exit;
}
updateHypervisorList();
updateVmList();
slash_vars();
$vmArray = get_SQL_array("SELECT vms.id, vms.name, vms.machine_type, vms.source_volume, vms.snapshot, vms.maintenance, vms.filecopy, vms.state, 
vms.spice_password, vms.clientid, vms.lastused, vms.os_type, vms.locked, vms.osHypervisorName, vms.osInstanceName, vms.osInstanceId, vms2.name 
AS source_volume_machine FROM `vms` vms left JOIN vms vms2 ON vms.source_volume=vms2.id WHERE vms.machine_type != 'ephemeralvdi' ORDER BY vms.name");
//SELECT vms.name,vms2.name AS source_volume_machine FROM `vms` vms1 left JOIN vms vms2 ON vms1.source_volume=vms2.id 
echo json_encode($vmArray);

