<?php
include dirname(__FILE__) . '/../../functions/config.php';
require_once(dirname(__FILE__) . '/../../functions/functions.php');
if (!check_session()){
    exit;
}
$vms=$_POST['vms'];
slash_vars();
$mac=json_encode(get_mac_address($vms));
echo $mac;