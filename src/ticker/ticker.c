/*
 * ticker.c - ticker daemon process
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <errno.h>
#include <signal.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>	/* chdir, getopt, getpid, usleep */

#include "config.h"
#include "database.h"
#include "event_handler.h"
#include "except.h"
#include "function.h"
#include "logging.h"
#include "memory.h"
#include "message.h"
#include "resource_ticker.h"
#include "ticker.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

#define TICKER_LOADAVG_TIME	300

/* ticker configuration parameters */

static const char *db_host;
static const char *db_name;
static const char *db_user;
static const char *db_passwd;

static const char *ticker_home;
static const char *pid_file;
static const char *debug_logfile;
static const char *error_logfile;
static const char *msg_logfile;

static long sleep_time;

/* ticker static variables */

static const char *config_file = "/etc/ticker.conf";
static log_handler_t *debug_log;
static log_handler_t *error_log;
static log_handler_t *msg_log;

static volatile int finish;
static volatile int reload;

/*
 * Get current configuration parameters.
 */
static void fetch_config_values (void)
{
  db_host = config_get_value("db_host");
  db_name = config_get_value("db_name");
  db_user = config_get_value("db_user");
  db_passwd = config_get_value("db_passwd");
  ticker_home = config_get_value("ticker_home");
  pid_file = config_get_value("pid_file");
  ticker_state = config_get_value("ticker_state");
  debug_logfile = config_get_value("debug_logfile");
  error_logfile = config_get_value("error_logfile");
  msg_logfile = config_get_value("msg_logfile");
  tick_interval = config_get_long_value("tick_interval");
  sleep_time = config_get_long_value("sleep_time");
}

/*
 * Store the process identifier in the pid file.
 */
static void write_pidfile (const char *pid_file)
{
  FILE *file = fopen(pid_file, "r");
  long pid;

  if (file)
  {
    /* check for an already running ticker instance */
    if (fscanf(file, "%ld", &pid) == 1 && kill(pid, 0) == 0)
      error("ticker already running, exiting");
    fclose(file);
  }

  file = fopen(pid_file, "w");

  if (file)
    fprintf(file, "%ld\n", (long) getpid());

  if (file == NULL || fclose(file))
    error("write_pidfile: %s: %s", pid_file, strerror(errno));
}

/*
 * Block automatic ticker restart and exit with error message.
 */
static void block_ticker (const char *error_msg)
{
  fopen("BLOCKED", "w");
  error("%s", error_msg);
}

/*
 * Signal handling functions.
 */
static void set_finish (int signum)
{
  finish = 1;
}

static void set_reload (int signum)
{
  reload = 1;
}

/*
 * The event scheduler and resource ticker.
 */
