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
slash_vars();
set_lang();
if(isset ($_GET['credential_type']))
    $credential_type=$_GET['credential_type'];
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <script src="inc/js/kvm-vdi.js"></script>
</head>
<body>
    <form id="CredentialsForm">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php
            if ($credential_type=='user')
                echo _("Add new administrator");
            else
                 echo _("Add new client");?>
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-2"></div>
                <div class="col-md-8">
                    <input type="text" class="form-control col-md-1" placeholder="<?php echo _("Username");?>" name="username" id="username" required>
                </div>
                <div class="col-md-2"></div>
            </div>
            <div class="row">
                <div class="col-md-2"></div>
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control col-md-1" placeholder="<?php echo _("Password");?>" name="password" id="password" required>
                        <span class="input-group-btn">
                            <button class="btn btn-secondary" type="button" id="PWGen"><?php echo _("Generate password");?></button>
                        </span>
                    </div>
                </div>
                <div class="col-md-2"></div>
            </div>
            <div class="row">
                <div class="col-md-2"></div>
                <div class="col-md-8"></div>
                <div class="col-md-2"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="button" class="btn btn-primary" id="UpdateCredentialsButton"><?php echo _("Save changes");?></button>
            <input type="submit" class="hide">
            <input type="hidden" id="credential_type" value="<?php echo $credential_type;?>">
        </div>
    </div>
    <form>
</body>
</html>