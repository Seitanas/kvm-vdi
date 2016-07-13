# qagentservice: Windows service wrapper for Qumranet monitoring agent
# The service is converted into an exe-file with py2exe

import ConfigParser
import _winreg
import cStringIO
import io
import logging
import logging.config
import os
import os.path

import servicemanager
import win32evtlogutil
import win32service
import win32serviceutil

from GuestAgentWin32 import WinVdsAgent


AGENT_CONFIG = 'ovirt-guest-agent.ini'
AGENT_DEFAULT_CONFIG = 'default.ini'
AGENT_DEFAULT_LOG_CONFIG = 'default-logger.ini'

# Values from WM_WTSSESSION_CHANGE message
# (http://msdn.microsoft.com/en-us/library/aa383828.aspx)
WTS_SESSION_LOGON = 0x5
WTS_SESSION_LOGOFF = 0x6
WTS_SESSION_LOCK = 0x7
WTS_SESSION_UNLOCK = 0x8


class OVirtGuestService(win32serviceutil.ServiceFramework):
    _svc_name_ = "OVirtGuestService"
    _svc_display_name_ = "OVirt Guest Agent Service"
    _svc_description_ = "OVirt Guest Agent Service"
    _svc_deps_ = ["EventLog"]

    def __init__(self, args):
        win32serviceutil.ServiceFramework.__init__(self, args)
        self._shutting_down = False

        global AGENT_CONFIG, AGENT_DEFAULT_CONFIG, AGENT_DEFAULT_LOG_CONFIG
        regKey = "System\\CurrentControlSet\\services\\%s" % self._svc_name_
        hkey = _winreg.OpenKey(_winreg.HKEY_LOCAL_MACHINE, regKey)
        filePath = _winreg.QueryValueEx(hkey, "ImagePath")[0].replace('"', '')
        hkey.Close()
        if "PythonService.exe" in filePath:
            hkey = _winreg.OpenKey(_winreg.HKEY_LOCAL_MACHINE,
                                   "%s\\PythonClass" % regKey)
            filePath = _winreg.QueryValueEx(hkey, "")[0].replace('"', '')
            hkey.Close()
        filePath = os.path.dirname(filePath)
        self._install_dir = filePath
        AGENT_CONFIG = os.path.join(filePath, AGENT_CONFIG)
        AGENT_DEFAULT_CONFIG = os.path.join(filePath, AGENT_DEFAULT_CONFIG)
        AGENT_DEFAULT_LOG_CONFIG = os.path.join(filePath,
                                                AGENT_DEFAULT_LOG_CONFIG)

        cparser = ConfigParser.ConfigParser()
        if os.path.exists(AGENT_DEFAULT_LOG_CONFIG):
            cparser.read(AGENT_DEFAULT_LOG_CONFIG)
        cparser.read(AGENT_CONFIG)
        strio = cStringIO.StringIO()
        cparser.write(strio)
        bio = io.BytesIO(strio.getvalue())
        logging.config.fileConfig(bio)
        bio.close()
        strio.close()

    # Overriding this method in order to accept session change notifications.
    def GetAcceptedControls(self):
        accepted = win32serviceutil.ServiceFramework.GetAcceptedControls(self)
        accepted |= win32service.SERVICE_ACCEPT_SESSIONCHANGE
        return accepted

    def SvcStop(self):
        self.ReportServiceStatus(win32service.SERVICE_STOP_PENDING)
        self.vdsAgent.stop()

    def SvcDoRun(self):
        # Write a 'started' event to the event log...
        self.ReportEvent(servicemanager.PYS_SERVICE_STARTED)
        logging.info("Starting OVirt Guest Agent service")
        config = ConfigParser.ConfigParser()
        if os.path.exists(AGENT_DEFAULT_CONFIG):
            config.read(AGENT_DEFAULT_CONFIG)
        config.read(AGENT_CONFIG)

        self.vdsAgent = WinVdsAgent(config, install_dir=self._install_dir)
        self.vdsAgent.run()

        # and write a 'stopped' event to the event log (skip this step if the
        # computer is shutting down, because the event log might be down).
        if not self._shutting_down:
            self.ReportEvent(servicemanager.PYS_SERVICE_STOPPED)

        logging.info("Stopping OVirt Guest Agent service")

    def SvcShutdown(self):
        self.vdsAgent.sessionShutdown()
        self._shutting_down = True
        self.vdsAgent.stop()

    def SvcSessionChange(self, event_type):
        if event_type == WTS_SESSION_LOGON:
            self.vdsAgent.sessionLogon()
        elif event_type == WTS_SESSION_LOGOFF:
            self.vdsAgent.sessionLogoff()
        elif event_type == WTS_SESSION_LOCK:
            self.vdsAgent.sessionLock()
        elif event_type == WTS_SESSION_UNLOCK:
            self.vdsAgent.sessionUnlock()

    def SvcOtherEx(self, control, event_type, data):
        if control == win32service.SERVICE_CONTROL_SESSIONCHANGE:
            self.SvcSessionChange(event_type)

    def ReportEvent(self, EventID):
        try:
            win32evtlogutil.ReportEvent(
                self._svc_name_,
                EventID,
                0,  # category
                servicemanager.EVENTLOG_INFORMATION_TYPE,
                (self._svc_name_, ''))
        except:
            logging.exception("Failed to write to the event log")


if __name__ == '__main__':
    # Note that this code will not be run in the 'frozen' exe-file!!!
    win32serviceutil.HandleCommandLine(OVirtGuestService)
