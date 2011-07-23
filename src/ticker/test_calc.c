/*
 * test_calc.c - simple battle calculator ("Kampfulator")
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdio.h>
#include <stdlib.h>
#include <time.h>

#include "calc_battle.h"
#include "game_rules.h"
#include "memory.h"

/*
 * dummy function to avoid link dependency on database
 */
double db_result_get_double (db_result_t *result, const char *name) {
  return 0;
}

/*
 * Argumentliste:
 *
 * range_factor_def range_factor_ang  : Range Factor fuer Vert. u. Ang.
 * range_bonus_def range_bonus_ang
 * areal_factor_def areal_factor_ang
 * areal_bonus_def areal_bonus_ang
 * melee_factor_def melee_factor_ang
 * melee_bonus_def melee_bonus_ang
 * defense_factor_def areal_factor_ang
 * defense_bonus_def areal_bonus_ang
 * size_factor_def size_factor_ang
 * size_bonus_def size_bonus_ang
 *
 * r1 .. rMax             : Resourcen des Verteidigers
 * ud1 ua1 .. udMax uaMax : Einheiten von Verteidiger / Angreifer (abwechselnd)
 * d1 .. dMax             : Verteidigungsanlage des Verteidigers
 *
 *
 * Ausgabe auf stdout:
 *
 * r1 .. rMax             : geklaute Resourcen des Angreifers
 * ud1 ua1 .. udMax uaMax : Einheiten von Verteidiger / Angreifer (abwechselnd)
 * d1 .. dMax             : Verteidigungsanlage des Verteidigers
 *
 * range_d range_a        : kampfwerte vor dem kampf
 * areal_d areal_a
 * melee_d melee_a
 * size_d  size_a
 */

int main (int argc, char *argv[]) {
  struct memory_pool *pool = memory_pool_new();
  Battle *battle;
  int c = 1;
  int i;

  srand(time(NULL));

  battle = battle_create(1, 1);
  battle->defenders[0].religion = 1;
  battle->defenders[0].religion_bonus = 1;
  battle->attackers[0].religion = 1;
  battle->attackers[0].religion_bonus = 1;
  battle->defenders[0].owner_caveID = 1;
  battle->attackers[0].owner_caveID = 1;

  battle->defenders[0].effect_rangeattack_factor = 1 + atof(argv[c++]);
  battle->attackers[0].effect_rangeattack_factor = 1 + atof(argv[c++]);

  battle->defenders[0].effect_arealattack_factor = 1 + atof(argv[c++]);
  battle->attackers[0].effect_arealattack_factor = 1 + atof(argv[c++]);

  battle->defenders[0].effect_attackrate_factor = 1 + atof(argv[c++]);
  battle->attackers[0].effect_attackrate_factor = 1 + atof(argv[c++]);

  battle->defenders[0].effect_defenserate_factor = 1 + atof(argv[c++]);
  battle->attackers[0].effect_defenserate_factor = 1 + atof(argv[c++]);

  battle->defenders[0].effect_size_factor = 1 + atof(argv[c++]);
  battle->attackers[0].effect_size_factor = 1 + atof(argv[c++]);

  for (i = 0; i < MAX_RESOURCE; ++i) {
    battle->defenders[0].resourcesBefore[i] = atoi(argv[c++]);
    battle->attackers[0].resourcesBefore[i] = 0;
  }

  for (i = 0; i < MAX_UNIT; ++i) {
    battle->defenders[0].units[i].amount_before = atoi(argv[c++]);
    battle->attackers[0].units[i].amount_before = atoi(argv[c++]);
  }

  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    battle->defenders[0].defenseSystems[i].amount_before = atoi(argv[c++]);
  }

  calcBattleResult(battle, NULL, 1);  /* calculate battle result */

  for (i = 0; i < MAX_RESOURCE; ++i)
     printf("%d\n", battle->attackers[0].resourcesAfter[i]);
  for (i = 0; i < MAX_UNIT;++i)
     printf("%d\n%d\n",
	    battle->defenders[0].units[i].amount_after,
	    battle->attackers[0].units[i].amount_after);

  for (i = 0; i < MAX_DEFENSESYSTEM; ++i)
     printf("%d\n", battle->defenders[0].defenseSystems[i].amount_after);

  printf("%d\n%d\n",
	 battle->defenders_acc_range_before,
	 battle->attackers_acc_range_before);

  printf("%d\n%d\n",
	 battle->defenders_acc_areal_before,
	 battle->attackers_acc_areal_before);

  printf("%d\n%d\n",
	 battle->defenders_acc_melee_before,
	 battle->attackers_acc_melee_before);

  printf("%d\n%d\n",
	 battle->defenders_acc_hitpoints_units_before +
	 battle->defenders_acc_hitpoints_defenseSystems_before,
	 battle->attackers_acc_hitpoints_units_before +
	 battle->attackers_acc_hitpoints_defenseSystems_before);

  memory_pool_free(pool);
  return 0;
}
