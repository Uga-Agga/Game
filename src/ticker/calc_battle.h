/*
 * calc_battle.h - battle and damage calculation
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _CALC_BATTLE_H_
#define _CALC_BATTLE_H_

#include "cave.h"

#define FLAG_DEFENDER 0
#define FLAG_ATTACKER 1

/*
 * In a battle participate defenders and attackers. Each defender and each
 * attacker has a single army. These armies are held in the battle struct.
 * A battle struct has two vectors, one for the defenders and one for the
 * attackers, that hold the armies. An army consists of several unit types.
 * The amounts of these units are held in the unit and in the defenseSystem
 * vector. Additionally there are two arrays for the resources before and
 * after the battle, that this army carries.
 */

typedef struct Army_unit
{
  int amount_before;	// unit count
  int amount_after;

  int acc_range;        // total damage values for all units of this type
  int acc_areal;
  int acc_melee;

  int acc_rangedDamageResistance; // total range hit points
  int acc_defense;                // total hit points
  int acc_hitpoints;              // accumulated size

  int acc_points;

  double criticalDamageProbability;
  double heavyDamageProbability;


  int points;			//Wertigkeit der armee
  int warpoints;			//Wertigkeit der armee

  int rangedDamageResistance;
  int defense;		// hit points of a single unit
  int hitpoints;	// size of a single unit
} Army_unit;

typedef struct Army
{
  int owner_caveID;

  int religion;
  float religion_bonus;

  Army_unit *units;
  Army_unit *defenseSystems;

  int *resourcesBefore;
  int *resourcesAfter;

  int acc_army_units_points;			//Wertigkeit der Armee
  int acc_army_defense_points;			//Wertigkeit der Armee

  int acc_hitpoints_units;		// accumulated size (units)
  int acc_hitpoints_defenseSystems;	// accumulated size (fortifications)

  int acc_range;			// accumulated damage values
  int acc_areal;
  int acc_melee;

  float effect_rangeattack_factor;
  float effect_arealattack_factor;
  float effect_attackrate_factor;
  float effect_size_factor;
  float effect_defenserate_factor;
  float effect_ranged_damage_resistance_factor;
  float relationMultiplicator;

  int heroFights;

} Army;

typedef struct Battle
{
  int size_defenders;			// number of defending armies
  int size_attackers;			// number of attacking armies
  float overpower_factor;

  Army *defenders;
  Army *attackers;

  int attackers_acc_hitpoints_units;	// accumulated army size
  int attackers_acc_hitpoints_defenseSystems;

  int attackers_acc_range;		// accumulated damage values
  int attackers_acc_areal;
  int attackers_acc_melee;

  int attackers_acc_points;		// Einheitenpunkte in der gesamten Armee

  int defenders_acc_hitpoints_units;	// accumulated army size
  int defenders_acc_hitpoints_defenseSystems;

  int defenders_acc_range;		// accumulated damage values
  int defenders_acc_areal;
  int defenders_acc_melee;

  int defenders_acc_units_points;		// Einheitenpunkte in der gesamten Armee
  int defenders_acc_defense_points;		// Def.Sys.punkte in der gesamten Armee

  // starting values remembered for the battle report.

  int size_defenders_before;
  int size_attackers_before;

  int attackers_acc_hitpoints_units_before;
  int attackers_acc_hitpoints_defenseSystems_before;

  int attackers_acc_range_before;
  int attackers_acc_areal_before;
  int attackers_acc_melee_before;

  int attackers_acc_points_before;		// Einheitenpunkte in der gesamten Armee

  int defenders_acc_hitpoints_units_before;
  int defenders_acc_hitpoints_defenseSystems_before;

  int defenders_acc_range_before;
  int defenders_acc_areal_before;
  int defenders_acc_melee_before;

  int defenders_acc_units_points_before;		// Einheitenpunkte in der gesamten Armee
  int defenders_acc_defense_points_before;		// Def.Sys.punkte in der gesamten Armee

  int winner;

  int attackers_hero_died;
  int defenders_hero_died;

  int isWar;
  int defenderIsPlayer;
} Battle;

extern Battle *battle_create (int num_defenders, int num_attackers);
extern void battle_free (Battle *battle);
extern void battle_recalc (Battle *battle);
extern void battle_copyBeforeToAfter (Battle *battle);
extern void battle_rememberBattleValues (Battle *battle);

extern Battle *calcBattleResult (Battle *battle, const struct Cave *cave, int get_resources);

#endif /* _CALC_BATTLE_H_ */
