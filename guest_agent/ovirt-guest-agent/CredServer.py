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

import array
from ctypes import Structure, POINTER, CDLL, c_ubyte, c_uint, c_void_p, \
    c_size_t, c_int, sizeof, cast, pointer, c_short, byref, get_errno
from ctypes.util import find_library
import errno
import logging
import pwd
import random
import socket
import struct
import threading
import time

import dbus
import dbus.service
import gobject
from dbus.mainloop.glib import DBusGMainLoop


# Initializes the use of Python threading in the gobject module.
gobject.threads_init()

DBusGMainLoop(set_as_default=True)

libc = CDLL(find_library('c'), use_errno=True)

# Traslated from C header file sys/socket.h:

SCM_CREDENTIALS = 2


class iovec(Structure):
    _fields_ = [
        ('iov_base', POINTER(c_ubyte)),
        ('iov_len',  c_uint)
    ]


class msghdr(Structure):
    _fields_ = [
        ('msg_name', c_void_p),
        ('msg_namelen', c_uint),
        ('msg_iov', POINTER(iovec)),
        ('msg_iovlen', c_uint),
        ('msg_control', c_void_p),
        ('msg_controllen', c_uint),
        ('msg_flags', c_int)
    ]


class cmsghdr(Structure):
    _fields_ = [
        ('cmsg_len', c_size_t),
        ('cmsg_level', c_int),
        ('cmsg_type', c_int),
        ('cmsg_data', POINTER(c_ubyte))
    ]


class ucred(Structure):
    _fields_ = [
        ('pid', c_int),
        ('uid', c_int),
        ('gid', c_int)
    ]


def CMSG_FIRSTHDR(mhdr):
    if mhdr.msg_controllen >= sizeof(cmsghdr):
        return cast(mhdr.msg_control, POINTER(cmsghdr)).contents
    else:
        return None


def CMSG_NXTHDR(mhdr, cmsg):
    return None


def CMSG_ALIGN(x):
    return ((x + sizeof(c_uint) - 1) & ~(sizeof(c_uint) - 1))


def CMSG_SPACE(x):
    return (CMSG_ALIGN(x) + CMSG_ALIGN(sizeof(cmsghdr)))


#
# The socket credential code is based on knowlage learned from the "Linux
# Socket Programming by Example (Warren Gay)" book.
#
def packcreds(user, password, domain=''):
    if domain != '':
        username = user + '@' + domain
    else:
        username = user
    username = username.encode('utf-8')
    password = password.encode('utf-8')
    s = struct.pack('>6sI%ds%ds' % (len(username), len(password) + 1),
                    'login', len(username), username, password)
    return s


class CredDBusObject(dbus.service.Object):
    DBUS_PATH = '/org/ovirt/vdsm/Credentials'
    DBUS_INTERFACE = 'org.ovirt.vdsm.Credentials'

    def __init__(self):
        bus = dbus.SystemBus()
        dbus.service.Object.__init__(self, bus, '/org/ovirt/vdsm/Credentials')
        self._name = dbus.service.BusName('org.ovirt.vdsm.Credentials', bus)

    @dbus.service.signal(dbus_interface='org.ovirt.vdsm.Credentials',
                         signature='s')
    def UserAuthenticated(self, token):
        logging.info("Emitting user authenticated signal (%s)." % (token))


