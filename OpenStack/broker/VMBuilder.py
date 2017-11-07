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
#        self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/UpdateMaintenance.php", data = {'vm_id': self.osInstanceId, 'state':'true'}, verify=False, headers=Variables.http_headers)
        if self.ephemeral_osInstanceId: #if old machine exists, delete it
            self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/UpdateMaintenance.php", data = {'vm_id': self.ephemeral_osInstanceId, 'state':'true'}, verify=False, headers=Variables.http_headers)
            logger.debug("Deleting VM id: %s name: %s", self.ephemeral_osInstanceId, self.vm_name);
            reply = json.loads(self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/DeleteVM.php", data = {'vm_id': self.ephemeral_osInstanceId, 'broker':'true'}, verify=False, headers=Variables.http_headers).text)
        logger.debug("Creating volume name: %s", self.vm_name);
        reply = json.loads(self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/CreateVolume.php", data = {'source': self.osInstanceId, 'vm_name': self.vm_name, 'vm_type' : 'ephemeralvdi'}, verify=False, headers=Variables.http_headers).text)
        if 'error' in reply:
            logger.debug ("Failed to connect to OpenStack")
            return 1
        while reply['volume']['status'] != 'available':
            reply = json.loads(self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/GetVolumeInfo.php", data = {'volume_id': reply['volume']['id']}, verify=False, headers=Variables.http_headers).text)
            time.sleep(2)
            if Variables.terminate:
                break
        logger.debug("Creating VM from id: %s with name: %s", self.osInstanceId, self.vm_name);
        reply = json.loads(self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/CreateEphemeralVM.php", data = {'vm_name': self.vm_name, 'vm_type': 'ephemeralvdi', 'volume_id': reply['volume']['id'], 'source_vm': self.osInstanceId, 'target_vm': self.ephemeral_osInstanceId}, verify=False, headers=Variables.http_headers).text)
        #query till machine is fully populated:
        new_vm_id = reply['server']['id']
        self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/UpdateMaintenance.php", data = {'vm_id': new_vm_id, 'state':'true'}, verify=False, headers=Variables.http_headers)
        new_vm_power = 1
        while True:
            logger.debug("Quering VM %s status", new_vm_id)
            try:
                response = self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/GetVMInfo.php", data = {'vm_id': new_vm_id}, verify=False, headers=Variables.http_headers).text
                reply = json.loads(response)
                if reply['server'].get('status', None) == 'ACTIVE' and new_vm_power:
                    #shutdown newly created vm:
                    logger.debug("Powering off VM %s", new_vm_id)
                    self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/PowerCycle.php", data = {'vm_id': new_vm_id, 'power_state': 'down'}, verify=False, headers=Variables.http_headers)
                    new_vm_power = 0
                elif reply['server'].get('status', None) == 'SHUTOFF':
                    break
            except Exception, err:
                logger.error("Got error in dashboard response: %s %s", err, reply)
                break

            if Variables.terminate:
                break
            time.sleep(5)
        Variables.vms_to_build.pop(self.osInstanceId, None)
        self.http_session.post(Variables.dashboard_path + "inc/infrastructure/OpenStack/UpdateMaintenance.php", data = {'vm_id': new_vm_id, 'state':'false'}, verify=False, headers=Variables.http_headers)
        logger.debug("Finishing thread")
        #print (Variables.vms_to_build)