<?php
/*
KVM-VDI
Tadas Ustinavičius
2016-10-27
Vilnius, Lithuania.
*/
function SQL_connect(){
    include (dirname(__FILE__).'/config.php');
    $mysql_connection=mysqli_connect($mysql_host,$mysql_user,$mysql_pass);
    mysqli_select_db($mysql_connection, $mysql_db);
    return $mysql_connection;
}
function add_SQL_line($sql_line){
    $mysql_connection=SQL_connect();
    mysqli_query($mysql_connection, $sql_line) or die (mysqli_error($mysql_connection));
    mysqli_close($mysql_connection);
    return 0;
}
//##############################################################################
function get_SQL_line($sql_line){
    $mysql_connection=SQL_connect();
    $result = mysqli_fetch_row(mysqli_query($mysql_connection, $sql_line));
    mysqli_close($mysql_connection);
    return $result;
}
//##############################################################################
function get_SQL_array($sql_line){
    $query_array=array();
    $mysql_connection=SQL_connect();
    $q_string = mysqli_query($mysql_connection, $sql_line)or die (mysqli_error($mysql_connection));
    while ($row=mysqli_fetch_array($q_string)){
        $query_array[]=$row;
    }
    mysqli_close($mysql_connection);
    return $query_array;
}

function ssh_connect($address){
    include ('config.php');
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
        else//put hypervisor to maintenance if cannot connect to it
            add_SQL_line("UPDATE hypervisors SET maintenance='1' WHERE id='$hyper_id'");
        ++$x;
    }
    return $error_return;
}
//##############################################################################
function check_session(){
    if (session_status() == PHP_SESSION_NONE) 
	session_start();
    if (isset($_SESSION['logged']))
	return $_SESSION['logged'];
    else return 0;
}
//##############################################################################
function check_client_session(){
    if (session_status() == PHP_SESSION_NONE) 
	session_start();
    if ($_SESSION['client_logged'])
	return $_SESSION['client_logged'];
    else return 0;
}
//##############################################################################
function close_session(){
    if (session_status() == PHP_SESSION_NONE) 
	session_start();
    $_SESSION['logged']='';

}
//##############################################################################
//check list of variables for any empty value
function check_empty(){
    foreach(func_get_args() as $arg){
        if(empty($arg))
            return 1;
	else
	    return false;
    }
}
//#############################################################################
function set_lang(){
    include ('config.php');
    $domain = 'kvm-vdi';
    setlocale(LC_ALL, $language.'.UTF-8');
    putenv('LC_ALL='.$language);
    bindtextdomain($domain, 'locale/');
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);
}
//############################################################################
function check_db(){
    return sizeof(get_SQL_array("SHOW TABLES LIKE 'vms'"));
}
//############################################################################
function populate_db(){
    $mysql_connection=SQL_connect();
    $sql_file=file_get_contents(dirname(__FILE__) . '/../sql/vdi.sql');
    $lines=explode(';', $sql_file);
    $failure=0;
    foreach($lines as $line) { 
	$result=mysqli_query($mysql_connection,$line);
	if (!$result)
	    $failure=1;
    }
    mysqli_close($mysql_connection);
    return $failure;
}
//###########################################################################
function slash_vars(){//add slashes to all post variables.
    $post_array = array();
    $get_array = array();
    array_walk_recursive($_POST, function(&$item, $key) {
	$item = addslashes($item);
    });
    array_walk_recursive($_GET, function(&$item, $key) {
	$item = addslashes($item);
    });
}
//##########################################################################
function check_upgrade(){
    $sql_reply=get_SQL_array("SELECT valuechar FROM config WHERE name='dbversion'");
    $sql_file=dirname(__FILE__) . '/../sql/' . $sql_reply[0]['valuechar'] . ".sql";
    if(file_exists($sql_file)){
	$lines=explode(';', file_get_contents($sql_file));
	foreach($lines as $line)
	    if (!empty($line))
		add_SQL_line($line);
	return $sql_reply[0]['valuechar'];
	exit;
	}
    return 0;
    exit;
}
//#########################################################################
function write_log($message){
    include (dirname(__FILE__).'/config.php');
    if ($write_debug_log)
	error_log($message);
}
//########################################################################
function remove_specialchars($item){
    $item=str_replace("\\'",'',$item);
    $item=str_replace('\"','',$item);
    $item=str_replace('\`','',$item);
    $item=str_replace('!','',$item);
    $item=str_replace('@','',$item);
    $item=str_replace('#','',$item);
    $item=str_replace('$','',$item);
    $item=str_replace('%','',$item);
    $item=str_replace('^','',$item);
    $item=str_replace('&','',$item);
    $item=str_replace('*','',$item);
    $item=str_replace('(','',$item);
    $item=str_replace(')','',$item);
    $item=str_replace('`','',$item);
    $item=str_replace('~','',$item);
    $item=str_replace("'",'',$item);
    $item=str_replace('"','',$item);
    $item=str_replace(',','',$item);
    $item=str_replace(':','',$item);
    $item=str_replace(';','',$item);
    $item=str_replace(' ','',$item);
    $item=str_replace('%','',$item);
    $item=str_replace('|','',$item);
    $item=str_replace('{','',$item);
    $item=str_replace('}','',$item);
    $item=str_replace('?','',$item);
    $item=str_replace('+','',$item);
    return $item;
}
//#############################################################################
function list_ad_groups($username,$password,$query_user,$html5_client){
    include (dirname(__FILE__).'/config.php');
    $ldap_login_err=0;
    $ldap = ldap_connect($LDAP_host) or $ldap_login_err=1;
    ldap_bind($ldap,$query_user,$password) or  $ldap_login_err=1;
    if ($ldap_login_err){
        write_log("LDAP bind failure. Invalid credentials.");
        if (!$html5_client){
            echo 'LOGIN_FAILURE';
            exit;
        }
        else {
            header ("Location: $serviceurl/client_index.php?error=1");
            exit;
        }
    }
    else {
        $results = ldap_search($ldap,$base_dn,"(samaccountname=$username)",array("memberof","primarygroupid","displayname"));
        $entries = ldap_get_entries($ldap, $results);
    }
    $output=array();
    $token=0;
    if (isset($entries[0]['memberof']))
        $output = $entries[0]['memberof'];
    $token = $entries[0]['primarygroupid'][0];
    $fullname= $entries[0]['displayname'][0];
    if(isset($output))
        array_shift($output);
    if (isset($group_dn))
        $results2 = ldap_search($ldap,$group_dn,"(objectcategory=group)",array("distinguishedname","primarygrouptoken"));
    else
        $results2 = ldap_search($ldap,$base_dn,"(objectcategory=group)",array("distinguishedname","primarygrouptoken"));
    $entries2 = ldap_get_entries($ldap, $results2);
    ldap_close($ldap);
    array_shift($entries2);
    foreach($entries2 as $e) {
        if($e['primarygrouptoken'][0] == $token) {
            $output[] = $e['distinguishedname'][0];
            break;
        }
    }
    $group_array='';
    foreach ($output as &$value) {
        $tmp_CN=explode(",",$value);
        $tmp_CN[0]=str_replace("CN=","",$tmp_CN[0]);
        if (!empty($tmp_CN[0]))
            $group_array= $group_array . "','" . $tmp_CN[0];
    }
    return ($group_array);
}
//###########################################################################################
function list_ldap_groups($username,$password,$query_user,$html5_client){
    include (dirname(__FILE__).'/config.php');
    $ldap_login_err=0;
    $ldap = ldap_connect($LDAP_host) or  $ldap_login_err=1;
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    $base_dn = str_replace('%username%',$username,$base_dn);
    if ($ldap_login_err){
    write_log("LDAP connect failure.");
        if (!$html5_client){
            echo 'LOGIN_FAILURE';
            exit;
        }
        else {
            header ("Location: $serviceurl/client_index.php?error=1");
            exit;
        }
    }
    if($ldap) {
	$ldap_user_login_err=0;
	ldap_bind($ldap, $base_dn, $password) or $ldap_user_login_err=1;
	if ($ldap_user_login_err){
	    write_log("LDAP bind failure failure. Invalid login credentials.");
    	    if (!$html5_client){
        	echo 'LOGIN_FAILURE';
        	exit;
    	    }
    	    else {
        	header ("Location: $serviceurl/client_index.php?error=1");
        	exit;
    	    }
	}
	$ldapbind = ldap_bind($ldap, $LDAP_username, $LDAP_password) or $ldap_login_err=1;
	if ($ldap_login_err){
	    write_log("LDAP bind failure failure. Invalid bind credentials.");
    	    if (!$html5_client){
        	echo 'LOGIN_FAILURE';
        	exit;
    	    }
    	    else {
        	header ("Location: $serviceurl/client_index.php?error=1");
        	exit;
    	    }
	}
        if ($ldapbind) {
    	    $result = ldap_search($ldap,$base_dn, "(cn=*)", array($LDAP_attribute_name)) or die ("Error in search query: ".ldap_error($ldap));
    	    $data = ldap_get_entries($ldap, $result);
    	    $x=0;
	    $group_array='';
    	    while ($x<$data[0][strtolower($LDAP_attribute_name)]['count']){
        	if (!empty($data[0][strtolower($LDAP_attribute_name)][$x]))
		    $group_array= $group_array . "','" . $data[0][strtolower($LDAP_attribute_name)][$x];
        	++$x;
    	    }
	}
    }
    ldap_close($ldap);
    return $group_array;
}
//############################################################################################
function draw_html5_buttons(){
    include ('functions/config.php');
    require_once('functions/functions.php');
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
            $pool_reply=get_SQL_array("SELECT DISTINCT(pool.id), pool.name FROM poolmap_ad  LEFT JOIN pool ON poolmap_ad.poolid=pool.id LEFT JOIN ad_groups ON poolmap_ad.groupid=ad_groups.id WHERE ad_groups.name IN ($group_array)");
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
