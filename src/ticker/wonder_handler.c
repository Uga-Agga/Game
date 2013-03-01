/*
 * wonder_handler.c - process wonder events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>  /* atoi */
#include <string.h>  /* memset */
#include <time.h>  /* time */

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

  for (index = 0; index < num; ++index) {
    int select = all ? 0 : index;
    int delta = entity[select].absolute +
      entity[select].relative * values[index];

    if (entity[select].maxDelta > 0 && entity[select].maxDelta < delta)
        delta = entity[select].maxDelta;

    if (values[index] + delta < 0)
        delta = -values[index];

    if (delta != 0) {
      const char *dbFieldName = object[index]->dbFieldName;
      const char *maxLevel = object[index]->maxLevel;

      dstring_append(query, "%s = ", dbFieldName);

      if (limit && delta > 0) {
        dstring_append(query, "GREATEST(0, LEAST(%s + %d, %s)),",
               dbFieldName, delta, function_to_sql(maxLevel));
      } else {
        dstring_append(query, "GREATEST(0, %s + %d),",
               dbFieldName, delta);
      }

        if (steal > 0) {
          dstring_append(query2, "%s = ", dbFieldName);

          if (maxLevel && delta < 0) {
            dstring_append(query2, "GREATEST(0, LEAST(%s - %d, %s)),",
             dbFieldName, (int) (delta * steal),
             function_to_sql(maxLevel));
          } else {
          dstring_append(query2, "GREATEST(0, %s - %d),",
             dbFieldName, (int) (delta * steal));
          }
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
void wonder_handler (db_t *database, db_result_t *result)
{
  db_result_t *active_result;
  int target_id;
  int wonder_id;
  int impact_id;
  int caster_id;
  int source_id;
  int special_duration_minutes;
  int tribeCaveWonderCaveID;
  const struct Wonder *wonder;
  const struct WonderImpact *impact;
  const char *wonder_start;
  int duration;
  char wonder_end[TIMESTAMP_LEN];
  struct Player caster;
  struct Player target;
  struct Cave cave;
  struct ReportEntity report;
  dstring_t *query;
  dstring_t *query2;
  dstring_t *active;
  array_t *changes;
  int index, len;

  debug(DEBUG_TICKER, "entering function wonder_handler()");

  /* fetch data from event table */
  target_id = db_result_get_int(result, "targetID");
  wonder_id = db_result_get_int(result, "wonderID");
  impact_id = db_result_get_int(result, "impactID");
  caster_id = db_result_get_int(result, "casterID");
  source_id = db_result_get_int(result, "sourceID");
  wonder_start = db_result_get_string(result, "end");
  special_duration_minutes = db_result_get_int(result, "specialdurationminutes");
  tribeCaveWonderCaveID = db_result_get_int(result, "tribeCaveWonderCaveID");

  debug(DEBUG_TICKER, "wonderID = %d, impactID = %d", wonder_id, impact_id);
  wonder = (struct Wonder *) wonder_type[wonder_id];
  impact = &wonder->impacts[impact_id];
  if (special_duration_minutes == 0)
  {
    duration = impact->duration * WONDER_TIME_BASE_FACTOR;
  }
  else
  {
    duration = special_duration_minutes * WONDER_TIME_BASE_FACTOR;
  }
  /* get data of the target cave */
  get_cave_info(database, target_id, &cave);

  try {
    get_player_info(database, caster_id, &caster);
      } catch (SQL_EXCEPTION) {
    /* caster's account has been deleted */
    memset(&caster, 0, sizeof caster);
  } end_try;

  if (cave.player_id == caster_id) {
    target = caster;
  } else if (cave.player_id) {
    get_player_info(database, cave.player_id, &target);
  } else {/* System */
    memset(&target, 0, sizeof target);
  }

  /* check whether this wonder is already affecting the target cave */
  active_result = db_query(database,
      "SELECT activeWonderID, end FROM Event_wonderEnd "
      "WHERE caveID = %d AND wonderID = %d AND impactID = %d",
      target_id, wonder_id, impact_id);

  if (db_result_next_row(active_result)) {
    int active_id = db_result_get_int_at(active_result, 0);
    time_t active_end = db_result_get_gmtime_at(active_result, 1);

    make_timestamp_gm(wonder_end, active_end + duration);
    db_query(database, "UPDATE Event_wonderEnd SET end = '%s' "
       "WHERE activeWonderID = %d", wonder_end, active_id);

    /* create message */
    if (wonder->isTribeCaveWonder == 0) {
      wonder_extend_report(database, &caster, &cave, &target, wonder, impact);
    }

    debug(DEBUG_TICKER, "leaving function wonder_handler()");
    return;
  }

  make_timestamp_gm(wonder_end, time(NULL) + duration);

  /* calculate the impact deltas and construct update query */
  query = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

  /* create updates for stealing and active wonders */
  query2 = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");
  active = dstring_new("INSERT INTO Event_wonderEnd SET "
     "wonderID = %d, impactID = %d, casterID = %d, "
     "caveID = %d, start = '%s', end = '%s', tribeCaveWonderCaveID = %d",
     wonder_id, impact_id, caster_id,
     target_id, wonder_start, wonder_end, tribeCaveWonderCaveID);

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

  if (impact->effects) {
    for (index = 0; index < MAX_EFFECT; ++index) {
      int select = impact->effectsAll ? 0 : index;
      double delta = impact->effects[select].absolute;

      if (delta != 0) {
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
  if (impact->deactivateTearDown) {
    char teardown[TIMESTAMP_LEN];

    make_timestamp(teardown, time(NULL) + duration);
    dstring_append(query, "toreDownTimeout = '%s',", teardown);
  }

  /* update the cave */
  len = dstring_len(query) - 1;

  if (dstring_str(query)[len] == ',') {
    dstring_truncate(query, len);
    dstring_append(query, " WHERE caveID = %d", target_id);

    debug(DEBUG_SQL, "%s", dstring_str(query));
    db_query_dstring(database, query);
  }

  /* insert stolen materials into cave of caster */
  len = dstring_len(query2) - 1;

  if (impact->steal > 0 && dstring_str(query2)[len] == ',') {
    dstring_truncate(query2, len);
    dstring_append(query2, " WHERE caveID = %d", source_id);

    debug(DEBUG_SQL, "%s", dstring_str(query2));
    db_query_dstring(database, query2);
  }

  /* add to active wonders, if necessary */
  if (duration > 0) {
    debug(DEBUG_SQL, "%s", dstring_str(active));
    db_query_dstring(database, active);
  }

  /* create messages */
  if (wonder->isTribeCaveWonder == 0) {
    wonder_report(database, &caster, &cave, &target, impact,
      array_values(changes), array_len(changes));
  }

  debug(DEBUG_TICKER, "leaving function wonder_handler()");
}