class CredChannel(threading.Thread):

    def __init__(self):
        threading.Thread.__init__(self, name='CredChannel')
        self._channel = None
        self._credentials = None
        self._allowed = list()

    def _read_cred(self, conn):
        # Enables receiving the credentials of the process that connects to
        # the channel.
        conn.setsockopt(socket.SOL_SOCKET, socket.SO_PASSCRED, 1)

        # Create an I/O element with a buffer to receive port number (ignored).
        iov = iovec()
        iov.iov_base = cast(pointer(c_short()), POINTER(c_ubyte))
        iov.iov_len = sizeof(c_short)

        # Allocate a message control buffer.
        ctrl = array.array('B', '\0' * CMSG_SPACE(sizeof(ucred)))

        # Initialize a message control structure.
        msgh = msghdr()
        msgh.msg_iov = pointer(iov)
        msgh.msg_iovlen = 1
        msgh.msg_control = ctrl.buffer_info()[0]
        msgh.msg_controllen = ctrl.buffer_info()[1]

        while True:
            ret = libc.recvmsg(conn.fileno(), byref(msgh), socket.MSG_PEEK)
            logging.debug("Receiving user's credential ret = %d errno = %d",
                          ret, get_errno())
            if (ret >= 0) or (get_errno() != errno.EINTR):
                break

        result = None

        if ret > 0:
            cmsgp = CMSG_FIRSTHDR(msgh)
            while cmsgp is not None:
                logging.debug("cmsgp: len=%d level=%d type=%d",
                              cmsgp.cmsg_len, cmsgp.cmsg_level,
                              cmsgp.cmsg_type)
                if cmsgp.cmsg_level == socket.SOL_SOCKET:
                    if cmsgp.cmsg_type == SCM_CREDENTIALS:
                        data = cast(pointer(cmsgp.cmsg_data), POINTER(ucred))
                        result = data.contents
                        break
                # This doesn't really work, but I got only one record during
                # development.
                cmsgp = CMSG_NXTHDR(msgh, cmsgp)

        return result

    def start(self, credentials):
        if type(credentials) != tuple:
            raise TypeError('1st arg must be a tuple')
        self._credentials = credentials
        threading.Thread.start(self)

    def set_allowed(self, users):
        if type(users) != list:
            raise TypeError('1st arg must be a list')
        for user in users:
            if type(user) == int:
                self._allowed.append(user)
            else:
                try:
                    self._allowed.append(pwd.getpwnam(user)[2])
                except KeyError, err:
                    logging.error(str(err)[1:-1])
        logging.info("The following users are allowed to connect: %s",
                     self._allowed)

    def run(self):
        self._channel = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
        self._channel.settimeout(5.0)
        self._channel.bind("\0/tmp/ovirt-cred-channel")
        self._channel.listen(1)

        try:
            conn, addr = self._channel.accept()
            cred = self._read_cred(conn)
            if cred is None:
                logging.error("Error receiving user's credential from socket.")
                return
            if cred.uid not in self._allowed:
                logging.error("User %d is not allowed to connect!", cred.uid)
                return
            logging.info("Incomming connection from user: %d process: %d",
                         cred.uid, cred.pid)

            token = conn.recv(1024)
            if not token:
                return

            if str(token) == self._credentials[0]:
                logging.info("Sending user's credential (token: %s)", token)
                conn.send(self._credentials[1])
            else:
                logging.warning("Unexpect token was received (token: %s)",
                                token)

            conn.close()

        except socket.timeout:
            logging.info("Credentials channel timed out.")
            self._channel.close()


class CredServer(threading.Thread):

    def __init__(self):
        threading.Thread.__init__(self, name='CredServer')
        self._cred_channel = None
        self._quit = False

    def run(self):
        self._dbus = CredDBusObject()
        self._quit = False
        main = gobject.MainLoop()
        context = main.get_context()
        logging.info('CredServer is running...')
        while not self._quit:
            context.iteration(False)
            time.sleep(1)
        logging.info('CredServer has stopped.')

    def join(self):
        self._quit = True
        threading.Thread.join(self)

    def user_authenticated(self, credentials):
        if self._cred_channel is None:
            self._cred_channel = CredChannel()
            self._cred_channel.set_allowed([0])
            token = str(random.randint(100000, 999999))
            logging.debug("Token: %s", token)
            logging.info("Opening credentials channel...")
            self._cred_channel.start((token, credentials))
            self._dbus.UserAuthenticated(token)
            self._cred_channel.join()
            logging.info("Credentials channel was closed.")
            self._cred_channel = None
        else:
            logging.warn("Ignored authentication while another one is "
                         "in progress.")


def main():
    try:
        server = CredServer()
        server.start()
        time.sleep(2)
        server.user_authenticated(packcreds('user@domain', 'pass')[6:])
        while True:
            time.sleep(1)
    except (KeyboardInterrupt, SystemExit):
        server.join()

if __name__ == "__main__":
    logging.root.level = logging.DEBUG
    main()
