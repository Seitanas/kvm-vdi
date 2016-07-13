#
# Copyright 2016 Hat, Inc. and/or its affiliates.
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
import subprocess


class UnknownHookError(LookupError):
    def __init__(self, hook_name):
        LookupError.__init__(self, 'Unknown hook "%s" requested' % hook_name)


class Hooks(object):
    def __init__(self, log, hook_dir):
        self._hook_dir = hook_dir
        self._log = log

    def _find_hooks(self, name):
        """ Return a sorted list of hooks for the given hook name """
        hooks_dir = os.path.join(self._hook_dir, name)
        files = os.listdir(hooks_dir)
        files.sort()
        return [os.path.join(hooks_dir, f) for f in files]

    def _execute(self, path):
        """ Executes the given path and returns return code and output """
        try:
            proc = subprocess.Popen(path, stderr=subprocess.PIPE,
                                    stdout=subprocess.PIPE)
            out, err = proc.communicate()
        except OSError:
            self._log.warning('Executing %s failed', path, exc_info=True)
            return -1, '', ''
        return proc.returncode, out, err

    def _run(self, name):
        """ Executes all hooks which are currently configured for the given
            event
        """
        for path in self._find_hooks(name):
            self._log.debug('Attempting to execute hook %s', path)
            retval, out, err = self._execute(path)
            if retval != 0:
                self._log.warning('Hook(%s) "%s" return non-zero exit code %d '
                                  '\nSTDOUT:\n%sSTDERR:\n%s\n', name, path,
                                  retval, out, err)
            else:
                self._log.info('Hook(%s) "%s" executed', name, path)

    def dispatch(self, hook):
        """ Runtime dispatch of the hook by name """
        func = getattr(self, hook, None)
        if func is not None:
            if callable(func):
                return func()
        raise UnknownHookError(hook)

    def before_hibernation(self):
        self._run('before_hibernation')

    def after_hibernation(self):
        self._run('after_hibernation')

    def before_migration(self):
        self._run('before_migration')

    def after_migration(self):
        self._run('after_migration')
