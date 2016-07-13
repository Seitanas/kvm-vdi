#!/usr/bin/python
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
import os
import os.path
import subprocess

import dbus


class SessionWrapper(object):
    def __init__(self, session, bus, path):
        self._bus = bus
        self._path = path
        self._session = session
        self._props = GetInterface(bus, 'login1', '', path,
                                   'org.freedesktop.DBus.Properties')

    def _getProperty(self, name):
        return self._props.Get('org.freedesktop.login1.Session', name)

    def GetId(self):
        return self._getProperty('Id')

    def IsActive(self):
        return self._getProperty('Active')

    def GetX11Display(self):
        return self._getProperty('Display')

    def GetUnixUser(self):
        return self._getProperty('User')[0]

    def Lock(self):
        return self._session.Lock()


def GetInterface(bus, service, name, path, fname=None):
    obj = bus.get_object('org.freedesktop.%s' % service, path)
    iface = fname
    if not iface:
        iface = 'org.freedesktop.%s.%s' % (service, name)
        if not name:
            iface = iface[:-1]
    return dbus.Interface(obj, dbus_interface=iface)


def GetInterfaceByName(bus, service, name, isSub):
    path = '/org/freedesktop/' + service
    if isSub:
        path += '/' + name
    return GetInterface(bus, service, name, path)


def GetSessions(manager):
    try:
        return manager.GetSessions()
    except dbus.DBusException:
        return [x[4] for x in manager.ListSessions()]


def GetSession(bus, service, managerIsSub, wrapSession):
    session = None
    try:
        manager = GetInterfaceByName(bus, service, 'Manager', managerIsSub)
        for session_path in GetSessions(manager):
            s = GetInterface(bus, service, 'Session', session_path)
            s = wrapSession(s, bus, session_path)
            if s.IsActive():
                session = s
                break
    except dbus.DBusException:
        logging.exception("%s seems not to be available", service)
    return session


def GetActiveSession():
    bus = dbus.SystemBus()
    ARGS = (('ConsoleKit', True, lambda *a: a[0]),
            ('login1', False, SessionWrapper))
    for args in ARGS:
        session = GetSession(bus, *args)
        if session:
            break
    return session


def GetScreenSaver():
    try:
        bus = dbus.SessionBus()
        screensaver = GetInterface(bus, 'ScreenSaver', '', '/ScreenSaver')
    except dbus.DBusException:
        logging.exception("Error retrieving ScreenSaver interface (ignore if "
                          "running on GNOME).")
        screensaver = None
    return screensaver


def LockSession(session):
    # First try to lock in the KDE "standard" interface. Since KDE is
    # using a session bus, all operations must be execued in the user
    # context.
    pid = os.fork()
    if pid == 0:
        os.environ['DISPLAY'] = session.GetX11Display()
        os.setuid(session.GetUnixUser())
        screensaver = GetScreenSaver()
        if screensaver is not None:
            screensaver.Lock()
            exitcode = 0
        else:
            logging.info("KDE standard interface seems not to be supported")
            exitcode = 1
        os._exit(exitcode)

    result = os.waitpid(pid, 0)
    logging.debug("Process %d terminated (result = %s)", pid, result)

    # If our first try failed, try the GNOME "standard" interface.
    if result[1] != 0:
        logging.info("Attempting session lock via ConsoleKit/LoginD")
        session.Lock()


def main():
    if os.path.exists('/usr/bin/loginctl'):
        subprocess.call(['/usr/bin/loginctl', 'lock-sessions'])
    else:
        session = GetActiveSession()
        if session is not None:
            try:
                LockSession(session)
                logging.info("Session %s should be locked now.",
                             session.GetId())
            except dbus.DBusException:
                logging.exception("Error while trying to lock session.")
        else:
            logging.error("Error locking session (no active session).")

if __name__ == '__main__':
    main()
