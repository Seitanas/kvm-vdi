<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-07-21
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
</head>
<body>
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("Add ActiveDirectory group");?></h4>
        </div>
        <div class="modal-body">
	    <div class="row">
		 <div class="col-md-2">
		 </div>
		 <div class="col-md-8">
		    <input type="text" class="form-control col-md-1" placeholder="<?php echo _("Name");?>" name="groupname" id="groupname">
		</div>
		 <div class="col-md-2">
		 </div>
	    </div>
	    <div class="row">
		 <div class="col-md-2">
		 </div>
		 <div class="col-md-8">
		     <div class="alert alert-info hide" id="progress"></div>
		</div>
		 <div class="col-md-2">
		 </div>
	    </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="button" class="btn btn-primary" id="submit"><?php echo _("Save changes");?></button>
        </div>
    </div>
</body>
<script>

$(document).ready(function(){
    function paint_danger(){
	$("#progress").removeClass('alert-info');
	$("#progress").addClass('alert-danger');
    }
    $('#submit').click(function() {
	$("#progress").removeClass('alert-danger');
	$("#progress").addClass('alert-info');
	$("#progress").removeClass('hide');
	
        $.ajax({
            type : 'POST',
            url : 'update_ad_groups.php',
            data: {
		type : 'new',
                groupname : $('#groupname').val(),
		
            },
            success:function (data) {
		if (data=='EXISTS'){
            	    $("#progress").html("<i class=\"fa fa-minus-circle fa-fw\"></i><?php echo _("Grpup already exists.");?>");
		    paint_danger();
		}
		if (data=='EMPTY_GROUP'){
            	    $("#progress").html("<i class=\"fa fa-minus-circle fa-fw\"></i><?php echo _("Groupname field is empty.");?>");
		    paint_danger();
		}
		if (data=='SUCCESS'){
    	    	    setTimeout(function() {
    			    $("#progress").addClass('hide');;
		    }, 2000);
    		    $("#progress").removeClass('alert-danger');
		    $("#progress").removeClass('alert-info');
		    $("#progress").addClass('alert-success');
		    $("#username").val("");
		    $("#password").val("");
            	    $("#progress").html("<i class=\"fa fa-thumbs-o-up fa-fw\"></i><?php echo _("Success");?>");
		}
            }
        });
    });
});
</script>
</html>