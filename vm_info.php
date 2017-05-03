<?php
/*
KVM-VDI
Tadas Ustinavičius

Vilnius University.
Center of Information Technology Development.


Vilnius,Lithuania.
2017-05-03
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_GET['vm'];
$hypervisor=$_GET['hypervisor'];
if (empty($vm)||empty($hypervisor)&&$engine!='OpenStack'){
    exit;
}
if ($engine=='OpenStack'){
    $v_reply=get_SQL_array("SELECT * FROM vms WHERE osInstanceId='$vm'");
    $source_machines_reply=get_SQL_array("SELECT * FROM vms WHERE (machine_type='sourcemachine' OR machine_type='initialmachine') AND id<>'$vm' ORDER BY name");
}
else {
    $h_reply=get_SQL_line("SELECT * FROM hypervisors WHERE id='$hypervisor'");
    $v_reply=get_SQL_array("SELECT * FROM vms WHERE id='$vm'");
    $source_machines_reply=get_SQL_array("SELECT * FROM vms WHERE hypervisor='$hypervisor' AND (machine_type='sourcemachine' OR machine_type='initialmachine') AND id<>'$vm'  ORDER BY name");
}
set_lang();
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <?php
        if ($engine == 'OpenStack')
            echo '<script src="inc/js/kvm-vdi-openstack.js"></script>'
    ?>
</head>
<body>
<form method="POST" action="vm_update.php">
    <input type="hidden" name="hypervisor" value="<?php echo $hypervisor; ?>">
    <input type="hidden" name="vm" id="vm" value="<?php echo $vm; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title">VM name: <?php echo $v_reply[0]['name']; ?></h4>
        </div>
        <div class="modal-body">
        <div class="row">
            <div class="col-md-3">
            <label><?php echo _("OS type:");?></label>
            <select class="form-control" name="os_type" id="os_type">
                <option value="linux"><?php echo _("Linux");?></option>
                <option value="windows"><?php echo _("Windows");?></option>
            </select>
            </div>
            <div class="col-md-2">
            </div>
            <div class="col-md-7">
               <?php
                 if ($engine!='OpenStack'){
                    $mac=get_mac_address($vm);
                    echo '
                <label>' . _("MAC address:") . '</label>
                <div>
                        ' .  $mac[0]['mac'] . '
                </div>
            </div>';
            }?>
	    </div>
	    <div class="row">
		 <div class="col-md-5">
		    <label><?php echo _("Machine type:");?></label>
		    <select class="form-control" name="machine_type" id="machine_type">
			<?php if (empty($v_reply[0]['machine_type'])){?>
				<option selected value=""><?php echo _("Please select machine type");?></option> <?php } ?>
	    	        <option value="simplemachine"><?php echo _("Simple machine");?></option>
        		<option value="initialmachine"><?php echo _("Initial machine");?></option>
			<option value="sourcemachine"><?php echo _("Source machine");?></option>
			<option value="vdimachine"><?php echo _("VDI machine");?></option>
	    	    </select>
		</div>
		 <div class="col-md-5 hide" id="sourcevolume">
		    <label>Use volume from:</label>
		    <?php
		        echo '<select class="form-control" name="source_volume" id="source_volume">';
			    $x=0;
			    while ($x<sizeof($source_machines_reply)){
				    echo '<option class="' . $source_machines_reply[$x]['machine_type'] .'" value="' . $source_machines_reply[$x]['id']  . '"> ' . $source_machines_reply[$x]['name']  . "</option>\n";
				++$x;
				}
			echo '</select>';?>
		</div>		 
	    </div>
        <?php if ($engine != 'OpenStack'){
echo'        <div class="row">
            <div class="col-md-4">
                <label>' . _("Use virtual snapshots") . '</label>
                <input type="checkbox" name="snapshot" id="snapshot">
            </div>
        </div>';} ?>
  </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
            <?php
            if ($engine!='OpenStack')
                echo '<button type="submit" class="btn btn-primary">' .  _("Save changes") . '</button>';
            else
                echo '<button type="button" class="btn btn-primary" id="OpenstackEditVmButton">' .  _("Save changes") . '</button>';
            ?>
        </div>
    </div>
</form>
<script>
function refresh_select(){
    $('#source_volume .sourcemachine').show();
    $('#source_volume .initialmachine').show();
}
$('#machine_type').on('change', function(){
    refresh_select();
    if ($(this).val() == 'initialmachine') {
	$('#source_volume .initialmachine').hide();
        $('#sourcevolume').removeClass('hide');
	$('#source_volume').prop('selectedIndex', -1);
    }
    if ($(this).val() == 'vdimachine') {
	$('#source_volume .sourcemachine').hide();
        $('#sourcevolume').removeClass('hide');
	$('#source_volume').prop('selectedIndex', -1);
    }
    if ($(this).val() == 'sourcemachine') {
	$('#sourcevolume').addClass('hide');
    }
    if ($(this).val() == 'simplemachine') {
        $('#sourcevolume').addClass('hide');
    }
})
<?php if (!empty($v_reply[0]['os_type'])){?>
    $("#os_type").val(<?php echo '"' . $v_reply[0]['os_type']  . '"'; ?>).change();
<?php } ?>
<?php if (!empty($v_reply[0]['machine_type'])){?>
    $("#machine_type").val(<?php echo '"' . $v_reply[0]['machine_type']  . '"'; ?>).change();
<?php } ?>
<?php if (!empty($v_reply[0]['source_volume'])){?>
    $("#source_volume").val(<?php echo '"' . $v_reply[0]['source_volume']  . '"'; ?>).change();
<?php } ?>
$("#snapshot").prop('checked', <?php if (empty($v_reply[0]['snapshot'])||$v_reply[0]['snapshot']=='false') echo 0; else echo 1; ?>);
</script>
</body>
</html>