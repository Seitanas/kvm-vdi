
SUBDIRS = pam consoleapps

AGENT_NAME = ovirt-guest-agent

agentdir=$(datadir)/ovirt-guest-agent
agent_PYTHON= \
    CredServer.py \
    GuestAgentLinux2.py \
    OVirtAgentLogic.py \
    VirtIoChannel.py \
    ovirt-guest-agent.py \
    timezone.py \
    hooks.py \
    $(NULL)

noinst_PYTHON=\
    bytesio.py \
    GuestAgentWin32.py \
    setup.py \
    version.py \
    WinFile.py \
    OVirtGuestService.py \
    $(NULL)

dist_pkgdata_SCRIPTS= \
    hibernate \
    LockActiveSession.py \
    LogoutActiveUser.py \
    $(NULL)

dbusconfdir=$(sysconfdir)/dbus-1/system.d
dbusconf_DATA=org.ovirt.vdsm.Credentials.conf

rulesdir=$(udevrulesdir)
rules_DATA = 55-$(AGENT_NAME).rules

55-$(AGENT_NAME).rules: $(AGENT_NAME).rules
	cp $(AGENT_NAME).rules 55-$(AGENT_NAME).rules
	cp $(AGENT_NAME).rules ../debian/$(AGENT_NAME).udev

# Conditionally install the systemd service
if HAVE_SYSTEMD
systemdsystemunit_DATA = ovirt-guest-agent.service
endif

EXTRA_DIST= \
    $(agent_DATA) \
    $(agentconf_DATA) \
    $(daemon_DATA) \
    $(dbusconf_DATA) \
    $(AGENT_NAME).rules \
    ovirt-guest-agent.in \
    ovirt-guest-agent.service \
    ovirt-guest-agent.sles \
    ovirt-guest-agent.el5.rules \
    Makefile.el5 \
    README-windows.txt \
    win-guest-agent-build-exe.bat \
    $(NULL)

CLEANFILES = \
    *~ \
    55-$(AGENT_NAME).rules \
	../debian/ovirt-guest-agent.udev \
	$(NULL)

MAINTAINERCLEANFILES = \
    ovirt-guest-agent \
	$(NULL)
