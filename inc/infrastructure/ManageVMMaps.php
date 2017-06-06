<?php
include dirname(__FILE__) . '/../../functions/config.php';
require_once(dirname(__FILE__) . '/../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
set_lang();
$poolid = addslashes($_POST['poolid']);
$vmlist = $_POST['vmlist'];
$vmlist = explode(",",$vmlist);
$vm_count = sizeof($vmlist);
$x=0;
$vmid = addslashes($vmlist[0]);
if ($vm_count && $poolid)
    add_SQL_line("DELETE FROM poolmap_vm WHERE poolid='$poolid'");
while ($vm_count >= $x){
    $vmid = addslashes($vmlist[$x]);
    if (!empty($vmid))
        add_SQL_line("INSERT INTO poolmap_vm (poolid, vmid) SELECT * FROM (SELECT '$poolid' AS pool, '$vmid' AS vm) AS tmp WHERE NOT EXISTS (SELECT vmid FROM poolmap_vm WHERE poolid = '$poolid' AND vmid='$vmid') LIMIT 1;");
    ++$x;
}
echo json_encode(array('success' => _('Updated successfully')));
?>