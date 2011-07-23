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
 * Calculate the deltas of the entities according to the impact.
 */
static void build_query (dstring_t *query, array_t *changes,
			 const struct Player *player,
			 const struct GameObject *object[], int num,
			 const int values[], const int deltas[])
{
    struct ReportEntity report;
    int index;

    for (index = 0; index < num; ++index)
    {
	int delta = -deltas[index];

	if (values[index] + delta < 0)
	    delta = -values[index];

	if (delta != 0)
	{
	    const char *dbFieldName = object[index]->dbFieldName;
	    const char *maxLevel = object[index]->maxLevel;

	    dstring_append(query, "%s = ", dbFieldName);

	    if (maxLevel && delta > 0)
		dstring_append(query, "GREATEST(0, LEAST(%s + %d, %s)),",
			       dbFieldName, delta, function_to_sql(maxLevel));
	    else
		dstring_append(query, "GREATEST(0, %s + %d),",
			       dbFieldName, delta);

	    report.object = object[index];
	    report.value = delta;
	    array_add(changes, &report);
	}
    }
}

/*
 * Function is called to instantiate a wonder's impact.
 */
void wonderEnd_handler (db_t *database, db_result_t *result)
{
    int cave_id;
    int wonder_id;
    int impact_id;
    int caster_id;
    const struct Wonder *wonder;
    const struct WonderImpact *impact;
    int resource[MAX_RESOURCE];
    int building[MAX_BUILDING];
    int science[MAX_SCIENCE];
    int defense_system[MAX_DEFENSESYSTEM];
    int unit[MAX_UNIT];
    float effect[MAX_EFFECT];
    struct Player caster;
    struct Player target;
    struct Cave cave;
    struct ReportEntity report;
    dstring_t *query;
    array_t *changes;
    int index, len;

    debug(DEBUG_TICKER, "entering function wonderEnd_handler()");

    /* fetch data from event table */
    cave_id   = db_result_get_int(result, "caveID");
    wonder_id = db_result_get_int(result, "wonderID");
    impact_id = db_result_get_int(result, "impactID");
    caster_id = db_result_get_int(result, "casterID");
    get_resource_list(result, resource);
    get_building_list(result, building);
    get_science_list(result, science);
    get_defense_system_list(result, defense_system);
    get_unit_list(result, unit);
    get_effect_list(result, effect);

    debug(DEBUG_TICKER, "caveID = %d, wonderID = %d", cave_id, wonder_id);
    wonder = (struct Wonder *) wonder_type[wonder_id];
    impact = &wonder->impacts[impact_id];

    /* get data of the target cave */
    get_cave_info(database, cave_id, &cave);

    try {
	get_player_info(database, caster_id, &caster);
    } catch (SQL_EXCEPTION) {
	/* caster's account has been deleted */
	memset(&caster, 0, sizeof caster);
    } end_try;

    if (cave.player_id == caster_id)
	target = caster;
    else if (cave.player_id)
	get_player_info(database, cave.player_id, &target);
    else /* System */
	memset(&target, 0, sizeof target);

    /* prepare update statement */
    query = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

    changes = array_new(sizeof (struct ReportEntity));
    memory_pool_add(changes, (void (*)(void *)) array_free);

    build_query(query, changes, &target, resource_type, MAX_RESOURCE,
		cave.resource, resource);
    build_query(query, changes, &target, building_type, MAX_BUILDING,
		cave.building, building);
    build_query(query, changes, &target, science_type, MAX_SCIENCE,
		cave.science, science);
    build_query(query, changes, &target, defense_system_type, MAX_DEFENSESYSTEM,
		cave.defense_system, defense_system);
    build_query(query, changes, &target, unit_type, MAX_UNIT,
		cave.unit, unit);

    for (index = 0; index < MAX_EFFECT; ++index)
    {
	double delta = -effect[index];

	if (delta != 0)
	{
	    const char *dbFieldName = effect_type[index]->dbFieldName;

	    dstring_append(query, "%s = %s + %f,",
			   dbFieldName, dbFieldName, delta);

	    report.object = effect_type[index];
	    report.value = delta;
	    array_add(changes, &report);
	}
    }

    /* update the cave */
    len = dstring_len(query) - 1;

    if (dstring_str(query)[len] == ',')
    {
	dstring_truncate(query, len);
	dstring_append(query, " WHERE caveID = %d", cave_id);

	debug(DEBUG_SQL, "%s", dstring_str(query));
	db_query_dstring(database, query);
    }

    /* create messages */
    wonder_end_report(database, &caster, &cave, &target, impact,
		      array_values(changes), array_len(changes));

    debug(DEBUG_TICKER, "leaving function wonderEnd_handler()");
}