static void run_ticker (db_t *database)
{
  time_t last = time(NULL);
  int events = 0, sleeps = 0;

  debug(DEBUG_TICKER, "running");

  while (!finish) {
    /* set up memory pool */
    struct memory_pool *pool = memory_pool_new();
    time_t now = time(NULL);
    int secs = now - last;

    if (secs >= TICKER_LOADAVG_TIME)
    {
	debug(DEBUG_TICKER, "ticker load: %.2f (%d events/min)",
	      1 - sleep_time / 1000000.0 * sleeps / secs, 60 * events / secs);

	events = sleeps = 0;
	last = now;
#ifdef DEBUG_MALLOC
	CHECK_LEAKS();
#endif
    }

    if (reload)
    {
	debug(DEBUG_TICKER, "reload config");
	reload = 0;

	/* read config file */
	config_read_file(config_file);
	fetch_config_values();
	log_handler_set_file(debug_log, debug_logfile);
	log_handler_set_file(error_log, error_logfile);
	log_handler_set_file(msg_log, msg_logfile);
    }

    try {
      char resource_timestamp[TIMESTAMP_LEN];
      char timestamp[TIMESTAMP_LEN];
      db_result_t *next_result = NULL;
      const char *next_timestamp =
	make_timestamp_gm(resource_timestamp, tick_next_event());
      const char *next_db_eventID;
      int next_eventType;
      int i;

      /* check each event queue to find the next event to process */
      debug(DEBUG_EVENTS, "start loop, next resource event");

      for (i = 0; i < eventTableSize; ++i)
      {
	db_result_t *result;

	/* get only the next non-blocked event, if its timestamp
	   is smaller than the smallest found timestamp */
	debug(DEBUG_EVENTS, "query event table: %s", eventTableList[i].table);
//	debug(DEBUG_TICKER, "query event table: %s", eventTableList[i].table);

	result = db_query(database,
		    "SELECT * FROM %s WHERE blocked = 0 AND end < '%s' "
		    "ORDER BY end ASC, %s ASC LIMIT 0,1",
		    eventTableList[i].table, next_timestamp,
		    eventTableList[i].id_field);

	if (db_result_num_rows(result))	/* is there an earlier event? */
	{
	  /* extract this earlier event's needed data */
	  db_result_next_row(result);

	  next_result = result;		/* remember the earlier one */
	  next_timestamp = db_result_get_string(result, "end");
	  next_db_eventID =
	    db_result_get_string(result, eventTableList[i].id_field);
	  next_eventType = i;
	}
      }

      if (strcmp(next_timestamp, make_timestamp_gm(timestamp, time(NULL))) > 0)
      {
	debug(DEBUG_EVENTS, "no event pending, sleep");
	++sleeps;
	usleep(sleep_time);
      }
      else
      {
	debug(DEBUG_TICKER, "event: scheduled %s, now %s",
	      next_timestamp, timestamp);

	/* check which handler to call (resource ticker or event handler) */
	if (next_result)
	{
	  /* found an event in the event tables: block the event */
	  debug(DEBUG_EVENTS, "block event: %s", next_db_eventID);
	  ++events;

	  db_query(database, "UPDATE %s SET blocked = 1 WHERE %s = %s",
			  eventTableList[next_eventType].table,
			  eventTableList[next_eventType].id_field,
			  next_db_eventID);

	  /* call handler and delete event */
	  eventTableList[next_eventType].handler(database, next_result);

	  debug(DEBUG_EVENTS, "delete event: %s", next_db_eventID);

	  db_query(database, "DELETE FROM %s WHERE %s = %s",
			  eventTableList[next_eventType].table,
			  eventTableList[next_eventType].id_field,
			  next_db_eventID);
	}
	else
	{
	  /* next event is resource tick: call resource ticker */
	  debug(DEBUG_TICKER, "resource tick %s", resource_timestamp);

	  resource_ticker(database, tick_advance());
	  debug(DEBUG_TICKER, "resource tick ended");
	  tick_log();			/* log last successful update */
	}
      }
    } catch (BAD_ARGUMENT_EXCEPTION) {
      warning("%s", except_msg);
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } catch (GENERIC_EXCEPTION) {
      warning("%s", except_msg);
    } catch (DB_EXCEPTION) {
      block_ticker(except_msg);
    } catch (NULL) {
      error("%s", except_msg);
    } end_try;

    memory_pool_free(pool);
  }

  debug(DEBUG_TICKER, "end");
}

int main (int argc, char *argv[])
{
  db_t *database;
  int opt;

  log_debug_flags = DEBUG_TICKER | DEBUG_TAKEOVER | DEBUG_FAME;

  while ((opt = getopt(argc, argv, "C:V")) >= 0)
  {
    switch (opt)
    {
      case 'C':
	config_file = optarg;
	break;
      case 'V':
	puts("$Id: ticker.c 2391 2009-01-12 20:14:20Z root $");
	return 0;
      default:
	return 1;
    }
  }

#ifdef DEBUG_MALLOC
  GC_find_leak = 1;
#endif

  /* init random number generator */
  srand(time(NULL));

  /* init function parser */
  function_setup();

  /* read config file */
  config_read_file(config_file);
  fetch_config_values();

  /* open ticker logfiles */
  if (chdir(ticker_home))
    error("%s: %s", ticker_home, strerror(errno));

  debug_log = log_handler_new(debug_logfile);
  error_log = log_handler_new(error_logfile);
  msg_log   = log_handler_new(msg_logfile);

  log_set_debug_handler(debug_log);
  log_set_warning_handler(error_log);
  log_set_error_handler(error_log);
  message_set_log_handler(msg_log);

  /* connect to the database */
  debug(DEBUG_TICKER, "init");
  write_pidfile(pid_file);

  try {
    database = db_connect(db_host, db_user, db_passwd, db_name);
  } catch (DB_EXCEPTION) {
    error("%s", except_msg);
  } end_try;

  /* read last tick */
  tick_init();

  /* install signal handler */
  signal(SIGTERM, set_finish);
  signal(SIGINT, set_finish);
#ifdef SIGHUP
  signal(SIGHUP, set_reload);
#endif

  /* start the ticker */
  run_ticker(database);

  /* clean up */
  debug(DEBUG_TICKER, "cleanup");

  db_close(database);
  remove(pid_file);
  return 0;
}
