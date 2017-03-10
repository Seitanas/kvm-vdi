<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-03-10
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
function ssh_command($command,$blocking){
    global $connection;
    write_log("Executing: " . preg_replace('%\\"password.*?,%i', '"password\":\"*****\",',$command));
    $reply = ssh2_exec($connection,$command);
    $errorReply = ssh2_fetch_stream($reply, SSH2_STREAM_STDERR);
    stream_set_blocking($reply, $blocking);
    stream_set_blocking($errorReply, $blocking);
    $output= stream_get_contents($reply);
    $error=stream_get_contents($errorReply);
    if (!empty($error))
        write_log($error);
    if (!empty($output))
        return $output;
    if (!empty($error)){
        $output=$error . $output;
        return $output;
    }
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
    include (dirname(__FILE__) . '/../../../functions/config.php');
    require_once(dirname(__FILE__) . '/../../../functions/functions.php');
    slash_vars();
    if (!check_client_session()){
        exit;
    }
    $userid=$_SESSION['userid'];
    $username=$_SESSION['username'];
    echo'<div class="container">
        <div class="row">
        <div class="alert alert-warning hidden" id="warningbox">
        </div>';
        $last_reload=get_SQL_array ("SELECT id FROM config WHERE name='lastreload' AND valuedate > DATE_SUB(NOW(), INTERVAL 30 SECOND) LIMIT 1"); //if there was no reload of VM list in 30 seconds, initiate reload.
        if (!isset($last_reload[0]['id'])){
            add_SQL_line("INSERT INTO config (name,valuedate) VALUES ('lastreload',NOW()) ON DUPLICATE KEY UPDATE valuedate=NOW()");
            reload_vm_info();
        }
        if ($_SESSION['ad_user']=='yes'||$_SESSION['ad_user']=='LDAP'){
            $group_array=$_SESSION['group_array'];
            if(!empty($group_array)){
                $pool_reply=get_SQL_array("SELECT DISTINCT(pool.id), pool.name FROM poolmap_ad  LEFT JOIN pool ON poolmap_ad.poolid=pool.id LEFT JOIN ad_groups ON poolmap_ad.groupid=ad_groups.id WHERE ad_groups.name IN ($group_array)");
            }
        }
        else
            $pool_reply=get_SQL_array("SELECT pool.id, pool.name FROM poolmap  LEFT JOIN pool ON poolmap.poolid=pool.id WHERE clientid='$userid'");
        $x=0;
        while ($x<sizeof($pool_reply)){
            $vm_count=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}' AND vms.maintenance!='true' AND vms.locked='false' AND hypervisors.maintenance!=1");
            $vm_count_available=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id  WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}' AND vms.maintenance='false' AND vms.locked!='true' AND hypervisors.maintenance!=1 AND vms.lastused < DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE) ");
            $already_have=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id  WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}'AND vms.maintenance!='true' AND hypervisors.maintenance!=1 AND vms.clientid='$userid' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE)");
            $vm_image="text-warning";
            $provided_vm=array();
            $provided_vm[0]['name']="none";
            if ($already_have[0][0]==1){
                $vm_image="text-success";
                $provided_vm=get_SQL_array("SELECT vms.name,vms.state,vms.id FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id  WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}'AND vms.maintenance!='true' AND hypervisors.maintenance!=1 AND vms.clientid='$userid' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE)");
            }
            else if ($vm_count_available[0][0]==0)
                $vm_image="text-muted";
            if (!isset($provided_vm[0]['state']))
                $provided_vm[0]['state']='';
            $pm_icons="";
            if ($provided_vm[0]['state']=='running'||$provided_vm[0]['state']=='pmsuspended'||$provided_vm[0]['state']=='paused'){
                $pm_icons='<a href="#" class="shutdown"  id="' . $provided_vm[0]['id'] . '"><i class="pull-left fa fa-stop-circle-o text-danger" title="' . _("Shutdown machine") . '"></i></a>';
                $pm_icons=$pm_icons.'<a href="#" class="terminate"  id="' . $provided_vm[0]['id'] . '"><i class="pull-left fa fa-times-circle-o text-danger" title="' . ("Terminate machine") . '"></i></a>';
            }
            echo'<div class="col-md-2">';
            echo '<div class="row text-info">
                <div class="panel panel-default">
                <div class="panel-heading">
                <div class="row">
                <div class="col-xs-4">
                <small>' . $pm_icons  . '</small>
                </div>
                <div class="col-xs-8">
                <small>' . $provided_vm[0]['name'] . '</small>
                </div>
                </div>
                <div class="row">
                <div class="text-center">
                    <a href="#" id="' . $pool_reply[$x]['id'] . '" class="pools">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-square-o fa-stack-2x"></i>
                        <i class="fa fa-power-off fa-stack-1x ' . $vm_image . '"></i>
                    </span>
                    </a>
                </div>
                </div>
                <div class="row text-center">
                    <div>
                        <span>' . $pool_reply[$x]['name'] . '</span>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left"><small>Pool size: ' . $vm_count[0][0] . '</small></span>
                <span class="pull-right"><small>Available: ' . $vm_count_available[0][0] . '</small></span>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
