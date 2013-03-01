/*
 * movement_handler.c - process movement events
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011-2013 Georg Pitterle <georg.pitterle@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>
#include <string.h>
#include <time.h>

#include "artefact.h"
#include "calc_battle.h"
#include "cave.h"
#include "database.h"
#include "event_handler.h"
#include "except.h"
#include "function.h"
#include "logging.h"
#include "memory.h"
#include "message.h"
#include "ticker.h"
#include "ugatime.h"
#include "game_rules.h"
#include "hero.h"

/* movement constants */
#define ROHSTOFFE_BRINGEN  1
#define VERSCHIEBEN    2
#define ANGREIFEN    3
#define SPIONAGE    4
#define RUECKKEHR    5
#define TAKEOVER    6


/* resource constants */
/* save resources at delivery for takeover (x of 100) */
#define TAKEOVER_RESOURCE_SAVE_PERCENTAGE  75

/* FARMEN = KEINE RESSIS */
/* farming constants */
#define NO_FARMING    0
#define FARMING      1

/* Mit diesen Flag kann man das Resi klauen nur während des Krieges erlauben*/
/* 0 bedeutet es geht immer*/
#define STEALOUTSIDEWAR    1

#define FAME_MAX_OVERPOWER_FACTOR 4

#define drand()    (rand() / (RAND_MAX+1.0))

static int isTakeoverableCave(db_t *database, int caveID) {
  db_result_t *result;

  result = db_query(database, "SELECT * FROM Cave WHERE playerID=0 AND caveID = %d AND takeoverable=1;", caveID);
  if (db_result_next_row(result))
    return 1;

  return 0;
}

//return 0 falls nicht
static int isMovingAllowed(db_t *database,
        struct Player *sender,
        struct Player *reciever,
        struct Relation *attToDef) {
  //einfache Fälle zuerst
  //beide spieler im selben stamm
  if (sender->tribe_id == reciever->tribe_id)
    return 1;

  //wenn beide im vorkrieg sind dann müsste das in der relation ja schon stehen
  if (attToDef->relationType == RELATION_TYPE_PRE_WAR)
    return 1;

  //wenn beide im krieg sind dann müsste das in der relation ja schon stehen
  if (attToDef->relationType == RELATION_TYPE_WAR)
    return 1;

  //Im Kriegsbuendniss ist es auch möglich
  if (attToDef->relationType == RELATION_TYPE_WAR_TREATMENT)
    return 1;

  if (reciever->player_id==0)
   return 1;

  //nun noch das schwierigere
  //ist einer von beiden im krieg
  db_result_t *result;
  result = db_query(database, "SELECT * FROM Relation WHERE relationType = %d AND tribeID = %d", RELATION_TYPE_PRE_WAR, sender->tribe_id);
  if(db_result_next_row(result))
    return 0;

  result = db_query(database, "SELECT * FROM Relation WHERE relationType = %d AND tribeID = %d", RELATION_TYPE_PRE_WAR, reciever->tribe_id);
  if(db_result_next_row(result))
    return 0;

  result = db_query(database, "SELECT * FROM Relation WHERE relationType = %d AND tribeID = %d", RELATION_TYPE_WAR, sender->tribe_id);
  if(db_result_next_row(result))
    return 0;

  result = db_query(database, "SELECT * FROM Relation WHERE relationType = %d AND tribeID = %d", RELATION_TYPE_WAR, reciever->tribe_id);
  if(db_result_next_row(result))
    return 0;

  return 1;
}

static int check_farming (db_t *database,
        int artefacts,
        struct Player *attacker,
        struct Player *defender,
        struct Relation *attToDef) {
  if(!STEALOUTSIDEWAR)
    return 0;

  db_result_t *result;

  /*
   * Wann ist es kein Farmen?
   * sie haben eine Beziehung || es gab ein artefakt
   * zu holen || verteidiger hat keinen stamm
   */
  if ( (attToDef->relationType == RELATION_TYPE_WAR)
        || (attToDef->relationType == RELATION_TYPE_PRE_WAR)
        || (defender->tribe_id == 0)
        || (strcmp(defender->tribe,"multi")==0)
        || (defender->tribe_id == attacker->tribe_id)
        || (defender->tribe_id == 0)
        || (artefacts > 0)
        || (defender->player_id) == PLAYER_SYSTEM) {
    return NO_FARMING;
  }

  /* Sind es Missionierungsgegner? */
  result = db_query(database, "SELECT c.caveID FROM Cave_takeover c ,Cave_takeover k WHERE c.caveID = k.caveID AND c.playerID = %d AND k.playerID = %d AND k.status > 0 AND c.status > 0", defender->player_id, attacker->player_id);
  return db_result_next_row(result) ? NO_FARMING : FARMING;
}

/*
 * Calculate an effect factor from the value of a database field
 */
static float effect_factor (float factor) {
  return factor < 0 ? 1 / (1 - factor) : 1 + factor;
}

/*
 * Initalize an Army structure for an army of the given cave.
 * The unit, resource and defense_system vectors are copied directly
 * into the Army structure (note: only defense_system may be NULL).
 */
