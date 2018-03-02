dnl Process this file with autoconf to produce a configure script.
dnl Copyright (c) 2003  Elmar Ludwig

AC_INIT(uga-agga, 1.0, uga-dev@uga-agga.de)
dnl AC_CONFIG_HEADER(src/config.h)

AC_ARG_VAR(PHP, PHP interpreter)
AC_ARG_VAR(XSLTPROC, XSLT processor)

dnl checks for optional features
AC_ARG_ENABLE(debug, [AC_HELP_STRING(--enable-debug, [turn on debugging [no]])])

dnl checks for external software
AC_ARG_WITH(gamedir,
  [AC_HELP_STRING(--with-gamedir, [game data directory [autodetect]])])

AC_ARG_WITH(wwwrun,
  [AC_HELP_STRING(--with-wwwrun, [web server user name [autodetect]])])

AC_ARG_WITH(logdir,
  [AC_HELP_STRING(--with-logdir, [log file directory [/var/log/ugaagga]])])
AC_SUBST(LOGDIR, ${with_logdir-/var/log/ugaagga})

AC_ARG_WITH(ticker-home,
  [AC_HELP_STRING(--with-ticker-home, [ticker install dir [/home/ticker]])])
AC_SUBST(TICKER_HOME, ${with_ticker_home-/home/ticker})

AC_ARG_WITH(ticker-user,
  [AC_HELP_STRING(--with-ticker-user, [ticker user name [ticker]])])
ticker_uid=`id -u ${with_ticker_user-ticker} 2>/dev/null`
ticker_gid=`id -g ${with_ticker_user-ticker} 2>/dev/null`
if test 0 != ${ticker_uid:-0}; then
  AC_SUBST(TICKER_INSTALL_FLAGS, "-o $ticker_uid -g $ticker_gid")
fi

dnl checks for programs
AC_PROG_CC
AC_PROG_AWK
AC_PROG_INSTALL
AC_CHECK_PROG(PHP, php, php)
AC_CHECK_PROG(PHP_CONFIG, php-config, php-config)
AC_CHECK_PROG(XSLTPROC, xsltproc, xsltproc)
AC_CHECK_PROG(MYSQL_CONFIG, mysql_config, mysql_config)
test -z "$PHP" && AC_MSG_ERROR(installation problem: PHP not found)
test -z "$PHP_CONFIG" && AC_MSG_ERROR(installation problem: PHP not found)
test -z "$XSLTPROC" && AC_MSG_ERROR(installation problem: xsltproc not found)
test -z "$MYSQL_CONFIG" && AC_MSG_ERROR(installation problem: MySQL client library development files not found. install libmysqld-dev and libmysqlclient-dev)

test "$GCC" && CFLAGS="$CFLAGS -Wall"

if test yes = "$enable_debug"; then
  dnl TODO replace DEBUG_MALLOC with HAVE_LIBGC
  CPPFLAGS="$CPPFLAGS -DDEBUG_MALLOC"
  AC_CHECK_LIB(gc, GC_malloc)
fi

dnl checks for libraries
AC_SEARCH_LIBS(sqrt, m)
AC_SEARCH_LIBS(mxmlNewXML, mxml)
AC_SUBST(PHP_CFLAGS, `$PHP_CONFIG --includes`)
AC_SUBST(PHP_LIBS, `$PHP_CONFIG --libs`)
AC_SUBST(PHP_EXT_DIR, `$PHP_CONFIG --extension-dir`)
AC_SUBST(MYSQL_CFLAGS, `$MYSQL_CONFIG --cflags`)
AC_SUBST(MYSQL_LIBS, `$MYSQL_CONFIG --libs`)

dnl checks for files
AC_CHECK_FILE(/etc/httpd/conf/httpd.conf, apacheconf=/etc/httpd/conf/httpd.conf,
  AC_CHECK_FILE(/etc/httpd/httpd.conf, apacheconf=/etc/httpd/httpd.conf,
    AC_CHECK_FILE(/etc/apache/httpd.conf, apacheconf=/etc/apache/httpd.conf)))
if test -n "$apacheconf"; then
  base=`$AWK '$1=="DocumentRoot" {print $2; exit}' $apacheconf | tr -d '"'`
  user=`$AWK '$1=="User" {print $2; exit}' $apacheconf | tr -d '"'`
