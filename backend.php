<?php
include("functions/config.php");
require_once('functions/functions.php');
slash_vars();
$pass=$_POST['pass'];
if (isset($_POST['vm']))
    $vm=$_POST['vm'];
if (isset($_POST['spice_password']))
    $spice_password=$_POST['spice_password'];
if ($pass==$backend_pass){
    $data=$_POST['data'];
    if (isset($vm)&&isset($data)){
#        file_put_contents("tmp/".$tmpname.".txt", $data);
	if ($data<100)
	    add_SQL_line("UPDATE vms SET filecopy='$data' WHERE id='$vm'");
	else
	    add_SQL_line("UPDATE vms SET filecopy='' WHERE id='$vm'");
    }
    if(isset($vm)&&isset($spice_password)){
	$spice_password=$_POST['spice_password'];
	add_SQL_line("UPDATE vms SET spice_password='$spice_password' WHERE name='$vm'");
	$v_reply=get_SQL_line("SELECT snapshot FROM vms WHERE name='$vm'");
	echo $v_reply[0];
    }
}
?>