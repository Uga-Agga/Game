/*
 * wonder_handler.c - process wonder events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>	/* atoi */
#include <string.h>	/* memset */
#include <time.h>	/* time */

#include "cave.h"
#include "database.h"
#include "event_handler.h"
#include "except.h"
#include "function.h"
#include "logging.h"
#include "memory.h"
#include "message.h"
#include "ticker.h"
#include "wonder_rules.h"

/*
 * Function is called to instantiate a wonder's impact.
 */
void weather_handler (db_t *database, db_result_t *result)
{
    int weather_id;
    int impact_id;
    int region_id;
    const struct Weather *weather;
    const struct WeatherImpact *impact;
    const char *weather_start;
    int duration;
    char weather_end[TIMESTAMP_LEN];
    dstring_t *query;
    dstring_t *active;
    int index, len;

    debug(DEBUG_TICKER, "entering function weather_handler()");

    /* fetch data from event table */
    weather_id = db_result_get_int(result, "weatherID");
    impact_id = db_result_get_int(result, "impactID");
    region_id = db_result_get_int(result, "regionID");
    weather_start = db_result_get_string(result, "end");
	 	
    debug(DEBUG_TICKER, "weatherID = %d, impactID = %d", weather_id, impact_id);
    weather = (struct Weather *) weather_type[weather_id];
    impact = &weather->impacts[impact_id];
    duration = impact->duration * WEATHER_TIME_BASE_FACTOR;

    make_timestamp_gm(weather_end, time(NULL) + duration);

    /* calculate the impact deltas and construct update query */
    query = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

    /* create updates for stealing and active wonders */
    active = dstring_new("INSERT INTO Event_weatherEnd SET "
			 "weatherID = %d, impactID = %d, regionID = %d, "
			 "start = '%s', end = '%s'",
			 weather_id, impact_id, region_id,
			 weather_start, weather_end);

    if (impact->effects)
    {
	for (index = 0; index < MAX_EFFECT; ++index)
	{
	    int select = impact->effectsAll ? 0 : index;
	    double delta = impact->effects[select].absolute;

	    if (delta != 0)
	    {
		const char *dbFieldName = effect_type[index]->dbFieldName;

		dstring_append(query, "%s = %s + %f,",
			       dbFieldName, dbFieldName, delta);
		dstring_append(active, ",%s = %f", dbFieldName, delta);

	    }
	}
    }

    /* update the cave */
    len = dstring_len(query) - 1;

    if (dstring_str(query)[len] == ',')
    {
	dstring_truncate(query, len);
	dstring_append(query, " WHERE regionID = %d", region_id);

	debug(DEBUG_SQL, "%s", dstring_str(query));
	db_query_dstring(database, query);
    }

    /* add to active wonders, if necessary */
    if (duration > 0)
    {
	debug(DEBUG_SQL, "%s", dstring_str(active));
	db_query_dstring(database, active);
    }
    debug(DEBUG_TICKER, "weather_handler SQL Query: %s",dstring_str(query));
    debug(DEBUG_TICKER, "weather_handler SQL Active: %s",dstring_str(active));

    debug(DEBUG_TICKER, "leaving function weather_handler()");
}
