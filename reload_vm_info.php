<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
reload_vm_info();
header("Location: $serviceurl/dashboard.php");
exit;
?>
