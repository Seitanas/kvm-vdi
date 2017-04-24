<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$vmArray = getSQLarray("SELECT vms.id, vms.name, vms.maintenance, vms.filecopy, vms.state,
vms.spice_password, vms.os_type, vms.locked, vms.osHypervisorName, vms.osInstanceName, vms.osInstanceId, vms2.name
AS ephemeral_machine_name, vms2.clientid, vms2.osInstanceId AS ephemeral_osInstanceId, vms2.lastused FROM `vms` vms left JOIN vms vms2 ON vms.id=vms2.source_volume WHERE vms.machine_type = 'vdimachine' AND vms.maintenance != 'true'
AND (vms2.osInstanceId IS NULL OR (vms2.lastused < DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE) AND vms2.lastused != '0')) ORDER BY vms.name");


#print_r($vmArray);
echo json_encode($vmArray);