import json
import logging
import re
import select
import socket
import subprocess
import time
import threading
import Variables


class VMStartupService(threading.Thread):
    def __init__(self, vmname, username, password, os_type, socket_timeout):
        threading.Thread.__init__(self)
        self.vmname = vmname
        self.username = username
        self.password = password
        self.os_type = os_type
        self.socket_timeout = socket_timeout
        self.logger = logging.getLogger('kvm-vdi-agent')

    def stop(self):
        self._stop.set()

    def stopped(self):
        return self._stop.isSet()

    def run(self):
        data = ""
        self.logger.info("Starting machine %s", self.vmname)
        err = subprocess.Popen(
            "virsh start " + self.vmname,
            shell=True,
            stdout=subprocess.PIPE).communicate()
        """ get current spice channel path
           (path changes on each VM startup):
        """
        socket_path = subprocess.Popen(
            "virsh dumpxml " + self.vmname +
            "| xpath -q -e /domain/devices/channel/source/@path|grep kvm-vdi",
            shell=True, stdout=subprocess.PIPE).communicate()
        """ Remove everythig outside double quotes:
        """
        socket_path = re.findall(r'"([^"]*)"', socket_path[0])
        try:
            self.logger.debug("Opening socket %s", socket_path)
            virtio_socket = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
            virtio_socket.connect(socket_path[0])
            """ Read VMs spice channel for n seconds.
                We assume that VM must boot in n seconds and start ovirt-agent:
            """
            ready = select.select([virtio_socket], [], [], self.socket_timeout)
            is_logged = 0
            retries = 0
            query_retry = 10
            if ready[0] and self.username:
                self.logger.debug("oVirt agent is up")
                time.sleep(2)  # wait for login
                serial_message = (
                                '{"__name__":"login","username": "' +
                                self.username + '","password": "' +
                                self.password + '"}' + "\n")
                while not is_logged and retries < 5:
                    self.logger.debug(
                                     "Requesting new data for guest: %s",
                                     self.vmname)
                    """ let's ask for data from oVirt agent on guest machine:
                    """
                    virtio_socket.sendall('{"__name__":"refresh"}'+"\n")
                    time.sleep(0.5)
                    self.logger.debug("Reading SPICE channel")
                    info = virtio_socket.recv(2048)
                    info_lines = info.split("\n")
                    got_reply = 1
                    """ if there are no logged-in users, execute SSO:
                    """
                    if not is_logged and got_reply and query_retry > 9:
                        self.logger.info("Trying SSO")
                        self.logger.debug(
                                        "Sending credentials for %s to VM: %s",
                                        self.username,
                                        self.vmname)
                        virtio_socket.sendall(serial_message)
                        retries += 1
                        query_retry = 0
                    elif not got_reply:
                        self.logger.debug(
                                        "Got no info about logged-in users"
                                        "from oVirt agent. Retrying.")
                    """ go through all json responces, search for active-user:
                    """
                    for python_line in info_lines:
                        if python_line:  # if line is not empty
                            try:
                                reply_data = json.loads(python_line)
                                if reply_data['__name__'] == "active-user":
                                    got_reply = 1
                                    query_retry += 1
                                    self.logger.debug(
                                                    "User query retry: %s",
                                                    query_retry)
                                    if (reply_data["name"] == "None" or
                                            reply_data["name"] == "" or
                                            reply_data["name"] == "(unknown)"):
                                        self.logger.debug(
                                                        "There are currently "
                                                        "no users logged "
                                                        "into machine " +
                                                        self.vmname)
                                    else:
                                        self.logger.debug(
                                                        "There's user " +
                                                        reply_data["name"] +
                                                        " logged into "
                                                        "machine " +
                                                        self.vmname)
                                        self.logger.info("User login success")
                                        is_logged = 1
                                else:
                                    got_reply = 0
                            except:
                                self.logger.debug(
                                                "Non-json data: " +
                                                python_line)
                    """ Do not wait for users to fully login as on windows:
                    """
                    if self.os_type == 'linux':
                        query_retry = 10
                    time.sleep(10)
            elif not ready[0]:
                self.logger.info("Socket timeout for VM: %s", self.vmname)
            elif not self.username:
                self.logger.debug(
                                "Username is empty for VM: %s. Skipping SSO",
                                self.vmname)
        except Exception, e:
            self.logger.warning(
                            "Virtio socket %s failure for VM: %s",
                            socket_path,
                            self.vmname)
            self.logger.debug(
                            "Closing vm %s, login thread due error %s",
                            self.vmname, e)
            # cleaning credential information from memory
            self.username = None
            self.password = None
            serial_message = None
            return 0
        # cleaning credential information from memory
        self.username = None
        self.password = None
        serial_message = None
        while True and not Variables.terminate:
            """
            make thread stay till global shutdown.
            We need to have VM socket open from this thread.
            Its a hack, due problem in ovirt windows agent,
            where it consumes entire guest core if socket
            is not open from hypervisor side.
            """
            time.sleep(2)
            info = virtio_socket.recv(1024)
            if not info:
                break
        self.logger.debug("Closing socket %s", socket_path)
        virtio_socket.close()
        self.logger.info("Done: %s", self.vmname)
        self.logger.debug("Closing vm %s, login thread", self.vmname)
