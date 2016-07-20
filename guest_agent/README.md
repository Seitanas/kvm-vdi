# Guest agent extensions
  
**Provides SSO functionality to Windows VMs**
  
  
Based on oVirt credential provider library and oVirt guest agent.
  
copy `OVirtCredProv.dll` from `Windows/bin/yourarch` to your Windows VM system32 folder.  
run `Register.reg`  

copy `oVirtGuestService` folder to your Windows VM. Execute `OVirtGuestService.exe -install`  
Ensure, that "oVirt Guest Agent Service" is running and configured to start automatically.

