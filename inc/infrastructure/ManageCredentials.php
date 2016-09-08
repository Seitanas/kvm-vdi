<?php
include ('../../functions/config.php');
require_once('../../functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$type='';
$credentialtype='';
if (isset($_POST['credentialtype']))
    $credentialtype=$_POST['credentialtype'];
if (isset($_POST['type']))
    $type=$_POST['type'];
if ($type=='new'){
    if (!empty($_POST['username']))
        $username=$_POST['username'];
    else{
        echo  'EMPTY_USER';
        exit;
    }
    if (!empty($_POST['password']))
        $password=$_POST['password'];
    else{
	echo  'EMPTY_PW';
	exit;
    }
    if ($credentialtype=='user')
        $existing=get_SQL_line("SELECT id FROM users WHERE username = '$username'");
    if ($credentialtype=='client')
	$existing=get_SQL_line("SELECT id FROM clients WHERE username = '$username'");
    if (!empty($existing[0])){
        echo 'EXISTS';
        exit;
    }
    $password=crypt($password,$salt);
    if ($credentialtype=='user')
	add_SQL_line("INSERT INTO users (username,password) VALUES ('$username','$password')");
    if ($credentialtype=='client')
	add_SQL_line("INSERT INTO clients (username,password) VALUES ('$username','$password')");
    echo 'SUCCESS';
    exit;
}
if ($type=='update-pw'){//using x-editable jQuery plugin, which uses different param naming
    $password=$_POST['password'];
    $id=$_POST['id'];
    $password=crypt($password,$salt);
    if ($credentialtype=='user')
	add_SQL_line("UPDATE users SET password='$password' WHERE id='$id' LIMIT 1");
    if ($credentialtype=='client')
	add_SQL_line("UPDATE clients SET password='$password' WHERE id='$id' LIMIT 1");
    exit;
}
if ($type=='delete'){
    $credid=$_POST['credid'];
    foreach ($credid as $id){
	if ($credentialtype=='user')
    	    add_SQL_line("DELETE FROM users WHERE id='$id' LIMIT 1");
	if ($credentialtype=='client')
	    add_SQL_line("DELETE FROM clients WHERE id='$id' LIMIT 1");
	if ($credentialtype=='adgroup'){
	    add_SQL_line("DELETE FROM poolmap_ad WHERE groupid='$id'");
	    add_SQL_line("DELETE FROM ad_groups WHERE id='$id' LIMIT 1");
	}
	if ($credentialtype=='pool'){
	    add_SQL_line("DELETE FROM poolmap WHERE poolid='$id'");
	    add_SQL_line("DELETE FROM poolmap_ad WHERE poolid='$id'");
	    add_SQL_line("DELETE FROM poolmap_vm WHERE poolid='$id'");
	    add_SQL_line("DELETE FROM pool WHERE id='$id' LIMIT 1");
	}


    }
}
