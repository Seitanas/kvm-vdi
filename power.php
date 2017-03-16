<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-03-16
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_GET['vm'];
$hypervisor=$_GET['hypervisor'];
$action=$_GET['action'];
if ($engine != 'OpenStack'){
    if (empty($vm) || empty($hypervisor)){
        exit;
    }
    vmPowerCycle($hypervisor, $vm, $action);
    header("Location: $serviceurl/reload_vm_info.php");
    exit;
}
else
    vmPowerCycle($vm, $action);