static void army_setup (db_t *database,
        Army *army,
        const struct Cave *cave,
        const float religion_bonus[],
        const int takeover_multiplier,
        const int unit[],
        const int resource[],
        const int defense_system[],
        int heroID) {
  int type;
  struct Hero hero;
  int armySize, isDef = 0;

  if (defense_system) {
    isDef = 1;
  }

  if (heroID > 0) {
    get_hero_by_id(database, heroID, &hero);
  }

  /* cave and religion bonus */
  army->owner_caveID = cave->cave_id;
  army->religion = get_religion(cave);
  army->religion_bonus = religion_bonus[army->religion];

  debug(DEBUG_BATTLE, "religion: %d bonus: %g",
  army->religion, army->religion_bonus);

  armySize = 0;
  for (type = 0; type < MAX_UNIT; ++type) {
    army->units[type].amount_before = unit[type];
    armySize += unit[type]*((struct BattleUnit *)unit_type[type])->hitPoints;
  }

  for (type = 0; type < MAX_RESOURCE; ++type)
    army->resourcesBefore[type] = resource[type];

  if (defense_system)
    for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
      army->defenseSystems[type].amount_before = defense_system[type];


  /* apply hero effects only if
  * - hero is alive
  * - armySize >= hero experience
  */
  army->heroFights = heroID > 0 && hero.exp >= armySize && hero.isAlive == 1;

  /* fill effects */
  /* XXX should this be effect_factor(... + takeover_multiplier)? */
  if (army->heroFights) {
    army->effect_rangeattack_factor = effect_factor(cave->effect[14]+hero.effect[14]) + takeover_multiplier;
    army->effect_arealattack_factor = effect_factor(cave->effect[15]+hero.effect[15]) + takeover_multiplier;
    army->effect_attackrate_factor  = effect_factor(cave->effect[16]+hero.effect[16]) + takeover_multiplier;
    army->effect_defenserate_factor = effect_factor(cave->effect[17]+hero.effect[17]) + takeover_multiplier;
    army->effect_size_factor        = effect_factor(cave->effect[18]+hero.effect[18]) + takeover_multiplier;
    army->effect_ranged_damage_resistance_factor = effect_factor(cave->effect[19]+hero.effect[19]) + takeover_multiplier;
  } else {
    army->effect_rangeattack_factor = effect_factor(cave->effect[14]) + takeover_multiplier;
    army->effect_arealattack_factor = effect_factor(cave->effect[15]) + takeover_multiplier;
    army->effect_attackrate_factor  = effect_factor(cave->effect[16]) + takeover_multiplier;
    army->effect_defenserate_factor = effect_factor(cave->effect[17]) + takeover_multiplier;
    army->effect_size_factor        = effect_factor(cave->effect[18]) + takeover_multiplier;
    army->effect_ranged_damage_resistance_factor = effect_factor(cave->effect[19]) + takeover_multiplier;
  }
}

static void increaseFarming(db_t *database,
        int player_id) {
  if (player_id != 0) {
    debug(DEBUG_BATTLE, "increaseFarming for %d", player_id);
    db_query(database, "UPDATE " DB_TABLE_PLAYER " SET fame = fame + 1 WHERE playerID = %d", player_id);
  }
}

static void bodycount_update(db_t *database,
        int player_id,
        int count) {
  if (player_id != 0) {
    debug(DEBUG_BATTLE, "bodycount: %d for %d", count, player_id);
    db_query(database, "UPDATE " DB_TABLE_PLAYER " SET body_count = body_count + %d WHERE playerID = %d", count, player_id);
  }
}

static int bodycount_calculate(const Battle *battle, int schalter) {
  int count = 0;
  int i = 0;

  if (schalter != FLAG_ATTACKER) {
    for (i = 0; i < MAX_UNIT; ++i) {
      const struct Army_unit *unit = &battle->attackers[0].units[i];
      count += unit->amount_before - unit->amount_after;
    }
  } else {
    for (i = 0; i < MAX_UNIT; ++i) {
      const struct Army_unit *unit = &battle->defenders[0].units[i];
      count += unit->amount_before - unit->amount_after;
    }
  }

  return count;
}

static int bodycount_va_calculate(const Battle *battle) {
  int count = 0;
  int i = 0;

  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    const struct Army_unit *defense = &battle->defenders[0].defenseSystems[i];
    count += defense->amount_before - defense->amount_after;
  }

  return count;
}

static void war_points_update(db_t *database,
        const struct Player* attacker,
        const struct Player* defender,
        int att_count,
        int def_count) {
    debug(DEBUG_BATTLE, "warpoints: %d for [%s]%s and %d for [%s]%s", att_count, attacker->tribe, attacker->name, def_count, defender->tribe, attacker->name);
    db_query(database, "UPDATE " DB_TABLE_RELATION " SET fame = fame + %d WHERE tribeID = %d AND tribeID_target = %d", att_count, attacker->tribe_id, defender->tribe_id);
    db_query(database, "UPDATE " DB_TABLE_RELATION " SET fame = fame + %d WHERE tribeID = %d AND tribeID_target = %d", def_count, defender->tribe_id, attacker->tribe_id);
    db_query(database, "UPDATE " DB_TABLE_TRIBE " SET warpoints_pos = warpoints_pos + %d, warpoints_neg =  warpoints_neg + %d WHERE tribeID = %d", att_count, def_count, attacker->tribe_id);
    db_query(database, "UPDATE " DB_TABLE_TRIBE " SET warpoints_pos = warpoints_pos + %d, warpoints_neg =  warpoints_neg + %d WHERE tribeID = %d", def_count, att_count, defender->tribe_id);
    db_query(database, "UPDATE " DB_TABLE_PLAYER " SET warpoints_pos = warpoints_pos + %d, warpoints_neg =  warpoints_neg + %d WHERE playerID = %d", att_count, def_count, attacker->player_id);
    db_query(database, "UPDATE " DB_TABLE_PLAYER " SET warpoints_pos = warpoints_pos + %d, warpoints_neg =  warpoints_neg + %d WHERE playerID = %d", def_count, att_count, defender->player_id);
}

static void war_points_update_verschieben(db_t *database,
        const struct Player* attacker,
        const struct Player* defender,
        int count){
    debug(DEBUG_BATTLE, "warpoints: %d for [%s]%s against [%s]%s", count, attacker->tribe, attacker->name, defender->tribe, attacker->name);
    db_query(database, "UPDATE Relation SET fame = fame + %d WHERE tribeID = %d AND tribeID_target = %d", count, attacker->tribe_id, defender->tribe_id);
//wegen Bugusing von cc und hoelle nehmen wir das hier erstmal raus
//    db_query(database, "UPDATE Tribe SET warpoints_pos = warpoints_pos - %d WHERE tag like '%s'", count, defender->tribe);
//    db_query(database, "UPDATE Tribe SET warpoints_neg = warpoints_neg - %d WHERE tag like '%s'", count, attacker->tribe);
//    db_query(database, "UPDATE Player SET warpoints_neg = warpoints_neg - %d WHERE playerID = %d", count, attacker->player_id);
//    db_query(database, "UPDATE Player SET warpoints_pos = warpoints_pos - %d WHERE playerID = %d", count, defender->player_id);
}


static int war_points_calculate(const Battle *battle, int schalter){
  int count = 0;
  int i = 0;

  if(schalter == FLAG_ATTACKER) {
    for (i = 0; i < MAX_UNIT; ++i) {
      const struct Army_unit *unit = &battle->defenders[0].units[i];
      count += (unit->amount_before - unit->amount_after) * ((struct Unit *)unit_type[i])->warpoints;
    }
    for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
      const struct Army_unit *defense = &battle->defenders[0].defenseSystems[i];
      count += (defense->amount_before - defense->amount_after) *((struct DefenseSystem *)defense_system_type[i])->warpoints;
    }
  }else{
    for (i = 0; i < MAX_UNIT; ++i) {
      const struct Army_unit *unit = &battle->attackers[0].units[i];
      count += (unit->amount_before - unit->amount_after) * ((struct Unit *)unit_type[i])->warpoints;
    }
  }

  return count;
}

