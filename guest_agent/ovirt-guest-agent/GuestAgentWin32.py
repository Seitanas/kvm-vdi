#!/usr/bin/python

import _winreg
import ctypes
from ctypes import c_ulong, byref, windll, create_unicode_buffer,\
    Structure, sizeof, c_void_p
from ctypes.util import find_library
from ctypes.wintypes import DWORD
import ctypes.wintypes
import logging
import os
import socket
import subprocess
import time

import pythoncom
import win32api
import win32com.client
import win32con
import win32file
import win32net
import win32netcon
import win32pipe
import win32process
import win32security
import win32ts

from OVirtAgentLogic import AgentLogicBase, DataRetriverBase
from hooks import Hooks


# Constants according to
# http://msdn.microsoft.com/en-us/library/windows/desktop/ms724878.aspx
KEY_WOW64_32KEY = 0x0100
KEY_WOW64_64KEY = 0x0200


class StoragePropertyQuery(ctypes.Structure):
    _fields_ = (
        ("a", ctypes.wintypes.ULONG),
        ("b", ctypes.wintypes.ULONG),
        ("c", ctypes.wintypes.BYTE),
    )


class StorageDeviceDescriptor(ctypes.Structure):
    _fields_ = (
        ("Version", ctypes.wintypes.ULONG),
        ("Size", ctypes.wintypes.ULONG),
        ("DeviceType", ctypes.wintypes.BYTE),
        ("DeviceTypeModifier", ctypes.wintypes.BYTE),
        ("RemovableMedia", ctypes.wintypes.BOOLEAN),
        ("CommandQueueing", ctypes.wintypes.BOOLEAN),
        ("VendorIdOffset", ctypes.wintypes.ULONG),
        ("ProductIdOffset", ctypes.wintypes.ULONG),
        ("ProductRevisionOffset", ctypes.wintypes.ULONG),
        ("SerialNumberOffset", ctypes.wintypes.ULONG),
        ("BusType", ctypes.wintypes.DWORD),
        ("RawPropertiesLength", ctypes.wintypes.ULONG),
        ("DATA", ctypes.wintypes.BYTE * 1024),
    )


# _winreg.QueryValueEx and win32api.RegQueryValueEx don't support reading
# Unicode strings from the registry (at least on Python 2.5.1).
def QueryStringValue(hkey, name):
    # if type(hkey) != type(PyHKEY):
    #     raise TypeError("1nd arg must be a PyHKEY.")
    if not isinstance(name, unicode):
        raise TypeError("2nd arg must be a unicode.")
    key_type = c_ulong(0)
    key_len = c_ulong(0)
    res = windll.advapi32.RegQueryValueExW(hkey.handle, name, None,
                                           byref(key_type), None,
                                           byref(key_len))
    if res != 0:
        return unicode()
    if (key_type.value != win32con.REG_SZ):
        return unicode()
    key_value = create_unicode_buffer(key_len.value)
    res = windll.advapi32.RegQueryValueExW(hkey.handle, name, None, None,
                                           byref(key_value), byref(key_len))
    if res != 0:
        return unicode()
    return key_value.value


def GetActiveSessionId():
    for session in win32ts.WTSEnumerateSessions():
        if session['State'] == win32ts.WTSActive:
            return session['SessionId']
    return win32ts.WTSGetActiveConsoleSessionId()


def merge_duplicate_interfaces(interfaces):
    temp = {}
    for interface in interfaces:
        hw = interface['hw']
        if hw in temp:
            temp[hw]['inet'] += interface['inet']
            temp[hw]['inet6'] += interface['inet6']
        else:
            temp[hw] = interface
    result = []
    for intf in temp.itervalues():
        intf['inet'] = list(set(intf['inet']))
        intf['inet6'] = list(set(intf['inet6']))
        result.append(intf)
    return result


def GetNetworkInterfaces():
    interfaces = list()
    try:
        objWMIService = win32com.client.Dispatch("WbemScripting.SWbemLocator")
        objSWbemServices = objWMIService.ConnectServer(".", "root\cimv2")
        adapters = objSWbemServices.ExecQuery(
            "SELECT * FROM Win32_NetworkAdapterConfiguration")
        for adapter in adapters:
            if adapter.IPEnabled:
                inet = []
                inet6 = []
                if adapter.IPAddress:
                    for ip in adapter.IPAddress:
                        try:
                            socket.inet_aton(ip)
                            inet.append(ip)
                        except socket.error:
                            # Assume IPv6 if parsing as IPv4 was failed.
                            inet6.append(ip)
                interfaces.append({
                    'name': adapter.Description,
                    'inet': inet,
                    'inet6': inet6,
                    'hw': adapter.MacAddress.lower().replace('-', ':')})
    except:
        logging.exception("Error retrieving network interfaces.")
    return merge_duplicate_interfaces(interfaces)


