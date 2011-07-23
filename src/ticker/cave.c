/*
 * cave.c - cave and player information
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>
#include <string.h>

#include "cave.h"
#include "database.h"
#include "except.h"
#include "logging.h"
#include "memory.h"
#include "ticker.h"
#include "ugatime.h"

/*
 * Retrieve the game object list from the result set.
 */
static void get_object_list (db_result_t *result, int list[],
			     const struct GameObject *object[], int num)
{
  int type;

  memset(list, 0, num * sizeof (int));

  for (type = 0; type < num; ++type)
    list[type] = db_result_get_int(result, object[type]->dbFieldName);
}

/*
 * Retrieve the resource list from the result set.
 */
void get_resource_list (db_result_t *result, int resource[])
{
  get_object_list(result, resource, resource_type, MAX_RESOURCE);
}

/*
 * Retrieve the building list from the result set.
 */
void get_building_list (db_result_t *result, int building[])
{
  get_object_list(result, building, building_type, MAX_BUILDING);
}

/*
 * Retrieve the science list from the result set.
 */
void get_science_list (db_result_t *result, int science[])
{
  get_object_list(result, science, science_type, MAX_SCIENCE);
}

/*
 * Retrieve the defense system list from the result set.
 */
void get_defense_system_list (db_result_t *result, int defense_system[])
{
  get_object_list(result, defense_system, defense_system_type,
		  MAX_DEFENSESYSTEM);
}

/*
 * Retrieve the unit list from the result set.
 */
void get_unit_list (db_result_t *result, int unit[])
{
  get_object_list(result, unit, unit_type, MAX_UNIT);
}

/*
 * Retrieve the effect list from the result set.
 */
void get_effect_list (db_result_t *result, float effect[])
{
  int type;

  memset(effect, 0, MAX_EFFECT * sizeof (float));

  for (type = 0; type < MAX_EFFECT; ++type)
    effect[type] = db_result_get_double(result, effect_type[type]->dbFieldName);
}

/*
 * Retrieve cave table information for the given cave id.
 */
void get_cave_info (db_t *database, int cave_id, struct Cave *cave)
{
  db_result_t *result = db_query(database,
	"SELECT * FROM " DB_TABLE_CAVE " WHERE caveID = %d", cave_id);

  if (!db_result_next_row(result))
    throwf(SQL_EXCEPTION, "get_cave_info: cave %d not found", cave_id);

  cave->result = result;
  cave->cave_id = cave_id;
  cave->xpos = db_result_get_int(result, "xCoord");
  cave->ypos = db_result_get_int(result, "yCoord");
  cave->name = db_result_get_string(result, "name");
  cave->player_id = db_result_get_int(result, "playerID");
  cave->terrain = db_result_get_int(result, "terrain");
  cave->takeoverable = db_result_get_int(result, "takeoverable");
  cave->artefacts = db_result_get_int(result, "artefacts");
  cave->monster_id = db_result_get_int(result, "monsterID");
  cave->secure = db_result_get_int(result, "secureCave");
  cave->protect_end = db_result_get_time(result, "protection_end");
  get_resource_list(result, cave->resource);
  get_building_list(result, cave->building);
  get_science_list(result, cave->science);
  get_defense_system_list(result, cave->defense_system);
  get_unit_list(result, cave->unit);
  get_effect_list(result, cave->effect);
}

/*
 * Retrieve the owner (player id) of the given gave.
 */
int get_cave_owner (db_t *database, int cave_id)
{
  db_result_t *result = db_query(database,
	"SELECT playerID FROM " DB_TABLE_CAVE " WHERE caveID = %d", cave_id);

  if (!db_result_next_row(result))
    throwf(SQL_EXCEPTION, "get_cave_owner: cave %d not found", cave_id);

  return db_result_get_int_at(result, 0);
}

/*
 * Get locale id of specified locale name.
 */
static int get_locale_id (const char *locale)
{
  int index;

  if (locale)
    for (index = 0; index < MAX_LOCALE; ++index)
      if (strcmp(locale, language[index].locale) == 0)
	return index;

  return 0;	/* unknown locale */
}

/*
 * Retrieve player table information for the given player id.
 */
void get_player_info (db_t *database, int player_id, struct Player *player)
{
  db_result_t *result = db_query(database,
	"SELECT * FROM " DB_TABLE_PLAYER " WHERE playerID = %d", player_id);

  if (!db_result_next_row(result))
    throwf(SQL_EXCEPTION, "get_player_info: player %d not found", player_id);

  player->player_id = player_id;
  player->name = db_result_get_string(result, "name");
  player->tribe = db_result_get_string(result, "tribe");
  player->max_caves = db_result_get_int(result, "takeover_max_caves");
  player->locale = db_result_get_string(result, "language");
  player->locale_id = get_locale_id(player->locale);
  get_science_list(result, player->science);
}

