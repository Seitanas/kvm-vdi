#
# Copyright 2010-2013 Red Hat, Inc. and/or its affiliates.
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

import locale
import logging
import os
import platform
import time
import unicodedata


# avoid pep8 warnings
def import_json():
    try:
        import json
        return json
    except ImportError:
        import simplejson
        return simplejson
json = import_json()

__REPLACEMENT_CHAR = u'\ufffd'
# Set taken from http://www.w3.org/TR/xml11/#NT-RestrictedChar
__RESTRICTED_CHARS = set(range(8 + 1))\
    .union(set(range(0xB, 0xC + 1)))\
    .union(set(range(0xE, 0x1F + 1)))\
    .union(set(range(0x7F, 0x84 + 1)))\
    .union(set(range(0x86, 0x9F + 1)))


def _string_convert(str):
    """
    This function tries to convert the given string to an unicode string
    """
    if isinstance(str, unicode):
        return str
    try:
        return str.decode(locale.getpreferredencoding(), 'strict')
    except UnicodeError:
        try:
            return str.decode(locale.getpreferredencoding(), 'replace')
        except UnicodeError:
            # unrepresentable string
            return u'????'


def _filter_xml_chars(u):
    """
    The set of characters allowed in XML documents is described in
    http://www.w3.org/TR/xml11/#charsets

    "Char" is defined as any unicode character except the surrogate blocks,
    \ufffe and \uffff.
    "RestrictedChar" is defiend as the code points in __RESTRICTED_CHARS above

    It's a little hard to follow, but the uposhot is an XML document must
    contain only characters in Char that are not in RestrictedChar.

    Note that Python's xmlcharrefreplace option is not relevant here -
    that's about handling charaters which can't be encoded in a given charset
    encoding, not which aren't permitted in XML.
    """
    def filter_xml_char(c):
        if ord(c) > 0x10ffff:
            return __REPLACEMENT_CHAR  # Outside Unicode range
        elif unicodedata.category(c) == 'Cs':
            return __REPLACEMENT_CHAR  # Surrogate pair code point
        elif ord(c) == 0xFFFE or ord(c) == 0xFFFF:
            return __REPLACEMENT_CHAR  # Specifically excluded code points
        elif ord(c) in __RESTRICTED_CHARS:
            return __REPLACEMENT_CHAR
        else:
            return c
    if not isinstance(u, unicode):
        raise TypeError

    return ''.join(filter_xml_char(c) for c in u)


def _filter_object(obj):
    """
    Apply _filter_xml_chars and _string_check on all strings in the given
    object
    """
    def filt(o):
        if isinstance(o, dict):
            return dict(map(filt, o.iteritems()))
        if isinstance(o, list):
            return map(filt, o)
        if isinstance(o, tuple):
            return tuple(map(filt, o))
        if isinstance(o, basestring):
            return _filter_xml_chars(_string_convert(o))
        return o

    return filt(obj)


class VirtIoStream(object):
    # Python on Windows 7 returns 'Microsoft' rather than 'Windows' as
    # documented.
    is_windows = platform.system() in ['Windows', 'Microsoft']
    is_test = False

    def __init__(self, vport_name):
        if self.is_test:
            from test_port import get_test_port
            self._vport = get_test_port(vport_name)
            self._read = self._vport.read
            self._write = self._vport.write
        elif self.is_windows:
            from WinFile import WinFile
            self._vport = WinFile(vport_name)
            self._read = self._vport.read
            self._write = self._vport.write
        else:
            self._vport = os.open(vport_name, os.O_RDWR)
            self._read = self._os_read
            self._write = self._os_write

    def _os_read(self, size):
        return os.read(self._vport, size)

    def _os_write(self, buffer):
        return os.write(self._vport, buffer)

    def read(self, size):
        return self._read(size)

    def write(self, buffer):
        return self._write(buffer)


class VirtIoChannel:
    def __init__(self, vport_name):
        self._stream = VirtIoStream(vport_name)
        self._buffer = ''

    def _readbuffer(self):
        buffer = self._stream.read(4096)
        if buffer:
            self._buffer += buffer
        else:
            # read() returns immediately (non-blocking) if no one is
            # listening on the other side of the virtio-serial port.
            # So in order not to be in a tight-loop and waste CPU
            # time, we just sleep for a while and hope someone will
            # be there when we will awake from our nap.
            time.sleep(1)

    def _readline(self):
        newline = self._buffer.find('\n')
        while newline < 0:
            self._readbuffer()
            newline = self._buffer.find('\n')
        if newline >= 0:
            line, self._buffer = self._buffer.split('\n', 1)
        else:
            line = None
        return line

    def _parseLine(self, line):
        try:
            args = json.loads(line.decode('utf8'))
            name = args['__name__']
            del args['__name__']
        except:
            name = None
            args = None
        return (name, args)

    def read(self):
        return self._parseLine(self._readline())

    def write(self, name, args={}):
        if not isinstance(name, str):
            raise TypeError("1nd arg must be a str.")
        if not isinstance(args, dict):
            raise TypeError("2nd arg must be a dict.")
        args['__name__'] = name
        args = _filter_object(args)
        message = (json.dumps(args) + '\n').encode('utf8')
        while len(message) > 0:
            written = self._stream.write(message)
            logging.debug("Written %s" % message[:written])
            message = message[written:]


def _create_vio():
    if (platform.system() == 'Windows') or (platform.system() == 'Microsoft'):
        vport_name = '\\\\.\\Global\\com.redhat.rhevm.vdsm'
    else:
        vport_name = '/dev/virtio-ports/com.redhat.rhevm.vdsm'
    return VirtIoChannel(vport_name)


def _test_write():
    vio = _create_vio()
    vio.write('network-interfaces',
              {'interfaces': [{
                  'name': 'eth0',
                  'inet': ['10.0.0.2'],
                  'inet6': ['fe80::213:20ff:fef5:f9d6'],
                  'hw': '00:1a:4a:23:10:00'}]})
    vio.write('applications', {'applications': ['kernel-2.6.32-131.4.1.el6',
                                                'rhev-agent-2.3.11-1.el6']})


def _test_read():
    vio = _create_vio()
    line = vio.read()
    while line:
        print line
        line = vio.read()

if __name__ == "__main__":
    _test_read()
