<?php
//###########################################################################################
function HTML5Buttons(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    require_once(dirname(__FILE__) . '/../../../functions/functions.php');
    slash_vars();
    set_lang();
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
            openStackConnect();
            updateVmList();
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
            $vm_count=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.source_volume LEFT JOIN hypervisors ON vms.osHypervisorName=hypervisors.name 
            WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}' AND vms.maintenance != 'true' AND vms.locked = 'false'");
 
            $vm_count_available=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.source_volume 
            LEFT JOIN hypervisors ON vms.osHypervisorName=hypervisors.name  WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}' 
            AND vms.maintenance != 'true' AND vms.locked != 'true' AND vms.clientid = 0 AND vms.lastused < DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE) ");

            $already_have=get_SQL_array("SELECT COUNT(*) FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.source_volume LEFT JOIN hypervisors
            ON vms.osHypervisorName = hypervisors.name  WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}'AND vms.maintenance != 'true' AND vms.clientid = '$userid'
            AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE)");
            $vm_image="text-warning";
            $provided_vm=array();
            $provided_vm[0]['name']=_("none");
            if ($already_have[0][0] == 1){//if vm was already provided within $return_to_pool_after period
                $vm_image="text-success";
                $provided_vm=get_SQL_array("SELECT vms.name, vms.state, vms.id, vms.osInstanceId FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.source_volume LEFT JOIN hypervisors ON vms.osHypervisorName = hypervisors.name 
                WHERE poolmap_vm.poolid='{$pool_reply[$x]['id']}' AND vms.maintenance != 'true' AND vms.clientid = '$userid' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE)");
            }
            else if ($vm_count_available[0][0]==0)
                $vm_image="text-muted";
            if (!isset($provided_vm[0]['state']))
                $provided_vm[0]['state']='';
            $pm_icons="";
            if ($provided_vm[0]['state'] == 'Running' || $provided_vm[0]['state'] == 'pmsuspended' || $provided_vm[0]['state'] == 'paused'){
                $pm_icons='<a href="#" class="shutdown"  id="' . $provided_vm[0]['osInstanceId'] . '"><i class="pull-left fa fa-stop-circle-o text-danger" title="' . _("Shutdown machine") . '"></i></a>';
            }
            echo'<div class="col-md-2">';
            $provided_vm[0]['name'] = str_replace('-ephemeral', '', $provided_vm[0]['name']);
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
                <span class="pull-left"><small>' . _("Pool size:") . $vm_count[0][0] . '</small></span>
                <span class="pull-right"><small>' . _("Available:") . $vm_count_available[0][0] . '</small></span>
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
//###########################################################################################