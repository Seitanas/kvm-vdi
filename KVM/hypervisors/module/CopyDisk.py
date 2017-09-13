from ConfigParser import ConfigParser
import logging
import os
import requests
from requests.packages.urllib3.exceptions import InsecureRequestWarning
import threading
import subprocess
import Variables
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)


class CopyDisk(threading.Thread):
    def __init__(
                self,
                source_file,
                destination_file,
                vm,
                backend_address,
                backend_password,
                delete_before_copy):
        threading.Thread.__init__(self)
        self.source_file = source_file
        self.destination_file = destination_file
        self.vm = vm
        self.backend_address = backend_address
        self.backend_password = backend_password
        self.delete_before_copy = delete_before_copy
        self.logger = logging.getLogger('kvm-vdi-agent')

    def stop(self):
        self._stop.set()

    def stopped(self):
        return self._stop.isSet()

    def run(self):

        requests.post(
                    self.backend_address +
                    "/backend.php",
                    data={
                        'pass': self.backend_password,
                        'vm': self.vm,
                        'data': '0'},
                    verify=False,
                    headers=Variables.http_headers)

        if self.delete_before_copy:
            dst = self.destination_file
        else:
            dst = self.destination_file + ".tmp"
        cmd = "qemu-img convert -p -O qcow2 " + self.source_file + " " + dst
        """ We are using qemu-img to copy image files, because qemu-img does not
            copy zero-valued data to target disk.
            So destination image will be smaller.
            This removes unnecessary IO write operations on target drive.
        """
        self.logger.debug("Initiating copy command: %s", cmd)
        proc = subprocess.Popen(
                                cmd.split(),
                                stdin=subprocess.PIPE,
                                stdout=subprocess.PIPE,
                                universal_newlines=True)
        for progress in iter(proc.stdout.readline, ''):
            progress = progress.replace(" ", "")
            progress = progress.replace("(", "")
            progress = progress.replace("/100%)", "")
            percentage = int(float(progress))
            requests.post(
                        self.backend_address +
                        "/backend.php",
                        data={
                            'pass': self.backend_password,
                            'vm': self.vm,
                            'data': percentage},
                        verify=False,
                        headers=Variables.http_headers)
        if not self.delete_before_copy:
            self.logger.debug(
                            "Moving temporary image "
                            "file over destination file.")
            os.rename(dst, self.destination_file)
        self.logger.info("Copy thread finished")
