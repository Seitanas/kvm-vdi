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

import ConfigParser
import cStringIO
import getopt
import logging
import logging.config
import os
import signal
import sys

from GuestAgentLinux2 import LinuxVdsAgent


io = None
try:
    import io as modio
    io = modio
except ImportError:
    import bytesio as modio
    io = modio


AGENT_CONFIG = '/etc/ovirt-guest-agent.conf'
AGENT_DEFAULT_CONFIG = '/usr/share/ovirt-guest-agent/default.conf'
AGENT_DEFAULT_LOG_CONFIG = '/usr/share/ovirt-guest-agent/default-logger.conf'
AGENT_PIDFILE = '/run/ovirt-guest-agent.pid'


class OVirtAgentDaemon:

    def __init__(self):
        cparser = ConfigParser.ConfigParser()
        cparser.read(AGENT_DEFAULT_LOG_CONFIG)
        cparser.read(AGENT_CONFIG)
        strio = cStringIO.StringIO()
        cparser.write(strio)
        bio = io.BytesIO(strio.getvalue())
        logging.config.fileConfig(bio)
        bio.close()
        strio.close()

    def run(self, daemon, pidfile):
        logging.info("Starting oVirt guest agent")
        config = ConfigParser.ConfigParser()
        config.read(AGENT_DEFAULT_CONFIG)
        config.read(AGENT_DEFAULT_LOG_CONFIG)
        config.read(AGENT_CONFIG)

        self.agent = LinuxVdsAgent(config)

        if daemon:
            self._daemonize()

        f = file(pidfile, "w")
        f.write("%s\n" % (os.getpid()))
        f.close()
        os.chmod(pidfile, 0644)   # rw-rw-r-- (664)

        self.register_signal_handler()
        self.agent.run()

        logging.info("oVirt guest agent is down.")

    def _daemonize(self):
        if os.getppid() == 1:
            raise RuntimeError("already a daemon")
        pid = os.fork()
        if pid == 0:
            os.umask(0)
            os.setsid()
            os.chdir("/")
            self._reopen_file_as_null(sys.stdin)
            self._reopen_file_as_null(sys.stdout)
            self._reopen_file_as_null(sys.stderr)
        else:
            os._exit(0)

    def _reopen_file_as_null(self, oldfile):
        nullfile = file("/dev/null", "rw")
        os.dup2(nullfile.fileno(), oldfile.fileno())
        nullfile.close()

    def register_signal_handler(self):

        def sigterm_handler(signum, frame):
            logging.debug("Handling signal %d" % (signum))
            if signum == signal.SIGTERM:
                logging.info("Stopping oVirt guest agent")
                self.agent.stop()

        signal.signal(signal.SIGTERM, sigterm_handler)


def usage():
    print "Usage: %s [OPTION]..." % (sys.argv[0])
    print ""
    print "  -p, --pidfile\t\tset pid file name (default: %s)" % AGENT_PIDFILE
    print "  -d\t\t\trun program as a daemon."
    print "  -h, --help\t\tdisplay this help and exit."
    print ""

if __name__ == '__main__':
    try:
        try:
            opts, args = getopt.getopt(sys.argv[1:],
                                       "?hp:d", ["help", "pidfile="])
            pidfile = AGENT_PIDFILE
            daemon = False
            for opt, value in opts:
                if opt in ("-h", "-?", "--help"):
                    usage()
                    os._exit(2)
                elif opt in ("-p", "--pidfile"):
                    pidfile = value
                elif opt in ("-d"):
                    daemon = True
            agent = OVirtAgentDaemon()
            agent.run(daemon, pidfile)
        except getopt.GetoptError, err:
            print str(err)
            print "Try `%s --help' for more information." % (sys.argv[0])
            os._exit(2)
        except:
            logging.exception("Unhandled exception in oVirt guest agent!")
            sys.exit(1)
    finally:
        try:
            os.unlink(AGENT_PIDFILE)
        except:
            pass
