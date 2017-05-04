<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-05-04
Vilnius, Lithuania.
*/
include dirname(__FILE__) . '/../../functions/config.php';
require_once(dirname(__FILE__) . '/../../functions/functions.php');
if (!check_client_session()&&!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$token=$_POST['token'];
$value=$_POST['value'];
if (empty($token)||empty($value)){
    exit;
}
if (!file_exists('/tmp/kvm-vdi/')) {
    mkdir('/tmp/kvm-vdi', 0777, true);
}
$token=remove_specialchars($token);
$value=str_replace('/','',$value);
$value=str_replace(' ','',$value);
$value=str_replace('$','',$value);
$value=str_replace('(','',$value);
$value=str_replace('"','',$value);
$value=str_replace("'",'',$value);
$value=str_replace(')','',$value);
file_put_contents("/tmp/kvm-vdi/$token","$token: $value");
echo "OK";
exit;