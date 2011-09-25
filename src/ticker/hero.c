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
  db_result_t *result = db_query(database, "SELECT * FROM " DB_TABLE_HERO " WHERE heroID = %d", heroID);

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
  get_effect_list(result, hero->effect);
}


/*
 * Put hero into cave after finished movement.
 */
void put_hero_into_cave (db_t *database, int heroID, int caveID)
{
  db_query(database, "UPDATE " DB_TABLE_HERO " SET caveID = %d, isMoving = 0 "
         "WHERE heroID = %d", caveID, heroID);

  //if (db_affected_rows(database) != 1)
  //  throw(BAD_ARGUMENT_EXCEPTION, "put_hero_into_cave: hero could not be placed in cave.");

  db_query(database, "UPDATE Cave SET hero = %d "
         " WHERE caveID = %d", heroID, caveID);

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

  /* save hero values; throws exception, if that hero is missing */
  get_hero_by_id(database, heroID, &hero);

  db_query(database, "UPDATE " DB_TABLE_HERO " SET caveID = 0 "
         "WHERE heroID = %d", heroID);

  db_query(database, "UPDATE Cave SET hero = 0 "
         "WHERE caveID = %d", hero.caveID);

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
  struct Cave cave;
  struct Player player;

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

  db_query(database, "UPDATE " DB_TABLE_HERO " SET isAlive = %d, healPoints = %d "
         " WHERE heroID = %d AND caveID = %d",
	   HERO_ALIVE, hero.maxHealPoints/2, hero.heroID, hero.caveID);

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

  get_hero_by_id(database, heroID, &hero);

  db_query(database, "UPDATE " DB_TABLE_HERO " SET isAlive = %d, caveID = 0, healPoints = 0, isMoving = 0 "
         " WHERE heroID = %d",
     HERO_DEAD, heroID);

  db_query(database, "DELETE FROM Event_hero WHERE heroID = %d",
     heroID);

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
}
