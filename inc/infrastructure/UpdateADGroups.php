<?php

include dirname(__FILE__) . '/../../functions/config.php';
require_once(dirname(__FILE__) . '/../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$type='';
if (isset($_POST['type']))
    $type=$_POST['type'];
if ($type=='new'){
    if (!empty($_POST['group_name']))
        $group_name=$_POST['group_name'];
    else{
        echo json_encode(array('error' => _('Empty group.')));
        exit;
    }
    $existing=get_SQL_line("SELECT id FROM ad_groups WHERE name = '$group_name'");
    if (!empty($existing[0])){
        echo json_encode(array('error' => _('Group already exists.')));
        exit;
    }
    add_SQL_line("INSERT INTO ad_groups (name) VALUES ('$group_name')");
    echo json_encode(array('success' => _('New group added.')));
    exit;
}
if ($type=='delete'){
    $group=$_POST['group'];
    foreach ($user as $id){
        add_SQL_line("DELETE FROM ad_groups WHERE id='$id' LIMIT 1");
    }
    echo json_encode(array('success' => _('Group deleted.')));
    exit;
}
