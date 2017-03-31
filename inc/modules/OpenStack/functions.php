<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-03-29
Vilnius, Lithuania.
*/
//############################################################################################
function memcachedReadConfig(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $memcache = new Memcache;
    $memcache->connect($memcached_address, $memcached_port) or die ("Could not connect to memcached");
    $config=array();
    $config['token']=memcache_get($memcache, 'token');
    $config['token_expire']=memcache_get($memcache, 'token_expire');
    $config['compute_url']=memcache_get($memcache, 'compute_url');
    $config['volumev2_url']=memcache_get($memcache, 'volumev2_url');
    $config['image_url']=memcache_get($memcache, 'image_url');
    $config['network_url']=memcache_get($memcache, 'network_url');
    return $config;
}
//############################################################################################
function OpenStackConnect(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $memcache = new Memcache;
    $memcache->connect($memcached_address, $memcached_port) or die ("Could not connect to memcached");
    $token_expire=memcache_get($memcache, 'token_expire');
    $curr_date_time = new DateTime('now');
    $expire_date_time = new DateTime($token_expire);
    $interval = $curr_date_time->diff($expire_date_time, false);
    $minutes_left=$interval->format('%R%d') * 1440 + $interval->format('%R%h') * 60 + $interval->format('%R%i');
//    echo $minutes_left;
    if ($minutes_left>30){ //if there is still more than 30mins left of token time, do not generate a new one
        return 0;
    }
    $ch = curl_init();
    $data_string='{"auth": {"tenantName": "' . $OpenStack_tenant_name . '", "passwordCredentials": {"username": "' . $OpenStack_user_name . '", "password": "' . $OpenStack_user_password . '"}}}';
    curl_setopt($ch, CURLOPT_URL, $OpenStack_service_url . ':35357/v2.0/tokens');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );
    $result = json_decode(curl_exec($ch), TRUE);
    curl_close($ch);
    foreach ($result['access']['serviceCatalog'] as $item){
        if ($item['type'] == 'compute')
            $compute_url = $item['endpoints'][0]['adminURL'];
        if ($item['type'] == 'volumev2')
            $volumev2_url = $item['endpoints'][0]['adminURL'];
        if ($item['type'] == 'image')
            $image_url = $item['endpoints'][0]['adminURL'];
        if ($item['type'] == 'network')
            $network_url = $item['endpoints'][0]['adminURL'];
    }
    $token = $result['access']['token']['id'];
    $token_expire=$result['access']['token']['expires'];
    memcache_set($memcache, 'token', $token);
    memcache_set($memcache, 'token_expire', $token_expire);
    memcache_set($memcache, 'compute_url', $compute_url);
    memcache_set($memcache, 'volumev2_url', $volumev2_url);
    memcache_set($memcache, 'image_url', $image_url);
    memcache_set($memcache, 'network_url', $network_url);
 //   print_r($result);
}
//############################################################################################
function updateHypervisorList(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/os-hypervisors/detail');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token']
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = json_decode(curl_exec($ch), TRUE);
    curl_close($ch);
    $x=0;
    while ($x <  sizeof($result['hypervisors'])){
        $hypervisorName=$result['hypervisors'][$x]['service']['host'];
        $hypervisorIP=$result['hypervisors'][$x]['host_ip'];
        $hypervisorAddress=$result['hypervisors'][$x]['hypervisor_hostname'];
        if (!empty($hypervisorName) && !empty($hypervisorIP) && !empty($hypervisorAddress)){
            $hypervisorEntry=get_SQL_ARRAY("SELECT * FROM hypervisors WHERE name='$hypervisorName'");
            if (sizeof($hypervisorEntry) == 0){
                add_SQL_line("INSERT INTO hypervisors (name, ip, address2) VALUES ('$hypervisorName', '$hypervisorIP', '$hypervisorAddress')");
            }
            else
                add_SQL_line("UPDATE hypervisors SET name='$hypervisorName', ip='$hypervisorIP', address2='$hypervisorAddress' WHERE name='$hypervisorName'");
        }
        ++$x;
    }
