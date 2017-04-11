<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
$password = $_POST['password'];
if ($password == $backend_pass){
    session_start();
    $_SESSION['logged']='yes';
    OpenStackConnect();
    echo json_encode(array('login' => 'success'));
}
else 
    echo json_encode(array('login' => 'failure'));