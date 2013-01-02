/*
 * cave.h - cave and player information
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _CAVE_H_
#define _CAVE_H_

#include <time.h>

#include "database.h"
#include "game_rules.h"

#define PLAYER_SYSTEM		0

#define RELATION_TYPE_NONE	0
#define RELATION_TYPE_PRE_WAR	5	/* FIXME user defined relation id */
#define RELATION_TYPE_WAR	2	/* FIXME user defined relation id */
#define RELATION_TYPE_WAR_TREATMENT 8
#define WAR_POINTS_FOR_TAKEOVER 400;
#define WAR_POINTS_FOR_TAKEOVER_DEFEND 25;


struct Cave
{
    db_result_t *result;

    int cave_id;
    int xpos, ypos;
    const char *name;
    int player_id;
    int terrain;
    int takeoverable;
    int artefacts;
    int monster_id;
    int heroID;
    int secure;
    int starting_position;
    time_t protect_end;
    int resource[MAX_RESOURCE];
    int building[MAX_BUILDING];
    int science[MAX_SCIENCE];
    int unit[MAX_UNIT];
    int defense_system[MAX_DEFENSESYSTEM];
    float effect[MAX_EFFECT];
};

struct Player
{
    int player_id;
    const char *name;
    const char *tribe;
    int max_caves;
    const char *locale;
    int locale_id;
    int science[MAX_SCIENCE];
};

struct Relation
{
    int relation_id;
    const char *tribe;
    const char *tribe_target;
    int relationType;
    float defenderMultiplicator;
    float attackerMultiplicator;
    int defenderReceivesFame;
    int attackerReceivesFame;
};

struct Monster
{
    int monster_id;
    const char *name;
    int attack;
    int defense;
    int mental;
    int strength;
    int exp_value;
    const char *attributes;
};

/*
 * Retrieve the resource list from the result set.
 */
extern void get_resource_list (db_result_t *result, int resource[]);

/*
 * Retrieve the building list from the result set.
 */
extern void get_building_list (db_result_t *result, int building[]);

/*
 * Retrieve the science list from the result set.
 */
extern void get_science_list (db_result_t *result, int science[]);

/*
 * Retrieve the defense system list from the result set.
 */
extern void get_defense_system_list (db_result_t *result, int defense_system[]);

/*
 * Retrieve the unit list from the result set.
 */
extern void get_unit_list (db_result_t *result, int unit[]);

/*
 * Retrieve the effect list from the result set.
 */
extern void get_effect_list (db_result_t *result, float effect[]);

/*
 * Retrieve cave table information for the given cave id.
 */
extern void get_cave_info (db_t *database, int cave_id, struct Cave *cave);

/*
 * Retrieve the owner (player id) of the given gave.
 */
extern int get_cave_owner (db_t *database, int cave_id);

/*
 * Retrieve player table information for the given player id.
 */
extern void get_player_info (db_t *database, int player_id,
			     struct Player *player);

/*
 * Retrieve relation table information for the given tribe and target tribe.
 */
extern int get_relation_info (db_t *database, const char *tribe,
			      const char *tribe_target,
			      struct Relation *relation);

/*
 * Retrieve the number of caves owned by player_id.
 */
extern int get_number_of_caves (db_t *database, int player_id);

/*
 * Return religion of the cave's owner.
 */
extern int get_religion (const struct Cave *cave);

/*
 * Return whether the given cave is protected or not.
 */
extern int cave_is_protected (const struct Cave *cave);

/*
 * Retrieve monster table information for the given monster id.
 */
extern void get_monster_info (db_t *database, int monster_id,
			      struct Monster *monster);

extern int get_tribe_at_war(db_t *database, const char *tribe);


#endif /* _CAVE_H_ */
