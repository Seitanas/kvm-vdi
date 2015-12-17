<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2015-12-17
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}

header("Location: $serviceurl/reload_vm_info.php");
exit;
?>
