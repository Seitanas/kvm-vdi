
if INSTALL_USERMODE_SCRIPTS
consoleappsdir = $(sysconfdir)/security/console.apps
consoleapps_DATA =       \
    ovirt-container-list \
    ovirt-shutdown       \
    ovirt-logout         \
    ovirt-locksession    \
    ovirt-hibernate      \
    ovirt-flush-caches   \
    $(NULL)
endif

EXTRA_DIST =             \
    ovirt-container-list \
    ovirt-shutdown       \
    ovirt-logout         \
    ovirt-locksession    \
    ovirt-hibernate      \
    ovirt-flush-caches   \
    $(NULL)

CLEANFILES = \
    *~

MAINTAINERCLEANFILES = \
    Makefile.in