else
  base=/var/www
  user=www-data
fi

AC_SUBST(GAMEDIR, ${with_gamedir-$base/game})
AC_SUBST(WWWRUN, ${with_wwwrun-$user})
AC_MSG_NOTICE(installing game in $GAMEDIR)
AC_MSG_NOTICE(web server user is $WWWRUN)

dnl local configuration
while read key value; do
  case $key in \#*|'') continue;; esac
  eval "cfg_$key=\$value"
done < ugaagga.conf

localhost=`hostname -f`
test "$cfg_game_db_host" = "$localhost" && cfg_game_db_host=localhost
test "$cfg_login_db_host" = "$localhost" && cfg_login_db_host=localhost

AC_SUBST(ROOT_DB_PASSWD, $cfg_root_db_passwd)
AC_SUBST(GAME_DB_HOST, $cfg_game_db_host)
AC_SUBST(GAME_DB_NAME, $cfg_game_db_name)
AC_SUBST(GAME_DB_USER, $cfg_game_db_user)
AC_SUBST(GAME_DB_PASSWD, $cfg_game_db_passwd)
AC_SUBST(LOGIN_DB_HOST, $cfg_login_db_host)
AC_SUBST(LOGIN_DB_NAME, $cfg_login_db_name)
AC_SUBST(LOGIN_DB_USER, $cfg_login_db_user)
AC_SUBST(LOGIN_DB_PASSWD, $cfg_login_db_passwd)
AC_SUBST(GAME_HOSTS, $cfg_game_hosts)
AC_SUBST(FORUM_URL, $cfg_forum_url)
AC_SUBST(GFX_URL, $cfg_gfx_url)
AC_SUBST(HELP_URL, $cfg_help_url)
AC_SUBST(LOGIN_URL, $cfg_login_url)
AC_SUBST(RULES_URL, $cfg_rules_url)
AC_SUBST(SESSION_TIMEOUT, $cfg_session_timeout)
AC_SUBST(BUILDING_TIME_BASE, $cfg_building_time_base)
AC_SUBST(DEFSYSTEM_TIME_BASE, $cfg_defsystem_time_base)
AC_SUBST(MOVEMENT_TIME_BASE, $cfg_movement_time_base)
AC_SUBST(SCIENCE_TIME_BASE, $cfg_science_time_base)
AC_SUBST(UNIT_TIME_BASE, $cfg_unit_time_base)
AC_SUBST(WONDER_TIME_BASE, $cfg_wonder_time_base)
AC_SUBST(WEATHER_TIME_BASE, $cfg_weather_time_base)
AC_SUBST(GOVERNMENT_CHANGE, $cfg_government_change)
AC_SUBST(TEAR_DOWN_TIMEOUT, $cfg_tear_down_timeout)
AC_SUBST(TRIBE_BLOCK_PERIOD, $cfg_tribe_block_period)
AC_SUBST(UNIT_BUILD_BATCH, $cfg_unit_build_batch)
AC_SUBST(GOD_TRIBE, $cfg_god_tribe)
AC_SUBST(CAVE_SIZE_DB_FIELD, $cfg_cave_size_db_field)
AC_SUBST(TRIBE_LEAVE_COST, $cfg_tribe_leave_cost)
AC_SUBST(FAME_DECREASE, $cfg_fame_decrease)
AC_SUBST(FAME_MIN_POINTS, $cfg_fame_min_points)
AC_SUBST(JABBER_BOSH, $cfg_jabber_bosh)
AC_SUBST(JABBER_SERVER, $cfg_jabber_server)
AC_SUBST(JABBER_MUC, $cfg_jabber_muc)
AC_SUBST(JABBER_MUC_LOG_PATH, $cfg_jabber_muc_log_path)
AC_SUBST(JABBER_NO_CLOSE, $cfg_jabber_no_close)
AC_SUBST(JABBER_MUC_STATIC, $cfg_jabber_muc_static)

AC_CONFIG_FILES(src/Makefile src/dbs/db-util src/ticker/ticker.conf
  src/utilities/crontab src/utilities/db_backup.cron src/utilities/util.inc.php
  src/game/include/config.inc.php src/www/config.inc.php)
AC_OUTPUT
