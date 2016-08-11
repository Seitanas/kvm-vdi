<?php
/*
KVM-VDI
Tadas Ustinavičius
2016-08-08
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_client_session()){
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
$token=str_replace('/','',$token);
$value=str_replace('/','',$value);
file_put_contents("/tmp/kvm-vdi/$token","$token: $value");
echo "OK";
exit;