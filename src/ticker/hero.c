/*
 * heroRitual.c - handle heros
 * Copyright (c) 2011 Georg Pitterle
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "hero.h"     /* hero/hero typedefs */
#include "cave.h"	  /* get_effect_list */
#include "database.h"     /* db_result_get_int etc. */
#include "except.h"       /* exception handling */
#include "logging.h"      /* debug function */
#include "memory.h"       /* dstring et.al. */
#include "ticker.h"       /* DB_TABLE_HERORITUAL */

/*
 * Retrieve heroRitual for the given id.
 */
void get_hero_by_id (db_t *database, int heroID, struct Hero *hero)
{
  dstring_t *ds;
  db_result_t *result;

  if (heroID == 0) {
    hero->heroID      = 0;
    hero->caveID      = 0;
    hero->isAlive     = 0;
    hero->playerID    = 0;
    hero->healPoints  = 0;
    hero->maxHealPoints = 0;
    hero->isMoving    = 0;
    hero->exp         = 0;
    hero->type        = 0;
  }
  else
  {
    ds = dstring_new("SELECT * FROM " DB_TABLE_HERO " WHERE heroID = %d", heroID);
    result = db_query_dstring(database, ds);
    debug(DEBUG_HERO, "get_hero_by_id: %s", dstring_str(ds));

    //  /* Bedingung: Held muss vorhanden sein */
    if (db_result_num_rows(result) != 1)
      throw(SQL_EXCEPTION, "get_hero_by_id: no such heroID");

      db_result_next_row(result);

    hero->heroID      = heroID;
    hero->caveID      = db_result_get_int(result, "caveID");
    hero->isAlive     = db_result_get_int(result, "isAlive");
    hero->playerID    = db_result_get_int(result, "playerID");
    hero->healPoints  = db_result_get_int(result, "healPoints");
    hero->maxHealPoints = db_result_get_int(result, "maxHealPoints");
    hero->isMoving    = db_result_get_int(result, "isMoving");
    hero->exp         = db_result_get_int(result, "exp");
    hero->type        = db_result_get_int(result, "heroTypeID");
    get_effect_list(result, hero->effect);
  }
}


/*
 * Put hero into cave after finished movement.
 */
void put_hero_into_cave (db_t *database, int heroID, int caveID)
{
  dstring_t *ds;

  ds = dstring_new("UPDATE " DB_TABLE_HERO " SET caveID = %d, isMoving = 0 "
         "WHERE heroID = %d", caveID, heroID);
  db_query_dstring(database, ds);

  debug(DEBUG_HERO, "put_hero_into_cave: %s", dstring_str(ds));

  //if (db_affected_rows(database) != 1)
  //  throw(BAD_ARGUMENT_EXCEPTION, "put_hero_into_cave: hero could not be placed in cave.");

  ds = dstring_new("UPDATE Cave SET hero = %d WHERE caveID = %d", heroID, caveID);
  db_query_dstring(database, ds);

  debug(DEBUG_HERO, "put_hero_into_cave: %s", dstring_str(ds));

  /* Bedingung: Höhle muss vorhanden sein */
  //if (db_affected_rows(database) != 1)
  //  throw(SQL_EXCEPTION, "put_hero_into_cave: no such caveID");
}

/*
 * User moves hero. Remove hero from its cave.
 */
void remove_hero_from_cave (db_t *database, int heroID)
{
  struct Hero hero;
  dstring_t *ds;

  /* save hero values; throws exception, if that hero is missing */
  get_hero_by_id(database, heroID, &hero);

  ds = dstring_new("UPDATE " DB_TABLE_HERO " SET caveID = 0 "
         "WHERE heroID = %d", heroID);
  db_query_dstring(database, ds);

  debug(DEBUG_HERO, "remove_hero_from_cave: %s", dstring_str(ds));

  ds = dstring_new("UPDATE Cave SET hero = 0 "
         "WHERE caveID = %d", hero.caveID);
  db_query_dstring(database, ds);
  debug(DEBUG_HERO, "remove_hero_from_cave: %s", dstring_str(ds));

//  /* Bedingung: Höhle muss vorhanden sein */
//  if (db_affected_rows(database) != 1)
//    throw(SQL_EXCEPTION, "remove_hero_from_cave: no such caveID");
}

/*
 * Reincarnation finished. Now set the status of isAlive to true
 */