static int get_takeover_multiplier (const struct Cave *cave) {
  #ifdef TAKEOVER_MULTIPLIER_BUILDING
    return 1 + cave->building[TAKEOVER_MULTIPLIER_BUILDING];
  #else
    return 1;
  #endif
}

static void prepare_battle(db_t *database,
        Battle          *battle,
        struct Player   *attacker,
        struct Player   *defender,
        struct Cave     *cave_attacker,
        struct Cave     *cave_defender,
        const float     *battle_bonus,
        int             takeover_multiplier,
        int             *units,
        int             *resources,
        int             *attacker_artefact_id,
        int             *defender_artefact_id,
        int             heroID,
        struct Relation *relation_from_attacker,
        struct Relation *relation_from_defender) {
  /* initialize defender army */
  army_setup(database, &battle->defenders[0], cave_defender, battle_bonus, takeover_multiplier, cave_defender->unit, cave_defender->resource, cave_defender->defense_system, cave_defender->heroID);

  /* initialize attacker army */
  army_setup(database, &battle->attackers[0], cave_attacker, battle_bonus, 0, units, resources, NULL, heroID);

  /* artefacts */
  debug(DEBUG_BATTLE, "artefacts in target cave: %d", cave_defender->artefacts);

  if (cave_defender->artefacts > 0) {
    db_result_t *result = db_query(database, "SELECT artefactID FROM " DB_TABLE_ARTEFACT " WHERE caveID = %d LIMIT 0,1", cave_defender->cave_id);

    if (!db_result_next_row(result))
      throw(SQL_EXCEPTION, "prepare_battle: no artefact in cave");

    /* warum nur das erste Artefakt?? */
    *defender_artefact_id = db_result_get_int_at(result, 0);
  }

  debug(DEBUG_BATTLE, "defender artefact: %d", *defender_artefact_id);
  debug(DEBUG_BATTLE, "attacker artefact: %d", *attacker_artefact_id);

  /* get the relation boni */
  battle->attackers[0].relationMultiplicator = (defender->player_id == 0) ? 1.0 : relation_from_attacker->attackerMultiplicator;
  battle->defenders[0].relationMultiplicator = relation_from_defender->defenderMultiplicator;

  /*hero */
  battle->attackers_hero_died = 0;
  battle->defenders_hero_died = 0;
}

static void after_battle_defender_update(db_t *database,
        int             player_id,
        const Battle    *battle,
        int             cave_id,
        struct Relation *relation) {
  dstring_t *ds;
  int       update = 0;
  int       i;

  /* construct defender update */
  ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

  /* which units need an update */
  debug(DEBUG_BATTLE, "preparing units update");
  for (i = 0; i < MAX_UNIT; ++i) {
    const struct Army_unit *unit = &battle->defenders[0].units[i];

    if (unit->amount_before != unit->amount_after) {
      dstring_append(ds, "%s%s = %d", update ? "," : "", unit_type[i]->dbFieldName, unit->amount_after);
      update = 1;
    }
  }

  /* which defense systems need an update */
  debug(DEBUG_BATTLE, "preparing defensesystems update");
  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    const struct Army_unit *defense_system = &battle->defenders[0].defenseSystems[i];

//  if ((relation->relationType == RELATION_TYPE_WAR) || (((struct DefenseSystem *)defense_system_type[i])->warpoints == 0 )) {
      if (defense_system->amount_before != defense_system->amount_after) {
        dstring_append(ds, "%s%s = %d", update ? "," : "", defense_system_type[i]->dbFieldName, defense_system->amount_after);
        update = 1;
      }
//  }
  }

  /* which resources need an update */
  debug(DEBUG_BATTLE, "preparing resources update");
  for (i = 0; i < MAX_RESOURCE; ++i) {
    if (battle->defenders[0].resourcesBefore[i] != battle->defenders[0].resourcesAfter[i]) {
      dstring_append(ds, "%s%s = LEAST(%d, %s)", update ? "," : "", resource_type[i]->dbFieldName, battle->defenders[0].resourcesAfter[i], function_to_sql(resource_type[i]->maxLevel));
      update = 1;
    }
  }

  dstring_append(ds, " WHERE caveID = %d", cave_id);

  if (update) {
    debug(DEBUG_SQL, "%s", dstring_str(ds));
    db_query_dstring(database, ds);
  }
}

static void takeover_cave(db_t *database,
        int    cave_id,
        int    attacker_id,
        const char   *return_start) {
  /* change owner of cave */
  db_query(database, "UPDATE " DB_TABLE_CAVE " SET playerID = %d WHERE caveID = %d", attacker_id, cave_id);

  dstring_t *ds;
  ds = dstring_new("UPDATE Event_movement SET target_caveID = source_caveID, ");
  dstring_append(ds, "end = addtime('%s',timediff('%s',start)), ",return_start,return_start);
  dstring_append(ds, "start='%s', ",return_start);
  dstring_append(ds, "movementID = 5 where caveID = %d and caveID = source_caveID",cave_id);
  debug(DEBUG_SQL, "Torben %s", dstring_str(ds));
  db_query_dstring(database, ds);

  /* delete research from event table*/
  db_query(database, "DELETE FROM Event_science WHERE caveID = %d", cave_id);

  /* copy sciences from new owner to cave */
  science_update_caves(database, attacker_id);
}

static void after_battle_attacker_update (
        db_t *database,
        int          player_id,
        const Battle *battle,
        int          source_caveID,
        int          target_caveID,
        const char   *speed_factor,
        const char   *return_start,
        const char   *return_end,
        int          artefact,
        int          heroID,
        struct Relation *relation) {
  int update = 0;
  int i;

  /* construct attacker update */
  for (i = 0; i < MAX_UNIT; ++i) {
    if (battle->attackers[0].units[i].amount_after > 0) {
      update = 1;
      break;
    }
  }

  if (update) {
    dstring_t *ds;

    /* send remaining units back */
    ds = dstring_new("INSERT INTO Event_movement (caveID, target_caveID, source_caveID, movementID, speedFactor, start, end, artefactID, heroID");

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, ",%s", resource_type[i]->dbFieldName);

    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

    dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d, %d", source_caveID, source_caveID, target_caveID, RUECKKEHR, speed_factor, return_start, return_end, artefact, heroID);

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, ",%d", battle->attackers[0].resourcesAfter[i]);
    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%d", battle->attackers[0].units[i].amount_after);

    dstring_append(ds, ")");

    debug(DEBUG_SQL, "%s", dstring_str(ds));
    db_query_dstring(database, ds);
  } else {
    /* kill hero if no units left */
    if (heroID > 0) {
      kill_hero(database, heroID);
    }
  }
}

