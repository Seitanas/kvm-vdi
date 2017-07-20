<?php
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
<form id="macform">
    <div class="modal-content">
        <div class="modal-header">
            <div class="row">
                <div class="col-md-8 text-left">
                    <h4 class="modal-title"><?php echo _("Generate DHCP config"); ?></h4>
                </div>
                <div class="col-md-3 text-right">
                </div>
                <div class="col-md-1 text-right">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
            </div>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-3">
                        <label for="multiselect" class="text-muted"><?php echo _("Available Vms:");?></label>
                        <select name="multiselect" id="multiselect" class="form-control" size="20" multiple="multiple"></select>
                    </div>
                    <div class="col-md-1">
                        <div style="margin-top:80px;">
                            <button type="button" id="multiselect_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>
                            <button type="button" id="multiselect_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>
                            <button type="button" id="multiselect_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>
                            <button type="button" id="multiselect_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="multiselect_to" class="text-muted"><?php echo _("Generate config for:");?></label>
                        <select id="multiselect_to" name="multiselect_to" class="form-control" size="20" multiple="multiple"></select>
                    </div>
                    <div class="col-md-5">
                        <label for="dhcp_conf" class="text-muted"><?php echo _("DHCP configuration:");?></label>
                        <textarea class="form-control" id="dhcp_conf"  rows="20"></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12"><hr></div>
                </div>
                <div class="row">
                    <div class="col-md-1"></div>
                    <div class="col-md-3 form-group">
                        <label for="start-ip" class="text-muted"><?php echo _("Start IP address");?></label>
                        <input type="text" id="start-ip" required="true" placeholder="192.168.0.1" pattern="((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}$">
                    </div>
                    <div class="col-md-3 form-group">
                    </div>
                    <div class="col-md-5"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="clearfix"></div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" id="generate" class="btn btn-primary"><?php echo _("Generate");?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close");?></button>
                        <input type="submit" class="hide">
                    </div>
                </div>
        </div>
    </div>
</form>
</body>

<script>
$(document).ready(function(){
    $('#multiselect').multiselect({
        keepRenderingSort: true
    });
    $poolid=$('#poollist').val();
    load_vm_list();
    $('#generate').click(function(){
        if(!$('#macform')[0].checkValidity()){
                $('#macform').find('input[type="submit"]').click();
        }
        else {
            $('#generate').addClass('disabled');
            var multivalues=[];
            $('#multiselect_to option').each(function(){
                multivalues.push($(this).val());
            });
            if (multivalues.length>0){
                $.ajax({
                    type : 'POST',
                    url : 'inc/infrastructure/ListMAC.php',
                    data: {
                        'vms': multivalues,
                    },
                    success:function (data) {
                        $('#dhcp_conf').val('');
                        var x = 0;
                        var vms = jQuery.parseJSON(data);
                        var conf_string='';
                        var ip=$('#start-ip').val().split('.');
                        $.each(vms, function(index,title) {
                            if (ip[3]==255){
                                ++ip[2];
                                ip[3]=1;
                                }
                            if (ip[2]==256){
                                ++ip[1];
                                ip[2]=1;
                                }
                            if (ip[1]==256){
                                ++ip[0];
                                ip[1]=1;
                                }
                            if (ip[0]==256)
                                ip[0]=1;
                            conf_string=conf_string + "host " + title.name + " { \n    hardware ethernet " + title.mac + ";\n    fixed-address " + ip[0] + "." + ip[1] + "." + ip[2] + "." + ip[3] + ";\n}\n";
                            ++ip[3];
                        });
                        $('#dhcp_conf').val(conf_string);
                        $('#generate').removeClass('disabled');
                    }
                });
            }

        }
    });
});
</script>
</html>
