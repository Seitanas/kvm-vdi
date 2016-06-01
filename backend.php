<?php
include("functions/config.php");
require_once('functions/functions.php');
slash_vars();
$pass=$_POST['pass'];
if ($pass==$backend_pass){
    $tmpname=$_POST['tmpname'];
    $data=$_POST['data'];
    $vm=$_POST['vm'];
    $spice_password=$_POST['spice_password'];
    if (!empty($tmpname)&&!empty($data)){
        file_put_contents("tmp/".$tmpname.".txt", $data);
	if ($data==100){
	    sleep(4);
	    add_SQL_line("UPDATE vms SET filecopy='' WHERE filecopy='$tmpname'");
	}
    }
    if(!empty($vm)){
	add_SQL_line("UPDATE vms SET spice_password='$spice_password' WHERE name='$vm'");
	$v_reply=get_SQL_line("SELECT snapshot FROM vms WHERE name='$vm'");
	echo $v_reply[0];
    }
}
?>