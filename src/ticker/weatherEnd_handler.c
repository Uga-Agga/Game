/*
 * wonderEnd_handler.c - process wonder end events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <string.h>	/* memset */

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
void weatherEnd_handler (db_t *database, db_result_t *result)
{
    int region_id;
    int weather_id;
    int impact_id;
    const struct Weather *weather;
    const struct WeatherImpact *impact;
    float effect[MAX_EFFECT];
    dstring_t *query;
    int index, len;

    debug(DEBUG_TICKER, "entering function weatherEnd_handler()");

    /* fetch data from event table */
    region_id   = db_result_get_int(result, "regionID");
    weather_id = db_result_get_int(result, "weatherID");
    impact_id = db_result_get_int(result, "impactID");
    get_effect_list(result, effect);

    debug(DEBUG_TICKER, "regionID = %d, weatherID = %d", region_id, weather_id);
    weather = (struct Weather *) weather_type[weather_id];
    impact = &weather->impacts[impact_id];

    /* prepare update statement */
    query = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

    for (index = 0; index < MAX_EFFECT; ++index)
    {
	double delta = -effect[index];

	if (delta != 0)
	{
	    const char *dbFieldName = effect_type[index]->dbFieldName;

	    dstring_append(query, "%s = %s + %f,",
			   dbFieldName, dbFieldName, delta);
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

    debug(DEBUG_TICKER, "leaving function weatherEnd_handler()");
}
