function drawOpenstackVmTable(){
    $.getJSON("inc/infrastructure/OpenStack/ListVms.php", {},  function(json){
            var machine_types=[];
            machine_types['sourcemachine']='Source machine';
            machine_types['initialmachine']='Initial machine';
            machine_types['vdimachine']='VDI machine';
            machine_types['simplemachine']='Simple machine';
            var initial_machines=[];
            var vdi_machines=[];
        $.each(json, function(i, obj){
            if (!obj['source_volume_machine'])//remove NULL
                obj['source_volume_machine']='';
            if (!obj['machine_type'])
                obj['machine_type'] = 'simplemachine';
            if (obj['machine_type'] == 'initialmachine')
                initial_machines.push(obj)
            if (obj['machine_type'] == 'vdimachine')
                vdi_machines.push(obj);
            var power_button="<a href=\"#\" class=\"power-button\" id=\"" + obj['osInstanceId'] + "\" data-power=\"down\"><i class=\"text-danger fa fa-stop fa-fw\"></i>Power down</a>";
            if (obj['state'] != "Running")
                power_button="<a href=\"#\" class=\"power-button\" id=\"" + obj['osInstanceId'] + "\" data-power=\"up\"><i class=\"text-success fa fa-play fa-fw\"></i>Power up</a>";
            if (obj['machine_type'] == 'sourcemachine' || obj['machine_type'] == 'simplemachine')
                $('#OpenstackVmTable').append("\
<tr class=\"table-stripe-bottom-line\" id=\"row-name-" + obj['id'] + "\">\
    <td \"col-md-1 clickable parent\" id=\"" + obj['id'] + "\" data-toggle=\"collapse\" data-target=\".child-" + obj['id'] + "\" >" + (++i) + "</td>\
    <td class=\"col-md-2\"><a data-toggle=\"modal\" href=\"vm_info.php?vm=" + obj['osInstanceId'] + "\" data-target=\"#modalWm\">" + obj['name'] + "</a> </td>\
    <td class=\"col-md-1\">" + machine_types[obj['machine_type']] + "</td>\
    <td class=\"col-md-1\">" + obj['source_volume_machine'] + "</td>\
    <td class=\"col-md-1\"><input type=\"checkbox\" checked onclick='handleSnapshot(this);' id=\"" + obj['osInstanceId'] + "\"></td>\
    <td class=\"col-md-1\"><input type=\"checkbox\"  onclick='handleMaintenance(this);' id=\"" + obj['osInstanceId'] + "\"></td>\
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
                <li class=\"lockable-vm-buttons-" + obj['id'] + "\"><a href=\"delete_vm.php?vm=43" + obj['osInstanceId'] + "\" onclick=\"return confirmBox('Are you sure?');\">\
                    <i class=\"fa fa-trash-o fa-fw text-danger\"></i>Delete machine</a>\
                </li>\
            </ul>\
        </div>\
    </td>\
    <td class=\"col-md-3\">\
        " + obj['os_type'] + " &#47; " + obj['state'] + " &#47; <i class=\"text-muted\">Nobody</i>\
    </td>\
</tr>");
        });
        //insert initial machine as child to source machine
        var i=initial_machines.length;
        while (initial_machines.length > 0){
            var obj = initial_machines.pop();
            var parent_row = document.getElementById("row-name-" + obj['source_volume']);
            var row = document.createElement('tr');
            row.setAttribute("class","table-stripe-bottom-line");
            row.setAttribute("id","row-name-" + obj['id']);
            row.innerHTML = "\
    <td class=\"col-md-1 clickable parent\" id=\"" + obj['id'] + "\" data-toggle=\"collapse\" data-target=\".child-" + obj['id'] + "\" >\
        <div class=\"row\">\
            <div class=\"col-md-3\"></div>\
            <div class=\"col-md-9\">" + i + "</div>\
        </div>\
    </td>\
    <td class=\"col-md-2\"><a data-toggle=\"modal\" href=\"vm_info.php?vm=" + obj['osInstanceId'] + "\" data-target=\"#modalWm\">" + obj['name'] + "</a> </td>\
    <td class=\"col-md-1\">" + machine_types[obj['machine_type']] + "</td>\
    <td class=\"col-md-1\">" + obj['source_volume_machine'] + "</td>\
    <td class=\"col-md-1\"><input type=\"checkbox\" checked onclick='handleSnapshot(this);' id=\"" + obj['osInstanceId'] + "\"></td>\
    <td class=\"col-md-1\"><input type=\"checkbox\"  onclick='handleMaintenance(this);' id=\"" + obj['osInstanceId'] + "\"></td>\
    <td class=\"col-md-2\">\
        <div class=\"btn-group\">\
            <button class=\"btn btn-default dropdown-toggle\" type=\"button\" id=\"VMSActionMenu\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"true\">VM Actions\
                <span class=\"caret\"></span>\
            </button>\
            <ul class=\"dropdown-menu\" aria-labelledby=\"VMSActionMenu\">\
                <li class=\"lockable-vm-buttons-" + obj['id'] + "\">\
                    <a href=\"power.php?action=single&state=up&vm=" + obj['osInstanceId'] + "\"><i class=\"text-success fa fa-play fa-fw text-success\"></i>Power up</a>\
                </li>\
                <li role=\"separator\" class=\"divider\"></li>\
                <li class=\"lockable-vm-buttons-" + obj['id'] + "\"><a href=\"delete_vm.php?vm=43" + obj['osInstanceId'] + "\" onclick=\"return confirmBox('Are you sure?');\">\
                    <i class=\"fa fa-trash-o fa-fw text-danger\"></i>Delete machine</a>\
                </li>\
            </ul>\
        </div>\
        <div class=\"btn-group\">\
            <button class=\"btn btn-default dropdown-toggle\" aria-expanded=\"false\" aria-haspopup=\"true\" data-toggle=\"dropdown\" type=\"button\">\
                VDI control\
                <span class=\"caret\"></span>\
            </button>\
        </div>\
    </td>\
    <td class=\"col-md-3\">\
        " + obj['os_type'] + " &#47; " + obj['state'] + " &#47; <i class=\"text-muted\">Nobody</i>\
    </td>";
            parent_row.parentNode.insertBefore(row, parent_row.nextSibling);
            --i;
        }

        //insert vdi machine as child to initial machine
        var i=vdi_machines.length;
        while (vdi_machines.length > 0){
            var obj = vdi_machines.pop();
            var parent_row = document.getElementById("row-name-" + obj['source_volume']);
            var row = document.createElement('tr');
            row.setAttribute("class","table-stripe-bottom-line");
            row.setAttribute("id","row-name-" + obj['id']);
            row.innerHTML = "\
    <td class=\"col-md-1 clickable parent\" id=\"" + obj['id'] + "\" data-toggle=\"collapse\" data-target=\".child-" + obj['id'] + "\" >\
        <div class=\"row\">\
            <div class=\"col-md-5\"></div>\
            <div class=\"col-md-7\">" + (i) + "</div>\
        </div>\
    </td>\
    <td class=\"col-md-2\"><a data-toggle=\"modal\" href=\"vm_info.php?vm=" + obj['osInstanceId'] + "\" data-target=\"#modalWm\">" + obj['name'] + "</a> </td>\
    <td class=\"col-md-1\">" + machine_types[obj['machine_type']] + "</td>\
    <td class=\"col-md-1\">" + obj['source_volume_machine'] + "</td>\
    <td class=\"col-md-1\"><input type=\"checkbox\" checked onclick='handleSnapshot(this);' id=\"" + obj['osInstanceId'] + "\"></td>\
    <td class=\"col-md-1\"><input type=\"checkbox\"  onclick='handleMaintenance(this);' id=\"" + obj['osInstanceId'] + "\"></td>\
    <td class=\"col-md-2\">\
        <div class=\"btn-group\">\
            <button class=\"btn btn-default dropdown-toggle\" type=\"button\" id=\"VMSActionMenu\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"true\">VM Actions\
                <span class=\"caret\"></span>\
            </button>\
            <ul class=\"dropdown-menu\" aria-labelledby=\"VMSActionMenu\">\
                <li class=\"lockable-vm-buttons-" + obj['id'] + "\">\
                    <a href=\"power.php?action=single&state=up&vm=" + obj['osInstanceId'] + "\"><i class=\"text-success fa fa-play fa-fw text-success\"></i>Power up</a>\
                </li>\
                <li role=\"separator\" class=\"divider\"></li>\
                <li class=\"lockable-vm-buttons-" + obj['id'] + "\"><a href=\"delete_vm.php?vm=43" + obj['osInstanceId'] + "\" onclick=\"return confirmBox('Are you sure?');\">\
                    <i class=\"fa fa-trash-o fa-fw text-danger\"></i>Delete machine</a>\
                </li>\
            </ul>\
        </div>\
    </td>\
    <td class=\"col-md-3\">\
        " + obj['os_type'] + " &#47; " + obj['state'] + " &#47; <i class=\"text-muted\">Nobody</i>\
    </td>";
            parent_row.parentNode.insertBefore(row, parent_row.nextSibling);
            --i;
        }
    });
}
function reloadOpenStackVmTable(){
    $('#OpenstackVmTable').html('');
    drawOpenstackVmTable();
}
function vmPowerCycle(vm_array){

}

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
    $('#main_table').on("click", "a.power-button", function() { //since table items are dynamically generated, we will not get ordinary .click() event
        var vm_array=[];
        vm_array.push({
            vm_id : $(this).attr('id'),
            power_state : $(this).attr('data-power'),
        });
        vmPowerCycle(vm_array);
    });
})