void reincarnate_hero (db_t *database, int heroID)
{
  struct Hero hero;
  dstring_t *ds;

  /* get hero values; throws exception, if that hero is missing */
  get_hero_by_id(database, heroID, &hero);

  /* Bedingung: muss gerade wiederbelebt werden */
  if (hero.isAlive != HERO_REINCARNATING)
    throw(BAD_ARGUMENT_EXCEPTION, "reincarnate_hero: hero was not reincarnating" );

  /* Bedingung: muss in einer Höhle liegen */
  if (hero.caveID == 0)
    throw(BAD_ARGUMENT_EXCEPTION, "reincarnate_hero: hero was not in a cave");



  /* Bedingung: Held und Höhle müssen existieren */
  if (db_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "initiate_hero: no such heroID or caveID");

  ds = dstring_new("UPDATE " DB_TABLE_HERO " SET isAlive = %d, healPoints = %d "
         " WHERE heroID = %d AND caveID = %d",
     HERO_ALIVE, hero.maxHealPoints/2, hero.heroID, hero.caveID);
  db_query_dstring(database, ds);

  debug(DEBUG_HERO, "reincarnate_hero: %s", dstring_str(ds));

  // apply effects
  apply_hero_effects_to_cave(database, heroID);

  put_hero_into_cave(database, heroID, hero.caveID);
}

/*
 * Hero is dead. Set isAlive to false, remove effects from cave
 */
void kill_hero (db_t *database, int heroID)
{
  struct Hero hero;
  dstring_t *ds;

  get_hero_by_id(database, heroID, &hero);

  ds = dstring_new("UPDATE " DB_TABLE_HERO " SET isAlive = %d, healPoints = 0, isMoving = 0 "
         " WHERE heroID = %d", HERO_DEAD, heroID);
  db_query_dstring(database, ds);

  debug(DEBUG_HERO, "kill_hero: %s", dstring_str(ds));

  ds = dstring_new("DELETE FROM Event_hero WHERE heroID = %d", heroID);
  db_query_dstring(database, ds);

  debug(DEBUG_HERO, "kill_hero: %s", dstring_str(ds));

  remove_hero_effects_from_cave(database, heroID);
  remove_hero_from_cave(database, heroID);
}

/*
 * Hero is alive, now apply the effects.
 */
void apply_hero_effects_to_cave (db_t *database, int heroID)
{
  struct Hero       hero;
  dstring_t             *ds = dstring_new("UPDATE Cave SET ");
  int                   i;

  /* get hero values; throws exception, if that hero is missing */
  get_hero_by_id(database, heroID, &hero);

  /* apply effects only if constructor type */
  if (hero.type != CONSTRUCTOR_ID)
    return;

  /* Bedingung: Held muss lebendig sein */
  if (hero.isAlive != HERO_ALIVE)
    throw(BAD_ARGUMENT_EXCEPTION, "apply_hero_effects_to_cave: hero is not alive");

  for (i = 0; i < MAX_EFFECT; ++i)
    dstring_append(ds, "%s %s = %s + %f",
                  (i == 0 ? "" : ","),
                  effect_type[i]->dbFieldName,
                  effect_type[i]->dbFieldName,
                  hero.effect[i]);

  dstring_append(ds, " WHERE caveID = %d", hero.caveID);
  db_query_dstring(database, ds);

  debug(DEBUG_HERO, "apply_hero_effects_to_cave: %s", dstring_str(ds));
}

/*
 * Hero is dead. Remove the effects (actually same as apply_effects but with a
 * "-" instead of the "+").
 */
void remove_hero_effects_from_cave (db_t *database, int heroID)
{
  struct Hero       hero;
  dstring_t             *ds = dstring_new("UPDATE Cave SET ");
  int                   i;

  /* get hero values; throws exception, if that hero is missing */
  get_hero_by_id(database, heroID, &hero);

  /* remove effects only, if constructor type*/
  if (hero.type != CONSTRUCTOR_ID)
    return;

  /* Bedingung: muss tot sein */
  if (hero.isAlive != HERO_DEAD)
    {
      if (!hero.isMoving)
        throw(BAD_ARGUMENT_EXCEPTION, "remove_hero_effect_from_cave: hero is not alive");
    }

  for (i = 0; i < MAX_EFFECT; ++i)
    dstring_append(ds, "%s %s = %s - %f",
                  (i == 0 ? "" : ","),
                  effect_type[i]->dbFieldName,
                  effect_type[i]->dbFieldName,
                  hero.effect[i]);

  dstring_append(ds, " WHERE caveID = %d", hero.caveID);
  db_query_dstring(database, ds);

  debug(DEBUG_HERO, "remove_hero_effects_from_cave: %s", dstring_str(ds));
}
