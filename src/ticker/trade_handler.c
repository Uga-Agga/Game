/*
 * trade_handler.c - process wonder events
 * Copyright (c) 2008  Uga-Agga Team
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
 * Calculate the deltas of the entities according to the impact.
 */
static void build_query (dstring_t *query, dstring_t *query2, dstring_t *active,
			 array_t *changes, const struct Player *player,
			 const struct GameObject *object[], int num,
			 const struct ImpactEffect *entity, int all,
			 const int values[], int limit, float steal)
{
    struct ReportEntity report;
    int index;

    if (entity == NULL) return;

    for (index = 0; index < num; ++index)
    {
	int select = all ? 0 : index;
	int delta = entity[select].absolute +
		    entity[select].relative * values[index];

	if (entity[select].maxDelta > 0 && entity[select].maxDelta < delta)
	    delta = entity[select].maxDelta;

	if (values[index] + delta < 0)
	    delta = -values[index];

	if (delta != 0)
	{
	    const char *dbFieldName = object[index]->dbFieldName;
	    const char *maxLevel = object[index]->maxLevel;

	    dstring_append(query, "%s = ", dbFieldName);

	    if (limit && delta > 0)
		dstring_append(query, "GREATEST(0, LEAST(%s + %d, %s)),",
			       dbFieldName, delta, function_to_sql(maxLevel));
	    else
		dstring_append(query, "GREATEST(0, %s + %d),",
			       dbFieldName, delta);

	    if (steal > 0)
	    {
		dstring_append(query2, "%s = ", dbFieldName);

		if (maxLevel && delta < 0)
		    dstring_append(query2, "GREATEST(0, LEAST(%s - %d, %s)),",
				   dbFieldName, (int) (delta * steal),
				   function_to_sql(maxLevel));
		else
		    dstring_append(query2, "GREATEST(0, %s - %d),",
				   dbFieldName, (int) (delta * steal));
	    }

	    dstring_append(active, ",%s = %d", dbFieldName, delta);

	    report.object = object[index];
	    report.value = delta;
	    array_add(changes, &report);
	}
    }
}

/*
 * Function is called to instantiate a wonder's impact.
 */
void trade_handler (db_t *database, db_result_t *result)
{
//    db_result_t *active_result;
    int target_id;
    int trade_id;
    int impact_id;
    int special_duration_minutes; 

    const struct Wonder *wonder;
    const struct WonderImpact *impact;
    const char *wonder_start;
    int duration;
    char wonder_end[TIMESTAMP_LEN];
    struct Cave cave;
    struct Player target;
    struct ReportEntity report;
    dstring_t *query;
    dstring_t *query2;
    dstring_t *active;
    array_t *changes;
    int index, len;

    debug(DEBUG_TICKER, "entering function trade_handler()");

    /* fetch data from event table */
    target_id = db_result_get_int(result, "targetID");
    trade_id = db_result_get_int(result, "tradeID");
    impact_id = db_result_get_int(result, "impactID");
    wonder_start = db_result_get_string(result, "end");
    special_duration_minutes = db_result_get_int(result, "specialdurationminutes");
	 	
    debug(DEBUG_TICKER, "tradeID .= %d, impactID = %d", trade_id, impact_id);
    wonder = (struct Wonder *) trade_type[trade_id];
    impact = &wonder->impacts[impact_id];
    if (special_duration_minutes == 0) {
    debug(DEBUG_TICKER, "aa stepper %d",WONDER_TIME_BASE_FACTOR);
      duration = impact->duration * WONDER_TIME_BASE_FACTOR;
    debug(DEBUG_TICKER, "aa stepper");
    } 
    else 
    {
    debug(DEBUG_TICKER, "stepper");
      duration = special_duration_minutes * WONDER_TIME_BASE_FACTOR;				
    debug(DEBUG_TICKER, "stepper");
    }				
    /* get data of the target cave */
    get_cave_info(database, target_id, &cave);

    try {
	get_player_info(database, cave.player_id, &target);
    } catch (SQL_EXCEPTION) {
	/* caster's account has been deleted */
	memset(&target, 0, sizeof target);
    } end_try;


    make_timestamp_gm(wonder_end, time(NULL) + duration);

    /* calculate the impact deltas and construct update query */
    query = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

    /* create updates for stealing and active wonders */
    query2 = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");
    active = dstring_new("INSERT INTO Event_tradeEnd SET "
			 "tradeID = %d, impactID = %d, "
			 "caveID = %d, start = '%s', end = '%s'",
			 trade_id, impact_id, 
			 target_id, wonder_start, wonder_end);

    changes = array_new(sizeof (struct ReportEntity));
    memory_pool_add(changes, (void (*)(void *)) array_free);

    build_query(query, query2, active, changes, &target,
		resource_type, MAX_RESOURCE,
		impact->resources, impact->resourcesAll,
		cave.resource, 1, impact->steal);

    build_query(query, query2, active, changes, &target,
		building_type, MAX_BUILDING,
		impact->buildings, impact->buildingsAll,
		cave.building, 0, impact->steal);

    build_query(query, query2, active, changes, &target,
		science_type, MAX_SCIENCE,
		impact->sciences, impact->sciencesAll,
		cave.science, 0, impact->steal);

    build_query(query, query2, active, changes, &target,
		defense_system_type, MAX_DEFENSESYSTEM,
		impact->defenseSystems, impact->defenseSystemsAll,
		cave.defense_system, 0, impact->steal);

    build_query(query, query2, active, changes, &target,
		unit_type, MAX_UNIT,
		impact->units, impact->unitsAll,
		cave.unit, 0, impact->steal);

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

		report.object = effect_type[index];
		report.value = delta;
		array_add(changes, &report);
	    }
	}
    }

    /* set tear down timeout, if necessary */
    if (impact->deactivateTearDown)
    {
	char teardown[TIMESTAMP_LEN];

	make_timestamp(teardown, time(NULL) + duration);
	dstring_append(query, "toreDownTimeout = '%s',", teardown);
    }

    /* update the cave */
    len = dstring_len(query) - 1;

    if (dstring_str(query)[len] == ',')
    {
	dstring_truncate(query, len);
	dstring_append(query, " WHERE caveID = %d", target_id);

	debug(DEBUG_SQL, "%s", dstring_str(query));
	db_query_dstring(database, query);
    }

    /* add to active wonders, if necessary */
    if (duration > 0)
    {
	debug(DEBUG_SQL, "%s", dstring_str(active));
	db_query_dstring(database, active);
    }

    /* create messages */
   merchant_report(database, &target, &cave, &target, impact,
		  array_values(changes), array_len(changes));
    

    debug(DEBUG_TICKER, "leaving function trade_handler()");
}
