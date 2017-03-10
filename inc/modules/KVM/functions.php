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
