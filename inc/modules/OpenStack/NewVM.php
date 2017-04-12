<?php
function draw_html(){
set_lang();
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <script src="inc/js/kvm-vdi-openstack.js"></script>
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
                 <div class="col-md-6">
                    <label><?php echo _("Machine type:");?></label>
                    <select class="form-control selectClass" name="OSMachineType" id="OSMachineType" required tabindex="1">
                        <option selected value=""><?php echo _("Please select machine type");?></option>
                        <option value="initialmachine"><?php echo _("Initial machine");?></option>
                        <option value="sourcemachine"><?php echo _("Source machine");?></option>
                        <option value="vdimachine"><?php echo _("VDI machine");?></option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label><?php echo _("Machine source:");?></label>
                    <select class="form-control selectClass" name="OSSource" id="OSSource" required>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label><?php echo _("Networks:");?><i class="fa fa-spinner fa-spin fa-1x fa-fw hide" id="OSNetworkLoad"></i></label>
                    <select multiple class="form-control osselection" name="OSNetworks" id="OSNetworks" tabindex="3" required type="number">
                    </select>
                </div>
                <div class="col-md-6 hide" id="OSVolumeSize">
                    <label><?php echo _("Volume size GB:");?></label>
                    <input type="number" name="OSVolumeGB" id="OSVolumeGB" min="1" value="1" class="form-control" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6" id="newmachine-os">
                    <label><?php echo _("System info:");?></label>
                    <div class="input-group">
                        <span class="input-group-addon"><?php echo _("OS type");?></span>
                        <select class="form-control osselection" name="os_type" id="os_type" tabindex="3" required type="number">
                            <option selected value=""><?php echo _("Please select OS type");?></option>
                            <option value="linux"><?php echo _("Linux");?></option>
                            <option value="windows"><?php echo _("Windows");?></option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <label><?php echo _("Flavors")?><i class="fa fa-spinner fa-spin fa-1x fa-fw hide" id="OSFlavorLoad"></i></label>
                    <select class="form-control selectClass" name="OSFlavor" id="OSFlavor" required tabindex="1">
                    </select>
                </div>
            </div>
            <div class="row machineDeployInfo">
                <div class="col-md-6">
                    <label><?php echo _("Machine name:");?></label>
                    <input type="text" name="machinename" id="machinename" placeholder="somename-" class="form-control" required pattern="[a-zA-Z0-9-_]+" oninvalid="setCustomValidity(<?php echo ("'Illegal characters detected'");?>)" onchange="try{setCustomValidity('')}catch(e){}" >
                </div>
                <div class="col-md-6 hide" id="OSMachineCount">
                    <label><?php echo _("Number of machines to create:");?></label>
                    <input type="number" name="machinecount" id="machinecount" min="1" value="1" class="form-control" required>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="row">
            <div class="col-md-7">
                <div class="alert alert-info text-left hide" id="new_vm_creation_info_box"></div>
                </div>
            <div class="col-md-5">
                <button type="button" class="btn btn-default create_vm_buttons" data-dismiss="modal"><?php echo _("Close");?></button>
                <button type="button" class="btn btn-primary create_vm_buttons" id="create-vm-button-click"><?php echo _("Create VMs");?></button>
                <input type="submit" class="hide">
            </div>
        </div>
   </div>
    </div>
</form>
</body>
<script type="text/javascript">
    $(document).ready(function () {
        loadNetworkList();
        loadFlavorList();
    });
</script>
</html>
<?php
}