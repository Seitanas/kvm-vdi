#! /usr/bin/env python
# -*- coding: utf-8 -*-
# vim:fenc=utf-8
#
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
import subprocess

from LockActiveSession import GetActiveSession


def LogoutUserGnome(session):
    pid = os.fork()
    if pid == 0:
        os.setuid(session.GetUnixUser())
        os.environ['DISPLAY'] = session.GetX11Display()
        subprocess.call(['/usr/bin/gnome-session-save', '--force-logout'])
    else:
        os.waitpid(pid, 0)


def LogoutUser():
    session = GetActiveSession()
    if os.path.exists('/usr/bin/loginctl'):
        subprocess.call(['/usr/bin/loginctl', 'terminate-session',
                         session.GetId()])
    else:
        LogoutUserGnome(session)


if __name__ == '__main__':
    LogoutUser()
