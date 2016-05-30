<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2016-05-30
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
    $port=$tmp[1];
    global $connection;
    $connection = ssh2_connect($ip, $port, array('hostkey'=>'ssh-rsa'));
    ssh2_auth_pubkey_file($connection, $ssh_user, $ssh_key_path.'id_rsa.pub',$ssh_key_path.'id_rsa');
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
    include ('config.php');
    $x=0;
    while ($x<sizeof($hypervizors)){
	$tmp = explode(":", $hypervizors[$x]);
	$ip=$tmp[0];
	$port=$tmp[1];
	$sql_reply=get_SQL_line("SELECT id,maintenance FROM hypervisors WHERE ip='$ip'");
	if ($sql_reply[1]!=1){//do not try to connect to disabled hypervisor
	    if (empty($sql_reply[0]))
    		add_SQL_line("INSERT INTO  hypervisors (ip,port,maintenance) VALUES ('$ip','$port','0')");
	    else
    		add_SQL_line("UPDATE hypervisors SET ip='$ip', port='$port' WHERE id='$sql_reply[0]'");
	    $sql_reply=get_SQL_line("SELECT id FROM hypervisors WHERE ip='$ip'");
	    $hyper_id=$sql_reply[0];
    	    ssh_connect($ip . ":" . $port);
    	    $output = ssh_command("sudo virsh list --all |tail -n +3|head -n -1|awk '{print $2" . '" "' . "$3}'",true);
	    $vms=array();
    	    $output=str_replace("\n"," ",$output);
	    $vms=explode(" ",$output);
	    $y=0;
	    while ($vms[$y]){
    		$sql_reply=get_SQL_line("SELECT id FROM vms WHERE name='$vms[$y]' AND hypervisor='$hyper_id'");
        	$state=$vms[$y+1];
    		if (empty($sql_reply[0]))
            	    add_SQL_line("INSERT INTO  vms (name,hypervisor,state) VALUES ('$vms[$y]','$hyper_id','$state')");
    		else
            	    add_SQL_line("UPDATE vms SET name='$vms[$y]', hypervisor='$hyper_id', state='$state' WHERE id='$sql_reply[0]'");
        	$y=$y+2;
	    }
	}
	++$x;
    }
}
//##############################################################################
function check_session(){
    session_start();
    if ($_SESSION['logged'])
	return $_SESSION['logged'];
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
    foreach(func_get_args() as $arg)
        if(empty($arg))
            return 1;
    return false;
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
    foreach ($_POST as $p_key => $post_array) {
	$_POST[$p_key] = addslashes($post_array);
    }
    foreach ($_GET as $g_key => $get_array) {
	$_GET[$g_key] = addslashes($get_array);
    }

}