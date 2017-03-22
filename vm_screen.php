<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.

Vilnius,Lithuania.
2017-03-22
*/
include('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_GET['vm'];
$hypervisor=$_GET['hypervisor'];
set_lang();
if (empty($vm)||empty($hypervisor) && $engine != 'OpenStack'){
    exit;
}
if ($engine != 'OpenStack'){
    drawVMScreen($vm, $hypervisor);
}
else {
    drawVMScreen($vm);
}


