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
* Hypervisors should have a user account with sudo nopasswd rights (see hypervisors/sudoers) file.
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
Ubuntu 16

    apt-get install mariadb-server apache2 php git libapache2-mod-php php-mbstring php-gettext php-ssh2 php-imagick

Debian, Ubuntu 15 and earlier.

    apt-get install mariadb-server apache2 php5 git libapache2-mod-php5 php-gettext php5-ssh2 php5-imagick

Create empty database/user on db server.

    cd /var/www/html/
    git clone https://github.com/Seitanas/kvm-vdi
    cd kvm-vdi

Edit functions/config.php file to fit your needs.  
Change permissions on tmp/ folder and functions/clients.xml file to give webserver writeable rights.  
Go to http://yourservename/kvm-vdi  
If installation is successful, you will be redirected to login page. Default credentials are: admin/password  
  


### Hypervisor installation

**On Debian bases systems:**

    apt-get install qemu-kvm libvirt-bin sudo python python-requests virtinst

**NOTICE: Ubuntu apparmor!**  
If you are using Ubuntu with apparmor enabled, you MUST allow libvirt execute VDI binaries.  
Add the wolowing line to /etc/apparmor.d/usr.sbin.libvirtd file:

      /usr/local/VDI/* PUx,

Afterwards run:

     apparmor_parser -r /etc/apparmor.d/usr.sbin.libvirtd

Failing to do so will disable "virtual snapshots" capabilities.  


On dashboard servers and hypervisors create VDI user:

    useradd -s /bin/bash -m VDI


On dashboard server type:

    su VDI
    cd
    ssh-keygen -t rsa

copy files from /home/VDI/.ssh to /var/hyper_keys folder.
copy rsa.pub file from dashboard /var/hyper_keys folder to each of hypervisors /home/VDI/.ssh/authorized_keys file.
To check if everything works, from dashboard server type:

    ssh -i /var/hyper_keys/id_rsa VDI@hypervisor_address

If passwordless connection is established, everythin is fine.

On each hypervisor create /usr/local/VDI folder. Copy all files from "hypervisors" folder.
Edit config file accordingly.
Edit your /etc/sudoers file according to examlpe of hypervisors/sudeors file.
  


### Thin client installation

**On Debian bases systems:**

    apt-get xdotool x11-utils xwit python python-requests virt-viewer freerdp-x11 pulseaudio xinit

Download and install vmware-view viewer from VMware if needed.  
Copy files from thin_clients/ folder to your clients /usr/local/bin folder.  
Edit vdi executable, and change dashboard_path variable to fit your configuration.  
If you are using systemd, copy vdi init script from thin_clients/ folder to systemd script folder:

    cp thin_clients/vdi.service /etc/systemd/system/
    systemctl daemon-reload

Edit clients.xml file in your dashboard, specify IP address of your thin client, protocol and name of VDI machine it will use.  
Start vdi service:

    systemctl start vdi

You should see VDI machine powering up and your thin client displaying VMs monitor output.



![Alt text](http://webjail.ring.lt/vdi/vdi.jpg?raw=true&3)
![Alt text](http://webjail.ring.lt/vdi/vdi2.jpg?raw=true&1)
![Alt text](http://webjail.ring.lt/vdi/vdi3.jpg?raw=true)
![Alt text](http://webjail.ring.lt/vdi/vdi4.png?raw=true)