static void after_takeover_attacker_update(db_t *database,
        int             player_id,
        const Battle    *battle,
        int             target_caveID,
        int             artefact,
        int             heroID,
        struct Relation *relation) {
  int update = 0;
  int i;

  /* construct attacker update */
  for (i = 0; i < MAX_UNIT; ++i) {
    if (battle->attackers[0].units[i].amount_after > 0) {
      update = 1;
      break;
    }
  }

  if (update) {
    dstring_t *ds;

    /* put artefact into cave */
    if (artefact > 0)
      put_artefact_into_cave(database, artefact, target_caveID);

    /* put hero into cave */
    if (heroID > 0) {
      remove_hero_effects_from_cave (database, heroID);
      put_hero_into_cave(database, heroID, target_caveID);
      apply_hero_effects_to_cave(database, heroID);
    }

    /* put remaining units into target_cave */
    ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "", resource_type[i]->dbFieldName, resource_type[i]->dbFieldName, battle->attackers[0].resourcesAfter[i], function_to_sql(resource_type[i]->maxLevel));

    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%s = %s + %d", unit_type[i]->dbFieldName, unit_type[i]->dbFieldName, battle->attackers[0].units[i].amount_after);

    dstring_append(ds, " WHERE caveID = %d", target_caveID);

    debug(DEBUG_SQL, "%s", dstring_str(ds));
    db_query_dstring(database, ds);
  } else {
    /* kill hero if no units left */
    if (heroID > 0) {
      kill_hero(database, heroID);
    }
  }
}

struct Battle *hero_update_after_battle(db_t *database,
        Battle *battle,
        int heroID,
        struct Cave *defender_cave,
        int war_points_attacker,
        int war_points_defender,
        int change_owner) {
  struct Hero hero;
  dstring_t *ds;

  // attacker hero update
  if (battle->attackers->heroFights) {
    if (heroID > 0) {
      get_hero_by_id(database, heroID, &hero);
      ds = dstring_new("UPDATE " DB_TABLE_HERO " SET healPoints = LEAST(GREATEST(healPoints - %d, 0), %d), exp = exp + %d", abs(battle->attackers_acc_hitpoints_units_before - battle->attackers_acc_hitpoints_units), hero.maxHealPoints, war_points_attacker);
      dstring_append(ds, " WHERE heroID = %d", heroID);

      debug(DEBUG_HERO, "hero_update_after_battle attacker: %s", dstring_str(ds));
      db_query_dstring(database, ds);

      // check if hero is alive
      get_hero_by_id(database, heroID, &hero);
      if (hero.healPoints <= 0) {
        kill_hero(database, heroID);
        battle->attackers_hero_died = 1;

        // check for returning movements
        ds = dstring_new("UPDATE Event_movement SET heroID = 0 WHERE heroID = %d", heroID);

        debug(DEBUG_SQL, "%s", dstring_str(ds));
        db_query_dstring(database, ds);
      }
    }
  }

  // defender hero update
  if (battle->defenders->heroFights) {
    heroID = defender_cave->heroID;

    if (heroID > 0) {
      get_hero_by_id(database, heroID, &hero);
      ds = dstring_new("UPDATE " DB_TABLE_HERO " SET healPoints = LEAST(GREATEST(healPoints - %d, 0), %d), exp = exp + %d", abs(battle->defenders_acc_hitpoints_units_before - battle->defenders_acc_hitpoints_units), hero.maxHealPoints, war_points_defender);
      dstring_append(ds, " WHERE heroID = %d", heroID);

      debug(DEBUG_HERO, "hero_update_after_battle defender: %s", dstring_str(ds));
      db_query_dstring(database, ds);

      // check if hero is alive
      get_hero_by_id(database, heroID, &hero);
      if (hero.healPoints <= 0) {
        kill_hero(database, heroID);
        battle->defenders_hero_died = 1;
      }
    }

    // on takeover owner change kill hero in defenders cave
    if (change_owner) {
      heroID = defender_cave->heroID;

      if (heroID > 0) {
        kill_hero(database, heroID);
        battle->defenders_hero_died = 1;
      }
    }
  }

  return battle;
}

void processTribeCaveWonder(db_t *database, struct Cave tribeCave, struct Player attPlayer) {
  db_result_t *result;
  int row;
  dstring_t *ds;
  int caveWonderId;

  caveWonderId = ((struct Terrain *)terrain_type[tribeCave.terrain])->tribeCaveWonderId;

  if (caveWonderId == -1) {
    //caveWonderId = getRandomTribeCaveWonder();
  }

  // end existing tribeCave Wonder
  ds = dstring_new("UPDATE Event_wonderEnd SET end = '0000-00-00 00:00:00' WHERE tribeCaveWonderCaveId = %d", tribeCave.cave_id);

  debug(DEBUG_TICKER, "processTribeCaveWonder: %s", dstring_str(ds));
  db_query_dstring(database, ds);

  // insert new tribeCave wonder
  ds = dstring_new ("SELECT c.caveID "
                      " FROM Cave c "
                      " LEFT JOIN Player p ON c.playerID = p.playerID "
                      "WHERE p.playerID IN (SELECT pl.playerID FROM Player pl WHERE pl.tribeID = %d)", attPlayer.tribe_id);
  result = db_query_dstring(database, ds);

  int targetID;
  while ((row = db_result_next_row(result))) {
    targetID = db_result_get_int(result, "caveID");
    ds = dstring_new("INSERT INTO Event_wonder (casterID, sourceID, targetID, wonderID, impactID, start, end) "
                       " VALUES (%d, %d, %d, %d, %d, '%s', '%s')",
                         attPlayer.player_id, 0, targetID, caveWonderId, 0, "0000-00-00 00:00:00", "0000-00-00 00:00:00");
    debug(DEBUG_TICKER, "processTribeCaveWonder: %s", dstring_str(ds));
      db_query_dstring(database, ds);
  }
}


