<?php
include ('../../functions/config.php');
require_once('../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$password1 = $_POST['password1'];
$password2 = $_POST['password2'];
if (!empty($password1) && !empty($password2)){
    $id = $_SESSION['userid'];
    $cryptpw = crypt($password1,$salt);
    add_SQL_line("UPDATE users SET password = '$cryptpw' WHERE id = '$id'");
}
else{
    echo json_encode(array('error' => _('Passwords do not match!')));
}
echo json_encode(array('success' => _('Password changed successfully.')));
exit;
?>