class PERFORMANCE_INFORMATION(Structure):
    _fields_ = [
        ('cb', DWORD),
        ('CommitTotal', DWORD),
        ('CommitLimit', DWORD),
        ('CommitPeak', DWORD),
        ('PhysicalTotal', DWORD),
        ('PhysicalAvailable', DWORD),
        ('SystemCache', DWORD),
        ('KernelTotal', DWORD),
        ('KernelPaged', DWORD),
        ('KernelNonpaged', DWORD),
        ('PageSize', DWORD),
        ('HandleCount', DWORD),
        ('ProcessCount', DWORD),
        ('ThreadCount', DWORD)
    ]


def get_perf_info():
    pi = PERFORMANCE_INFORMATION()
    pi.cb = sizeof(pi)
    windll.psapi.GetPerformanceInfo(byref(pi), pi.cb)
    return pi


class IncomingMessageTypes:
        Credentials = 11


class OSVERSIONINFOEXW(ctypes.Structure):
    _fields_ = [('dwOSVersionInfoSize', ctypes.c_ulong),
                ('dwMajorVersion', ctypes.c_ulong),
                ('dwMinorVersion', ctypes.c_ulong),
                ('dwBuildNumber', ctypes.c_ulong),
                ('dwPlatformId', ctypes.c_ulong),
                ('szCSDVersion', ctypes.c_wchar*128),
                ('wServicePackMajor', ctypes.c_ushort),
                ('wServicePackMinor', ctypes.c_ushort),
                ('wSuiteMask', ctypes.c_ushort),
                ('wProductType', ctypes.c_byte),
                ('wReserved', ctypes.c_byte)]


class WinOsTypeHandler:
    WIN2003 = 'Win 2003'
    WIN2008 = 'Win 2008'
    WIN2008R2 = 'Win 2008 R2'
    WIN2012 = 'Win 2012'
    WIN2012R2 = 'Win 2012 R2'
    WIN2016 = 'Win 2016'
    WIN2K = 'Win 2000'
    WINXP = 'Win XP'
    WINVISTA = 'Win Vista'
    WIN7 = 'Win 7'
    WIN8 = 'Win 8'
    WIN81 = 'Win 8.1'
    WIN10 = 'Win 10'
    UNKNOWN = 'Unknown'

    desktopVersionMatrix = {
        '5.0': WIN2K,
        '5.1': WINXP,
        '6.0': WINVISTA,
        '6.1': WIN7,
        '6.2': WIN8,
        '6.3': WIN81,
        '10.0': WIN10,
    }
    serverVersionMatrix = {
        '5.2': WIN2003,
        '6.0': WIN2008,
        '6.1': WIN2008R2,
        '6.2': WIN2012,
        '6.3': WIN2012R2,
        '10.0': WIN2016,
    }

    def getWinOsType(self):
        name = self.UNKNOWN
        version = ''
        os_version = OSVERSIONINFOEXW()
        os_version.dwOSVersionInfoSize = ctypes.sizeof(os_version)
        retcode = ctypes.windll.Ntdll.RtlGetVersion(ctypes.byref(os_version))
        VER_NT_WORKSTATION = 1
        if retcode == 0:
            major = os_version.dwMajorVersion
            minor = os_version.dwMinorVersion
            matrix = self.serverVersionMatrix
            if os_version.wProductType == VER_NT_WORKSTATION:
                matrix = self.desktopVersionMatrix
            version = '%d.%d' % (major, minor)
            name = matrix.get(version, '')
        return {'name': name, 'version': version}


def set_bcd_useplatformclock():
    osinfo = WinOsTypeHandler().getWinOsType()
    if osinfo.get('version', '5.0').split('.')[0] > '5':
        try:
            subprocess.call(['%SystemRoot%\\sysnative\\bcdedit.exe', '/set',
                             '{current}', 'USEPLATFORMCLOCK', 'on'],
                            shell=True)
        except OSError:
            logging.info('Failed to set the USEPLATFORMCLOCK flag via '
                         'bcdedit.exe', exc_info=True)