/*
 * This function is responsible for all the movement.
 *
 * @params database  the function needs this link to the DB
 * @params result    current movement event (from DB)
 */
void movement_handler (db_t *database, db_result_t *result) {
  int movementID;
  int target_caveID;
  int source_caveID;
  const char *speed_factor;

  time_t event_start;
  time_t event_end;
  const char *return_start;
  char return_end[TIMESTAMP_LEN];

  struct Cave cave1;
  struct Cave cave2;
  struct Player player1;
  struct Player player2;
  struct Relation relation1;
  struct Relation relation2;

  int i;
  int units[MAX_UNIT];
  int resources[MAX_RESOURCE];
  int takeover_multiplier;
  int change_owner;
  int isFarming = 0;

  Battle *battle;
  dstring_t *ds;
  double spy_result;
  struct SpyReportReturnStruct srrs;

  /* time related issues */
  const float *battle_bonus;

  /* artefacts */
  int artefact = 0;
  int artefact_def = 0;
  int artefact_id = 0;
  int artefact_kill = 0;
  int lostTo = 0;

  int heroID = 0;
  int hero_points_attacker = 0;
  int hero_points_defender = 0;

  int body_count = 0;
  int attacker_lose = 0;
  int defender_lose = 0;
  int defender_va_lose = 0;

  int war_points_attacker = 0;
  int war_points_defender = 0;
  int war_points_sender = 0;
  int war_points_show = 0;

  int  takeover = 0;
  debug(DEBUG_TICKER, "entering function movement_handler()");

  /* get movement id and target/source cave id */
  movementID    = db_result_get_int(result, "movementID");
  target_caveID = db_result_get_int(result, "target_caveID");
  source_caveID = db_result_get_int(result, "source_caveID");
  speed_factor  = db_result_get_string(result, "speedFactor");

  /* get event_start and event_end */
  event_start  = db_result_get_gmtime(result, "start");
  return_start = db_result_get_string(result, "end");
  event_end    = make_time_gm(return_start);
  make_timestamp_gm(return_end, event_end + (event_end - event_start));

  /* get resources, units, hero and artefact id */
  get_resource_list(result, resources);
  get_unit_list(result, units);
  artefact = db_result_get_int(result, "artefactID");
  heroID = db_result_get_int(result, "heroID");

  /* TODO reduce number of queries */
  get_cave_info(database, source_caveID, &cave1);
  get_cave_info(database, target_caveID, &cave2);
  if (cave1.player_id) {
    get_player_info(database, cave1.player_id, &player1);
  } else {  /* System */
    memset(&player1, 0, sizeof player1);
    player1.tribe = "";
    player1.tribe_id = 0;
  }

  if (cave2.player_id == cave1.player_id) {
    player2 = player1;
  } else if (cave2.player_id) {
    get_player_info(database, cave2.player_id, &player2);
  } else {  /* System */
    memset(&player2, 0, sizeof player2);
    player2.tribe = "";
    player2.tribe_id = 0;
  }

  debug(DEBUG_TICKER, "sourceCaveID = %d, targetCaveID = %d, movementID = %d", source_caveID, target_caveID, movementID);

  /**********************************************************************/
  /*** THE INFAMOUS GIANT SWITCH ****************************************/
  /**********************************************************************/
  switch (movementID) {
    /**********************************************************************/
    /*** ROHSTOFFE BRINGEN ************************************************/
    /**********************************************************************/
    case ROHSTOFFE_BRINGEN:
      /* record in takeover table */
      ds = dstring_new("UPDATE " DB_TABLE_CAVE_TAKEOVER " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
        dstring_append(ds, "%s%s = %s + %d", i > 0 ? "," : "", resource_type[i]->dbFieldName, resource_type[i]->dbFieldName, resources[i]);

      dstring_append(ds, " WHERE caveID = %d AND playerID = %d", target_caveID, cave1.player_id);

      db_query_dstring(database, ds);
      if (db_affected_rows(database)!=0) {
        takeover=1;
      }

      /* put resources into cave */
      dstring_set(ds, "UPDATE " DB_TABLE_CAVE " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
        dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "", resource_type[i]->dbFieldName, resource_type[i]->dbFieldName, (takeover==1)?resources[i] * TAKEOVER_RESOURCE_SAVE_PERCENTAGE / 100:resources[i], function_to_sql(resource_type[i]->maxLevel));

      dstring_append(ds, " WHERE caveID = %d", target_caveID);

      db_query_dstring(database, ds);

      if (artefact > 0) {
        struct Artefact_class artefact_class;

        // auslesen des Artefaktes. Sollte das Verschieben nicht erlaubt sein wird das Artefact nicht in die neue Höhle verschoben
        get_artefact_class_by_artefact_id(database, artefact, &artefact_class);
        if (artefact_class.destroyOnMove) {
          artefact_kill = 1;
        } else {
          put_artefact_into_cave(database, artefact, target_caveID);
        }
      }

      /* send all units back */
      dstring_set(ds, "INSERT INTO Event_movement (caveID, target_caveID, source_caveID, movementID, speedFactor, start, end, heroID");

      for (i = 0; i < MAX_UNIT; ++i)
        dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

      dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d", source_caveID, source_caveID, target_caveID, RUECKKEHR, speed_factor, return_start, return_end, heroID);

      for (i = 0; i < MAX_UNIT; ++i)
        dstring_append(ds, ",%d", units[i]);

      dstring_append(ds, ")");

      db_query_dstring(database, ds);

      /* generate trade report and receipt for sender */
      trade_report(database, &cave1, &player1, &cave2, &player2,
      resources, NULL, artefact, artefact_kill, 0, 0);
    break;

    /**********************************************************************/
    /*** EINHEITEN/ROHSTOFFE VERSCHIEBEN **********************************/
    /**********************************************************************/
    case VERSCHIEBEN:
      get_relation_info(database, player1.tribe_id, player2.tribe_id, &relation1);
      /*überprüfen ob sender und versender eine kriegsbeziehung haben */
      if (!(isMovingAllowed(database, &player1, &player2, &relation1) || isTakeoverableCave(database, target_caveID))) {
        //bewegung umdrehen//
        /* send remaining units back */
        ds = dstring_new("INSERT INTO Event_movement (caveID, target_caveID, source_caveID, movementID, speedFactor, start, end, artefactID, heroID");

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, ",%s", resource_type[i]->dbFieldName);
        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

        dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d, %d",
        source_caveID, source_caveID, target_caveID, RUECKKEHR,
        speed_factor, return_start, return_end, artefact, heroID);

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, ",%d", resources[i]);

        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%d", units[i]);

        dstring_append(ds, ")");

        db_query_dstring(database, ds);
        break;
      }

      /* record in takeover table */
      ds = dstring_new("UPDATE " DB_TABLE_CAVE_TAKEOVER " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, "%s%s = %s + %d", i > 0 ? "," : "",
      resource_type[i]->dbFieldName,
      resource_type[i]->dbFieldName, resources[i]);

      dstring_append(ds, " WHERE caveID = %d AND playerID = %d",
        target_caveID, cave1.player_id);

      db_query_dstring(database, ds);
      if (db_affected_rows(database)!=0){
        takeover=1;
      }

      /* put resources and units into cave */
      dstring_set(ds, "UPDATE " DB_TABLE_CAVE " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
        dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "", resource_type[i]->dbFieldName, resource_type[i]->dbFieldName,  (takeover==1)?resources[i] * TAKEOVER_RESOURCE_SAVE_PERCENTAGE / 100:resources[i], function_to_sql(resource_type[i]->maxLevel));

      for (i = 0; i < MAX_UNIT; ++i) {
        war_points_sender += ((struct Unit *)unit_type[i])->warpoints * units[i];
        dstring_append(ds, ",%s = %s + %d", unit_type[i]->dbFieldName, unit_type[i]->dbFieldName, units[i]);
      }

      if (relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR) {
        war_points_update_verschieben(database, &player1, &player2, -1* war_points_sender);
      } else {
        war_points_sender = 0;
      }
      dstring_append(ds, " WHERE caveID = %d", target_caveID);

      db_query_dstring(database, ds);
      if (artefact > 0) {
        struct Artefact_class artefact_class;

        // auslesen des Artefaktes. Sollte das Verschieben nicht erlaubt sein wird das Artefact nicht in die neue Höhle verschoben
        get_artefact_class_by_artefact_id(database, artefact, &artefact_class);
        if (artefact_class.destroyOnMove) {
          artefact_kill = 1;
        } else {
          put_artefact_into_cave(database, artefact, target_caveID);
        }
      }

      if (heroID > 0) {
        if (cave1.player_id != cave2.player_id) {
          kill_hero(database, heroID);
          heroID = -1;
        } else {
          remove_hero_effects_from_cave (database, heroID);
          put_hero_into_cave(database, heroID, target_caveID);
          apply_hero_effects_to_cave(database, heroID);
        }
      }

      /* generate trade report and receipt for sender */
      trade_report(database, &cave1, &player1, &cave2, &player2,
      resources, units, artefact, artefact_kill, heroID, war_points_sender);
    break;

    /**********************************************************************/
    /*** RUECKKEHR ********************************************************/
    /**********************************************************************/
    case RUECKKEHR:
      /* put resources into cave */
      ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
        dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
          resource_type[i]->dbFieldName,
          resource_type[i]->dbFieldName, resources[i],
          function_to_sql(resource_type[i]->maxLevel));

      for (i = 0; i < MAX_UNIT; ++i)
        dstring_append(ds, ",%s = %s + %d",
           unit_type[i]->dbFieldName,
           unit_type[i]->dbFieldName, units[i]);

      dstring_append(ds, " WHERE caveID = %d", target_caveID);

      db_query_dstring(database, ds);

      if (artefact > 0)
        put_artefact_into_cave(database, artefact, target_caveID);

      if (heroID > 0)
        put_hero_into_cave(database, heroID, target_caveID);

      /* generate return report */
      return_report(database, &cave1, &player1, &cave2, &player2,
        resources, units, artefact, heroID);
      break;

    /**********************************************************************/
    /*** ANGREIFEN ********************************************************/
    /**********************************************************************/
    case ANGREIFEN:
      /* beginner protection active in target cave? */
      if (cave_is_protected(&cave2)) {
        debug(DEBUG_TICKER, "hallo2");
        /* send remaining units back */
        ds = dstring_new("INSERT INTO Event_movement (caveID, target_caveID, source_caveID, movementID, speedFactor, start, end, artefactID, heroID");

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, ",%s", resource_type[i]->dbFieldName);

        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

        dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d, %d", source_caveID, source_caveID, target_caveID, RUECKKEHR, speed_factor, return_start, return_end, artefact, heroID);

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, ",%d", resources[i]);

        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%d", units[i]);

        dstring_append(ds, ")");

        db_query_dstring(database, ds);

        /* create and send reports */
        protected_report(database, &cave1, &player1, &cave2, &player2);
        break;
      }

      /* get relations between the two players' tribes */
      get_relation_info(database, player1.tribe_id, player2.tribe_id, &relation1);
      get_relation_info(database, player2.tribe_id, player1.tribe_id, &relation2);
      debug(DEBUG_BATTLE, "Relationtypes: %d and %d", relation1.relationType, relation2.relationType);

      battle = battle_create(1, 1);
      battle_bonus = get_battle_bonus();

      debug(DEBUG_BATTLE, "entering prepare_battle");
      /* prepare structs for battle, exceptions are uncaught! */
      prepare_battle(database, battle, &player1, &player2, &cave1, &cave2, battle_bonus, 0, units, resources, &artefact, &artefact_def, heroID, &relation1, &relation2);

      /* calculate the fame */
      /* Calculatin is diferent if the battle was just pure farming*/
      isFarming = check_farming(database, cave2.artefacts, &player1, &player2, &relation1);
      if (relation1.relationType == RELATION_TYPE_WAR) {
        battle->isWar = 1;
      }

      /* calculate battle result */
      calcBattleResult(battle, &cave2, 0);

      /* change artefact ownership */
      debug(DEBUG_BATTLE, "entering change artefact");
      after_battle_change_artefact_ownership(database, battle->winner, &artefact, &artefact_id, &artefact_def, target_caveID, &cave2, &lostTo);

      /* attackers artefact (if any) is stored in variable artefact,
         artefact_id is id of the artefact that changed owner (or 0) */

      /* no relation -> attacker get negative fame*/
      debug(DEBUG_BATTLE, "Relation Type %d",relation1.relationType);

      /* construct attacker update */
      debug(DEBUG_BATTLE, "entering attacker update");
      after_battle_attacker_update(database, player1.player_id, battle, source_caveID, target_caveID, speed_factor, return_start, return_end, artefact, heroID, &relation1);

      /* defender update: exception still uncaught (better leave) */
      debug(DEBUG_BATTLE, "entering defender update");
      after_battle_defender_update(database, player2.player_id, battle, target_caveID, &relation2);

      /* Farming update */
      if (isFarming) {
        increaseFarming(database, player1.player_id);
      }

      /* reset DB_TABLE_CAVE_TAKEOVER */
      ds = dstring_new("UPDATE " DB_TABLE_CAVE_TAKEOVER " SET status = 0");

      for (i = 0; i < MAX_RESOURCE; ++i)
        dstring_append(ds, ",%s = 0", resource_type[i]->dbFieldName);

      dstring_append(ds, " WHERE caveID = %d AND playerID = %d",
      target_caveID, cave1.player_id);

      db_query_dstring(database, ds);

      /* cave takeover by battle */
      if (battle->winner == FLAG_ATTACKER && ((struct Terrain *)terrain_type[cave2.terrain])->takeoverByCombat) {
        db_query(database, "UPDATE " DB_TABLE_CAVE " SET playerID = %d WHERE caveID = %d", cave1.player_id, target_caveID);
        db_query(database, "DELETE FROM Event_science WHERE caveID = %d",
        target_caveID);

        science_update_caves(database, cave1.player_id);

        //kill hero in defenders cave
        if (cave2.heroID > 0) {
          kill_hero(database, cave2.heroID);
          cave2.heroID = 0;
        }
      }

      //bodycount calculate
      attacker_lose = bodycount_calculate(battle, FLAG_DEFENDER);
      defender_lose = bodycount_calculate(battle, FLAG_ATTACKER);
      defender_va_lose = bodycount_va_calculate(battle);

      if (relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR) {
        bodycount_update( database, player1.player_id, defender_lose);
        bodycount_update( database, player2.player_id, attacker_lose);
        war_points_show = 1;
        war_points_attacker = (defender_lose>0||defender_va_lose>11?war_points_calculate(battle,FLAG_ATTACKER):0);
        war_points_defender = (attacker_lose>0?war_points_calculate(battle,FLAG_DEFENDER):0);

        war_points_update(database, &player1, &player2, war_points_attacker, war_points_defender);
      }

      hero_points_attacker = war_points_calculate(battle,FLAG_ATTACKER);
      hero_points_defender = war_points_calculate(battle,FLAG_DEFENDER);

      // update hero
      battle = hero_update_after_battle(database, battle, heroID, &cave2, hero_points_attacker, hero_points_defender, 0);

      // set last attacking tribe
      if (battle->winner == FLAG_ATTACKER) {
        db_query(database, "UPDATE " DB_TABLE_CAVE " SET lastAttackingTribeID = %d WHERE caveID = %d", player1.tribe, player1.tribe_id, target_caveID);
      }

      // check and set take takeoverable
      int caveSetTakeoverable = 0;
      debug(DEBUG_TICKER, "0");
      if (battle->winner == FLAG_ATTACKER
          && !cave2.takeoverable
          && (cave2.takeover_level > 0)
          && !cave2.starting_position
          && (cave2.player_id == 0)
          && ((struct Terrain *)terrain_type[cave2.terrain])->tribeRegion == 0)
      {
        // if there are no units and defense systems in the cave, set it takeoverable
        if (battle->defenders_acc_hitpoints_units == 0 && battle->defenders_acc_hitpoints_defenseSystems == 0) {
          db_query(database, "UPDATE " DB_TABLE_CAVE " SET takeoverable = 1 WHERE caveID = %d", cave2.cave_id);
          caveSetTakeoverable = 1;
          debug(DEBUG_TICKER, "movement_handler: Set cave with ID %d as takeoverable", cave2.cave_id);
        }
      }
      debug(DEBUG_TICKER, "1");
      // process tribeCaveWonders
      if (((struct Terrain *)terrain_type[cave2.terrain])->tribeRegion == 1 && battle->winner == FLAG_ATTACKER) {
        debug(DEBUG_TICKER, "2");
        if (player1.tribe_id != cave2.lastAttackingTribeId) {
          debug(DEBUG_TICKER, "3");
          if (((struct Terrain *)terrain_type[cave2.terrain])->tribeCaveWonderId != 0) {
            debug(DEBUG_TICKER, "4");
            processTribeCaveWonder(database, cave2, player1);
          }
        }
      }

      /* create and send reports */
      battle_report(database, &cave1, &player1, &cave2, &player2, battle,
          artefact_id, lostTo, 0, 0, &relation1, &relation2, war_points_show, war_points_attacker,
          war_points_defender, heroID, hero_points_attacker, hero_points_defender, caveSetTakeoverable);
    break;

    /**********************************************************************/
    /*** Spionieren *******************************************************/
    /**********************************************************************/
    case SPIONAGE:
      /* generate spy report */
      srrs = spy_report(database, &cave1, &player1, &cave2, &player2, resources, units, &artefact);
      spy_result = srrs.value;

      if (spy_result == 1) {
        // artefact sollte immer < 1 sein wenn srrs.artefactID > 0!!!
        if (srrs.artefactID > 0 && artefact == 0) {
          artefact = srrs.artefactID;
        }

        /* send all units back */
        ds = dstring_new("INSERT INTO Event_movement (caveID, target_caveID, source_caveID, movementID, speedFactor, start, end, artefactID, heroID");

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, ",%s", resource_type[i]->dbFieldName);

        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

        dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d, %d", source_caveID, source_caveID, target_caveID, RUECKKEHR, speed_factor, return_start, return_end, artefact, heroID);

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, ",%d", resources[i]);

        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%d", units[i]);

        dstring_append(ds, ")");

        db_query_dstring(database, ds);
      } else {
        /* send remaining units back */
        int count = 0;

        ds = dstring_new("INSERT INTO Event_movement (caveID, target_caveID, source_caveID, movementID, speedFactor, start, end, artefactID, heroID");

        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

        dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d, %d", source_caveID, source_caveID, target_caveID, RUECKKEHR, speed_factor, return_start, return_end, artefact, heroID);

        for (i = 0; i < MAX_UNIT; ++i) {
          int num = units[i] * spy_result;

          dstring_append(ds, ",%d", num);
          count += num;
          body_count += units[i] - num;
        }

        dstring_append(ds, ")");

        if (count)
          db_query_dstring(database, ds);

        /* put resources into cave */
        ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "", resource_type[i]->dbFieldName, resource_type[i]->dbFieldName, resources[i], function_to_sql(resource_type[i]->maxLevel));

        dstring_append(ds, " WHERE caveID = %d", target_caveID);

        db_query_dstring(database, ds);

        if (artefact > 0)
          put_artefact_into_cave(database, artefact, target_caveID);
      }

      // update hero
      if (heroID > 0) {
        put_hero_into_cave(database, heroID, target_caveID);
      }

      get_relation_info(database, player1.tribe_id, player2.tribe_id, &relation1);
      if (relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR) {
        bodycount_update(database, player2.player_id, body_count);
      }
    break;

    /**********************************************************************/
    /*** UEBERNEHMEN ******************************************************/
    /**********************************************************************/
    case TAKEOVER:
      /* secure or protected target gave? */
      if (cave2.secure || cave_is_protected(&cave2)) {
        /* send remaining units back */
        ds = dstring_new("INSERT INTO Event_movement (caveID, target_caveID, source_caveID, movementID, speedFactor, start, end, artefactID, heroID");

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, ",%s", resource_type[i]->dbFieldName);
        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

        dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d, %d",
                 source_caveID, source_caveID, target_caveID, RUECKKEHR,
                 speed_factor, return_start, return_end, artefact, heroID);

        for (i = 0; i < MAX_RESOURCE; ++i)
          dstring_append(ds, ",%d", resources[i]);
        for (i = 0; i < MAX_UNIT; ++i)
          dstring_append(ds, ",%d", units[i]);

        dstring_append(ds, ")");

        db_query_dstring(database, ds);

        /* create and send reports */
        /* FIXME use different message in report (protected -> secure) */
        protected_report(database, &cave1, &player1, &cave2, &player2);
        break;
      }

      get_relation_info(database, player1.tribe_id, player2.tribe_id, &relation1);
      get_relation_info(database, player2.tribe_id, player1.tribe_id, &relation2);

      battle = battle_create(1, 1);

      battle_bonus = get_battle_bonus();
      takeover_multiplier = get_takeover_multiplier(&cave2);

      /* prepare structs for battle, exceptions are uncaught! */
      prepare_battle(database, battle, &player1, &player2, &cave1, &cave2, battle_bonus, takeover_multiplier, units, resources, &artefact, &artefact_def, heroID, &relation1, &relation2);

      /* calculate battle result */
      /*bei ner Übernahme kein resi klau möglich*/
      calcBattleResult(battle, &cave2, 1);

      /* change artefact ownership */
      after_battle_change_artefact_ownership(database, battle->winner, &artefact, &artefact_id, &artefact_def,target_caveID, &cave2, &lostTo);

      /* attackers artefact (if any) is stored in variable artefact,
         artefact_id is id of the artefact that changed owner (or 0) */

      /* defender update: exception still uncaught (better leave) */
      after_battle_defender_update(database, player2.player_id, battle, target_caveID, &relation2);

      int war1 = get_tribe_at_war(database,player1.tribe_id);
      int war2 = get_tribe_at_war(database,player2.tribe_id);

      /* attacker won:  put survivors into cave, change owner
       * attacker lost: send back survivors */
      change_owner =
          battle->winner == FLAG_ATTACKER
            && cave2.player_id != PLAYER_SYSTEM
            && player1.max_caves > get_number_of_caves(database, player1.player_id)
            && ((relation1.relationType == RELATION_TYPE_WAR
                 && relation2.relationType == RELATION_TYPE_WAR)
                 || (!war1 && !war2)
                 || (player1.tribe_id == player2.tribe_id)); // Spieler im selben stamm

      //bodycount calculate
      attacker_lose = bodycount_calculate(battle, FLAG_DEFENDER);
      defender_lose = bodycount_calculate(battle, FLAG_ATTACKER);
      defender_va_lose = bodycount_va_calculate(battle);


      if (relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR) {
        war_points_show = 1;
        war_points_attacker = (defender_lose>0||defender_va_lose>11?war_points_calculate(battle,FLAG_ATTACKER):0);
        war_points_defender = (attacker_lose>0?war_points_calculate(battle,FLAG_DEFENDER):0);
        bodycount_update( database, player1.player_id, defender_lose);
        bodycount_update( database, player2.player_id, attacker_lose);
      }

      hero_points_attacker = war_points_calculate(battle,FLAG_ATTACKER);
      hero_points_defender = war_points_calculate(battle,FLAG_DEFENDER);

      /* update hero */
      battle = hero_update_after_battle(database, battle, heroID, &cave2, hero_points_attacker, hero_points_defender, change_owner);

      if (change_owner) {
        debug(DEBUG_TAKEOVER, "change owner of cave %d to new owner %d", target_caveID, cave1.player_id);
        takeover_cave(database, target_caveID, cave1.player_id,return_start);
        after_takeover_attacker_update(database, player1.player_id, battle, target_caveID, artefact, heroID, &relation1);

        if (relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR) {
          war_points_attacker += WAR_POINTS_FOR_TAKEOVER;
        }
      } else { /* send survivors back */
        debug(DEBUG_TAKEOVER, "send back attacker's survivors");
        after_battle_attacker_update(database, player1.player_id, battle, source_caveID, target_caveID, speed_factor, return_start, return_end, artefact, heroID, &relation1);
        if (relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR) {
          war_points_defender += WAR_POINTS_FOR_TAKEOVER_DEFEND;
        }
      }

      if (relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR) {
        war_points_update(database, &player1, &player2, war_points_attacker, war_points_defender);
      }

      // create and send reports
      battle_report(database, &cave1, &player1, &cave2, &player2, battle, artefact_id, lostTo,
          change_owner, 1 + takeover_multiplier, &relation1, &relation2,war_points_show,
          war_points_attacker, war_points_defender, heroID, hero_points_attacker, hero_points_defender, caveSetTakeoverable);

      //bodycount calculate

      if (relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR) {
        bodycount_update( database, player1.player_id, defender_lose);
        bodycount_update( database, player2.player_id, attacker_lose);
      }
    break;

    default:
      throw(BAD_ARGUMENT_EXCEPTION, "movement_handler: unknown movementID");
  }

  /**********************************************************************/
  /*** END OF THE INFAMOUS GIANT SWITCH *********************************/
  /**********************************************************************/
  debug(DEBUG_TICKER, "leaving function movement_handler()");
}
