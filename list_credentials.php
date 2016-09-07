<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-09-07
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
if(isset ($_GET['credentialtype']))
    $credentialtype=$_GET['credentialtype'];
set_lang();
if ($credentialtype=='client')
    $users_reply=get_SQL_array("SELECT * FROM clients WHERE isdomain=0 ORDER BY username");
else
    $users_reply=get_SQL_array("SELECT * FROM users ORDER BY username");
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <link href="inc/x-editable/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet">
    <script src="inc/x-editable/bootstrap3-editable/js/bootstrap-editable.js"></script>
</head>
<body>
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("Modify clients");?></h4>
        </div>
        <div class="modal-body">
		<div class="row pre-scrollable credential-list-div">
		    <div class="col-md-1">
		    </div>
		    <div class="col-md-10">
			<div class="row">
        		    <div class="col-md-6 users-line">
				<?php echo _("Username");?>
            		    </div>
            		    <div class="col-md-6 users-line">
            		    </div>
			</div>
	<?php
	$x=0;
	while ($x<sizeof($users_reply)){
	    echo '<div class="row users-list" id="row-name-' . $users_reply[$x]['id']  . '">
                    <div class="col-md-6 users-line name-' . $users_reply[$x]['id']  . '">
		    ' . $users_reply[$x]['username'] . '
                    </div>
                    <div class="col-md-6 users-line">
			<input class="hide" type="checkbox" name="users[]" value="' . $users_reply[$x]['id']  . '" id="user-' . $users_reply[$x]['id']  . '">';
			if ($users_reply[$x]['username']!='admin'||$credentialtype=='client')
    			    echo '<button type="button" class="btn btn-warning delete"  data-id="' . $users_reply[$x]['id']  . '">' . _("Delete") . '</button>';
			echo '<button type="button" class="btn btn-info reset-pw"  data-id="' . $users_reply[$x]['id']  . '">' . _("Reset password") . '</button>
            	    </div>
		</div>';
	    ++$x;
	}
	?>
	    </div>
	    <div class="col-md-1">
	    </div>
	    </div>
	        <div class="row">
		    <div class="col-md-12">
                        <div class="alert alert-info hide" id="progress"><i </div>
            	    </div>
                </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="submit" class="btn btn-primary hide" id="submit"><?php echo _("Save changes");?></button>
        </div>
    </div>
</body>
<script>
$(document).ready(function(){
    $('.users').editable({
    })
    $('.reset-pw').click(function() {
	var id = $(this).data('id');
	var password = "";
	var char_map = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	for( var i=0; i < 10; i++ )
	    password += char_map.charAt(Math.floor(Math.random() * char_map.length));
	$("#progress").removeClass('hide');
        $.ajax({
            type : 'POST',
            url : 'inc/infrastructure/ManageCredentials.php',
            data: {
		id : id,
                type : 'update-pw',
		password : password,
		credentialtype : <?php echo "'" . $credentialtype . "'";?>,
	    },
	    success:function (data) {
		$("#progress").text("<?php echo _("Password changed to: ");?>" + password);
	    }
	})

    })
    $('.delete').click(function() {
	$("#submit").removeClass('hide');
	var id = $(this).data('id');
	$('#user-'+id).prop('checked', true);
	$(".name-"+id).addClass('users-deleted');
    })
    $('#submit').click(function() {
	var question=confirm("<?php echo _("Are you sure you wish to save changes?");?>");
	var to_delete = [];
	if (question){
	    $(":checked").each(function() {
		if ($(this).val()!='on')
		    to_delete.push($(this).val());
		$("#row-name-"+$(this).val()).remove();
	});
        $.ajax({
            type : 'POST',
            url : 'inc/infrastructure/ManageCredentials.php',
            data: {
                type : 'delete',
		user : to_delete,
		credentialtype : <?php echo "'" . $credentialtype . "'";?>,
	    },
	    success:function (data) {
		$("#submit").addClass('hide');
	    }
	})
	}
    })
})
</script>
</html>