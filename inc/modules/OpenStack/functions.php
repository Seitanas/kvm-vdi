<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-11-07
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
    $config['cinderv3_url']=memcache_get($memcache, 'cinderv3_url');
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
    $curl_reply = curl_exec($ch);
    if (curl_errno($ch) > 0){
        return array('error' => array('message' => 'Connection returned error: ' . ReturnCurlError(curl_errno($ch))));
        exit;
    }
    $result = json_decode($curl_reply, TRUE);
    if ($result['error']){
        return $result;
        exit;
    }
    curl_close($ch);
    foreach ($result['access']['serviceCatalog'] as $item){
        if ($item['type'] == 'compute')
            $compute_url = $item['endpoints'][0]['adminURL'];
        if ($item['type'] == 'volumev3')
            $cinderv3_url = $item['endpoints'][0]['adminURL'];
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
    memcache_set($memcache, 'cinderv3_url', $cinderv3_url);
    memcache_set($memcache, 'image_url', $image_url);
    memcache_set($memcache, 'network_url', $network_url);
    return 0;
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
//    print_r( $result);
    $power_state=array('0' => 'Shutoff', '1' => 'Running', '2' => 'Paused', '3' => 'Crashed', '4' => 'Shutoff', '5' => 'Suspended', '7' => 'Suspended');
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
            if (sizeof($vmEntry) == 0 && $vm_state != 'deleting' && strpos($vmName, 'ephemeral') === false) // do not add ephemeral VMs to vdi (let machine creation part do the job)
                add_SQL_line("INSERT INTO vms  (name, state, osHypervisorName,  osInstanceName,  osInstanceId) VALUES ('$vmName', '$vm_state', '$vmHypervisor', '$vmInstanceName', '$vmInstanceId')");
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
    $data['os-getSPICEConsole'] = array('type' => 'spice-html5');
    $data = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/servers/' . $vm . '/action');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
        'Content-type: application/json',
    ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
    curl_setopt($ch, CURLOPT_URL,$config['cinderv3_url'] . '/snapshots/' . $snapshot_id);
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
    curl_setopt($ch, CURLOPT_URL, $config['cinderv3_url'] . '/volumes/' . $volume_id);
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
function getImageInfo($image_id){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['image_url'] . '/v2/images/' . $image_id);
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
    if (isset($result['server']['fault']))
        return array('error' => array('message' => $result['server']['fault']['message']));
    if  ($vm_state != 'deleting')
        add_SQL_line("UPDATE vms SET state = '$vm_state', osInstanceName = '$osInstanceName', osHypervisorName = '$osHypervisorName' WHERE osInstanceId='$vm'");
    return $result;
}
//############################################################################################
function vmPowerCycle($vm, $action){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    if ($action == 'up')
        $data = array('os-start' => null);
    if ($action == 'down' || $action == 'shutdown')
        $data = array('os-stop' => null);
    if ($action == 'resume')
        $data = array('resume' => null);
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
    curl_setopt($ch, CURLOPT_URL,$config['cinderv3_url'] . '/snapshots');
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
function createVolume($source, $vm_name, $vm_type, $size){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $data=array();
    $data['volume'] = array('name' => $vm_name, 'description' => 'For VM: ' . $vm_name . ' From: ' . $source . ' Machine type: ' . $vm_type, 'source_volid' => $source, 'force' => 'true');
    $data=json_encode($data);
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['cinderv3_url'] . '/volumes');
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
function createVM($vm_name, $vm_type, $flavor, $snapshot_id, $networks, $delete_on_termination, $source_type, $volume_size){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $block_device = array();
    if ($vm_type == 'sourcemachine')
        $block_device = array(array('boot_index' => '0', 'uuid' => $snapshot_id, 'source_type' => $source_type, 'delete_on_termination' => $delete_on_termination, 'destination_type' => 'volume', 'volume_size' => $volume_size));
    else
        $block_device = array(array('boot_index' => '0', 'uuid' => $snapshot_id, 'source_type' => $source_type, 'delete_on_termination' => $delete_on_termination, 'destination_type' => 'volume'));
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
function deleteVM($vm_id){
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $config=array();
    $config=memcachedReadConfig();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$config['compute_url'] . '/servers/' . $vm_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Token: ' . $config['token'],
    ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode != 204){
        switch ($httpcode){
            case 401: 
                $error = "unauthorized";
                break;
            case 403:
                $error = "forbidden";
                break;
            case 404:
                $error = "itemNotFound";
                break;
            case 409:
                $error = "conflict";
                break;
        }
        $result = array('error' => array('message' => 'OpenStack returned error: ' . $error));
    }
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
    echo '<div class="table-responsive"  style="overflow: inherit;">
            <table class="table table-striped table-hover" >
                <thead>
                    <tr>
                        <th>#</th>
                        <th>' . _("Machine name") . '</th>
                        <th>' . _("Machine type") . '</th>
                        <th>' . _("Source image") . '</th>
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
    include (dirname(__FILE__) . '/../../../functions/config.php');
    $v_reply = getSQLarray("SELECT vms.*, hypervisors.ip FROM vms LEFT JOIN hypervisors ON vms.osHypervisorName=hypervisors.name WHERE vms.osInstanceId='$vm' ");
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
                            <button type="button" class="btn btn-success" onclick="dashboard_open_html5_console_click()" data-dismiss="modal">' . _("HTML5 console") . '</button>
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
            function dashboard_open_html5_console_click(){' . "\n";
            if ($use_kvmvdi_html5_client){
                $html5_token_value = $v_reply[0]['ip'] . ":" .$v_reply[0]['osInstancePort'];
                echo 'send_token(\'' . $websockets_address . '\', \'' . $websockets_port . '\', \'' . $v_reply[0]['name'] . '\', \'' . $html5_token_value . '\', \'' . $v_reply[0]['spice_password'] . '\');';
            }
            else {
                $console = json_decode(listConsoles($vm), TRUE);
                echo "window.open('" . $console['console']['url'] . '&password=' . $v_reply[0]['spice_password'] . "', '_blank')";
            }
   echo '
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
    openStackConnect();
    updateVmList();
}
//############################################################################################
function draw_html5_buttons(){
    require_once ('HTML5Buttons.php');
    HTML5Buttons();
}
//############################################################################################
function ReturnCurlError($errno){
    $error_codes=array(
        1 => 'CURLE_UNSUPPORTED_PROTOCOL',
        2 => 'CURLE_FAILED_INIT',
        3 => 'CURLE_URL_MALFORMAT',
        4 => 'CURLE_URL_MALFORMAT_USER',
        5 => 'CURLE_COULDNT_RESOLVE_PROXY',
        6 => 'CURLE_COULDNT_RESOLVE_HOST',
        7 => 'CURLE_COULDNT_CONNECT',
        9 => 'CURLE_REMOTE_ACCESS_DENIED',
        21 => 'CURLE_QUOTE_ERROR',
        22 => 'CURLE_HTTP_RETURNED_ERROR',
        23 => 'CURLE_WRITE_ERROR',
        26 => 'CURLE_READ_ERROR',
        27 => 'CURLE_OUT_OF_MEMORY',
        28 => 'CURLE_OPERATION_TIMEDOUT',
        33 => 'CURLE_RANGE_ERROR',
        34 => 'CURLE_HTTP_POST_ERROR',
        35 => 'CURLE_SSL_CONNECT_ERROR',
        37 => 'CURLE_FILE_COULDNT_READ_FILE',
        41 => 'CURLE_FUNCTION_NOT_FOUND',
        42 => 'CURLE_ABORTED_BY_CALLBACK',
        43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
        45 => 'CURLE_INTERFACE_FAILED',
        47 => 'CURLE_TOO_MANY_REDIRECTS',
        48 => 'CURLE_UNKNOWN_TELNET_OPTION',
        51 => 'CURLE_PEER_FAILED_VERIFICATION',
        52 => 'CURLE_GOT_NOTHING',
        53 => 'CURLE_SSL_ENGINE_NOTFOUND',
        54 => 'CURLE_SSL_ENGINE_SETFAILED',
        55 => 'CURLE_SEND_ERROR',
        56 => 'CURLE_RECV_ERROR',
        58 => 'CURLE_SSL_CERTPROBLEM',
        59 => 'CURLE_SSL_CIPHER',
        60 => 'CURLE_SSL_CACERT',
        61 => 'CURLE_BAD_CONTENT_ENCODING',
        64 => 'CURLE_USE_SSL_FAILED',
        65 => 'CURLE_SEND_FAIL_REWIND',
        66 => 'CURLE_SSL_ENGINE_INITFAILED',
        67 => 'CURLE_LOGIN_DENIED',
        75 => 'CURLE_CONV_FAILED',
        76 => 'CURLE_CONV_REQD',
        77 => 'CURLE_SSL_CACERT_BADFILE',
        78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
        81 => 'CURLE_AGAIN',
        88 => 'CURLE_CHUNK_FAILED');
    return $error_codes[$errno];
}