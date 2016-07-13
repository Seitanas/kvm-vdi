#! /usr/bin/env python
# -*- coding: utf-8 -*-
# vim:fenc=utf-8
# Copyright 2014 Vinzenz Feenstra, Red Hat, Inc. and/or its affiliates.
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

import os
import os.path
import platform
import time


_IS_WINDOWS = platform.system() in ('Windows', 'Microsoft')


def _get_win_timezone_info():
    try:
        import win32com.client
        from pywintypes import com_error
        wmi = win32com.client.Dispatch('WbemScripting.SWbemLocator')
        server = wmi.ConnectServer('.', 'root\cimv2')
        for tz in server.ExecQuery('SELECT * FROM Win32_TimeZone'):
            return (tz.StandardName, (tz.Bias + tz.StandardBias))
    except (ImportError, AttributeError, com_error):
        pass
    return ('', 0)


def _read_etc_timezone():
    try:
        f = open('/etc/timezone', 'r')
        result = f.read().strip()
        f.close()
    except (OSError, IOError):
        return None
    return result


def _parse_etc_sysconfig_clock():
    try:
        f = open('/etc/sysconfig/clock', 'r')
        data = f.read().split('\n')
        f.close()
    except (OSError, IOError):
        return None
    for line in data:
        kv = line.split('=')
        if len(kv) == 2 and kv[0] == 'ZONE':
            return kv[1].replace('"', '')
    return None


def _zoneinfo_to_tz(path):
    return '/'.join(path.split('/')[-2:])


def _split_etc_localtime_symlink():
    return _zoneinfo_to_tz(os.readlink('/etc/localtime'))


def _get_linux_offset():
    return -time.timezone / 60


def _get_name_linux():
    result = None
    # is /etc/localtime a symlink?
    if os.path.islink('/etc/localtime'):
        result = _split_etc_localtime_symlink()
    # Debianoid
    if not result and os.path.exists('/etc/timezone'):
        result = _read_etc_timezone()
    # Pre-systemd RHEL/Fedora/CentOS
    if not result and os.path.exists('/etc/sysconfig/clock'):
        result = _parse_etc_sysconfig_clock()
    return result or ''


def get_timezone_info():
    if _IS_WINDOWS:
        return _get_win_timezone_info()
    return (_get_name_linux(), _get_linux_offset())


def get_name():
    if _IS_WINDOWS:
        return _get_win_timezone_info()[0]
    return _get_name_linux()


def _main():
    print "Timezone info:", get_timezone_info()
    if _IS_WINDOWS:
        return
    if os.path.islink('/etc/localtime'):
        print '_split_etc_localtime_symlink =', _split_etc_localtime_symlink()
    if os.path.exists('/etc/sysconfig/clock'):
        print '_parse_etc_sysconfig_clock =', _parse_etc_sysconfig_clock()
    if os.path.exists('/etc/timezone'):
        print '_read_etc_timezone =', _read_etc_timezone()
    print 'get_name_linux =', _get_name_linux()


if __name__ == '__main__':
    _main()
