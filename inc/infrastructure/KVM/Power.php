<?php
/*
KVM-VDI
Tadas Ustinavičius

2017-06-08
Vilnius, Lithuania.
*/
include ('../../../functions/config.php');
require_once('../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$vm = $_POST['vm'];
$hypervisor = $_POST['hypervisor'];
$action = $_POST['action'];
$state = '';
if (isset($_POST['state']))
    $state = $_POST['state'];
if (empty($vm) || empty($hypervisor)){
    echo json_encode(array('error' => _('Missing values.')));
    exit;
}
vmPowerCycle($hypervisor, $vm, $action, $state);
echo json_encode(array('success' => _('Power cycle updated.')));
exit;
