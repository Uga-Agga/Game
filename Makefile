# makefile for ugaagga

# programs
DISTCLEAN =	config.cache config.log config.status src/Makefile \
						src/dbs/db-util src/ticker/ticker.conf src/utilities/crontab \
						src/utilities/db_backup.cron src/utilities/util.inc.php \
						src/game/include/config.inc.php src/www/config.inc.php
SHELL =			/bin/sh

# default target
all:
	cd src && $(MAKE) $@

# install files
install-db:
	cd src && $(MAKE) $@

install-game:
	cd src && $(MAKE) $@

install-phpext:
	cd src && $(MAKE) $@

install-ticker:
	cd src && $(MAKE) $@

# uninstall files
uninstall-db:
	cd src && $(MAKE) $@

uninstall-game:
	cd src && $(MAKE) $@

uninstall-phpext:
	cd src && $(MAKE) $@

uninstall-ticker:
	cd src && $(MAKE) $@

# clean up
clean:
	cd src && $(MAKE) $@

doit:
	cd src && $(MAKE) install-game
	cd src && $(MAKE) install-ticker
	killall ticker

distclean: clean
	rm -f $(DISTCLEAN)
