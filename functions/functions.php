<?php
/*
KVM-VDI
Tadas Ustinavičius
2016-08-18
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
	    $vms=array();
    	    $output=str_replace("\n"," ",$output);
	    $vms=explode(" ",$output);
	    $y=0;
	    while ($vms[$y]){
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
}
//##############################################################################
function check_session(){
    session_start();
    if (isset($_SESSION['logged']))
	return $_SESSION['logged'];
    else return 0;
}
//##############################################################################
function check_client_session(){
    session_start();
    if ($_SESSION['client_logged'])
	return $_SESSION['client_logged'];
    else return 0;
}
//##############################################################################
function close_session(){
    session_start();
    session_unset();
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