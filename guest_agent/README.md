# Guest agent extensions
  
**Provides SSO functionality to Windows VMs**
  
  
Based on oVirt credential provider library and oVirt guest agent.
  
copy `OVirtCredProv.dll` from `Windows/bin/yourarch` to your Windows VM system32 folder.  
run `Register.reg`

copy `oVirtGuestService` folder to your Windows VM. Execute `OVirtGuestService.exe -install`


**Linux**
  

apt-get install kdebase-workspace-dev libqt4-core libqt4-gui libqt4-dbus libpam-dev libqt4-dev