//    print_r($result);
}
//############################################################################################
function updateVmList(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/servers/detail');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token']
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = json_decode(curl_exec($ch), TRUE);
    curl_close($ch);
    $x=0;
    $instanceList=array();
    //print_r( $result);
    $power_state=['Shutoff', 'Running', 'Paused', 'Crashed', 'Shutoff', 'Suspended'];
    while ($x <  sizeof($result['servers'])){
        $vmName=$result['servers'][$x]['name'];
        $vmHypervisor=$result['servers'][$x]['OS-EXT-SRV-ATTR:host'];
        $vmInstanceName=$result['servers'][$x]['OS-EXT-SRV-ATTR:instance_name'];
        $vmInstanceId=$result['servers'][$x]['id'];
        if (!empty($vmName) && !empty($vmHypervisor) && !empty($vmInstanceName) && !empty($vmInstanceId)){
            $vmEntry=get_SQL_ARRAY("SELECT * FROM vms WHERE osInstanceId='$vmInstanceId'");
            array_push($instanceList,"'" . $vmInstanceId . "'");
            if ($result['servers'][$x]['OS-EXT-STS:task_state'] != '')
                $vm_state = $result['servers'][$x]['OS-EXT-STS:task_state'];
            else 
                $vm_state = $power_state[$result['servers'][$x]['OS-EXT-STS:power_state']];
            if ($vm_state == 'powering-on')
                $vm_state = 'Powering on';
            if ($vm_state == 'powering-off')
                $vm_state = 'Powering off';
            if (sizeof($vmEntry) == 0){
                add_SQL_line("INSERT INTO vms  (name, state, osHypervisorName,  osInstanceName,  osInstanceId) VALUES ('$vmName', '$vm_state', '$vmHypervisor', '$vmInstanceName', '$vmInstanceId')");
            }
            else
                add_SQL_line("UPDATE vms SET name='$vmName', state='$vm_state', osHypervisorName='$vmHypervisor', osInstanceName='$vmInstanceName', osInstanceId='$vmInstanceId' WHERE osInstanceId='$vmInstanceId'");
        }
        ++$x;
    }
    //$notToDelete=join(', ', $instanceList);
    //if (!empty($notToDelete))//delete all instances, that still exists in DB, but are removed in OpenStack
    //    add_SQL_line("DELETE FROM vms WHERE osInstanceId NOT IN ($notToDelete)");
    //print_r($result);
}
//############################################################################################
function listConsoles($vm){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/servers/a17ac994-8311-42ab-84d0-614e1ef8f1cd/consoles');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token']
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return($result);
}
//############################################################################################
function listNetworks(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['network_url'] . '/v2.0/networks');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token']
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return($result);
}
//############################################################################################
function listFlavors(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/flavors');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token']
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return($result);
}
//############################################################################################
function listImages(){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['image_url'] . '/v2/images');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);
//    print_r(json_decode($result));
    return $result;
}

//############################################################################################
function getSnapshotInfo($snapshot_id){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['volumev2_url'] . '/snapshots/' . $snapshot_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
        'Content-type: application/json',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);
//    print_r(json_decode($result));
    return $result;
}
//############################################################################################
function getVolumeInfo($volume_id){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['volumev2_url'] . '/volumes/' . $volume_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
        'Content-type: application/json',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);
//    print_r(json_decode($result));
    return $result;
}
//############################################################################################
function getVMInfo($vm){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/servers/' . $vm);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token']
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($result,true);
    $power_state=['Shutoff', 'Running', 'Paused', 'Crashed', 'Shutoff', 'Suspended'];
    if ($result['server']['OS-EXT-STS:task_state'] != '')
        $vm_state = $result['server']['OS-EXT-STS:task_state'];
    else 
        $vm_state = $power_state[$result['server']['OS-EXT-STS:power_state']];
    if ($vm_state == 'powering-on')
        $vm_state = 'Powering on';
    if ($vm_state == 'powering-off')
        $vm_state = 'Powering off';
