function drawOpenStackVMTable(obj, type, i){
    var machine_types=[];
    machine_types['sourcemachine']='Source machine';
    machine_types['initialmachine']='Initial machine';
    machine_types['vdimachine']='VDI machine';
    machine_types['simplemachine']='Simple machine';
    if (obj['maintenance'] == 'true')
        obj['maintenance'] = 'checked';
    else
        obj['maintenance'] = '';
    var tab=['1','11'];
    var power_button="<a href=\"#\" class=\"power-button\" id=\"" + obj['osInstanceId'] + "\" data-power=\"down\" data-power-button-rowid=\"" + obj['id'] +"\"><i class=\"text-danger fa fa-stop fa-fw\"></i>Power down</i></a>";
    if (obj['state'] != "Running")
        power_button="<a href=\"#\" class=\"power-button\" id=\"" + obj['osInstanceId'] + "\" data-power=\"up\" data-power-button-rowid=\"" + obj['id'] +"\"><i class=\"text-success fa fa-play fa-fw\"></i>Power up</a>";
    if (obj['state'] == "Suspended")
        power_button="<a href=\"#\" class=\"power-button\" id=\"" + obj['osInstanceId'] + "\" data-power=\"resume\" data-power-button-rowid=\"" + obj['id'] +"\"><i class=\"text-warning fa fa-play fa-fw\"></i>Resume</a>";
    var additional_buttons='';
    var rowclass='';
    if (type == 'initialmachine'){
        rowclass = ' info';
        tab=['3','9 glyphicon glyphicon-menu-down'];
        additional_buttons="\
        <div class=\"btn-group\">\
            <button class=\"btn btn-default dropdown-toggle\" aria-expanded=\"false\" aria-haspopup=\"true\" data-toggle=\"dropdown\" type=\"button\">\
                VDI control\
                <span class=\"caret\"></span>\
            </button>\
        </div>";
    }
    if (type == 'vdimachine'){
        tab=['5','7 glyphicon glyphicon-menu-right'];
        rowclass = ' warning';
    }
    if (!obj['source_volume_machine']) // remove null value
        obj['source_volume_machine'] = '';
    var table_rows="\
<tr class=\"table-stripe-bottom-line\" id=\"row-name-" + obj['id'] + "\">\
    <td class=\"col-md-1 clickable parent\" id=\"" + obj['id'] + "\" data-toggle=\"collapse\" data-target=\".child-" + obj['id'] + "\" >\
        <div class=\"row\">\
            <div class=\"col-md-" + tab[0] + "\"></div>\
            <div class=\"col-md-" + tab[1] + "\">" + i + "</div>\
        </div>\
    </td>\
    <td class=\"col-md-2\"><a data-toggle=\"modal\" href=\"vm_info.php?vm=" + obj['osInstanceId'] + "\" data-target=\"#modalWm\">" + obj['name'] + "</a> </td>\
    <td class=\"col-md-1\">" + machine_types[obj['machine_type']] + "</td>\
    <td class=\"col-md-2\">" + obj['source_volume_machine'] + "</td>\
    <td class=\"col-md-1\"><input class=\"MaintenanceCheckbox\" type=\"checkbox\"  id=\"" + obj['osInstanceId'] + "\" " + obj['maintenance'] + "></td>\
    <td class=\"col-md-2\">\
        <div class=\"btn-group\">\
            <button class=\"btn btn-default dropdown-toggle\" type=\"button\" id=\"VMSActionMenu\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"true\">VM Actions\
                <span class=\"caret\"></span>\
            </button>\
            <ul class=\"dropdown-menu\" aria-labelledby=\"VMSActionMenu\">\
                <li class=\"lockable-vm-buttons-" + obj['id'] + "\">\
                    " + power_button + "\
                </li>\
                <li role=\"separator\" class=\"divider\"></li>\
                <li class=\"lockable-vm-buttons-" + obj['id'] + "\"><a class=\"delete-button\" href=\"#\" id=\"" + obj['osInstanceId'] + "\" data-delete-button-rowid=\"" + obj['id'] + "\">\
                    <i class=\"fa fa-trash-o fa-fw text-danger\"></i>Delete machine</a>\
                </li>\
                <li class=\"lockable-vm-buttons-" + obj['id'] + "\"><a data-target=\"#modalWm\" data-toggle=\"modal\" href=\"vm_screen.php?vm=" + obj['osInstanceId'] + "\">\
                    <i class=\"fa fa-window-maximize fa-fw text-info\"></i>Open Console</a>\
                </li>\
            </ul>\
        </div>\
        " + additional_buttons + "\
    </td>\
    <td class=\"col-md-3\">\
        <i id=\"os-type-" + obj['id'] + "\">" + obj['os_type'] +"</i>\
        <i> &#47; </i>\
        <i id=\"vm-state-" + obj['id'] + "\">" + obj['state'] + "</i>\
        <i> &#47; </i>\
        <i id=\"vm-user\"><i class=\"text-muted\">Nobody</i></i>\
        <div class=\"row hide\" id=\"progress-bar-" + obj['id'] + "\">\
            <div class=\"col-md-5\">\
                <div class=\"progress\">\
                    <div class=\"progress-bar progress-bar-info progress-bar-striped active\" role=\"progressbar\"\
                        aria-valuenow=\"100\" aria-valuemin=\"0\" aria-valuemax=\"100\" style=\"width:100%\">\
                    </div>\
                </div>\
                <div class=\"col-md-7\"></div>\
            </div>\
        </div>\
    </td>\
</tr>"
    if (type == 'sourcemachine')
        $('#OpenstackVmTable').append(table_rows);
    else {
        var parent_row = document.getElementById("row-name-" + obj['source_volume']);
        var row = document.createElement('tr');
        row.setAttribute("class","table-stripe-bottom-line" + rowclass);
        row.setAttribute("id","row-name-" + obj['id']);
        row.innerHTML = table_rows;
        parent_row.parentNode.insertBefore(row, parent_row.nextSibling);
    }
}
//==================================================================
function drawOpenstackVmTable(){
    $.getJSON("inc/infrastructure/OpenStack/ListVMS.php", {},  function(json){
        var initial_machines=[];
        var vdi_machines=[];
        var x=0;
        if ("error" in json){
            showAlert("Error", json.error.message, "fa fa-exclamation-triangle fa-fw", "error");
            return 1;
        }
        $.each(json, function(i, obj){
            if (!obj['source_volume_machine'])//remove NULL
                obj['source_volume_machine']='';
            if (!obj['machine_type'])
                obj['machine_type'] = 'simplemachine';
            if (obj['machine_type'] == 'initialmachine')
                initial_machines.push(obj)
            if (obj['machine_type'] == 'vdimachine')
                vdi_machines.push(obj);
            if (obj['machine_type'] == 'sourcemachine' || obj['machine_type'] == 'simplemachine')
               drawOpenStackVMTable(obj, 'sourcemachine', ++x);
        });
        //insert initial machine as child to source machine
        var i=initial_machines.length;
        x=0;
        while (initial_machines.length > 0){
            var obj = initial_machines.pop();
            drawOpenStackVMTable(obj, 'initialmachine', '');
            --i;
        }
        //insert vdi machine as child to initial machine
        var i=vdi_machines.length;
        while (vdi_machines.length > 0){
            var obj = vdi_machines.pop();
            drawOpenStackVMTable(obj, 'vdimachine', '');
            --i;
        }
    });
}
//==================================================================
function reloadOpenStackVmTable(){
    $('#OpenstackVmTable').html('');
    drawOpenstackVmTable();
}
//==================================================================
function drawVMStatus(row_id, vm_id, power_state){
    var state_should_be=0;
    if (power_state == 'up' || power_state == 'resume') //define what powerstate we called
        state_should_be=1; //we need machine state to become running (1)
    else
        state_should_be=4; //we need machine state to become shutdown (4)
    run_query();
    function run_query(){
        $.post({
            url : 'inc/infrastructure/OpenStack/GetVMInfo.php',
                data: {
                    vm_id: vm_id,
                },
                success:function (data) {
                    var reply=jQuery.parseJSON(data);
                    if ("error" in reply){
                        showAlert("Error", reply.error.message, "fa fa-exclamation-triangle fa-fw", "error");
                        $('#progress-bar-' + row_id).addClass('hide');
                        $('#modalWm').modal('toggle');
                        return 1;
                    }
                    if (reply['server']['OS-EXT-STS:task_state'] == 'Powering on'){
                        $('#vm-state-' + row_id).text('Powering on');
                    }
                    if (reply['server']['OS-EXT-STS:power_state'] == state_should_be){ //machine is in wanted state
                        $('#progress-bar-' + row_id).addClass('hide');
                        if (power_state == 'up' || power_state == 'resume'){
                            $('#vm-state-' + row_id).text('Running');
                            $('#' + vm_id + '.power-button').html('<i class=\"text-danger fa fa-stop fa-fw\"></i>Power down</i>');
                            $('#' + vm_id + '.power-button').attr('data-power', 'down');
                        }
                        else{
                            $('#vm-state-' + row_id).text('Shutoff');
                            $('#' + vm_id + '.power-button').html('<i class=\"text-success fa fa-play fa-fw\"></i>Power up</a>');
                            $('#' + vm_id + '.power-button').attr('data-power', 'up');
                        }
                    $('#modalWm').modal('toggle');
                    }
                    else{
                        setTimeout(function() {run_query()}, 4000);
                    }
                }
            });
    }
}
//==================================================================
function vmPowerCycle(vm_array){
    $.each(vm_array, function(i, obj){
        $("#progress-bar-" + obj['row_id']).removeClass('hide');
        $.post({
            url : 'inc/infrastructure/OpenStack/PowerCycle.php',
            data: {
                vm_id: obj['vm_id'],
                power_state: obj['power_state']
            },
            success:function (data) {
                drawVMStatus(obj['row_id'], obj['vm_id'], obj['power_state']);
            }
          });
    });
}
//==================================================================
function vmDelete(vm_array){
    $.each(vm_array, function(i, obj){
        $("#progress-bar-" + obj['row_id']).removeClass('hide');
        $.post({
            url : 'inc/infrastructure/OpenStack/DeleteVM.php',
            data: {
                vm_id: obj['vm_id'],
            },
            success:function (data) {
                reply = $.parseJSON(data);
                if ("error" in reply){
                    showAlert("Error", reply.error.message, "fa fa-exclamation-triangle fa-fw", "error");
                    $("#progress-bar-" + obj['row_id']).addClass('hide');
                    return 1;
                }
                if (reply['delete'] == 'success')
                    $('#row-name-' + obj['row_id']).remove();
                else
                    console.log(data);
            }
        });
    });
}
//==================================================================
function getVMConsole(vm_id, console_type){
    $("#ConsoleMessage").addClass("alert alert-info");
    $("#ConsoleMessage").html('<p class="text-left"><i class="fa fa-spinner fa-spin fa-1x fa-fw"></i>Please wait</p>');
    $.post({
        url : 'inc/infrastructure/OpenStack/GetConsole.php',
            data: {
                vm_id: vm_id,
                console_type: console_type,
            },
            success:function (data) {
                reply = $.parseJSON(data);
                if (reply['error']){
                    $("#ConsoleMessage").addClass("alert alert-danger");
                    $("#ConsoleMessage").html('<p class="text-left"><i class="fa fa-remove fa-1x fa-fw text-left"></i>Error occured: ' + reply['error']);
                }
                else {
                    window.open("spice://" + reply['spice_address'] + ":" + reply['spice_port'] + "?password=" + reply['spice_password']);
                    $("#ConsoleMessage").removeClass("alert alert-info");
                    $("#ConsoleMessage").html('');
                    $('#modalWm').modal('toggle');
                }
            }
    });
}
//==================================================================
function fillSourceImages(vm_type){
    var source;
    if (vm_type == 'vdimachine'){
        $("#OSMachineCount").removeClass("hide");
        source = 'initialmachine';
    }
    if (vm_type == 'initialmachine'){
        source = 'sourcemachine';
        $("#OSMachineCount").addClass("hide");
    }
    if (vm_type == 'sourcemachine'){
        source = 'images';
        $("#OSMachineCount").addClass("hide");
    }
    if (source){
        $.post({
            url : 'inc/infrastructure/OpenStack/GetSourceImage.php',
                data: {
                    vm_type: source,
                },
                success:function (data) {
                    reply = $.parseJSON(data);
                    $('#OSSource').empty();
                    $.each(reply, function(i, obj){
                        $("#OSSource").append($("<option></option>").attr("value",obj['id']).text(obj['name']));
                    });
                }
        });
    }
}
//==================================================================
function heartbeatVM(vm_id){
   $.post({
        url : 'inc/infrastructure/OpenStack/HeartbeatVM.php',
            data: {
                vm_id: vm_id,
             },
            success:function (data) {
                setTimeout(function() {heartbeatVM(vm_id)}, 30000);
            }
   });
}
//==================================================================
function createOSVM(){
    /* First of all we create volume from source machine.
    JS will loop-query OpenStack volume service, till volume is created.
    After volume is up, JS will create new VM with volume as its storage.
    Note, that VM does not spin from volume directly, but from volume, which
    OpenStack will create form taht volume at VM build time.
    drawMessage() is just a loop to dislpay information box, till all volumes are created.
    */
    var vm_type = $('#OSMachineType').val();
    var source = $('#OSSource').val();
    var os_type = $('#os_type').val();
    var flavor = $('#OSFlavor').val();
    var networks = $('#OSNetworks').val();
    var vm_name = $('#machinename').val();
    var vm_count = $('#machinecount').val();
    var volume_size = $('#OSVolumeGB').val();
    var volumes_incomplete = vm_count;
    $(".create_vm_buttons").addClass('disabled');
    function drawMessage(){
        if (volumes_incomplete)
            setTimeout(function() {drawMessage()}, 1000);
        else{
            $(".create_vm_buttons").removeClass('disabled');
            $("#new_vm_creation_info_box").addClass('hide');
            $('#modalWm').modal('toggle');
        }
    }
    function getVolumeInfo(volume_id, new_vm_name){
        $.post({
            url : 'inc/infrastructure/OpenStack/GetVolumeInfo.php',
                data: {
                    volume_id: volume_id,
                },
                success:function (data) {
                    reply = $.parseJSON(data);
                    if (reply['volume']['status'] == 'available'){
                        $.post({
                            url : 'inc/infrastructure/OpenStack/CreateVM.php',
                                data: {
                                    vm_name: new_vm_name,
                                    vm_type: vm_type,
                                    os_type: os_type,
                                    flavor: flavor,
                                    volume_id: volume_id,
                                    networks: networks,
                                    source_vm: source,
                                },
                                success:function (data) {
                                    reply = $.parseJSON(data);
                                    drawOpenStackVMTable(reply, vm_type, '');
                                    $('#progress-bar-' + reply['id']).removeClass('hide');
                                    drawVMStatus(reply['id'], reply['osInstanceId'], 'up');
                                }
                        });
                        --volumes_incomplete;
                    }
                    else
                        setTimeout(function() {getVolumeInfo(volume_id, new_vm_name)}, 4000);
                }
        });
    }
    if (vm_name){
        drawMessage(); //Show message box till all volumes are created 
        $("#new_vm_creation_info_box").removeClass('hide');
        $("#new_vm_creation_info_box").html('Please wait. Building instances. <i class="fa fa-spinner fa-spin fa-1x fa-fw"></i>');
        var x=0;
        var new_vm_name = vm_name;
        function post_values(source, new_vm_name, vm_type, volume_size){ // we need to call post as external function because of async call.This solves problem with incremental number in machine name
            if (vm_type != 'sourcemachine'){
                $.post({
                    url : 'inc/infrastructure/OpenStack/CreateVolume.php',
                        data: {
                            source: source,
                            vm_name: new_vm_name,
                            vm_type: vm_type,
                            volume_size: volume_size,
                        },
                        success:function (data) {
                            reply = $.parseJSON(data);
                            console.log(reply);
                            if (reply['volume']['id'])
                                getVolumeInfo(reply['volume']['id'], new_vm_name);
                        }
                });
            }
            else {
                $.post({
                    url : 'inc/infrastructure/OpenStack/CreateVM.php',
                        data: {
                            vm_name: new_vm_name,
                            vm_type: vm_type,
                            os_type: os_type,
                            flavor: flavor,
                            volume_id: source,
                            networks: networks,
                            source_vm: source,
                            volume_size: volume_size,
                        },
                        success:function (data) {
                            reply = $.parseJSON(data);
                            drawOpenStackVMTable(reply, vm_type, '');
                            $('#progress-bar-' + reply['id']).removeClass('hide');
                            drawVMStatus(reply['id'], reply['osInstanceId'], 'up');
                        }
                    });
                }
        }
        while (vm_count){
            ++x;
            if (vm_type == 'vdimachine')
                new_vm_name = vm_name + "-" + x;
            post_values(source, new_vm_name, vm_type, volume_size);
            --vm_count;
        }
    }
}
//==================================================================
function loadNetworkList(){
    $("#OSNetworkLoad").removeClass('hide');
    $.get( "inc/infrastructure/OpenStack/ListNetworks.php", function( data ) {
        $("#OSNetworks").empty();
        reply = $.parseJSON(data);
        $.each(reply, function(i, obj){
            $("#OSNetworks").append($("<option></option>").attr("value",obj['id']).text(obj['name']));
        });
        $("#OSNetworkLoad").addClass('hide');
    });
}
//==================================================================
function loadFlavorList(){
    $("#OSFlavorLoad").removeClass('hide');
    $.get( "inc/infrastructure/OpenStack/ListFlavors.php", function( data ) {
        $("#OSFlavor").empty();
        reply = $.parseJSON(data);
        $.each(reply['flavors'], function(i, obj){
            $("#OSFlavor").append($("<option></option>").attr("value",obj['id']).text(obj['name']));
        });
        $("#OSFlavorLoad").addClass('hide');
    });
}
//==================================================================
function changeMaintenanceStatus(vm_id, state){
    $.post({
        url : 'inc/infrastructure/OpenStack/UpdateMaintenance.php',
            data: {
                vm_id: vm_id,
                state: state
            },
            success:function (data) {
            }
    });

}
//==================================================================
$(document).ready(function(){
    $('#OpenstackEditVmButton').click(function() {
        $.ajax({
                type : 'POST',
                url : 'vm_update.php',
                data: {
                    vm: $('#vm').val(),
                    machine_type: $('#machine_type').val(),
                    os_type: $('#os_type').val(),
                    source_volume: $('#source_volume').val(),
                },
                success:function (data) {
                    reloadOpenStackVmTable();
                    $('#modalWm').modal('toggle');
                }
        });
    });
    $('#SpiceConsoleButton').click(function() {
        getVMConsole($("#vm_id").val(), 'spice');
    });
    $('#main_table').on("click", ".MaintenanceCheckbox", function() { //since table items are dynamically generated, we will not get ordinary .click() event
//        console.log($(this).prop('checked'));
        changeMaintenanceStatus($(this).attr('id'), $(this).prop('checked'));
    });
    $('#OSMachineType').change(function() {
        fillSourceImages($("#OSMachineType").val());
        if ($('#OSMachineType').val() == 'sourcemachine')
            $("#OSVolumeSize").removeClass('hide');
        else
            $("#OSVolumeSize").addClass('hide');
    });
    $('#main_table').on("click", "a.power-button", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        var vm_array=[];
        vm_array.push({
            vm_id : $(this).attr('id'),
            power_state : $(this).attr('data-power'),
            row_id : $(this).attr('data-power-button-rowid'),
        });
        vmPowerCycle(vm_array);
    });
    $('#main_table').on("click", "a.delete-button", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        if (confirm('Are you sure?')){
            var vm_array=[];
            vm_array.push({
                vm_id : $(this).attr('id'),
                row_id : $(this).attr('data-delete-button-rowid'),
            });
            vmDelete(vm_array);
        }
    });

     $('#create-vm-button-click').click(function() {
        if(!$('#new_vm')[0].checkValidity()){
            $('#new_vm').find('input[type="submit"]').click();
        }
        else{
            createOSVM();
        }
    });
})