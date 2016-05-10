# KVM-VDI

This project aims to provide fully functional VDI solution by using open source virtualization.

**"KVM-VDI"** consists of three parts:

* **Dashboard**. A webservice, which provides virtual machine control.
* **Thin client**. A collection of scripts (thin_clients directory), which are run from thin client side (must be copied /usr/local/bin on thin client).
* **Hypervisor**. A collection of scripts (hypervisors directory) which are used at hypervisor side (must be copied to /usr/local/VDI on hypervisor).

Project uses qemu-kvm virtualization and provides VMs to thin client via SPICE protocol.
Additionally dashboard can provide thin client with a RDP session of remote machine, or a VM from vmWare Horizon VDI pool (if used). - These options are optional and are designed as a quick failover solution when KVM hypervisors are not available (maintenance etc.).

Basic architecture would look like this:
* Thin clients are booted from a network (or local storage for that matter). /usr/local/bin/vdi_init application should be run on system startup. I suggest using systemd for that. (systemd config file is provided in thin_clients directory).
* Hypervisors should have a user account with sudo nopasswd rights:
username     ALL=(ALL:ALL) NOPASSWD: /usr/bin/virsh, /usr/local/VDI/copy-file, /usr/bin/qemu-img
* Dashboard web service should be able to ssh to hypervisors via 'username' using RSA public/private keys.

Dashboard service has four types of virtual machines:

* **Simple machine** - a standard VM, which is not connected with VDI service in any way.
* **Source machine** - a VM, which is used as source image - the "Initial machine" will be run from the copy of it's drive image.
* **Initial machine** - a VM, which will provide a bootable source for VDI VMs.
* **VDI** - a virtual machine, which runs from "Initial machine's" disk image. All changes are written to it's snapshot drive, or virtual snapshot (if enabled).

* **Virtual snapshot** - if marked, VM will write disk changes to a temporary location (defined in hypervisor's kvm-snap file). After machine is shut down (or rebooted, depending on its libvirt configuration), snapshot will be deleted.
* **Maintenance** - if marked, VM will not be provided to thin client.


To provide a VDI enviroment you should take folowing steps:
Create a "Source machine" and install a guest operating system to it.
Create required number of VDI machines. 
Copy "Source machine's" disk image to "Initial machine's" disk image  ("Copy disk from source" button).
Create snapshots for all VDI VM's from "Initial machine's" disk image ("Populate machines" button);
Configure "clients.xml" file, to provide thin clients with it's own VDI VM.


### Dashboard installation

**On Debian bases systems:**

Note: you can use mysql server instead of Maria-db  
apt-get install mariadb-server apache2 php git libapache2-mod-php php-mbstring php-gettext php-ssh2  
Create empty database/user on db server.  
cd /var/www/html/  
git clone https://github.com/Seitanas/kvm-vdi  
cd kvm-vdi  
Edit functions/config.php file to fit your needs.  
Go to http://yourservename/kvm-vdi  
If installation is successful, you will be redirected to login page. Default credentials are: admin/password
  

![Alt text](http://webjail.ring.lt/vdi/vdi.jpg?raw=true&1)
![Alt text](http://webjail.ring.lt/vdi/vdi2.jpg?raw=true&1)
![Alt text](http://webjail.ring.lt/vdi/vdi3.jpg?raw=true)
![Alt text](http://webjail.ring.lt/vdi/vdi4.png?raw=true)
