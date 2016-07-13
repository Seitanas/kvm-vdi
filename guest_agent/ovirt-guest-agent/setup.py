
from distutils.core import setup
from glob import glob
import os
import sys
import version

import py2exe

dsa_path = os.path.join(
    os.path.dirname(
        os.path.dirname(
            os.path.dirname(
                os.path.abspath(
                    sys.argv[0])))),
    "re")
sys.path.append(dsa_path)

if len(sys.argv) == 1:
    sys.argv.append("py2exe")
    sys.argv.append("-b 1")


class Target:
    def __init__(self, **kw):
        self.__dict__.update(kw)
        self.version = "%s.%s" % (version.version_info['software_version'],
                                  version.version_info['software_revision'])
        self.company_name = "Red Hat"
        self.copyright = "Copyright(C) Red Hat Inc."
        self.name = "Guest VDS Agent "

OVirtAgentTarget = Target(description="Ovirt Guest Agent",
                          modules=["OVirtGuestService"])

DLL_EXCLUDES = ['POWRPROF.dll', 'KERNELBASE.dll',
                'WTSAPI32.dll', 'MSWSOCK.dll']
for name in glob(os.getenv('windir') + '\*\API-MS-Win-*.dll'):
    DLL_EXCLUDES.append(name[name.rfind('\\') + 1:])

setup(service=[OVirtAgentTarget],
      options={'py2exe': {
          'bundle_files': 1,
          'dll_excludes': DLL_EXCLUDES}},
      zipfile=None)