class CommandHandlerWin:

    def lock_screen(self):
        self.LockWorkStation()

    def _setSoftwareSASPolicy(self, value):
        KEY_PATH = "SOFTWARE\\Microsoft\\Windows\\CurrentVersion" \
                   "\\Policies\\System"
        if value is not None:
            view_flag = KEY_WOW64_64KEY
            handle = _winreg.OpenKey(_winreg.HKEY_LOCAL_MACHINE, KEY_PATH, 0,
                                     view_flag | _winreg.KEY_READ |
                                     _winreg.KEY_WRITE)
            try:
                old = _winreg.QueryValueEx(handle, 'SoftwareSASGeneration')
            except OSError:
                # Expected to happen if it does not exist
                old = (None, None)
            _winreg.SetValueEx(handle, 'SoftwareSASGeneration', 0,
                               _winreg.REG_DWORD, value)
            return old[0]
        return None

    def _performSAS(self):
        if find_library('sas') is not None:
            logging.debug("Simulating a secure attention sequence (SAS).")
            # setSoftwareSASPolicy is used to set the value to 3 to enable the
            # simulated secure attention sequence, and reverts the to the
            # previous value after the function was performed.
            oldValue = self._setSoftwareSASPolicy(3)
            windll.sas.SendSAS(0)
            self._setSoftwareSASPolicy(oldValue)

    def login(self, credentials):
        PIPE_NAME = "\\\\.\\pipe\\VDSMDPipe"
        BUFSIZE = 1024
        RETIRES = 3
        try:
            self._performSAS()
        except Exception:
            logging.warning("Failed to perform SAS", exc_info=True)
        try:
            retries = 1
            while retries <= RETIRES:
                try:
                    time.sleep(1)
                    win32pipe.CallNamedPipe(PIPE_NAME, credentials, BUFSIZE,
                                            win32pipe.NMPWAIT_WAIT_FOREVER)
                    logging.debug("Credentials were written to pipe.")
                    break
                except:
                    error = windll.kernel32.GetLastError()
                    logging.error("Error writing credentials to pipe [%d/%d] "
                                  "(error = %d)", retries, RETIRES, error)
                    retries += 1
        except:
            logging.exception("Error occurred during user login.")

    def logoff(self):
        sessionId = GetActiveSessionId()
        if sessionId != 0xffffffff:
            logging.debug("Logging off current user (session %d)", sessionId)
            win32ts.WTSLogoffSession(win32ts.WTS_CURRENT_SERVER_HANDLE,
                                     sessionId, 0)
        else:
            logging.debug("No active session. Ignoring log off command.")

    def shutdown(self, timeout, msg, reboot=False):
        param = '-s'
        action = 'shutdown'
        if reboot:
            param = '-r'
            action = 'reboot'

        cmd = "%s\\system32\\shutdown.exe %s -t %d -f -c \"%s\"" \
            % (os.environ['WINDIR'], param, timeout, msg)

        logging.debug("Executing %s command: '%s'", action, cmd)

        # Since we're a 32-bit application that sometimes is executed on
        # Windows 64-bit, executing C:\Windows\system32\shutdown.exe is
        # redirected to C:\Windows\SysWOW64\shutdown.exe. The later doesn't
        # exist and we get a "file not found" error. The solution is to
        # disable redirection before executing the shutdown command and
        # re-enable redirection once we're done.
        old_value = c_void_p()
        try:
            windll.kernel32.Wow64DisableWow64FsRedirection(byref(old_value))
        except AttributeError:
            # The function doesn't exist on 32-bit Windows so exeception is
            # ignored.
            pass

        subprocess.call(cmd)

        # Calling this function with the old value received from the first
        # call re-enable the file system redirection.
        if old_value:
            windll.kernel32.Wow64DisableWow64FsRedirection(old_value)

    def hibernate(self, state):
        token = win32security.OpenProcessToken(
            win32api.GetCurrentProcess(),
            win32security.TOKEN_QUERY | win32security.TOKEN_ADJUST_PRIVILEGES)
        shutdown_priv = win32security.LookupPrivilegeValue(
            None,
            win32security.SE_SHUTDOWN_NAME)
        privs = win32security.AdjustTokenPrivileges(
            token,
            False,
            [(shutdown_priv, win32security.SE_PRIVILEGE_ENABLED)])
        logging.debug("Privileges before hibernation: %s", privs)
        if windll.powrprof.SetSuspendState(state == 'disk', True, False) != 0:
            logging.info("System was in hibernation state.")
        else:
            logging.error(
                "Error setting system to hibernation state: %d",
                win32api.GetLastError())

    # The LockWorkStation function is callable only by processes running on the
    # interactive desktop.
    def LockWorkStation(self):
        try:
            logging.debug("LockWorkStation was called.")
            sessionId = GetActiveSessionId()
            if sessionId != 0xffffffff:
                logging.debug("Locking workstation (session %d)", sessionId)
                dupToken = None
                userToken = win32ts.WTSQueryUserToken(sessionId)
                if userToken is not None:
                    logging.debug("Got the active user token.")
                    # The following access rights are required for
                    # CreateProcessAsUser.
                    access = win32security.TOKEN_QUERY
                    access |= win32security.TOKEN_DUPLICATE
                    access |= win32security.TOKEN_ASSIGN_PRIMARY
                    dupToken = win32security.DuplicateTokenEx(
                        userToken,
                        win32security.SecurityImpersonation,
                        access,
                        win32security.TokenPrimary,
                        None)
                    userToken.Close()
                if dupToken is not None:
                    logging.debug("Duplicated the active user token.")
                    lockCmd = os.path.join(os.environ['WINDIR'],
                                           "system32\\rundll32.exe")
                    lockCmd += " user32.dll,LockWorkStation"
                    logging.debug("Executing \"%s\".", lockCmd)
                    win32process.CreateProcessAsUser(
                        dupToken, None, lockCmd,
                        None, None, 0, 0, None,
                        None, win32process.STARTUPINFO())
                    dupToken.Close()
            else:
                logging.debug("No active session. Ignoring lock workstation "
                              "command.")
        except:
            logging.exception("LockWorkStation exception")

    def setNumberOfCPUs(self, count):
        pass