</div>'."\n";
        ++$x;
        if ((($x % 4) / 4)==0)//number of columns
    echo '</div>' . "\n". '<div class="row">' . "\n";

    }
    echo '</div>
        </div>';

}
//############################################################################################
function draw_dashboard_table(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
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
        if (!empty($sql_reply[$x]['name']))
            $hypervisor_name=$sql_reply[$x]['name'];
        else
            $hypervisor_name=$sql_reply[$x]['ip'];
        echo '<h1 class="sub-header">' .  _("Hypervisor: ") . $hypervisor_name;
        if (!$sql_reply[$x]['maintenance'])
            echo '<a href="hypervisor.php?maintenance=1&id=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn glyphicon glyphicon-ok-circle btn-success"> ' . _("Enabled") . '</a>';
        else {
            echo '<a href="hypervisor.php?maintenance=0&id=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  class="btn glyphicon glyphicon-ban-circle btn-default"> ' . _("Disabled") . '</a>';
            $table_status="hypervisor-screen-disabled";
        }
        echo '</h1>
                <div class="table-responsive"  style="overflow: inherit;">
                <table class="table table-striped table-hover ' . $table_status . '">
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
                $pwr_status="on";
                $lockstatus='';
                $pwr_button="btn-success";
                switch ($vms_query[$y]['state']) {
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
                      <td colspan="2" class="col-md-1 clickable parent" id="' . $vms_query[$y]['id'] . '" data-toggle="collapse" data-target=".child-' . $vms_query[$y]['id'] . '" >' . ($y+1);
                if (!empty($VDI_query))
                    echo '<i class="fa ' . $vdi_collapse_button . ' fa-fw" id="childof-' . $vms_query[$y]['id'] . '"></i>';
                echo '</td> 
                    <td class="col-md-2"><a data-toggle="modal" href="vm_info.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id']  . '" data-target="#modalWm">' . $vms_query[$y]['name'] . '</a> </td> 
                    <td class="col-md-1">', (!empty($vms_query[$y]['machine_type'])) ? $machine_type[$vms_query[$y]['machine_type']]  : "", '</td>
                    <td class="col-md-1">' . $vms_query[$y]['sourcename'] . '</td>
                    <td class="col-md-1"><input type="checkbox" '. $vms_query[$y]['snapshot'] . " onclick='handleSnapshot(this);' " . 'id="' . $vms_query[$y]['id'] .  '"></td>
                    <td class="col-md-1"><input type="checkbox" '. $vms_query[$y]['maintenance']. " onclick='handleMaintenance(this);' " . 'id="' . $vms_query[$y]['id'] .  '">';
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
                                            <li class="' . $lockstatus . '" id="copy-disk-from-source-button-' . $vms_query[$y]['id'] . '"><a href="copy_disk.php?hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] . '" onclick="return confirmation1();">' . _("Copy disk from source") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="maintenance.php?action=mass_on&source=' . $vms_query[$y]['id'] .  '">' . _("Turn maintenance on") . '</a></li>
                                            <li><a href="maintenance.php?action=mass_off&source=' . $vms_query[$y]['id'] .  '">' . _("Turn maintenance off") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li class="' . $lockstatus . '" id="populate-machines-button-' . $vms_query[$y]['id'] . '"><a href="populate.php?hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '" onclick="return confirmation();" >' . _("Populate machines") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="power.php?action=mass_on&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass power on") . '</a></li>
                                            <li><a href="power.php?action=mass_off&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass shut down (soft)") . '</a></li>
                                            <li><a href="power.php?action=mass_destroy&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Mass shut down (forced)") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="snapshot.php?action=mass_on&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Turn on snapshots") . '</a></li>
                                            <li><a href="snapshot.php?action=mass_off&hypervisor=' . $sql_reply[$x]['id'] .  '&vm=' . $vms_query[$y]['id'] .  '">' . _("Turn off snapshots") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="delete_vm.php?action=mass_delete&hypervisor=' . $sql_reply[$x]['id'] .  '&parent=' . $vms_query[$y]['id'] .  '" onclick="return confirmation2();">' . _("Delete all child VMs") . '</a></li>
                                            <li role="separator" class="divider"></li>';
                                            if ($vms_query[$y]['locked']=='false')
                            echo '<li><a href="#" id="lock-vm-button-' . $vms_query[$y]['id'] . '" class="lock-vm-button-click" data-id=' . $vms_query[$y]['id'] . '>' . _("VM locked:") . '<i class="fa fa-fw fa-square-o" aria-hidden="true"></i></a></li>';
                                            else
                            echo '<li><a href="#" id="lock-vm-button-' . $vms_query[$y]['id'] . '" class="lock-vm-button-click" data-id=' . $vms_query[$y]['id'] . '>' . _("VM locked:") . '<i class="fa fa-fw fa-check-square-o" aria-hidden="true"></i></a></li>';

                                echo '</ul>
                                    </div>';

                        }
                         echo '<div class="btn-group">
                                <button class="btn btn-default dropdown-toggle" type="button" id="VMSActionMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                    ' . _("VM Actions") . '
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="VMSActionMenu">';
                                    if ($vms_query[$y]['state']=='shut'){
                                        echo  '<li class="lockable-vm-buttons-' . $vms_query[$y]['id'] . ' ' . $lockstatus . '"><a href="power.php?action=single&state=up&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '"><i class="text-success fa fa-play fa-fw text-success"></i>Power up</a></li>';
                                    }
                                    if ($pwr_status=="on"){
                                      echo '<li><a data-toggle="modal" data-target="#vmConsole" href="vm_screen.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '">
                                                <i class="fa fa-window-maximize fa-fw text-info"></i>' . _("Open console") . '</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="power.php?action=single&state=down&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                                                <i class="fa fa-power-off fa-fw text-danger" ></i>Soft shut down</a></li>
                                            <li><a href="power.php?action=single&state=destroy&vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                                                <i class="fa fa-times-circle-o fa-fw text-danger" aria-hidden="true"></i>Forced shut down</a></li>';
                                      }
                                      echo '<li role="separator" class="divider"></li>
                                            <li class="lockable-vm-buttons-' . $vms_query[$y]['id'] . ' ' . $lockstatus . '"><a href="delete_vm.php?vm=' . $vms_query[$y]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                                                <i class="fa fa-trash-o fa-fw text-danger"></i>' . _("Delete machine") . '</a></li>
                                        </ul>
                                    </div>';
                        echo    '</td>';
                if (strtotime($vms_query[$y]['lastused']) > strtotime("-" . $return_to_pool_after . " minutes"))
                    $used_by=$vms_query[$y]['username'];
                else
                    $used_by=_("Nobody");
                if (empty($vms_query[$y]['os_type']))
                    echo '<td class="col-md-3 text-danger">' . _("Unknown") . ' &#47; ' . $vms_status_display . '</td>';
                else
                    echo '<td class="col-md-3">' . ucfirst($vms_query[$y]['os_type']) . ' &#47; ' . $vms_status_display . '<h5><small>' . $used_by . '</small></h5></td>';
                              echo '</tr>'; 
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
                            <td class="col-md-1"><input type="checkbox" '. $VDI_query[$q]['snapshot'] . " onclick='handleSnapshot(this);' " . 'id="' . $VDI_query[$q]['id'] .  '"></td>
                            <td class="col-md-1"><input type="checkbox" '. $VDI_query[$q]['maintenance']. " onclick='handleMaintenance(this);' " . 'id="' . $VDI_query[$q]['id'] .  '"></td>
                            <td class="col-md-1">';
                            echo '<div class="btn-group">
                                    <button class="btn btn-default dropdown-toggle" type="button" id="VDIActionMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                        ' . _("Actions") . '
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="VDIActionMenu">';
                                        if ($VDI_query[$q]['state']=='shut'){
                                            echo  '<li><a href="power.php?action=single&state=up&vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '"><i class="text-success fa fa-play fa-fw text-success"></i>Power up</a></li>';

                                        }
                                        if ($pwr_status=="on"){
                                            echo '<li><a data-toggle="modal" data-target="#vmConsole" href="vm_screen.php?vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '">
                                                    <i class="fa fa-window-maximize fa-fw text-info"></i>' . _("Open console") . '</a></li>
                                                 <li role="separator" class="divider"></li>
                                                 <li><a href="power.php?action=single&state=down&vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover" onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                                                    <i class="fa fa-power-off fa-fw text-danger" ></i>Soft shut down</a></li>
                                                 <li><a href="power.php?action=single&state=destroy&vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" data-toggle="hover"  onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                                                    <i class="fa fa-times-circle-o fa-fw text-danger" aria-hidden="true"></i>Forced shut down</a></li>';
                                        }
                                        echo '<li role="separator" class="divider"></li>
                                            <li><a href="delete_vm.php?vm=' . $VDI_query[$q]['id'] . '&hypervisor=' . $sql_reply[$x]['id'] . '" onclick="return confirmBox(' . "'" . _("Are you sure?") . "'" . ');">
                                            <i class="fa fa-trash-o fa-fw text-danger"></i>' . _("Delete machine") . '</a></li>
                                       </ul>
                                  </div>';
                                echo '</td>';
                if (strtotime($VDI_query[$q]['lastused']) > strtotime("-" . $return_to_pool_after . " minutes"))
                    $used_by=$VDI_query[$q]['username'];
                else
                    $used_by=_("Nobody");
                                if (empty($VDI_query[$q]['os_type']))
                                    echo '<td class="col-md-3 text-danger">' . _("Unknown") . ' &#47; ' . $vdi_status_display . '</td>';
                                else
                                    echo '<td class="col-md-3">' . ucfirst($VDI_query[$q]['os_type']) . ' &#47; ' . $vdi_status_display . ' <h5><small>' . $used_by . '</small></h5></td>';
                                echo '</tr>';
                                ++$q;
                            }
                        }
                        ++$y;
                    }
        echo '</tbody>
        </table>
        <?php';
        ++$x;
     }
}