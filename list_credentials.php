<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius,Lithuania.
2017-06-05
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
if(isset ($_GET['credential_type']))
    $credential_type=$_GET['credential_type'];
set_lang();
if ($credential_type=='client')
    $cred_reply=get_SQL_array("SELECT * FROM clients WHERE isdomain=0 ORDER BY username");
if ($credential_type=='adgroup')
    $cred_reply=get_SQL_array("SELECT id, name AS username FROM ad_groups ORDER BY name");
if ($credential_type=='user')
    $cred_reply=get_SQL_array("SELECT * FROM users ORDER BY username");
if ($credential_type=='pool')
    $cred_reply=get_SQL_array("SELECT id, name AS username FROM pool ORDER BY name");
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <script src="inc/js/kvm-vdi.js"></script>
</head>
<body>
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php
            if ($credential_type=='client')
                echo _("Manage clients");
            if ($credential_type=='adgroup')
                echo _("Manage AD groups");
            if ($credential_type=='user')
                echo _("Manage administrators");
            if ($credential_type=='pool')
                echo _("Manage pools");?>
            </h4>
        </div>
        <div class="modal-body">
            <div class="row pre-scrollable credential-list-div">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <div class="row">
                        <div class="col-md-6 users-line">
                            <?php echo _("Username");?>
                        </div>
                        <div class="col-md-6 users-line"></div>
                    </div>
<?php
                    $x=0;
                    while ($x<sizeof($cred_reply)){
                        echo '<div class="row users-list" id="row-name-' . $cred_reply[$x]['id']  . '">
                                <div class="col-md-5 users-line name-' . $cred_reply[$x]['id']  . '">' . $cred_reply[$x]['username'] . '</div>
                                <div class="col-md-7 users-line">
                                    <input class="hide" type="checkbox" name="users[]" value="' . $cred_reply[$x]['id']  . '" id="user-' . $cred_reply[$x]['id']  . '">';
                                if ($cred_reply[$x]['username']!='admin'||$credential_type=='client')
                                    echo '<button type="button" class="btn btn-warning DeleteCredentialButton"  data-id="' . $cred_reply[$x]['id']  . '"><i class="fa fa-trash-o fa-lg fa-fw"></i>' . _("Delete") . '</button>';
                                if($credential_type!='adgroup'&&$credential_type!='pool')
                                    echo '<button type="button" class="btn btn-info ResetPasswordButton"  data-id="' . $cred_reply[$x]['id']  . '"><i class="fa fa-lock fa-lg fa-fw"></i>' . _("Reset password") . '</button>';
                          echo '</div>
                            </div>';
                        ++$x;
                    }?>
                </div>
                <div class="col-md-1"></div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info hide" id="ShowPasswordBox"></div>
                </div>
           </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="button" class="btn btn-primary hide" id="SubmitCredentialsButton"><?php echo _("Save changes");?></button>
            <input type="hidden" id="credential_type" value="<?php echo $credential_type;?>">
        </div>
    </div>
</body>
</html>