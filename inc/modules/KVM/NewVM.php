<?php

function draw_html(){
include (dirname(__FILE__) . '/../../../functions/config.php');
$h_reply=get_SQL_array("SELECT * FROM hypervisors WHERE maintenance=0 ORDER BY name,ip");
set_lang();
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <script src="inc/js/modal/kvm-vdi-kvm-new-vm.js"></script>
</head>
<body>
<style>
.input-group-addon {
    min-width:80px;
}
</style>
<form id="new_vm">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             <h4 class="modal-title"><?php echo _("Create virtual machine(s)");?></h4>
        </div>
        <div class="modal-body">
            <div class="row targetConfig">
                 <div class="col-md-5">
                    <label><?php echo _("Machine type:");?></label>
                    <select class="form-control selectClass" name="machine_type" id="machine_type" required tabindex="1">
                        <option selected value=""><?php echo _("Please select machine type");?></option>
                        <option value="simplemachine"><?php echo _("Simple machine");?></option>
                        <option value="initialmachine"><?php echo _("Initial machine");?></option>
                        <option value="sourcemachine"><?php echo _("Source machine");?></option>
                        <option value="vdimachine"><?php echo _("VDI machine");?></option>
            <option value="import"><?php echo _("Import from another hypervisor");?></option>
                    </select>
                </div>
                 <div class="col-md-5">
                    <label><?php echo _("Target hypervisor:");?></label>
                    <select class="form-control selectClass" name="hypervisor" id="hypervisor" required tabindex="2">
                        <option selected value=""><?php echo _("Please select hypervisor");?></option>
                        <?php
                        $x=0;
                        while ($x<sizeof($h_reply)){
                if ($h_reply[$x]['name'])
                            echo '<option value="' . $h_reply[$x]['id'] .  '">' . $h_reply[$x]['name'] . '</option>';
                else
                    echo '<option value="' . $h_reply[$x]['id'] .  '">' . $h_reply[$x]['ip'] . '</option>';
                            ++$x;
                        }?>
                    </select>
                 </div>
            </div>
            <div class="row">
                <?php 
                        echo '<div class="col-md-5 hide" id="hypervisor-sourceimage">
                            <label>' . _("Use volume from:") . '</label>';
                        $v_reply=get_SQL_array("SELECT id,name,machine_type,hypervisor FROM vms WHERE (machine_type='sourcemachine' OR machine_type='initialmachine') ORDER By name");
                        $y=0;
                        echo '<select class="form-control" name="source_volume" id="source_volume">' ."\n";
                        echo '<option selected value="">' . _("Please select source") . '</option>'."\n";
                        while ($y<sizeof($v_reply)){
                            echo '<option class="' . $v_reply[$y]['machine_type'] . ' hypervisor-' . $v_reply[$y]['hypervisor']  . '" value="' . $v_reply[$y]['id'] .  '">' . $v_reply[$y]['name'] . '</option>' ."\n";
                            ++$y;
                        }
                        echo '</select>' . "\n";
                        echo '</div>' . "\n";
                    ?>
                    <div id="hypervisor-manualpath" class="hide">
                        <div class="col-md-5">
                            <label><?php echo _("Specify disk path:");?></label>
                            <input type="text" name="source_drivepath" class="form-control" id="source_drivepath" value="<?php echo $default_imagepath; ?>">
                        </div>
                        <div class="col-md-4" id="SourceDriveSize">
                                <label><?php echo _("Disk size");?></label>
                            <div class="input-group">
                                <input type="number" min="1" value="10" name="source_drive_size" id="source_drive_size" class="form-control" type="number">
                                <span class="input-group-addon">GB</span>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="hide" id="hypervisor-imagepath">
                <div class="row">
                    <div class="col-md-9">
                        <label><?php echo _("Mount CD iso:");?></label>
                        <div class="input-group">
                            <span class="input-group-addon" style="min-width:40px;">
                                <input type="checkbox" name="iso_image" id="iso_image">
                            </span>
                            <select class="form-control" name="iso_path" id="iso_path" disabled>
                                <option value=""><?php echo _("Select ISO image");?></option>
                        <?php
                        $x=0;
                        while ($x<sizeof($h_reply)){
                            ssh_connect($h_reply[$x]['ip'].":".$h_reply[$x]['port']);
                            $files=explode("\n",ssh_command("sudo ls " . $default_iso_path . "|grep -i .iso", true));
                            foreach ($files as &$value) {
                                if (!empty($value))
                                    echo '<option class="iso_option hypervisor_iso-' . $h_reply[$x]['id'] . '" value="' . $value . '">' . $value . '</option>'."\n";
                            }
                            ++$x;
                        }
                        ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row machineConfig">
                <div class="col-md-4">
                    <label><?php echo _("Hardware info:");?></label>
                    <div class="input-group">
                        <input type="number" min="1" value="1" class="form-control" name="numsock" id="numsock">
                        <span class="input-group-addon"><?php echo _("Sockets");?></span>
                    </div>
                    <div class="input-group">
                        <input type="number" min="1" value="1" class="form-control" name="numcore" id="numcore">
                        <span class="input-group-addon"><?php echo _("Cores");?></span>
                    </div>
                    <div class="input-group">
                        <input type="number" min="1" value="1" class="form-control" name="numram" id="numram">
                        <span class="input-group-addon"><?php echo _("GB RAM");?></span>
                    </div>
                    <div class="input-group">
                        <input type="text" value="<?php echo $default_bridge; ?>" class="form-control" name="network" id="network">
                        <span class="input-group-addon"><?php echo _("Network");?></span>
                    </div>
                </div>
                <div class="col-md-8" id="newmachine-os">
                    <label>System info:</label>
                    <div class="input-group">
                        <span class="input-group-addon"><?php echo _("OS type");?></span>
                        <select class="form-control osselection" name="os_type" id="os_type" tabindex="3" required type="number">
                            <option selected value=""><?php echo _("Please select OS type");?></option>
                            <option value="linux"><?php echo _("Linux");?></option>
                            <option value="windows"><?php echo _("Windows");?></option>
                        </select>
                    </div>
                    <div class="input-group hide" id="os">
                        <span class="input-group-addon"><?php echo _("Version");?></span>
                        <select class="form-control osselection" name="os_version" id="os_version" tabindex="4" required>
                            <option selected value=""><?php echo _("Please select version");?></option>
                            <option class="linux" value="debiansqueeze">Debian Squeeze (or newer)</option>
                            <option class="linux" value="debianlenny">Debian Lenny</option>
                            <option class="linux" value="debianetch">Debian Etch</option>
                            <option class="linux" value="ubuntuprecise">Ubuntu 12.04 LTS</option>
                            <option class="linux" value="ubuntusaucy">Ubuntu 13.10 (or newer)</option>
                            <option class="linux" value="fedora18">Fedora 18</option>
                            <option class="linux" value="fedora19">Fedora 19</option>
                            <option class="linux" value="fedora20">Fedora 20</option>
                            <option class="windows" value="win7">Microsoft Windows 7 (or newer)</option>
                            <option class="windows" value="vista">Microsoft Windows Vista</option>
                            <option class="windows" value="winxp">Microsoft Windows XP</option>
                            <option class="windows" value="win2k8">Microsoft Windows Server 2008</option>
                            <option class="windows" value="win2k3">Microsoft Windows Server 2003</option>
                        </select>
                    </div>
                </div>
            </div>
        <div class="row massDeployment">
            <hr class="divider">
            <div class="col-md-5">
                <label><?php echo _("Mass deployment");?></label>
            </div>
        </div>
        <div class="row machineDeployInfo">
            <div class="col-md-5">
                <label><?php echo _("Prepend machine name:");?></label>             
                <input type="text" name="machinename" id="machinename" placeholder="somename-" class="form-control" required pattern="[a-zA-Z0-9-_]+" oninvalid="setCustomValidity(<?php echo ("'Illegal characters detected'");?>)" onchange="try{setCustomValidity('')}catch(e){}" >
            </div>
            <div class="col-md-5">
                <label><?php echo _("Number of machines to create:");?></label>             
                <input type="number" name="machinecount" id="machinecount" min="1" value="1" class="form-control" required>
            </div>
        </div>
        <div class="row sourceHypervisor hide">
            <div class="col-md-5">
                <label><?php echo _("Source hypervisor:");?></label>
                <select class="form-control" name="source-hypervisor" id="source-hypervisor">
                <option selected value=""><?php echo _("Please select hypervisor");?></option>
        <?php 
            $x=0;
            while ($x<sizeof($h_reply)){
                if ($h_reply[$x]['name'])
                    echo '<option value="' . $h_reply[$x]['id'] .  '">' . $h_reply[$x]['name'] . '</option>';
                else
                    echo '<option value="' . $h_reply[$x]['id'] .  '">' . $h_reply[$x]['ip'] . '</option>';
                ++$x;
            }
            echo '</select>' . "\n";
            echo '</div>' . "\n";
            ?>
            <div class="col-md-5">
                 <label><?php echo _("Source VM:");?></label>
                <select class="form-control" name="source-machine" id="source-machine" tabindex="2">
                </select>
            </div>
        </div>
        <div class="row sourceHypervisor hide">
            <div class="col-md-1"></div>
            <div class="col-md-10 text-warning">
                <i class="fa fa-hand-pointer-o fa-lg"></i>
                <?php echo _("Attention: this assumes, that VM image is on shared storage and is accesible on target hypervisor.");?>
            </div>
            <div class="col-md-1"></div>
        </div>
    </div>
    <div class="modal-footer">
        <div class="row">
            <div class="col-md-7">
                <div class="alert text-left alert-info hide" id="new_vm_creation_info_box"></div>
                </div>
            <div class="col-md-5">
                <button type="button" class="btn btn-default create_vm_buttons" data-dismiss="modal"><?php echo _("Close");?></button>
                <button type="button" class="btn btn-primary create_vm_buttons" id="CreateVMButton"><?php echo _("Create VMs");?></button>
                <input type="submit" class="hide">
            </div>
        </div>
   </div>
    </div>
</form>
<script>

$('#source-hypervisor').on('change', function(){
   fill_source_machines($('#source-hypervisor').val());
})


$('.selectClass').on('change', function(){
    $('#hypervisor-sourceimage').addClass('hide');
    $('#hypervisor-imagepath').addClass('hide');
    $('#hypervisor-manualpath').addClass('hide');
    $('.sourcemachine').hide();
    $('.initialmachine').hide();
    $('.iso_option').hide();
    $('.machineConfig').removeClass('hide');
    $('.massDeployment').removeClass('hide');
    $('.machineDeployInfo').removeClass('hide');
    $('#SourceDriveSize').removeClass('hide');
    $('.sourceHypervisor').addClass('hide');
    $hypervisor_id=$('#hypervisor').val();
    $('#source-machine').prop('required',false);
    $('#machinename').prop('required',true);
    $('#machinecount').prop('required',true);
    if (($('#machine_type').val() == 'initialmachine' || $('#machine_type').val() == 'vdimachine') && $hypervisor_id!='') {
        $('#hypervisor-sourceimage').removeClass('hide');
        $('#hypervisor-manualpath').removeClass('hide');
        $('.hypervisor-'+$hypervisor_id).show();
        if ($('#machine_type').val() == 'initialmachine'){
            $('.initialmachine').hide();
            $('#source_volume').prop('selectedIndex',0);
        }
        if ($('#machine_type').val() == 'vdimachine'){
            $('.sourcemachine').hide();
            $('#source_volume').prop('selectedIndex',0);
        }
        if ($('#machine_type').val() == 'vdimachine')
            $('#SourceDriveSize').addClass('hide');
        $('#hypervisor-'+$hypervisor_id).removeClass('hide');
        $('#hypervisor-manualpath').removeAttr('required');
        $('#source_drivepath').prop('required',true);
        $('#source_volume').prop('required',true);
        $('.osselection').prop('required',false);
    }
    if (($('#machine_type').val() == 'simplemachine' || $('#machine_type').val() == 'sourcemachine') && $hypervisor_id!='') {
        $('.hypervisor_iso-'+$hypervisor_id).show();
        $('#hypervisor-manualpath').removeClass('hide');
        $('#hypervisor-imagepath').removeClass('hide');
        $('#source_drivepath').prop('required',true);
        $('#source_volume').prop('required',false);
        $('.osselection').prop('required',true);
    }
    if ($('#machine_type').val() == 'import') {
        $('.machineConfig').addClass('hide');
        $('.massDeployment').addClass('hide');
        $('.machineDeployInfo').addClass('hide');
        $('.sourceHypervisor').removeClass('hide');
        $('#source-machine').prop('required',true);
        $('#source_drivepath').prop('required',true);
        $('#source_volume').prop('required',false);
        $('.osselection').prop('required',false);
        $('#machinename').prop('required',false);
        $('#machinecount').prop('required',false);
    }


})
$('#os_type').on('change', function(){
    if ($('#os_type').val()=='linux'){
        $('#os_version').prop('selectedIndex',0);
        $('#os').removeClass('hide');
        $('.windows').hide();
        $('.linux').show();
    }
    if ($('#os_type').val()=='windows'){
        $('#os_version').prop('selectedIndex',0);
        $('#os').removeClass('hide');
        $('.windows').show();
        $('.linux').hide();
    }
})
$('#iso_image').on('change', function(){
    if (this.checked) {
        $('#iso_path').prop('disabled', false);
    }
    else {
        $('#iso_path').prop('disabled', true);
    }
})
</script>
</body>
</html>
<?php
}