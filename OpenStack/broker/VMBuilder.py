import threading
import logging
import requests
import time
import Variables
import json
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
        logger = logging.getLogger('kvm-vdi-broker')
        if self.ephemeral_osInstanceId: #if old machine exists, delete it
            reply = json.loads(self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/DeleteVM.php", data = {'vm_id': self.ephemeral_osInstanceId, 'broker':'true'}, verify=False, headers=Variables.http_headers))
            logger.debug("Deleting VM id: %s name: %s", self.vm_id, self.vm_name);
            if reply['delete'] == 'success':
                print ("success");
        logger.debug("Creating volume name: %s", self.vm_name);
        reply = json.loads(self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/CreateVolume.php", data = {'source': self.osInstanceId, 'vm_name': self.vm_name, 'vm_type' : 'ephemeralvdi'}, verify=False, headers=Variables.http_headers).text)
        while reply['volume']['status'] != 'available':
            reply = json.loads(self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/GetVolumeInfo.php", data = {'volume_id': reply['volume']['id']}, verify=False, headers=Variables.http_headers).text)
            time.sleep(2)
            print (reply['volume']['status'])
        logger.debug("Creating VM from id: %s with name: %s", self.osInstanceId, self.vm_name);
        reply = self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/CreateEphemeralVM.php", data = {'vm_name': self.vm_name, 'vm_type': 'ephemeralvdi', 'volume_id': reply['volume']['id'], 'source_vm': self.osInstanceId, 'target_vm': self.ephemeral_osInstanceId}, verify=False, headers=Variables.http_headers).text
        print (self.vm_name)
        print (Variables.vms_to_build)