<?php
include ('../../functions/config.php');
require_once('../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
if (!empty($_POST['poolname']))
$poolname = $_POST['poolname'];
else{
    echo json_encode(array('error' => _('Empty pool name')));
    exit;
}
$existing = get_SQL_line("SELECT id FROM pool WHERE name = '$poolname'");
if (!empty($existing[0])){
    echo json_encode(array('error' => _("Pool $poolname already exists")));
    exit;
}
add_SQL_line("INSERT INTO pool (name) VALUES ('$poolname')");
echo json_encode(array('success' => _("Sucessfully created $poolname")));
exit;
