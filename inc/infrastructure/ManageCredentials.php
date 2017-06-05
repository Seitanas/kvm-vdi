<?php
include ('../../functions/config.php');
require_once('../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$type='';
$credential_type='';
if (isset($_POST['credential_type']))
    $credential_type = $_POST['credential_type'];
if (isset($_POST['type']))
    $type=$_POST['type'];
if ($type == 'new'){
    if (!empty($_POST['username']))
        $username = $_POST['username'];
    else{
        echo json_encode(array('error' => _('Empty username.')));
        exit;
    }
    if (!empty($_POST['password']))
        $password = $_POST['password'];
    else{
        echo json_encode(array('error' => _('Empty password.')));
        exit;
    }
    if ($credential_type == 'user')
        $existing = get_SQL_line("SELECT id FROM users WHERE username = '$username'");
    if ($credential_type == 'client')
        $existing = get_SQL_line("SELECT id FROM clients WHERE username = '$username'");
    if (!empty($existing[0])){
        echo json_encode(array('error' => _('User already exists.')));
        exit;
    }
    $password = crypt($password,$salt);
    if ($credential_type == 'user')
        add_SQL_line("INSERT INTO users (username,password) VALUES ('$username','$password')");
    if ($credential_type == 'client')
        add_SQL_line("INSERT INTO clients (username,password) VALUES ('$username','$password')");
    echo json_encode(array('success' => _('Credential created successfully')));
    exit;
}
if ($type == 'update-pw'){//using x-editable jQuery plugin, which uses different param naming
    $password = $_POST['password'];
    $id=$_POST['id'];
    $password = crypt($password,$salt);
    if ($credential_type == 'user')
        add_SQL_line("UPDATE users SET password='$password' WHERE id='$id' LIMIT 1");
    if ($credential_type == 'client')
        add_SQL_line("UPDATE clients SET password='$password' WHERE id='$id' LIMIT 1");
    echo json_encode(array('success' => _('Password changed successfully')));
    exit;
}
if ($type == 'delete'){
    $credid = $_POST['credid'];
    foreach ($credid as $id){
        if ($credential_type == 'user')
            add_SQL_line("DELETE FROM users WHERE id='$id' LIMIT 1");
        if ($credential_type == 'client')
            add_SQL_line("DELETE FROM clients WHERE id='$id' LIMIT 1");
        if ($credential_type == 'adgroup'){
            add_SQL_line("DELETE FROM poolmap_ad WHERE groupid='$id'");
            add_SQL_line("DELETE FROM ad_groups WHERE id='$id' LIMIT 1");
        }
        if ($credential_type == 'pool'){
            add_SQL_line("DELETE FROM poolmap WHERE poolid='$id'");
            add_SQL_line("DELETE FROM poolmap_ad WHERE poolid='$id'");
            add_SQL_line("DELETE FROM poolmap_vm WHERE poolid='$id'");
            add_SQL_line("DELETE FROM pool WHERE id='$id' LIMIT 1");
        }
    }
    echo json_encode(array('success' => _('Updated successfully')));
    exit;
}