/*
 * Retrieve relation table information for the given tribe and tribe_target.
 */
int get_relation_info (db_t *database, const char *tribe,
		       const char *tribe_target, struct Relation *relation)
{
  db_result_t *result = NULL;

  if (tribe && tribe_target)
  {
    debug(DEBUG_BATTLE, "get relation for tribes %s %s", tribe, tribe_target);
    result = db_query(database,
	"SELECT * FROM " DB_TABLE_RELATION
	" WHERE tribe = '%s' AND tribe_target = '%s'", tribe, tribe_target);
  }

  if (!result || !db_result_next_row(result))
  {
    debug(DEBUG_BATTLE, "filling dummy relation");
    relation->relation_id = 0;
    relation->tribe = tribe;
    relation->tribe_target = tribe_target;
    relation->relationType = RELATION_TYPE_NONE;

    /* FIXME these values should be read from relation types */
    relation->attackerMultiplicator = 0.5;
    relation->defenderMultiplicator = 1.0;
    relation->attackerReceivesFame = 0;
    relation->defenderReceivesFame = 0;

    return 0;	/* no relation entry */
  }

  relation->relation_id = db_result_get_int(result, "relationID");
  relation->tribe = tribe;
  relation->tribe_target = tribe_target;
  relation->relationType = db_result_get_int(result, "relationType");
  relation->defenderMultiplicator =
    db_result_get_double(result, "defenderMultiplicator");
  relation->attackerMultiplicator =
    db_result_get_double(result, "attackerMultiplicator");
  relation->defenderReceivesFame =
    db_result_get_int(result, "defenderReceivesFame");
  relation->attackerReceivesFame =
    db_result_get_int(result, "attackerReceivesFame");

  return 1;
}



int get_tribe_at_war(db_t *database, const char *tribe)
{
  db_result_t *result = NULL;

  if (tribe)
  {
    debug(DEBUG_BATTLE, "get tribe at war for %s", tribe);
    result = db_query(database,
        "SELECT * FROM " DB_TABLE_RELATION
        " WHERE tribe = '%s' AND relationType = '%i'", tribe, RELATION_TYPE_WAR);
  }

  return (result && db_result_next_row(result));

}


/*
 * Retrieve the number of caves owned by player_id.
 */
int get_number_of_caves (db_t *database, int player_id)
{
  db_result_t *result = db_query(database,
	"SELECT COUNT(caveID) AS n FROM " DB_TABLE_CAVE
	" WHERE playerID = %d", player_id);

  db_result_next_row(result);

  return db_result_get_int_at(result, 0);
}

/*
 * Return religion of the cave's owner.
 */
int get_religion (const struct Cave *cave)
{
#if defined(ID_SCIENCE_UGA) && defined(ID_SCIENCE_AGGA)
  return cave->player_id == PLAYER_SYSTEM   ? RELIGION_NONE :
	 //cave->science[ID_SCIENCE_ENZIO] > 0 ? RELIGION_ENZIO :
	 cave->science[ID_SCIENCE_AGGA] > 0 ? RELIGION_AGGA :
	 cave->science[ID_SCIENCE_UGA]  > 0 ? RELIGION_UGA  : RELIGION_NONE;
#else
  return RELIGION_NONE;
#endif
}

/*
 * Return whether the given cave is protected or not.
 */
int cave_is_protected (const struct Cave *cave)
{
  return cave->protect_end > time(NULL);
}

/*
 * Retrieve monster table information for the given monster id.
 */
void get_monster_info (db_t *database, int monster_id, struct Monster *monster)
{
  db_result_t *result = db_query(database,
	"SELECT * FROM Monster WHERE monsterID = %d", monster_id);

  if (!db_result_next_row(result))
    throwf(SQL_EXCEPTION, "get_monster_info: monster %d not found", monster_id);

  monster->monster_id = monster_id;
  monster->name = db_result_get_string(result, "name");
  monster->attack = db_result_get_int(result, "angriff");
  monster->defense = db_result_get_int(result, "verteidigung");
  monster->mental = db_result_get_int(result, "mental");
  monster->strength = db_result_get_int(result, "koerperkraft");
  monster->exp_value = db_result_get_int(result, "erfahrung");
  monster->attributes = db_result_get_string(result, "eigenschaft");
}
