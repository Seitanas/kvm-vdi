import threading
import logging
import requests
import time
import Variables
from requests.packages.urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)



class VMBuilder(threading.Thread):
    def __init__(self, vm_name, osInstanceId, ephemeral_osInstanceId, http_session):
        threading.Thread.__init__(self)
        self.vm_name = vm_name
        self.osInstanceId = osInstanceId
        self.ephemeral_osInstanceId = ephemeral_osInstanceId
        self.http_session = http_session
    def stop(self):
        self._stop.set()
    def stopped(self):
        return self._stop.isSet()
    def run(self):
        if self.ephemeral_osInstanceId: #if old machine exists, delete it
            self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/DeleteVM.php", data={'osInstanceId': self.ephemeral_osInstanceId}, verify=False, headers=Variables.http_headers)
        print (self.vm_name)
        print (Variables.vms_to_build)