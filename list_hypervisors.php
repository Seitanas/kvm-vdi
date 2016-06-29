<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2016-06-29
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
set_lang();
$hypervisors_reply=get_SQL_array("SELECT * FROM hypervisors ORDER BY id");
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
             <h4 class="modal-title"><?php echo _("Modify hypervisors");?></h4>
        </div>
        <div class="modal-body">
	    <div class="row">
        	<div class="col-md-3 hypervisor-line">
		    Name
                </div>
                <div class="col-md-3 hypervisor-line">
		    Address
            	</div>
                <div class="col-md-3 hypervisor-line">
		    SPICE address
            	</div>
                <div class="col-md-3 hypervisor-line">
            	</div>
	    </div>
	<?php
	$x=0;
	while ($x<sizeof($hypervisors_reply)){
	    echo '<div class="row hypervisor-list" id="row-name-' . $hypervisors_reply[$x]['id']  . '">
                    <div class="col-md-3 hypervisor-line name-' . $hypervisors_reply[$x]['id']  . '">
		    <a href="#" class="hypervisor" data-type="text" data-name="update-name" data-pk="' . $hypervisors_reply[$x]['id']  . '" data-url="update_hypervisors.php">' . $hypervisors_reply[$x]['name'] . '</a>
                    </div>
                    <div class="col-md-3 hypervisor-line name-' . $hypervisors_reply[$x]['id']  . '">
		    <a href="#" class="hypervisor" data-type="text" data-name="update-address" data-pk="' . $hypervisors_reply[$x]['id']  . '" data-url="update_hypervisors.php">' . $hypervisors_reply[$x]['ip'] . '</a>
            	    </div>
            	    <div class="col-md-3 hypervisor-line  name-' . $hypervisors_reply[$x]['id']  . '">
		    <a href="#" class="hypervisor" data-type="text" data-name="update-spice" data-pk="' . $hypervisors_reply[$x]['id']  . '" data-url="update_hypervisors.php">' . $hypervisors_reply[$x]['address2'] . '</a>
            	    </div>
                    <div class="col-md-3 hypervisor-line">
			<input class="hide" type="checkbox" name="hypervisor[]" value="' . $hypervisors_reply[$x]['id']  . '" id="hypervisor-' . $hypervisors_reply[$x]['id']  . '">
			<button type="button" class="btn btn-warning delete"  data-id="' . $hypervisors_reply[$x]['id']  . '">' . _("Delete") . '</button>
            	    </div>
		</div>';
	    ++$x;
	}
	?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <button type="submit" class="btn btn-primary hide" id="submit"><?php echo _("Save changes");?></button>
        </div>
    </div>
</body>
<script>
$(document).ready(function(){
    $('.hypervisor').editable({
	success: function(response, newValue) {
	    draw_table();
	}
    })

    $('.delete').click(function() {
	$("#submit").removeClass('hide');
	var id = $(this).data('id');
	$('#hypervisor-'+id).prop('checked', true);
	$(".name-"+id).addClass('hypervisor-deleted');
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
            url : 'update_hypervisors.php',
            data: {
                type : 'delete',
		hypervisor : to_delete,
	    },
	    success:function (data) {
		$("#submit").addClass('hide');
		draw_table();
	    }
	})
	}
    })
})
</script>
</html>