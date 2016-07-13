#!/usr/bin/python
#
# Copyright 2011 Red Hat, Inc. and/or its affiliates.
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
import time

import pywintypes
import win32con
import win32event
import win32file
import win32security


# Using Python's os.read() to do a blocking-read doesn't allow
# to use os.write() on a different thread. This class overrides
# this problem by using Windows's API.
class WinFile(object):

    def __init__(self, filename):
        self._hfile = win32file.CreateFile(
            filename,
            win32con.GENERIC_READ | win32con.GENERIC_WRITE,
            win32con.FILE_SHARE_READ | win32con.FILE_SHARE_WRITE,
            win32security.SECURITY_ATTRIBUTES(),
            win32con.OPEN_EXISTING,
            win32con.FILE_FLAG_OVERLAPPED,
            0)
        self._read_ovrlpd = pywintypes.OVERLAPPED()
        self._read_ovrlpd.hEvent = win32event.CreateEvent(None, True, False,
                                                          None)
        self._write_ovrlpd = pywintypes.OVERLAPPED()
        self._write_ovrlpd.hEvent = win32event.CreateEvent(None, True, False,
                                                           None)

    def read(self, n):
        (nr, buf) = (0, ())
        try:
            (hr, buf) = win32file.ReadFile(
                self._hfile,
                win32file.AllocateReadBuffer(n),
                self._read_ovrlpd)
            nr = win32file.GetOverlappedResult(self._hfile,
                                               self._read_ovrlpd,
                                               True)
        except:
            logging.debug("Exception on reading from VirtIO", exc_info=True)
            # We do sleep here to avoid constant reads spike the CPU
            time.sleep(1)
        return buf[:nr]

    def write(self, s):
        try:
            win32file.WriteFile(self._hfile, s, self._write_ovrlpd)
            return win32file.GetOverlappedResult(self._hfile,
                                                 self._write_ovrlpd,
                                                 True)
        except:
            logging.debug("Exception writing to VirtIO", exc_info=True)
            # We do sleep here to avoid constant writes to spike the CPU
            time.sleep(1)
            return 0
