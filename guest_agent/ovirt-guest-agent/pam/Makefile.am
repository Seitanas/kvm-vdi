if INSTALL_USERMODE_SCRIPTS
pamdir = $(PAM_PREFIX)/pam.d
pam_DATA =               \
    ovirt-container-list \
    ovirt-flush-caches   \
    ovirt-shutdown       \
    ovirt-logout         \
    ovirt-locksession    \
    ovirt-hibernate      \
    $(NULL)
endif

EXTRA_DIST =             \
    ovirt-container-list \
    ovirt-flush-caches   \
    ovirt-shutdown       \
    ovirt-logout         \
    ovirt-locksession    \
    ovirt-hibernate      \
    $(NULL)

CLEANFILES = \
    *~

MAINTAINERCLEANFILES = \
    Makefile.in
