# KVM-VDI OpenStack broker
  
  
Currently OpenStack provides HTML5 console connections only. This limits VDI functionality alot.
(no sound nor USB device redirection). This service is designed to redirect raw SPICE channels from  
isolated OpenStack network to public network.  
  
**Syntax**  
  
Communication with broker is done by sending json-formatted commands via UNIX socket:  
  
`/usr/local/kvm-vdi/kvm-vdi-broker.sock`
  
`{"command":"make-spice-channel","hypervisor_ip":"IP_OF_COMPUTE_NODE","spice_password":"SPICE_CHANNEL_PASSWORD","spice_port":"SPICE_CHANNEL_PORT","vm_id":"OPENSTACK_VM_ID"}`
  
Broker should create a TCP socket in public network, and listen on it for 5 seconds  
it also sends reply to UNIX socket with json-formatted message of port number SPICE client should connect to:
  
`{"spice_port":13000}`
  
Port range, which broker will use for opening public connections is configured in `/etc/kvm-vdi/kvm-vdi.cfg`:
  
    [broker]
    port_range=13000:13020
  