class WinDataRetriver(DataRetriverBase):
    def __init__(self):
        self.arch = self._getArch()
        self.os = WinOsTypeHandler().getWinOsType()
        DataRetriverBase.__init__(self)

    def getMachineName(self):
        return os.environ.get('COMPUTERNAME', '')

    def getOsVersion(self):
        return self.os['name']

    def _getArch(self):
        arch = 'x86'
        try:
            kernel32 = ctypes.windll.kernel32
            result = ctypes.c_int()
            proc = kernel32.GetCurrentProcess()
            if kernel32.IsWow64Process(proc, ctypes.byref(result)) == 1:
                if result:
                    arch = 'x86_64'
        except AttributeError:
            pass
        return arch

    def getOsInfo(self):
        return {
            'version': self.os['version'],
            'distribution': '',
            'codename': self.os['name'],
            'arch': self.arch,
            'type': 'windows',
            'kernel': ''}

    def getContainerList(self):
        return []

    def getAllNetworkInterfaces(self):
        return GetNetworkInterfaces()

    def _is_item_update(self, reg_key):
        RTPATTERNS = ("Hotfix", "Security Update", "Software Update", "Update")
        release_type = QueryStringValue(reg_key, u'ReleaseType')
        for pattern in RTPATTERNS:
            if release_type.find(pattern) >= 0:
                return True
        parent_key_name = QueryStringValue(reg_key, u'ParentKeyName')
        if parent_key_name.find("OperatingSystem") >= 0:
            return True
        return False

    def getApplications(self):
        retval = set()
        key_path = "SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Uninstall"
        for view_flag in (KEY_WOW64_32KEY, KEY_WOW64_64KEY):
            rootkey = _winreg.OpenKey(_winreg.HKEY_LOCAL_MACHINE, key_path, 0,
                                      view_flag | _winreg.KEY_READ)
            items = _winreg.QueryInfoKey(rootkey)[0]
            for idx in range(items):
                cur_key_path = _winreg.EnumKey(rootkey, idx)
                cur_key = _winreg.OpenKey(rootkey, cur_key_path, 0,
                                          view_flag | _winreg.KEY_READ)
                try:
                    if self._is_item_update(cur_key):
                        continue
                    display_name = QueryStringValue(cur_key, u'DisplayName')
                    if len(display_name) == 0:
                        continue
                    retval.add(display_name)
                except:
                    pass
        return list(retval)

    def getAvailableRAM(self):
        # Returns the available physical memory (including the system cache).
        pi = get_perf_info()
        return str(int((pi.PhysicalAvailable * pi.PageSize) / (1024 ** 2)))

    def getUsers(self):
        total_list = []
        try:
            server = self.getMachineName()
            res = 1  # initially set it to true
            pref = win32netcon.MAX_PREFERRED_LENGTH
            level = 1  # setting it to 1 will provide more detailed info
            while res:  # loop until res2
                (user_list, total, res2) = \
                    win32net.NetWkstaUserEnum(server, level, res, pref)
                logging.debug("getUsers: user_list = '%s'", user_list)
                for i in user_list:
                    if not i['username'].startswith(server):
                        total_list.append([i['username'], i['logon_domain']])
                res = res2
        except win32net.error:
            logging.exception("WinDataRetriver::getUsers")
        logging.debug("WinDataRetriver::getUsers retval = '%s'", total_list)
        return total_list

    def getActiveUser(self):
        user = "None"
        try:
            domain = ""
            sessionId = GetActiveSessionId()
            if sessionId != 0xffffffff:
                user = win32ts.WTSQuerySessionInformation(
                    win32ts.WTS_CURRENT_SERVER_HANDLE,
                    sessionId,
                    win32ts.WTSUserName)
                domain = win32ts.WTSQuerySessionInformation(
                    win32ts.WTS_CURRENT_SERVER_HANDLE,
                    sessionId,
                    win32ts.WTSDomainName)
            if domain == "":
                pass
            elif domain.lower() != self.getMachineName().lower():
                # Use FQDN as user name if computer is part of a domain.
                try:
                    user_orig = user
                    user = u"%s\\%s" % (domain, user_orig)
                    user = win32security.TranslateName(
                        user,
                        win32api.NameSamCompatible,
                        win32api.NameUserPrincipal)
                    # Check for error because no exception is raised when
                    # running under Windows XP.
                    err = win32api.GetLastError()
                    if err != 0:
                        raise RuntimeError(err, 'TranslateName')
                except:
                    logging.debug("Error on user name translation. Requested "
                                  "translation '%s' '%s'", user_orig, domain)
                    user = u"%s@%s" % (user_orig, domain)
            else:
                user = u"%s@%s" % (user, domain)
        except:
            logging.exception("Error retrieving active user name.")
        logging.debug("Active user: %s", user)
        return user

    def getDisksUsage(self):
        usages = list()
        try:
            drives_mask = win32api.GetLogicalDrives()
            path = 'a'
            while drives_mask > 0:
                path_name = path + ':\\'
                if (drives_mask & 1):
                    try:
                        res = win32api.GetDiskFreeSpaceEx(path_name)
                        (free, total) = res[:2]
                        fs = win32api.GetVolumeInformation(path_name)[4]
                        used = total - free
                        usages.append({
                            'path': path_name,
                            'fs': fs,
                            'total': total,
                            'used': used})
                    except:
                        pass
                drives_mask >>= 1
                path = chr(ord(path) + 1)
        except:
            logging.exception("Error retrieving disks usages.")
        return usages

    def _readSMART(self, name):
        serial = 'NO SERIAL NUMBER'
        handle = ctypes.windll.kernel32.CreateFileW(
            name,
            win32file.GENERIC_READ | win32file.GENERIC_WRITE,
            win32file.FILE_SHARE_READ | win32file.FILE_SHARE_WRITE,
            None, win32file.OPEN_EXISTING, 0, 0)
        if handle == -1:
            logging.warning("Failed to open device '%s' for querying the "
                            "serial number. Error code: %d", name,
                            win32api.GetLastError())
            return serial
        q = StoragePropertyQuery()
        r = StorageDeviceDescriptor()
        read_count = ctypes.wintypes.ULONG()
        ret = ctypes.windll.kernel32.DeviceIoControl(
            handle, 0x002D1400, ctypes.addressof(q), ctypes.sizeof(q),
            ctypes.addressof(r), ctypes.sizeof(r),
            ctypes.addressof(read_count), 0)

        if ret:
            serial = buffer(r)[r.SerialNumberOffset:r.SerialNumberOffset + 20]
        else:
            logging.warning("DeviceIoControl for device %s failed with"
                            "eror code: %d. Could not look up serial number",
                            name, win32api.GetLastError())
        ctypes.windll.kernel32.CloseHandle(handle)
        return serial

    def getDiskMapping(self):
        result = {}
        try:
            strComputer = "."
            objWMIService = \
                win32com.client.Dispatch("WbemScripting.SWbemLocator")
            objSWbemServices = \
                objWMIService.ConnectServer(strComputer, "root\cimv2")
            colItems = \
                objSWbemServices.ExecQuery(
                    "SELECT * FROM Win32_DiskDrive")
            for objItem in colItems:
                try:
                    serial = objItem.SerialNumber
                except AttributeError:
                    serial = self._readSMART(objItem.DeviceID)
                result[serial] = {'name': objItem.DeviceID}
        except Exception:
            logging.exception("Failed to retrieve disk mapping")
        return result

    def _getSwapStats(self):
        try:
            strComputer = "."
            objWMIService = \
                win32com.client.Dispatch("WbemScripting.SWbemLocator")
            objSWbemServices = \
                objWMIService.ConnectServer(strComputer, "root\cimv2")
            colItems = \
                objSWbemServices.ExecQuery(
                    "SELECT * FROM Win32_PageFileUsage")
            for objItem in colItems:
                # Keep the unit consistent with Linux guests (KiB)
                self.memStats['swap_usage'] = objItem.CurrentUsage * 1024
                self.memStats['swap_total'] = objItem.AllocatedBaseSize * 1024
        except Exception:
            logging.exception("Failed to retrieve page file stats")
            pass

    def getMemoryStats(self):
        pi = get_perf_info()
        # keep the unit consistent with Linux guests
        self.memStats['mem_total'] = \
            str(int((pi.PhysicalTotal * pi.PageSize) / 1024))
        self.memStats['mem_free'] = \
            str(int((pi.PhysicalAvailable * pi.PageSize) / 1024))
        self.memStats['mem_unused'] = self.memStats['mem_free']
        self.memStats['mem_cached'] = 0   # TODO: Can this be reported?
        self.memStats['mem_buffers'] = 0  # TODO: Can this be reported?
        try:
            strComputer = "."
            objWMIService = \
                win32com.client.Dispatch("WbemScripting.SWbemLocator")
            objSWbemServices = \
                objWMIService.ConnectServer(strComputer, "root\cimv2")
            colItems = \
                objSWbemServices.ExecQuery(
                    "SELECT * FROM Win32_PerfFormattedData_PerfOS_Memory")
            for objItem in colItems:
                # Please see the definition of
                # Win32_PerfFormattedData_PerfOS_Memory class in the MSDN
                # for the explanations of the following fields.
                self.memStats['swap_in'] = objItem.PagesInputPersec
                self.memStats['swap_out'] = objItem.PagesOutputPersec
                self.memStats['pageflt'] = objItem.PageFaultsPersec
                self.memStats['majflt'] = objItem.PageReadsPersec
        except:
            logging.exception("Error retrieving detailed memory stats")
        self._getSwapStats()
        return self.memStats


