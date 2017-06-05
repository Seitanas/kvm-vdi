<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$vm = $_POST['vm'];
$lock = $_POST['lock'];
add_SQL_line("UPDATE vms SET locked='$lock' WHERE id='$vm' LIMIT 1");
echo json_encode(array('success' => _('VM lock updated')));
