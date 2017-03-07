<?php
include dirname(__FILE__) . '/../../functions/config.php';
require_once(dirname(__FILE__) . '/../../functions/functions.php');
if (!check_session()){
    exit;
}
$list_type=$_GET['type'];
slash_vars();
if ($list_type=='ALL'){
    $vm_array=get_SQL_array("SELECT vms.id, vms.name FROM vms ORDER BY name");
}
echo json_encode($vm_array);
?>
