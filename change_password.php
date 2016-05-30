<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-05-30
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
set_lang();
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <title>Change password</title>  
</head>
<body>
<form method="POST" action="update_password.php" id="passwordForm">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("Change password");?></h4>
        </div>
        <div class="modal-body">
	    <div class="row">
		 <div class="col-md-3">
		 </div>
		 <div class="col-md-6">
		    <input type="password" class="form-control" placeholder="<?php echo _("Enter password");?>" name="password1" id="password1">
		</div>
	    </div>
	    <div class="row">
		 <div class="col-md-3">
		 </div>
		 <div class="col-md-6">
		    <input type="password" class="form-control" placeholder="<?php echo _("Repeat password");?>" name="password2" id="password2">
		</div>
	    </div>
	    <div class="row">
		 <div class="col-md-3">
		 </div>
		 <div class="col-md-6">
		    <div class="alert alert-danger hide" id="alert">
		   </div>
		</div>
	    </div>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="submit" class="btn btn-primary" id="submit"><?php echo _("Save changes");?></button>
        </div>
    </div>
</form>
</body>
<script>

jQuery(function(){
        $("#submit").click(function(){
        $(".error").hide();
        var hasError = false;
        var passwordVal = $("#password1").val();
        var checkVal = $("#password2").val();
        if (passwordVal == '') {
	     $('#alert').removeClass('hide');
	     $('#alert').html(<?php echo _("'<strong>Error!</strong> Empty password.'");?>);
            hasError = true;
        } else if (checkVal == '') {
	     $('#alert').removeClass('hide');
	     $('#alert').html(<?php echo _("'<strong>Error!</strong> Empty password.'");?>);
            hasError = true;
        } else if (passwordVal != checkVal ) {
	     $('#alert').removeClass('hide');
	     $('#alert').html(<?php echo _("'<strong>Error!</strong> Passwords do not match.'");?>);
	    hasError = true;
        }
        if(hasError == true) {return false;}
    });
});

</script>
</html>