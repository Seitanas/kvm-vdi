<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-06-15
Vilnius, Lithuania.
*/
function SQL_connect(){
    include (dirname(__FILE__).'/config.php');
    $mysql_connection=mysqli_connect($mysql_host,$mysql_user,$mysql_pass);
    mysqli_select_db($mysql_connection, $mysql_db);
    return $mysql_connection;
}
//##############################################################################
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

//##############################################################################
function getSQLArray($sql_line){
    $query_array=array();
    $mysql_connection=SQL_connect();
    $q_string = mysqli_query($mysql_connection, $sql_line)or die (mysqli_error($mysql_connection));
    while ($row=mysqli_fetch_array($q_string, MYSQLI_ASSOC)){
        $query_array[]=$row;
    }
    mysqli_close($mysql_connection);
    return $query_array;
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
    include (dirname(__FILE__) . '/config.php');
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
    include (dirname(__FILE__) . '/config.php');
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
    include (dirname(__FILE__) . '/config.php');
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
    $group_array=array();
    foreach ($output as &$value) {
        $tmp_CN=explode(",",$value);
        $tmp_CN[0]=str_replace("CN=","",$tmp_CN[0]);
        if (!empty($tmp_CN[0]))
            $group_array[]= $tmp_CN[0];
    }
    return ($group_array);
}
//###########################################################################################
function list_ldap_groups($username,$password,$query_user,$html5_client){
    include (dirname(__FILE__) . '/config.php');
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
            $LDAP_attribute_name=explode(",",$LDAP_attribute_name);
            $result = ldap_search($ldap,$base_dn, "(cn=*)", $LDAP_attribute_name) or die ("Error in search query: ".ldap_error($ldap));
            $data = ldap_get_entries($ldap, $result);
            $group_array=array();
            foreach ($LDAP_attribute_name as $AttrName){
                $x=0;
                while ($x<$data[0][strtolower($AttrName)]['count']){
                    if (!empty($data[0][strtolower($AttrName)][$x]))
                        $group_array[]=$data[0][strtolower($AttrName)][$x];
                    ++$x;
                }
            }
        }
    }
    ldap_close($ldap);
    return $group_array;
}
//############################################################################################
function get_userconf(){
    $configEntry=get_SQL_array("SELECT config FROM users WHERE id='{$_SESSION['userid']}'");
    return unserialize($configEntry[0]['config']);
}
//############################################################################################
function write_userconf($userConfig){
    $userConfig=serialize($userConfig);
    add_SQL_line("UPDATE users SET config='$userConfig' WHERE id='{$_SESSION['userid']}'");
}
//############################################################################################
include (dirname(__FILE__).'/config.php');
if (!isset($engine) || $engine == 'KVM')
    require_once(dirname(__FILE__) . '/../inc/modules/KVM/functions.php');
if ($engine == 'OpenStack')
    require_once(dirname(__FILE__) . '/../inc/modules/OpenStack/functions.php');