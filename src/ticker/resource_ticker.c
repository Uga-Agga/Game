/*
 * resource_ticker.c - automatic resource production
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <errno.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "database.h"
#include "function.h"
#include "game_rules.h"
#include "logging.h"
#include "memory.h"
#include "resource_ticker.h"
#include "ticker.h"
#include "ugatime.h"
#include "artefact.h"
#include "cave.h"
#include "message.h"

/* configuration parameters */

const char *ticker_state;
long tick_interval;

/* static variables */

static time_t last_tick;

/*
 * Initialize resource events. Last event is taken from state file
 * or current time (if there is no state file).
 */
void tick_init (void)
{
    FILE *file = fopen(ticker_state, "r");
    long timeval = time(NULL);

    if (file)
    {
	fscanf(file, "%ld", &timeval);
	fclose(file);
    }

    /* init with stored tick, round to multiple of interval */
    last_tick = timeval / tick_interval * tick_interval;
    tick_log();
}

/*
 * Write last resource event timestamp to the state file.
 */
void tick_log (void)
{
    FILE *file = fopen(ticker_state, "w");

    if (file)
	fprintf(file, "%ld\n", (long) last_tick);

    if (file == NULL || fclose(file))
	error("tick_log: %s: %s", ticker_state, strerror(errno));
}

/*
 * Return the timestamp of the next resource event.
 */
time_t tick_next_event (void)
{
    return last_tick + tick_interval;
}

/*
 * Advance to the next resource event, return timestamp.
 */
time_t tick_advance (void)
{
    return last_tick += tick_interval;
}

/*
 * Perform resource update on the cave table.
 */
void resource_ticker (db_t *database, time_t timeval)
{
  const struct ugatime *uga_time = get_ugatime(timeval);
  float uga_bonus  = get_bonus(RELIGION_UGA,  uga_time)->production;
  float agga_bonus = get_bonus(RELIGION_AGGA, uga_time)->production;
  dstring_t *ds;
  db_result_t *result;
  int i;
  int row;

  /* create start of the SQL statement */
  ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

  /* update each resource delta */
  for (i = 0; i < MAX_RESOURCE; ++i)
  {
printf("%d",i);
    /* the function for the actual resource */
    const char *function = function_to_sql(((struct Resource *) resource_type[i])->production);

    /* update the delta and value for this resource */
    dstring_append(ds, "%s%s_delta = (%s) * IF((%s) <= 0, 1, 1 + SIGN(%s)*%g + SIGN(%s)*%g), %s = GREATEST(LEAST(%s + %s_delta, %s), 0)",
    i > 0 ? "," : "",
    resource_type[i]->dbFieldName, function, function,
#if defined(ID_SCIENCE_UGA) && defined(ID_SCIENCE_AGGA)
    science_type[ID_SCIENCE_UGA]->dbFieldName, uga_bonus,
    science_type[ID_SCIENCE_AGGA]->dbFieldName, agga_bonus,
#else
    "0", 0.0,
    "0", 0.0,
#endif
    resource_type[i]->dbFieldName,
    resource_type[i]->dbFieldName,
    resource_type[i]->dbFieldName,
    function_to_sql(resource_type[i]->maxLevel));
  }

  /* end of the SQL statement */
  dstring_append(ds, " WHERE playerID != 0");

  debug(DEBUG_SQL, "%s", dstring_str(ds));
  db_query_dstring(database, ds);

  /* Update Hero Heal Points */
  ds = dstring_new("UPDATE " DB_TABLE_HERO " SET healPoints = LEAST(healPoints + regHP, maxHealPoints) WHERE isAlive > 0 ");
  debug(DEBUG_SQL, "%s", dstring_str(ds));
  db_query_dstring(database, ds);

  /* nun lassen wir die Artes mal verhungern */
  ds = dstring_new("SELECT a.artefactID, c.caveID FROM " DB_TABLE_ARTEFACT
                " a LEFT JOIN " DB_TABLE_ARTEFACT_CLASS
                " ac ON ac.ArtefactClassID = a.ArtefactClassID "
                " LEFT JOIN " DB_TABLE_CAVE
                " c ON c.caveID = a.caveID "
                " WHERE a.initiated = 1 "
                " AND c.food = 0 "
                " AND c.playerID != 0 "
                " AND ac.noZeroFood = 1");
  result = db_query_dstring(database, ds);

  while ((row = db_result_next_row(result))) {
    struct Player player;
    struct Cave   cave;

    int artefactID;
    int caveID;

    artefactID = db_result_get_int(result, "artefactID");
    caveID = db_result_get_int(result, "caveID");

    get_cave_info(database, caveID, &cave);
    get_player_info(database, cave.player_id, &player);

    remove_effects_from_cave(database,  artefactID);
    uninitiate_artefact(database,       artefactID);
    remove_artefact_from_cave(database, artefactID);
    artefact_zero_food_report(database, &cave, &player, artefactID, caveID);
  }
}
