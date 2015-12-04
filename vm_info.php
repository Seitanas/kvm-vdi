<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
$vm=addslashes($_GET['vm']);
$hypervisor=addslashes($_GET['hypervisor']);
if (empty($vm)||empty($hypervisor)){
    exit;
}
$h_reply=get_SQL_line("SELECT * FROM hypervisors WHERE id='$hypervisor'");
$v_reply=get_SQL_line("SELECT * FROM vms WHERE id='$vm'");
$all_machines_reply=get_SQL_array("SELECT * FROM vms WHERE hypervisor='$hypervisor' ORDER BY name");
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <title>Remote file for Bootstrap Modal</title>  
</head>
<body>
<form method="POST" action="vm_update.php">
    <input type="hidden" name="hypervisor" value="<?php echo $hypervisor; ?>">
    <input type="hidden" name="vm" value="<?php echo $vm; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title">VM name: <?php echo $v_reply[1]; ?></h4>
        </div>
        <div class="modal-body">
	    <div class="row">
		 <div class="col-md-5">
		    <label>Machine type:</label>
		    <select class="form-control" name="machine_type" id="machine_type">
			<?php if (empty($v_reply[3])){?>
				<option selected value="">Please select machine type</option> <?php } ?>
	    	        <option value="simplemachine">Simple machine</option>
        		<option value="initialmachine">Initial machine</option>
			<option value="sourcemachine">Source machine</option>
			<option value="vdimachine">VDI machine</option>
	    	    </select>
		</div>
		 <div class="col-md-5 hide" id="sourcevolume">
		    <label>Use volume from:</label>
		    <select class="form-control" name="source_volume" id="source_volume">
			<?php
			    $x=0;
			    while ($all_machines_reply[$x]['id']){
				echo '<option value="' . $all_machines_reply[$x]['id']  . '"> ' . $all_machines_reply[$x]['name']  . '</option>';
				++$x;
			    }
			?>
	    	    </select>
		</div>		 
	    </div>
	    <div class="row">
		<div class="col-md-4">
		    <label>Use virtual snapshots </label>
		    <input type="checkbox" name="snapshot" id="snapshot">
		</div>
	    </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </div>
</form>
<script>
$('#machine_type').on('change', function(){       
    if ($(this).val() == 'initialmachine' ) {
        $('#sourcevolume').removeClass('hide');
    }
    if ($(this).val() == 'vdimachine' ) {
        $('#sourcevolume').removeClass('hide');
    }
    if ($(this).val() == 'sourcemachine' ) {
        $('#sourcevolume').addClass('hide');
    }
    if ($(this).val() == 'simplemachine' ) {
        $('#sourcevolume').addClass('hide');
    }

})
<?php if (!empty($v_reply[3])){?>
    $("#machine_type").val(<?php echo '"' . $v_reply[3]  . '"'; ?>).change();
<?php } ?>
$("#source_volume").val(<?php echo '"' . $v_reply[4]  . '"'; ?>).change();
$("#snapshot").prop('checked', <?php echo $v_reply[5]; ?>);
</script>
</body>
</html>