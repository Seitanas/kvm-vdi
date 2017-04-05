<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
//print_r(json_decode(listNetworks(), TRUE));
$networks = json_decode(listNetworks(), TRUE);
$network_list=array();
foreach ($networks['networks'] as $network){
    if ($network['tenant_id']) //do not list infrastructure netwoks, eg HA network tenants
        array_push($network_list,array('name' => $network['name'], 'id' => $network['id']));
}
echo json_encode($network_list);