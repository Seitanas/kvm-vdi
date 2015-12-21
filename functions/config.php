<?php
###################Dashboard config##########################
$hypervizors=array('192.168.10.1:22','192.168.10.2:22');
#Substitute local hypervisor IP address with external one if needed.
#Substituted IP adresses will be provided for SPICE remote console connections in dashboard.
$remote_spice_substitute=array('192.168.10.1'=>'172.31.1.1','192.168.10.2'=>'172.31.1.2');
$serviceurl='https://dashboardaddress/vdi';
############################################################

##################vmWare Horizon config (if used)###########
$vmView_server='view.someadress.tld';
############################################################

##################Hypervisor config#########################
$temp_folder='/data_tmp/tmp';
$backend_pass='12345';
$ssh_user='VDI';
$ssh_key_path='/var/hyper_keys/';
$hypervisor_cmdline_path='/usr/local/VDI/';
$default_bridge='br0';
$default_imagepath='/data';
$default_iso_path='/var/lib/libvirt/images';
############################################################


####################Database config#########################
$mysql_host='localhost';
$mysql_db='vdi';
$mysql_user='user';
$mysql_pass='password';
############################################################
