<?php
/*
KVM-VDI
Tadas Ustinavičius
2016-10-19
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
    if (!empty($output))
        return $output;
    else return $error;
}
//#############################################################################
function reload_vm_info(){
    $x=0;
    $sql_reply=get_SQL_array("SELECT * FROM hypervisors WHERE maintenance=0");
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
	    while ($vms[$y]){
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

	}
	else
	    add_SQL_line("UPDATE hypervisors SET maintenance='1' WHERE id='$hyper_id'");
	++$x;
    }
    $PlainVMS = join(', ', $PlainVMS);
    if (!empty($PlainVMS))
    //remove all VMS, that do not exist on hypervisor, but still are in database
       $TrashVMS=add_SQL_line("DELETE FROM vms WHERE hypervisor='$hyper_id' AND name NOT IN ($PlainVMS)");
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