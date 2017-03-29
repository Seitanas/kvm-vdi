<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    exit;
}
slash_vars();
$vm_id = $_POST['vm_id'];
if (!empty ($vm_id))
   echo GetVMInfo($vm_id);
