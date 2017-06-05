<?php 

/*
KVM-VDI
Tadas Ustinavičius

Vilnius,Lithuania.
2017-06-05
*/
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
set_lang();
reload_vm_info();
$userConfig=get_userconf();
draw_dashboard_table();

