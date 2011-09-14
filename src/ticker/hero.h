/*
 * artefact.h - handle artefacts
 * Copyright (c) 2003  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _HERO_H_
#define _HERO_H_

#include "database.h"
#include "game_rules.h"

#define HERO_REINCARNATING -1
#define HERO_DEAD  0
#define HERO_ALIVE  1

typedef struct Hero
{
    int heroID;
    int caveID;
    int isAlive; // 3 status: dead, alive, reincarnating
    int playerID;
    int healPoints;
    int maxHealPoints;
    int isMoving;
    float effect[MAX_EFFECT];
} Hero;

extern void get_hero_by_id (db_t *database, int heroID, struct Hero *hero);

extern void put_hero_into_cave (db_t *database, int heroID, int caveID);
extern void remove_hero_from_cave (db_t *database, int heroID);

extern void reincarnate_hero (db_t *database, int playerID);
extern void kill_hero (db_t *database, int playerID);

extern void apply_hero_effects_to_cave (db_t *database, int heroID);
extern void remove_hero_effects_from_cave (db_t *database, int heroID);



#endif /* _HERO_H_ */
