#
# Copyright 2010-2012 Red Hat, Inc. and/or its affiliates.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#   http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# Refer to the README and COPYING files for full details of the license.
#

import logging
import socket
import struct
import thread
from threading import Event
import time

import hooks
import timezone
from VirtIoChannel import VirtIoChannel


multiproc = None
try:
    import multiprocessing
    multiproc = multiprocessing
except ImportError:
    class MultiProcessingFake:
        def cpu_count(self):
            return -1
    multiproc = MultiProcessingFake()


_MAX_SUPPORTED_API_VERSION = 3
_DISABLED_API_VALUE = 0

_MESSAGE_MIN_API_VERSION = {
    'active-user': 0,
    'applications': 0,
    'completion': 3,
    'disks-usage': 0,
    'echo': 0,
    'fqdn': 0,
    'heartbeat': 0,
    'host-name': 0,
    'memory-stats': 0,
    'network-interfaces': 0,
    'number-of-cpus': 1,
    'os-version': 0,
    'os-info': 2,
    'session-lock': 0,
    'session-logoff': 0,
    'session-logon': 0,
    'session-shutdown': 0,
    'session-startup': 0,
    'session-unlock': 0,
    'timezone': 2,
    'containers': 3}


# Return a safe (password masked) repr of the credentials block.
def safe_creds_repr(creds):
    int_len = struct.calcsize('>I')
    user_len = struct.unpack('>I', creds[:int_len])[0]
    pass_len = len(creds) - user_len - int_len - 1
    cut = user_len + int_len
    return repr(creds[:cut] + ('*' * 8) + creds[cut + pass_len:])


class DataRetriverBase:
    def __init__(self):
        self.apiVersion = _DISABLED_API_VALUE
        self.memStats = {
            'mem_total': 0,
            'mem_free': 0,
            'mem_unused': 0,
            'swap_in': 0,
            'swap_out': 0,
            'pageflt': 0,
            'majflt': 0}

    def onAPIVersionUpdated(self, oldVersion, newVersion):
        pass

    def getAPIVersion(self):
        return self.apiVersion

    def setAPIVersion(self, version):
        oldVersion = self.apiVersion
        try:
            version = int(version)
        except ValueError:
            logging.info("Invalid api version value '%s' set. Version value "
                         "not changed.", version)
            return

        if _MAX_SUPPORTED_API_VERSION < version:
            logging.debug("API version requested (%d) higher than known (%d). "
                          "Using max known version.", version,
                          _MAX_SUPPORTED_API_VERSION)
            version = _MAX_SUPPORTED_API_VERSION

        if version == self.apiVersion:
            logging.debug("API version %d already set, no update necessary",
                          version)
            return
        self.apiVersion = version

        logging.info("API Version updated from %d to %d", oldVersion, version)
        try:
            self.onAPIVersionUpdated(oldVersion, version)
        except Exception:
            logging.exception("onAPIVersionUpdated failed")

    def getMachineName(self):
        pass

    def getOsVersion(self):
        pass

    def getContainerList(self):
        pass

    def getAllNetworkInterfaces(self):
        pass

    def getApplications(self):
        pass

    def getAvailableRAM(self):
        pass

    def getUsers(self):
        pass

    def getActiveUser(self):
        pass

    def getDisksUsage(self):
        pass

    def getDiskMapping(self):
        pass

    def getMemoryStats(self):
        pass

    def getFQDN(self):
        return socket.getfqdn()

    def getOsInfo(self):
        pass

    def getNumberOfCPUs(self):
        """
        Reports the number of CPUs or -1 if this was not implemented for the
        current OS/Platform
        """
        try:
            return multiproc.cpu_count()
        except NotImplementedError:
            return -1

    def getTimezoneInfo(self):
        return timezone.get_timezone_info()