//    write_log(serialize($result['server']));
    $osInstanceName = $result['server']['OS-EXT-SRV-ATTR:instance_name'];
    $osHypervisorName = $result['server']['OS-EXT-SRV-ATTR:host'];
    add_SQL_line("UPDATE vms SET state = '$vm_state', osInstanceName = '$osInstanceName', osHypervisorName = '$osHypervisorName' WHERE osInstanceId='$vm'");
    return json_encode($result);
}
//############################################################################################
function vmPowerCycle($vm, $action){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    if ($action=='up')
        $data = array('os-start' => null);
    if ($action=='down')
        $data = array('os-stop' => null);
    $ch = curl_init();
    $data=json_encode($data);
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/servers/' . $vm . '/action');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
        'Content-type: application/json',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
//############################################################################################
function createSnapshot($source, $vm_name, $vm_type){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $data=array();
    $data['snapshot'] = array('name' => $vm_name, 'description' => 'For VM: ' . $vm_name . ' From: ' . $source . ' Machine type: ' . $vm_type, 'volume_id' => $source, 'force' => 'true');
    $data=json_encode($data);
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['volumev2_url'] . '/snapshots');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
        'Content-type: application/json',
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);
//    print_r(json_decode($result));
    return $result;
}
//############################################################################################
function createVolume($source, $vm_name, $vm_type){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $data=array();
    $data['volume'] = array('name' => $vm_name, 'description' => 'For VM: ' . $vm_name . ' From: ' . $source . ' Machine type: ' . $vm_type, 'source_volid' => $source, 'force' => 'true');
    $data=json_encode($data);
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['volumev2_url'] . '/volumes');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
        'Content-type: application/json',
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $result = curl_exec($ch);
    curl_close($ch);
//    print_r(json_decode($result));
    return $result;
}
//############################################################################################
function createVM($vm_name, $flavor, $snapshot_id, $networks){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $block_device = array();
    $block_device = array(array('boot_index' => '0', 'uuid' => $snapshot_id, 'source_type' => 'volume', 'delete_on_termination' => true, 'destination_type' => 'volume'));
    $data = array();
    $data['server'] = array('name' => $vm_name, 'flavorRef' => $flavor, 'availability_zone' => $OpenStack_availability_zone, 'networks' => $networks, 'block_device_mapping_v2' => $block_device);
    $ch = curl_init();
    $data=json_encode($data);
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/servers');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
        'Content-type: application/json',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
//############################################################################################
function sendToBroker($command){
    $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
    socket_connect($socket, '/usr/local/kvm-vdi/kvm-vdi-broker.sock');
    socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
    $result = socket_write($socket , $command , strlen($command));
    if (!$result)
        return $result;
    else
        $result = socket_read($socket, 1024);
    return $result;
}
//############################################################################################
function draw_dashboard_table(){
    openStackConnect();
    updateHypervisorList();
    updateVmList();
    echo '<div class="table-responsive"  style="overflow: inherit;">
            <table class="table table-striped table-hover" >
                <thead>
                    <tr>
                        <th>#</th>
                        <th>' . _("Machine name") . '</th>
                        <th>' . _("Machine type") . '</th>
                        <th>' . _("Source image") . '</th>
                        <th>' . _("Virt-snapshot") . '</th>
                        <th>' . _("Maintenance") . '</th>
                        <th>' . _("Operations") . '</th>
                        <th>' . _("OS type/Status/Used by") . '</th>
                    </tr>
                </thead>
                <tbody id="OpenstackVmTable">
                </tbody>
            </table>
        </div>';
}
//############################################################################################
function drawVMScreen($vm){
    $v_reply=get_SQL_array("SELECT * FROM vms WHERE osInstanceId='$vm'");
    echo '<!DOCTYPE html>
         <html>
            <head>
                <meta http-equiv="content-type" content="text/html; charset=UTF-8">
                <script src="inc/js/kvm-vdi-openstack.js"></script>
                <title>' . _("VM screen") . '</title>
            </head>
        <body>
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">' . _("VM name: ") . $v_reply[0]['name'] . '</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-md-1"></div>
                        <div class="col-md-3"></div>
                        <div class="col-md-8">
                            <input type="hidden" id="vm_id" value="' . $vm . '">
                            <button type="button" class="btn btn-success" id="SpiceConsoleButton">' . _("SPICE console") . '</button>
                            <button type="button" class="btn hidden btn-success" onclick="dashboard_open_html5_console_click()" data-dismiss="modal">' . _("HTML5 console") . '</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">' . _("Close") .'</button>
                        </div>
                    </div>
                    <div class="row>
                        <div class="col-md-12 text-center" id="ConsoleMessage"></div>
                    </div>
                </div>
            </div>
        </body>
        <script>
            function dashboard_open_html5_console_click(){
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
function reload_vm_info(){
}