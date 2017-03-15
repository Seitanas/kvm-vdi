function drawOpenstackVmTable(){
    $.getJSON("inc/infrastructure/OpenStack/ListVms.php", {},  function(json){
        $.each(json, function(i, obj){
            var machineTypes=[];
            machineTypes['sourcemachine']='Source machine';
            machineTypes['initialmachine']='Initial machine';
            machineTypes['vdimachine']='VDI machine';
            machineTypes['simplemachine']='Simple machine';
            if (!obj['source_volume_machine'])//remove NULL
                obj['source_volume_machine']='';
            if (!obj['machine_type'])
                obj['machine_type'] = 'simplemachine';
            if (obj['machine_type'] != 'vdimachine')
                $('#OpenstackVmTable').append("\
<tr class=\"table-stripe-bottom-line\">\
    <td colspan=\"2\" class=\"col-md-1 clickable parent\" id=\"" + obj['id'] + "\" data-toggle=\"collapse\" data-target=\".child-" + obj['id'] + "\" >" + (++i) + "</td>\
    <td class=\"col-md-2\"><a data-toggle=\"modal\" href=\"vm_info.php?vm=" + obj['osInstanceId'] + "\" data-target=\"#modalWm\">" + obj['name'] + "</a> </td>\
    <td class=\"col-md-1\">" + machineTypes[obj['machine_type']] + "</td>\
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
    </td>\
</tr>");

        });
    });
}
function reloadOpenStackVmTable(){
    $('#OpenstackVmTable').html('');
    drawOpenstackVmTable();
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
        console.log('lol');
    });

})