class AgentLogicBase:

    def __init__(self, config):
        logging.debug("AgentLogicBase:: __init__() entered")
        self.wait_stop = Event()
        self.heartBitRate = config.getint("general", "heart_beat_rate")
        self.userCheckRate = config.getint("general", "report_user_rate")
        self.appRefreshRate = config.getint("general",
                                            "report_application_rate")
        self.disksRefreshRate = config.getint("general", "report_disk_usage")
        self.numCPUsCheckRate = config.getint("general", "report_num_cpu_rate")
        self.activeUser = ""
        self.vio = VirtIoChannel(config.get("virtio", "device"))
        self.dr = None
        self.commandHandler = None

    def _send(self, name, arguments=None):
        version = _MESSAGE_MIN_API_VERSION.get(name, None)
        if version is None:
            logging.error('Undocumented message "%s"', name)
        elif version <= self.dr.getAPIVersion():
            logging.debug("Sending %s with args %s" % (name, arguments))
            self.vio.write(name, arguments or {})
        else:
            logging.debug("Message %s not supported by api version %d.",
                          name, self.dr.getAPIVersion())

    def run(self):
        logging.debug("AgentLogicBase:: run() entered")
        thread.start_new_thread(self.doListen, ())
        thread.start_new_thread(self.doWork, ())

        # Yuck! It's seem that Python block all signals when executing
        # a "real" code. So there is no way just to sit and wait (with
        # no timeout).
        # Try breaking out from this code snippet:
        # $ python -c "import threading; threading.Event().wait()"
        while not self.wait_stop.isSet():
            self.wait_stop.wait(1)

    def stop(self):
        logging.debug("AgentLogicBase:: baseStop() entered")
        self.wait_stop.set()

    def doWork(self):
        logging.debug("AgentLogicBase:: doWork() entered")
        self.sendInfo()
        self.sendUserInfo()
        self.sendAppList()
        self.sendContainerList()
        self.sendFQDN()
        self.sendTimezone()
        self.sendOsInfo()
        counter = 0
        hbsecs = self.heartBitRate
        appsecs = self.appRefreshRate
        disksecs = self.disksRefreshRate
        usersecs = self.userCheckRate
        numcpusecs = self.numCPUsCheckRate
        reportedVersion = _DISABLED_API_VALUE

        try:
            while not self.wait_stop.isSet():
                counter += 1
                hbsecs -= 1
                if hbsecs <= 0:
                    self._send('heartbeat',
                               {'free-ram': self.dr.getAvailableRAM(),
                                'memory-stat': self.dr.getMemoryStats(),
                                'apiVersion': reportedVersion})
                    reportedVersion = _MAX_SUPPORTED_API_VERSION
                    hbsecs = self.heartBitRate
                usersecs -= 1
                if usersecs <= 0:
                    self.sendUserInfo()
                    usersecs = self.userCheckRate
                appsecs -= 1
                if appsecs <= 0:
                    self.sendAppList()
                    self.sendContainerList()
                    self.sendInfo()
                    self.sendFQDN()
                    appsecs = self.appRefreshRate
                disksecs -= 1
                if disksecs <= 0:
                    self.sendDisksUsages()
                    disksecs = self.disksRefreshRate
                numcpusecs -= 1
                if numcpusecs <= 0:
                    self.sendNumberOfCPUs()
                    numcpusecs = self.numCPUsCheckRate
                time.sleep(1)
            logging.debug("AgentLogicBase:: doWork() exiting")
        except:
            logging.exception("AgentLogicBase::doWork")

    def doListen(self):
        logging.debug("AgentLogicBase::doListen() - entered")
        if self.commandHandler is None:
            logging.debug("AgentLogicBase::doListen() - no commandHandler "
                          "... exiting doListen thread")
            return
        while not self.wait_stop.isSet():
            try:
                logging.debug("AgentLogicBase::doListen() - "
                              "in loop before vio.read")
                cmd, args = self.vio.read()
                if cmd:
                    self.parseCommand(cmd, args)
            except:
                logging.exception('Error while reading the virtio-serial '
                                  'channel.')
        logging.debug("AgentLogicBase::doListen() - exiting")

    def _onApiVersion(self, args):
        before = self.dr.apiVersion
        self.dr.setAPIVersion(args['apiVersion'])
        if before != self.dr.apiVersion:
            self._refresh()

    def _refresh(self):
        self.sendUserInfo(True)
        self.sendAppList()
        self.sendContainerList()
        self.sendInfo()
        self.sendDisksUsages()
        self.sendFQDN()
        self.sendTimezone()
        self.sendOsInfo()

    def parseCommand(self, command, args):
        logging.info("Received an external command: %s..." % (command))
        if command == 'lock-screen':
            self.commandHandler.lock_screen()
        elif command == 'log-off':
            self.commandHandler.logoff()
        elif command == 'api-version':
            self._onApiVersion(args)
        elif command == 'shutdown':
            try:
                timeout = int(args['timeout'])
            except:
                timeout = 0
            try:
                msg = args['message']
            except:
                msg = 'System is going down'
            try:
                reboot = args['reboot'].lower() == 'true'
            except:
                reboot = False

            action = 'Shutting down'
            if reboot:
                action = 'Rebooting'
            logging.info("%s (timeout = %d, message = '%s')"
                         % (action, timeout, msg))
            self.commandHandler.shutdown(timeout, msg, reboot)
        elif command == 'login':
            username = args['username'].encode('utf8')
            password = args['password'].encode('utf8')
            credentials = struct.pack(
                '>I%ds%ds' % (len(username), len(password) + 1),
                len(username), username, password)
            logging.debug("User log-in (credentials = %s)"
                          % (safe_creds_repr(credentials)))
            self.commandHandler.login(credentials)
        elif command == 'refresh':
            if 'apiVersion' not in args and self.dr.getAPIVersion() > 0:
                logging.info('API versioning not supported by VDSM. Disabling '
                             'versioning support.')
                self.dr.setAPIVersion(_DISABLED_API_VALUE)
            self._refresh()
        elif command == 'echo':
            logging.debug("Echo: %s", args)
            self._send('echo', args)
        elif command == 'hibernate':
            state = args.get('state', 'disk')
            self.commandHandler.hibernate(state)
        elif command == 'set-number-of-cpus':
            count = args.get('count', 0)
            if count > 0:
                self.commandHandler.set_number_of_cpus(count)
                self.sendNumberOfCPUs()
        elif command == 'lifecycle-event':
            name = args.pop('type', None)
            if name:
                try:
                    self.hooks.dispatch(name)
                except hooks.UnknownHookError as e:
                    logging.debug('Unknown hook error: %s', e.args[0])
            if 'reply_id' in args:
                self.reply(args['reply_id'], done=True)
        else:
            logging.error("Unknown external command: %s (%s)"
                          % (command, args))

    def reply(self, id, **kwargs):
        args = {'reply_id': id}
        args.update(kwargs)
        self._send('completion', args)

    def sendFQDN(self):
        self._send('fqdn', {'fqdn': self.dr.getFQDN()})

    def sendUserInfo(self, force=False):
        cur_user = self.dr.getActiveUser()
        logging.debug("AgentLogicBase::sendUserInfo - cur_user = '%s'" %
                      (cur_user))
        if cur_user != self.activeUser or force:
            self._send('active-user', {'name': cur_user})
            self.activeUser = cur_user

    def sendTimezone(self):
        ti = self.dr.getTimezoneInfo()
        self._send('timezone', {'zone': ti[0], 'offset': ti[1]})

    def sendInfo(self):
        self._send('host-name', {'name': self.dr.getMachineName()})
        self._send('os-version', {'version': self.dr.getOsVersion()})
        self._send('network-interfaces',
                   {'interfaces': self.dr.getAllNetworkInterfaces()})

    def sendContainerList(self):
        self._send('containers', {'list': self.dr.getContainerList()})

    def sendAppList(self):
        self._send('applications', {'applications': self.dr.getApplications()})

    def sendDisksUsages(self):
        self._send('disks-usage', {'disks': self.dr.getDisksUsage(),
                                   'mapping': self.dr.getDiskMapping()})

    def sendMemoryStats(self):
        self._send('memory-stats', {'memory': self.dr.getMemoryStats()})

    def sendNumberOfCPUs(self):
        self._send('number-of-cpus', {'count': self.dr.getNumberOfCPUs()})

    def sendOsInfo(self):
        self._send('os-info', self.dr.getOsInfo())

    def sessionLogon(self):
        logging.debug("AgentLogicBase::sessionLogon: user logs on the system.")
        cur_user = self.dr.getActiveUser()
        retries = 0
        while (cur_user == 'None') and (retries < 5):
            time.sleep(1)
            cur_user = self.dr.getActiveUser()
            retries = retries + 1
        self.sendUserInfo()
        self._send('session-logon')

    def sessionLogoff(self):
        logging.debug("AgentLogicBase::sessionLogoff: "
                      "user logs off from the system.")
        self.activeUser = 'None'
        self._send('session-logoff')
        self._send('active-user', {'name': self.activeUser})

    def sessionLock(self):
        logging.debug("AgentLogicBase::sessionLock: "
                      "user locks the workstation.")
        self._send('session-lock')

    def sessionUnlock(self):
        logging.debug("AgentLogicBase::sessionUnlock: "
                      "user unlocks the workstation.")
        self._send('session-unlock')

    def sessionStartup(self):
        logging.debug("AgentLogicBase::sessionStartup: system starts up.")
        self._send('session-startup')

    def sessionShutdown(self):
        logging.debug("AgentLogicBase::sessionShutdown: system shuts down.")
        self._send('session-shutdown')
