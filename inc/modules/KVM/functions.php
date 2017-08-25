<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-08-25
Vilnius, Lithuania.
*/

function ssh_connect($address){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $tmp = explode(":", $address);
    $ip=$tmp[0];
    $port=(int)$tmp[1];
    global $connection;
    $connection = ssh2_connect($ip, $port, array('hostkey'=>'ssh-rsa'));
    if (!$connection)
        return 'BAD_SSH_ADDRESS';
    if (!ssh2_auth_pubkey_file($connection, $ssh_user, $ssh_key_path.'id_rsa.pub',$ssh_key_path.'id_rsa'))
        return 'BAD_SSH_CREDENTIALS';

}
function ssh_disconnect(){
    global $connection;
    ssh2_exec($connection, 'exit;');
}
function ssh_command($command, $blocking, $ignore_error = false){
    global $connection;
    write_log("Executing: " . preg_replace('%\\"password.*?,%i', '"password\":\"*****\",',$command));
    $reply = ssh2_exec($connection,$command);
    $errorReply = ssh2_fetch_stream($reply, SSH2_STREAM_STDERR);
    stream_set_blocking($reply, $blocking);
    stream_set_blocking($errorReply, $blocking);
    $output = stream_get_contents($reply);
    $error = stream_get_contents($errorReply);
    if (!empty($error) && !$ignore_error)
        return $error;
    if (!empty($output))
        return $output;
}
//#############################################################################
function reload_vm_info(){
    $x=0;
    $sql_reply=get_SQL_array("SELECT * FROM hypervisors WHERE maintenance=0");
    $error_return=0;
    while ($x<sizeof($sql_reply)){
        $ip=$sql_reply[$x]['ip'];
        $port=$sql_reply[$x]['port'];
        $hyper_id=$sql_reply[$x]['id'];
        $reply=ssh_connect($ip . ":" . $port);
        if (!$reply){//if connection is successfull
            $output=ssh_command("sudo virsh list --all |tail -n +3|head -n -1|awk '{print $2" . '" "' . "$3}'",true);
            $vms_in_db=get_SQL_array("SELECT * FROM vms WHERE hypervisor='$hyper_id'");
            $vms=array();
            $PlainVMS=array();
            $output=str_replace("\n"," ",$output);
            $vms=explode(" ",$output);
            $y=0;
            $error_reply=0;
            if (strpos($output, 'error:') !== false){
                $error_reply=1;
                write_log("Error occured on hypervisor id: $hyper_id Output: " . $output);
                $error_return=$error_return.'ERROR:'.$hyper_id.',';
            }
            while ($vms[$y]&&!$error_reply){
                $PlainVMS[]="'" . $vms[$y] . "'";
                $vms_reply=get_SQL_line("SELECT id,maintenance FROM vms WHERE name='$vms[$y]' AND hypervisor='$hyper_id'"); 
                if (!$vms_reply[1])
                    $maint="false";
                else
                    $maint=$vms_reply[1];
                $state=$vms[$y+1];
                if (empty($vms_reply[0]))//New VM is found
                    add_SQL_line("INSERT INTO  vms (name,hypervisor,state,maintenance) VALUES ('$vms[$y]','$hyper_id','$state','$maint')");
                else
                    add_SQL_line("UPDATE vms SET name='$vms[$y]', hypervisor='$hyper_id', state='$state', maintenance='$maint' WHERE id='$vms_reply[0]'");
                $y=$y+2;
            }
            $PlainVMS = join(', ', $PlainVMS);
            if (!empty($PlainVMS)&&!$error_reply)//remove all VMS, that do not exist on hypervisor, but still are in database
                $TrashVMS=add_SQL_line("DELETE FROM vms WHERE hypervisor='$hyper_id' AND name NOT IN ($PlainVMS)");
        }
        else {//put hypervisor to maintenance if cannot connect to it
            add_SQL_line("UPDATE hypervisors SET maintenance='1' WHERE id='$hyper_id'");
            include (dirname(__FILE__) . '/../../../functions/config.php');
            if ($send_email_alerts){
                require_once "Mail.php";
                $email_message = _("KVM-VDI is unable to connect to hypervisor with address: " . $sql_reply[$x]['ip'] . "\nPutting hypervisor to maintenance mode.");
                $headers = array ('From' => $alert_email_from, 'To' => $alert_email_to, 'Subject' => 'KVM-VDI alert', 'Reply-To' => $alert_email_from);
                if($smtp_ssl)
                        $smtp_server_address='ssl://' . $smtp_server_address;
                if ($smtp_auth)
                    $smtp = Mail::factory('smtp', array ('host' => $smtp_server_address, 'port' => $smtp_server_port, 'auth' => true, 'username' => $smtp_auth_username, 'password' => $smtp_auth_password));
                else 
                    $smtp = Mail::factory('smtp', array ('host' => $smtp_server_address, 'port' => $smtp_server_port, 'auth' => false));
                $mail = $smtp->send($alert_email_to, $headers, $email_message);
                }
            }
        ++$x;
    }
    return $error_return;
}
//############################################################################################
function get_mac_address($vm){
    if (!is_array($vm))//get_mac_address() acepts single vm or array of vms. We need to do form array of single-value variable
        $vm=array($vm);
    $vm=join(',',$vm);
    $vmEntry=get_SQL_array("SELECT id,name,hypervisor,mac FROM vms WHERE id IN ($vm) ORDER by name");
    $x=0;
    $macAddr=array();
        while ($x<sizeof($vmEntry)){
            if (empty($vmEntry[$x]['mac'])){
                $sql_reply=get_SQL_array("SELECT * FROM hypervisors WHERE id='{$vmEntry[$x]['hypervisor']}'");
                $ip=$sql_reply[0]['ip'];
                $port=$sql_reply[0]['port'];
                $reply=ssh_connect($ip . ":" . $port);
                $output=ssh_command("sudo virsh domiflist  " . $vmEntry[$x]['name'] . "|tail -n +3|head -n -1|awk '{print $5}'",true);
                $output=str_replace("\n","",$output);
                if (strpos($output, ' ') == false){//simple hack to check if reply contains anything more than single word (error reply instead of VM name)
                    add_SQL_line("UPDATE vms SET mac='$output' WHERE id='{$vmEntry[$x]['id']}'");
                    array_push($macAddr, array('name' => $vmEntry[$x]['name'], 'mac' => $output));
                }
                else {
                    write_log("Error occured: " . $output);
                    array_push($macAddr, array('name' => $vmEntry[$x]['name'], 'mac' => 'FF:FF:FF:FF:FF:FF'));
                }
            }
            else
                array_push($macAddr, array('name' => $vmEntry[$x]['name'], 'mac' => $vmEntry[$x]['mac']));
            ++$x;
        }
    return $macAddr;
}
//############################################################################################
function draw_html5_buttons(){
    require_once ('HTML5Buttons.php');
    HTML5Buttons();
}
//############################################################################################
function drawStateInfo($state){
    switch ($state){
        case "shut":
            $vms_status_display='<i class="text-danger">' . _("Shutoff") . '</i>';
            $pwr_status="off";
            $pwr_button="btn-default";
            break;
        case "running":
            $vms_status_display='<i class="text-success">' . _("Running") . '</i>';
            break;
        case "paused":
            $vms_status_display='<i class="text-warning">' . _("Paused") . '</i>';
            $pwr_button="btn-default";
            break;
        case "pmsuspended":
            $vms_status_display='<i class="text-warning">' . _("Suspended") . '</i>';
            $pwr_button="btn-default";
            break;
        default:
            $vms_status_display='<i class="text-muted">' . _("Unknown") . '</i>';
        }
    return $vms_status_display;
}
//############################################################################################
function draw_dashboard_table(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $userConfig=get_userconf();
    $sql_reply=get_SQL_array("SELECT * FROM hypervisors ORDER BY name,ip ASC");
    if (sizeof($sql_reply)<1 && $engine=='KVM'){
        echo '<div class="row">
            <div class="col-md-6 col-md-offset-2" style="margin-top:200px;text-align:center;">
            <div class="alert alert-success" role="alert"><?php echo _("Congratulations! Installation was successfull. Now you should add hypervisors to your dashboard.");?></div>
            <i class="fa fa-hand-o-up fa-5x text-info"></i>
            </div>
            </div>';
    }
    $x=0;
    while ($x<sizeof($sql_reply)){
        $table_status="";
        $vms_query=get_SQL_array("SELECT vms.id,vms.name,vms.hypervisor,vms.machine_type,vms.source_volume,vms.snapshot,vms.maintenance,vms.filecopy,vms.state,vms.os_type,vms.locked,vms.lastused,clients.username, vms_tmp.name AS sourcename  FROM vms LEFT JOIN vms AS vms_tmp ON vms.source_volume=vms_tmp.id LEFT JOIN clients ON clients.id=vms.clientid WHERE vms.hypervisor='{$sql_reply[$x]['id']}' AND vms.machine_type <> 'vdimachine' ORDER BY vms.name");
        if (!empty($sql_reply[$x]['name'])){
            $hypervisor_name=$sql_reply[$x]['name'];
        }
        else{
            $hypervisor_name=$sql_reply[$x]['ip'];
        }
        echo '<h1>' . _("Hypervisor: ") . $hypervisor_name . ' ';
        if (!$sql_reply[$x]['maintenance'])
            echo '<a href="#" data-maintenance="1" data-hypervisor="' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn glyphicon glyphicon-ok-circle btn-success HypervisorMaintenanceButton"> ' . _("Enabled") . '</a>';
        else {
            echo '<a href="#" data-mainetenance=0 data-hypervisor="' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn glyphicon glyphicon-ban-circle btn-default HypervisorMaintenanceButton"> ' . _("Disabled") . '</a>';
            $table_status="hypervisor-screen-disabled";
        }
        echo '</h1>
                <div class="table-responsive"  style="overflow: inherit;">
                <table class="table table-striped table-hover ' . $table_status . '" id="hypervisor-table-' . $sql_reply[$x]['id'] . '">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th></th>
                            <th>' . _("Machine name") . '</th>
                            <th>' . _("Machine type") . '</th>
                            <th>' . _("Source image") . '</th>
                            <th>' . _("Virt-snapshot") . '</th>
                            <th>' . _("Maintenance") . '</th>
                            <th>' . _("Operations") . '</th>
                            <th>' . _("OS type/Status/Used by") . '</th>
                        </tr>
                    </thead>
                    <tbody>';
                    $y=0;
            $machine_type['simplemachine']=_("Simple machine");
            $machine_type['initialmachine']=_("Initial machine");
            $machine_type['sourcemachine']=_("Source machine");
            $machine_type['vdimachine']=_("VDI machine");
            while ($y<sizeof($vms_query)){
                $lockstatus='';
                $pwr_button="btn-success";
                $vms_status_display = drawStateInfo($vms_query[$y]['state']);
                $vms_query[$y]['snapshot']=str_replace("true","checked",$vms_query[$y]['snapshot']);
                $vms_query[$y]['snapshot']=str_replace("false","",$vms_query[$y]['snapshot']);
                $vms_query[$y]['maintenance']=str_replace("true","checked",$vms_query[$y]['maintenance']);
                $vms_query[$y]['maintenance']=str_replace("false","",$vms_query[$y]['maintenance']);
                $VDI_query=array();
                $vdi_table_section_collapse='in';
                $vdi_collapse_button='fa-minus';
                if ($vms_query[$y]['machine_type']=='initialmachine'){
                    $VDI_query=get_SQL_array("SELECT vms.id,vms.name,vms.hypervisor,vms.machine_type,vms.source_volume,vms.snapshot,vms.maintenance,vms.filecopy,vms.state,vms.os_type,vms.lastused,clients.username,vms_tmp.name AS sourcename  FROM vms LEFT JOIN vms AS vms_tmp ON vms.source_volume=vms_tmp.id LEFT JOIN clients ON clients.id=vms.clientid WHERE vms.source_volume='{$vms_query[$y]['id']}' AND vms.machine_type = 'vdimachine' ORDER BY vms.name");
            if (isset($userConfig['table_section-'.$vms_query[$y]['id']])){
                if ($userConfig['table_section-'.$vms_query[$y]['id']] == 'hide'){
                    $vdi_table_section_collapse='';
                    $vdi_collapse_button='fa-plus';
                }
            }
        }
                echo '<tr class="table-stripe-bottom-line">
                      <td colspan="2" class="col-md-1 clickable ParentRow" id="' . $vms_query[$y]['id'] . '" data-toggle="collapse" data-target=".child-' . $vms_query[$y]['id'] . '" >' . ($y+1);
                if (!empty($VDI_query))
                    echo '<i class="fa ' . $vdi_collapse_button . ' fa-fw" id="childof-' . $vms_query[$y]['id'] . '"></i>';
                echo '</td> 
                    <td class="col-md-2"><a data-toggle="modal" href="vm_info.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id']  . '" data-target="#modalWm">' . $vms_query[$y]['name'] . '</a> </td> 
                    <td class="col-md-1">', (!empty($vms_query[$y]['machine_type'])) ? $machine_type[$vms_query[$y]['machine_type']]  : "", '</td>
                    <td class="col-md-1">' . $vms_query[$y]['sourcename'] . '</td>
                    <td class="col-md-1"><input type="checkbox" class="SnapshotCheckbox" ' . $vms_query[$y]['snapshot'] . ' data-id="' . $vms_query[$y]['id'] .  '"></td>
                    <td class="col-md-1"><input type="checkbox" class="MaintenanceCheckbox" '. $vms_query[$y]['maintenance'] . ' data-id="' . $vms_query[$y]['id'] .  '">';
                if (is_numeric($vms_query[$y]['filecopy'])){
                    echo '<div class="progress">
                            <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progress-' . $vms_query[$y]['id'] . '" style="width:100%">
                            </div>
                          </div>
                          <script>
                            countdown("' . $serviceurl . '/progress.php?vm=' . $vms_query[$y]['id']  . '","' . $vms_query[$y]['id'] . '");
                          </script>';
                        }
                        echo  '</td>
                              <td class="col-md-2">';
                        if ($vms_query[$y]['machine_type']=="initialmachine"){
                            if ($vms_query[$y]['locked']=='true')
                                $lockstatus='disabled';
                            echo '<div class="btn-group">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                ' . _("VDI control") . '<span class="caret"></span>
                                </button>
                                        <ul class="dropdown-menu">
                                            <li class="' . $lockstatus . '" id="copy-disk-from-source-button-' . $vms_query[$y]['id'] . '"><a href="#" class="CopyDiskButton" data-hypervisor="' . $sql_reply[$x]['id'] .  '" data-vm="' . $vms_query[$y]['id'] . '">' . _("Copy disk from source") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="#" class="MassMaintenanceButton" data-action="mass_on" data-source="' . $vms_query[$y]['id'] .  '">' . _("Turn maintenance on") . '</a></li>
                                            <li><a href="#" class="MassMaintenanceButton" data-action="mass_off" data-source="' . $vms_query[$y]['id'] .  '">' . _("Turn maintenance off") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li class="' . $lockstatus . '" id="PopulateMachinesButton-' . $vms_query[$y]['id'] . '"><a class="PopulateMachinesButton" href="#" data-hypervisor="' . $sql_reply[$x]['id'] .  '" data-vm="' . $vms_query[$y]['id'] .  '">' . _("Populate machines") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a class="PowerButton" href="#" data-action="mass_on" data-hypervisor="' . $sql_reply[$x]['id'] .  '" data-vm="' . $vms_query[$y]['id'] .  '">' . _("Mass power on") . '</a></li>
                                            <li><a class="PowerButton" href="#" data-action="mass_off" data-hypervisor="' . $sql_reply[$x]['id'] .  '" data-vm="' . $vms_query[$y]['id'] .  '">' . _("Mass shut down (soft)") . '</a></li>
                                            <li><a class="PowerButton" href="#" data-action="mass_destroy" data-hypervisor="' . $sql_reply[$x]['id'] .  '" data-vm="' . $vms_query[$y]['id'] .  '">' . _("Mass shut down (forced)") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="#" class="MassSnapshotButton" data-action="mass_on" data-source="' . $vms_query[$y]['id'] .  '">' . _("Turn on snapshots") . '</a></li>
                                            <li><a href="#" class="MassSnapshotButton" data-action="mass_off" data-source="' . $vms_query[$y]['id'] .  '">' . _("Turn off snapshots") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="#" class="DeleteVMButton" data-action="mass_delete" data-hypervisor="' . $sql_reply[$x]['id'] .  '" data-parent="' . $vms_query[$y]['id'] .  '">' . _("Delete all child VMs") . '</a></li>
                                            <li role="separator" class="divider"></li>';
                                            if ($vms_query[$y]['locked']=='false')
                            echo '<li><a href="#" id="lock-vm-button-' . $vms_query[$y]['id'] . '" class="LockVMButton" data-id=' . $vms_query[$y]['id'] . '>' . _("VM locked:") . '<i class="fa fa-fw fa-square-o" aria-hidden="true"></i></a></li>';
                                            else
                            echo '<li><a href="#" id="lock-vm-button-' . $vms_query[$y]['id'] . '" class="LockVMButton" data-id=' . $vms_query[$y]['id'] . '>' . _("VM locked:") . '<i class="fa fa-fw fa-check-square-o" aria-hidden="true"></i></a></li>';

                                echo '</ul>
                                    </div>';

                        }
                         echo '<div class="btn-group">
                                <button class="btn btn-default dropdown-toggle" type="button" id="VMSActionMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                    ' . _("VM Actions") . '
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="VMSActionMenu">';
                                    $vm_is_off_buttons = '';
                                    $vm_is_on_buttons = '';
                                    if ($vms_query[$y]['state'] == 'shut')
                                        $vm_is_on_buttons = 'hide';
                                    else if ($vms_query[$y]['state'] == 'running' || $vms_query[$y]['state'] == 'paused' || $vms_query[$y]['state'] == 'pmsuspended')
                                        $vm_is_off_buttons = 'hide';
                                    echo    '<li class="VMIsOffButtons-' . $vms_query[$y]['id'] . ' ' . $vm_is_off_buttons . ' lockable-vm-buttons-' . $vms_query[$y]['id'] . ' ' . $lockstatus . '"><a class="PowerButton" href="#" data-action="single" data-state="up" data-vm="' . $vms_query[$y]['id'] . '" data-hypervisor="' . $sql_reply[$x]['id'] . '"><i class="text-success fa fa-play fa-fw text-success"></i>Power up</a></li>
                                            <li class="VMIsOnButtons-' . $vms_query[$y]['id'] . ' ' . $vm_is_on_buttons . '"><a data-toggle="modal" data-target="#vmConsole" href="vm_screen.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '">
                                               <i class="fa fa-window-maximize fa-fw text-info"></i>' . _("Open console") . '</a></li>
                                            <li class="VMIsOnButtons-' . $vms_query[$y]['id'] . ' ' . $vm_is_on_buttons . '" role="separator" class="divider"></li>
                                            <li class="VMIsOnButtons-' . $vms_query[$y]['id'] . ' ' . $vm_is_on_buttons . '"><a class="PowerButton" href="#" data-action="single" data-state="down" data-vm="' . $vms_query[$y]['id'] . '" data-hypervisor="' . $sql_reply[$x]['id'] . '" data-toggle="hover">
                                               <i class="fa fa-power-off fa-fw text-danger" ></i>Soft shut down</a></li>
                                            <li class="VMIsOnButtons-' . $vms_query[$y]['id'] . ' ' . $vm_is_on_buttons . '"><a class="PowerButton" data-action="single" data-state="destroy" data-vm="' . $vms_query[$y]['id'] . '" data-hypervisor="' . $sql_reply[$x]['id'] . '" data-toggle="hover">
                                               <i class="fa fa-times-circle-o fa-fw text-danger" aria-hidden="true"></i>Forced shut down</a></li>';
                                      echo '<li role="separator" class="divider"></li>
                                            <li class="lockable-vm-buttons-' . $vms_query[$y]['id'] . ' ' . $lockstatus . '"><a href="#" class="DeleteVMButton" data-vm="' . $vms_query[$y]['id'] . '" data-hypervisor="' . $sql_reply[$x]['id'] . '">
                                                <i class="fa fa-trash-o fa-fw text-danger"></i>' . _("Delete machine") . '</a></li>
                                        </ul>
                                    </div>';
                        echo    '</td>';
                if (strtotime($vms_query[$y]['lastused']) > strtotime("-" . $return_to_pool_after . " minutes"))
                    $used_by=$vms_query[$y]['username'];
                else
                    $used_by=_("Nobody");
                if (empty($vms_query[$y]['os_type']))
                    echo '<td class="col-md-3 text-danger">' . _("Unknown") . ' &#47; ' . '<i id="VMStatusText">' . $vms_status_display . '</i>';
                else
                    echo '<td class="col-md-3">' . ucfirst($vms_query[$y]['os_type']) . ' &#47; ' . '<i id="VMStatusText-' . $vms_query[$y]['id'] . '">' . $vms_status_display . '</i><h5><small>' . $used_by . '</small></h5>';
                              echo '<div class="row hide" id="PowerProgressBar-' . $vms_query[$y]['id'] . '">
                                        <div class="col-md-3"></div>
                                        <div class="col-md-6">
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:100%"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3"></div>
                                    </div>
                                </td></tr>';
                        if ($vms_query[$y]['machine_type']=='initialmachine'){
                            $q=0;
                            if (!empty($VDI_query))
                                echo '<thead class="vdi-font">
                                <tr class="table-stripe-static child-' . $vms_query[$y]['id'] . ' collapse ' . $vdi_table_section_collapse . '">
                                    <th class="table-stripe-clear"></th>
                                    <th>#</th>
                                    <th>' . _("VDI name") . '</th>
                                    <th>' . _("Machine type") . '</th>
                                    <th>' . _("Source image") . '</th>
                                    <th>' . _("Virt-snapshot") . '</th>
                                    <th>' . _("Maintenance") . '</th>
                                    <th>' . _("Operations") . '</th>
                                    <th>' . _("OS type/Status/Used by") . '</th>
                                </tr>
                                </thead>';
                            while ($q<sizeof($VDI_query)){
                                $VDI_query[$q]['snapshot']=str_replace("true","checked",$VDI_query[$q]['snapshot']);
                                $VDI_query[$q]['snapshot']=str_replace("false","",$VDI_query[$q]['snapshot']);
                                $VDI_query[$q]['maintenance']=str_replace("true","checked",$VDI_query[$q]['maintenance']);
                                $VDI_query[$q]['maintenance']=str_replace("false","",$VDI_query[$q]['maintenance']);
                        switch ($VDI_query[$q]['state']) {
                            case "shut":
                                $vdi_status_display='<i class="text-danger">' . _("Shutoff") . '</i>';
                                $pwr_status="off";
                            break;
                                case "running":
                                $vdi_status_display='<i class="text-success">' . _("Running") . '</i>';
                                $pwr_button="text-success";
                                $pwr_status="on";
                            break;
                            case "paused":
                                $vdi_status_display='<i class="text-warning">' . _("Paused") . '</i>';
                                $pwr_status="on";
                            break;
                            case "pmsuspended":
                                $vdi_status_display='<i class="text-warning">' . _("Suspended") . '</i>';
                                $pwr_status="on";
                            break;
                            default:
                                $vdi_status_display='<i class="text-muted">' . _("Unknown") . '</i>';;
                        }
                        echo '<tr class="table-stripe-ani vdi-font child-' . $vms_query[$y]['id'] . ' collapse ' . $vdi_table_section_collapse . '"> 
                            <td class="col-md-1 table-stripe-clear"></td> 
                            <td class="col-md-1">' . ($y+1) . "-" . ($q+1) . '</td> 
                            <td class="col-md-2"><a data-toggle="modal" href="vm_info.php?vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id']  . '" data-target="#modalWm">' . $VDI_query[$q]['name'] . '</a> </td> 
                            <td class="col-md-1">' . $machine_type[$VDI_query[$q]['machine_type']] . '</td>
                            <td class="col-md-1">' . $VDI_query[$q]['sourcename'] . '</td>
                            <td class="col-md-1"><input type="checkbox" class="SnapshotCheckbox SnapshotCheckboxChild-'. $vms_query[$y]['id'] . '" ' . $VDI_query[$q]['snapshot'] . ' data-id="' . $VDI_query[$q]['id'] .  '"></td>
                            <td class="col-md-1"><input type="checkbox" class="MaintenanceCheckbox MaintenanceCheckboxChild-' . $vms_query[$y]['id'] . '" '. $VDI_query[$q]['maintenance']. ' data-id="' . $VDI_query[$q]['id'] .  '"></td>
                            <td class="col-md-1">';
                            echo '<div class="btn-group">
                                    <button class="btn btn-default dropdown-toggle" type="button" id="VDIActionMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                        ' . _("Actions") . '
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="VDIActionMenu">';
                                        $vm_is_off_buttons = '';
                                        $vm_is_on_buttons = '';
                                        if ($VDI_query[$q]['state']=='shut')
                                            $vm_is_on_buttons = 'hide';
                                        if ($pwr_status=="on")
                                            $vm_is_off_buttons = 'hide';
                                        echo '<li class="VMIsOffButtons-' . $VDI_query[$q]['id'] .' ' . $vm_is_off_buttons . '"><a class="PowerButton" href="#" data-action="single" data-state="up" data-vm="' . $VDI_query[$q]['id'] . '" data-hypervisor=' . $sql_reply[$x]['id'] . '"><i class="text-success fa fa-play fa-fw text-success"></i>Power up</a></li>';
                                        echo '<li class="VMIsOnButtons-' . $VDI_query[$q]['id'] . ' ' . $vm_is_on_buttons . '"><a data-toggle="modal" data-target="#vmConsole" href="vm_screen.php?vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '">
                                                <i class="fa fa-window-maximize fa-fw text-info"></i>' . _("Open console") . '</a></li>
                                              <li class="VMIsOnButtons-' . $VDI_query[$q]['id'] . ' ' . $vm_is_on_buttons . ' divider" role="separator" class="divider"></li>
                                              <li class="VMIsOnButtons-' . $VDI_query[$q]['id'] . ' ' . $vm_is_on_buttons . '"><a class="PowerButton" href="#" data-action="single" data-state="down" data-vm="' . $VDI_query[$q]['id'] . '" data-hypervisor="' . $sql_reply[$x]['id'] . '" data-toggle="hover">
                                                <i class="fa fa-power-off fa-fw text-danger" ></i>Soft shut down</a></li>
                                              <li class="VMIsOnButtons-' . $VDI_query[$q]['id'] . ' ' . $vm_is_on_buttons . '"><a class="PowerButton" href="#" data-action="single" data-state="destroy" data-vm="' . $VDI_query[$q]['id'] . '" data-hypervisor="' . $sql_reply[$x]['id'] . '" data-toggle="hover">
                                                <i class="fa fa-times-circle-o fa-fw text-danger" aria-hidden="true"></i>Forced shut down</a></li>
                                              <li role="separator" class="divider"></li>
                                              <li><a href="#" data-vm="' . $VDI_query[$q]['id'] . '" data-hypervisor="' . $sql_reply[$x]['id'] . '" class="DeleteVMButton">
                                                <i class="fa fa-trash-o fa-fw text-danger"></i>' . _("Delete machine") . '</a></li>
                                       </ul>
                                  </div>';
                                echo '</td>';
                if (strtotime($VDI_query[$q]['lastused']) > strtotime("-" . $return_to_pool_after . " minutes"))
                    $used_by=$VDI_query[$q]['username'];
                else
                    $used_by=_("Nobody");
                                if (empty($VDI_query[$q]['os_type']))
                                    echo '<td class="col-md-3 text-danger">' . _("Unknown") . ' &#47; ' . '<i id="VMStatusText">' . $vdi_status_display . '</i>';
                                else
                                    echo '<td class="col-md-3">' . ucfirst($VDI_query[$q]['os_type']) . ' &#47; ' . '<i id="VMStatusText-' . $VDI_query[$q]['id']  . '">' . $vdi_status_display . '</i><h5><small>' . $used_by . '</small></h5>';
                              echo '<div class="row hide" id="PowerProgressBar-' . $VDI_query[$q]['id'] . '">
                                        <div class="col-md-3"></div>
                                        <div class="col-md-6">
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:100%"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3"></div>
                                    </div>
                                </td></tr>';
                                ++$q;
                            }
                        }
                        ++$y;
                    }
        echo '</tbody>
        </table>
    </div>';
        ++$x;
     }
}
//############################################################################################
function vmPowerCycle($hypervisor, $vm, $action, $state){
    $h_reply=get_SQL_line("SELECT * FROM hypervisors WHERE id='$hypervisor'");
    ssh_connect($h_reply[2].":".$h_reply[3]);
    if ($action=="mass_on" || $action == "mass_off" || $action == "mass_destroy"){
        $child_vms=get_SQL_array("SELECT name,os_type FROM vms WHERE source_volume='$vm'");
        $x=0;
        while ($child_vms[$x]['name']){
            if ($action=="mass_on"){
                $agent_command=json_encode(array('vmname' => $child_vms[$x]['name'], 'username' => '', 'password' => '', 'os_type' => $child_vms[$x]['os_type']));
                ssh_command('echo "' . addslashes($agent_command) . '"| socat /usr/local/VDI/kvm-vdi.sock - ',true);
            }
            if ($action=="mass_off")
                ssh_command("sudo virsh shutdown " . $child_vms[$x]['name'], true);
            if ($action=="mass_destroy")
                ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true);
            ++$x;
        }
    }
    if ($action=="single"){
        $v_reply=get_SQL_array("SELECT id,name,os_type,machine_type FROM vms WHERE id='$vm'");
        if ($state=="up"){
            if ($v_reply[0]['machine_type']=='initialmachine'){//if we are powering initial machine up, we should power down all child VMs and put them to maintenance mode
                $child_vms=get_SQL_array("SELECT name,os_type FROM vms WHERE source_volume='{$v_reply[0]['id']}' AND state<>'shut'");
                $x=0;
                while ($x<sizeof($child_vms)){
                    write_log(ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true));
                    ++$x;
                }
                add_SQL_line("UPDATE vms SET maintenance='true' WHERE source_volume='{$v_reply[0]['id']}'");
            }
        $agent_command=json_encode(array('vmname' => $v_reply[0]['name'], 'username' => '', 'password' => '', 'os_type' => $v_reply[0]['os_type']));
        ssh_command('echo "' . addslashes($agent_command) . '"| socat /usr/local/VDI/kvm-vdi.sock - ',true);
        }
        if ($state=="down")
            ssh_command("sudo virsh shutdown " . $v_reply[0]['name'], true);
        if ($state=="destroy")
            ssh_command("sudo virsh destroy " . $v_reply[0]['name'], true);

    }
}
//############################################################################################
function drawVMScreen($vm, $hypervisor){
    include dirname(__FILE__) . '/../../../functions/config.php';
    $h_reply=getSQLArray("SELECT * FROM hypervisors WHERE id='$hypervisor'");
    $v_reply=get_SQL_array("SELECT * FROM vms WHERE id='$vm'");
    ssh_connect($h_reply[0]['ip'].":".$h_reply[0]['port']);
    $address=ssh_command("sudo virsh domdisplay " . $v_reply[0]['name'], true, true);
    if (!empty($h_reply[0]['address2']))
        $address=str_replace("localhost",$h_reply[0]['address2'],$address);
    else
        $address=str_replace("localhost",$h_reply[0]['ip'],$address);
    $address=str_replace("\n","",$address);
    $html5_token_value=$address;
    $html5_token_value=str_replace('spice://',"",$html5_token_value);
    $address=$address . "?password=" . $v_reply[0]['spice_password'];
    $rnd=uniqid();
    echo '<!DOCTYPE html>
         <html>
            <head>
                <meta http-equiv="content-type" content="text/html; charset=UTF-8">
                <title>' . _("VM screen") . '</title>
            </head>
        <body>
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">' . _("VM name: ") . $v_reply[0]['name'] . '</h4>
                </div>
                <div class="modal-body">
                    <img src="screenshot.php?vm=' . $vm . '&hypervisor=' . $hypervisor . '&' . $rnd . '">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="javascript:window.location=\'' . $address . '\'" target="_new" data-dismiss="modal">' . _("SPICE console") . '</button>
                    <button type="button" class="btn btn-success" onclick="dashboard_open_html5_console_click()" data-dismiss="modal">' . _("HTML5 console") . '</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">' . _("Close") .'</button>
                </div>
            </div>
        </body>
        <script>
            function dashboard_open_html5_console_click(){
                send_token(\'' . $websockets_address . '\', \'' . $websockets_port . '\', \'' . $v_reply[0]['name'] . '\', \'' . $html5_token_value . '\', \'' . $v_reply[0]['spice_password'] . '\');
            }
        </script>
    </html>';
}
//############################################################################################
function drawNewVMScreen(){
    require_once('NewVM.php');
    draw_html();
}
//############################################################################################
function process_stdout($message){
    write_log($message);
    if (mb_substr(strtolower($message), 0, 5 ) === 'error' || mb_substr(strtolower($message), 0, 9 ) === 'traceback'){
        return $message;
    }
    else
        return 0;
}