class WinVdsAgent(AgentLogicBase):

    def __init__(self, config, install_dir):
        AgentLogicBase.__init__(self, config)
        self.dr = WinDataRetriver()
        self.commandHandler = CommandHandlerWin()
        hooks_dir = os.path.join(install_dir, 'hooks')
        self.hooks = Hooks(logging.getLogger('Hooks'), hooks_dir)
        set_bcd_useplatformclock()

    def run(self):
        logging.debug("WinVdsAgent:: run() entered")
        try:
            self.disable_screen_saver()
            AgentLogicBase.run(self)
        except:
            logging.exception("WinVdsAgent::run")

    def doListen(self):
        # CoInitializeEx() should be called in multi-threading program
        # according to msdn document.
        pythoncom.CoInitializeEx(0)
        AgentLogicBase.doListen(self)

    def doWork(self):
        # CoInitializeEx() should be called in multi-threading program
        # according to msdn document.
        pythoncom.CoInitializeEx(0)
        AgentLogicBase.doWork(self)

    def disable_screen_saver(self):
        keyHandle = win32api.RegOpenKeyEx(
            win32con.HKEY_USERS,
            ".DEFAULT\Control Panel\Desktop",
            0,
            win32con.KEY_WRITE)
        win32api.RegSetValueEx(keyHandle, "ScreenSaveActive", 0,
                               win32con.REG_SZ, "0")
        keyHandle.Close()


def test():
    dr = WinDataRetriver()
    print "Machine Name:", dr.getMachineName()
    print "Fully Qualified Domain Name:", dr.getFQDN()
    print "OS Version:", dr.getOsVersion()
    print "Network Interfaces:", dr.getAllNetworkInterfaces()
    print "Installed Applications:", dr.getApplications()
    print "Available RAM:", dr.getAvailableRAM()
    print "Logged in Users:", dr.getUsers()
    print "Active User:", dr.getActiveUser()
    print "Disks Usage:", dr.getDisksUsage()
    print "Memory Stats:", dr.getMemoryStats()
    print "DiskMapping:", dr.getDiskMapping()

if __name__ == '__main__':
    test()
