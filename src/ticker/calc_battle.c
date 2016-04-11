/*
 * calc_battle.c - battle and damage calculation
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>
#include <math.h>



#include "calc_battle.h"
#include "function.h"
#include "game_rules.h"
#include "logging.h"
#include "memory.h"
#include "ticker.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

#define HIT_PROBABILITY_RANGE 0.2
#define HIT_PROBABILITY_AREAL 0.1
#define HIT_PROBABILITY_MELEE 0.1

#define BATTLE_ROUNDS 2

static unsigned long next = 88;

int myrand(void){
  next = next * 1101325233 + 98765;
  return ((unsigned)(next/65536) % 100);
}
//////////////////////////////////////////////////////////////////////////////
///////////////////////////// unit functions /////////////////////////////////

/**
 * this one inflicts the damage to a single unit type
 * this function decides, wheter half killed units die or don't
 */
static void army_unit_inflictDamage (Army_unit *unit, double damage,
				     float factor)
{
  int casualties;

  if (damage < 1)                     // do damage only if bigger than 1
    return;

  if (unit->acc_defense == 0) {
    unit->amount_after = 0;
    return;
  }

  /* FIXME this is WRONG if there is a defenserate bonus or factor? */

  /* a unit that is wounded will die with corresponding probability */
  casualties = damage /
    (factor * unit->defense) + rand() / (1.5*(RAND_MAX+1.0));

  if (casualties > unit->amount_after)
    unit->amount_after = 0;
  else
    unit->amount_after -= casualties;
}

/**
 * this one inflicts the range damage to a single unit type
 * this function decides, wheter half killed units die or don't
 */
static void army_unit_inflictRangeDamage (Army_unit *unit, double damage,
				     float factor)
{
  int casualties;

  if (damage < 1)                     // do damage only if bigger than 1
    return;

  if (unit->acc_rangedDamageResistance == 0) {
    unit->amount_after = 0;
    return;
  }

  /* FIXME this is WRONG if there is a defenserate bonus or factor? */

  /* a unit that is wounded will die with corresponding probability */
  casualties = damage /
    (factor * unit->rangedDamageResistance) + rand() / (1.5*(RAND_MAX+1.0));

  if (casualties > unit->amount_after)
    unit->amount_after = 0;
  else
    unit->amount_after -= casualties;
}

//////////////////////////////////////////////////////////////////////////////
///////////////////////////// army functions /////////////////////////////////

/**
 * initialize the army structure
 */
static void army_init (Army *army)
{
  int i;

  /////////////////////////// initialize effects /////////////////////////////

  army->effect_rangeattack_factor =
  army->effect_arealattack_factor =
  army->effect_attackrate_factor  =
  army->effect_defenserate_factor =
  army->effect_size_factor        =
  army->effect_ranged_damage_resistance_factor =
  army->relationMultiplicator     = 1;
  army->heroFights = 0;

  /////////////////////////// create unit arrays /////////////////////////////

  army->units = xcalloc(MAX_UNIT, sizeof (Army_unit));

  for (i = 0; i < MAX_UNIT; ++i) {
    const struct BattleUnit *unit = (struct BattleUnit *) unit_type[i];

    army->units[i].hitpoints = unit->hitPoints;
    army->units[i].defense = unit->defenseRate;
    army->units[i].rangedDamageResistance = unit->rangedDamageResistance;
    army->units[i].points = ((struct Expansion *) unit)->ratingValue;
    army->units[i].criticalDamageProbability = unit->criticalDamageProbability;
    army->units[i].heavyDamageProbability = unit->heavyDamageProbability;
  }

  ///////////////////////// create resource arrays ///////////////////////////

  army->resourcesBefore = xcalloc(MAX_RESOURCE, sizeof (int));
  army->resourcesAfter  = xcalloc(MAX_RESOURCE, sizeof (int));

  ///////////////////////// defense system arrays ///////////////////////////

  army->defenseSystems  = xcalloc(MAX_DEFENSESYSTEM, sizeof (Army_unit));

  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    const struct BattleUnit *defense_system =
	(struct BattleUnit *) defense_system_type[i];

    army->defenseSystems[i].hitpoints = defense_system->hitPoints;
    army->defenseSystems[i].defense = defense_system->defenseRate;
    army->defenseSystems[i].points = ((struct Expansion *) defense_system)->ratingValue;
    army->defenseSystems[i].warpoints = ((struct DefenseSystem *) defense_system)->warpoints;
    //army->defenseSystems[i].warpoints = 1;
    army->defenseSystems[i].criticalDamageProbability = defense_system->criticalDamageProbability;
    army->defenseSystems[i].heavyDamageProbability = defense_system->heavyDamageProbability;
  }
}

/**
 * destroy the army stucture
 */
static void army_destroy (Army *army)
{
  if (!army) return;                     // wasn't initialised, do nothing

  free(army->units);                     // free unit arrays

  free(army->resourcesBefore);           // free resources
  free(army->resourcesAfter);

  free(army->defenseSystems);            // free defense systems
}

/**
 * recalculates the values of this army (size, damage, resistance)
 * uses the religion boni
 */
static void army_recalc (Army *army)
{
  int i;
  Army_unit *unit;

  army->acc_range           = 0;
  army->acc_areal           = 0;
  army->acc_melee           = 0;
  army->acc_hitpoints_units = 0;
  army->acc_hitpoints_defenseSystems = 0;
  army->acc_army_units_points 	= 0;
  army->acc_army_defense_points 	= 0;


  // calculate values for units

  for (i = 0; i < MAX_UNIT; ++i) {
    const struct BattleUnit *battle_unit = (struct BattleUnit *) unit_type[i];

    unit = &army->units[i];            // let unit point to actual values

    double dice = (double)myrand()/(double)100;
 
    double damageMultiplicator = 1;

    if(dice < unit->criticalDamageProbability){
      damageMultiplicator = 2;
    }else if(dice < (unit->heavyDamageProbability + unit->criticalDamageProbability)){
      damageMultiplicator = 1.5;
    }
    
  
    army->acc_range +=
      unit->acc_range =
        damageMultiplicator *
	army->religion_bonus *
	army->relationMultiplicator *
	unit->amount_after *
	(army->effect_rangeattack_factor * battle_unit->attackRange);

    army->acc_areal +=
      unit->acc_areal =
        damageMultiplicator *
	army->religion_bonus *
	army->relationMultiplicator *
	unit->amount_after *
	(army->effect_arealattack_factor * battle_unit->attackAreal);

    army->acc_melee +=
      unit->acc_melee =
        damageMultiplicator *
	army->religion_bonus *
	army->relationMultiplicator *
	unit->amount_after *
	(army->effect_attackrate_factor * battle_unit->attackRate);

    unit->acc_rangedDamageResistance = unit->amount_after *
      (army->effect_ranged_damage_resistance_factor * unit->rangedDamageResistance);

    unit->acc_defense = unit->amount_after *
      (army->effect_defenserate_factor * unit->defense);

    army->acc_hitpoints_units +=
      unit->acc_hitpoints = unit->amount_after *
      (army->effect_size_factor * unit->hitpoints);

    army->acc_army_units_points +=
      unit->acc_points = unit->amount_after *
        unit->points;
  }

  // calculate values for defenseSystems

  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    
    const struct BattleUnit *battle_unit =
	(struct BattleUnit *) defense_system_type[i];
    unit = &army->defenseSystems[i];    // let unit point to actual values
    double dice = (double)myrand()/(double)100;
 
    double damageMultiplicator = 1;

    if(dice < unit->criticalDamageProbability){
      damageMultiplicator = 2;
    }else if(dice < (unit->heavyDamageProbability + unit->criticalDamageProbability)){
      damageMultiplicator = 1.5;
    }
    


    army->acc_range +=
      unit->acc_range =
        damageMultiplicator *
	army->religion_bonus *
	army->relationMultiplicator *
	unit->amount_after *
	(army->effect_rangeattack_factor * battle_unit->attackRange);

    army->acc_areal +=
      unit->acc_areal =
        damageMultiplicator *
	army->religion_bonus *
	army->relationMultiplicator *
	unit->amount_after *
	(army->effect_arealattack_factor * battle_unit->attackAreal);

    army->acc_melee +=
      unit->acc_melee =
        damageMultiplicator *
	army->religion_bonus *
	army->relationMultiplicator *
	unit->amount_after *
	(army->effect_attackrate_factor * battle_unit->attackRate);

    unit->acc_defense = unit->amount_after *
      (army->effect_defenserate_factor * unit->defense);

    army->acc_hitpoints_defenseSystems +=
      unit->acc_hitpoints = unit->amount_after *
      (army->effect_size_factor * unit->hitpoints);

    army->acc_army_defense_points +=
      unit->acc_points = unit->amount_after *
        unit->points;
  }
}

static void army_copyBeforeToAfter (Army *army)
{
  int i;
  for (i = 0; i < MAX_UNIT; ++i) {
    army->units[i].amount_after = army->units[i].amount_before;
  }
  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    army->defenseSystems[i].amount_after =
      army->defenseSystems[i].amount_before;
  }
  for (i = 0; i < MAX_RESOURCE; ++i) {
    army->resourcesAfter[i] = army->resourcesBefore[i];
  }

}

static void army_propagateNormalDamage (Army *army, double damage)
{
  int i;
  Army_unit *unit;
  double damage_per_hitpoint;

  debug(DEBUG_BATTLE,
	"Called army's propagate normal damage: Damage %g Hitpoints %d",
	damage, army->acc_hitpoints_defenseSystems + army->acc_hitpoints_units);

  if (damage < 1)
    return;

  if (army->acc_hitpoints_units + army->acc_hitpoints_defenseSystems == 0)
    return;

  damage_per_hitpoint = damage /
    (army->acc_hitpoints_units + army->acc_hitpoints_defenseSystems);

  for (i = 0; i < MAX_UNIT; ++i) {
    unit = &army->units[i];

    army_unit_inflictDamage(unit,
			    unit->acc_hitpoints * damage_per_hitpoint,
			    army->effect_defenserate_factor);
  }
}

static void army_propagateRangeDamage (Army *army, double damage)
{
  int i;
  Army_unit *unit;
  double damage_per_hitpoint;

  debug(DEBUG_BATTLE,
	"Called army's propagate range damage: Damage %g Hitpoints %d",
	damage, army->acc_hitpoints_defenseSystems + army->acc_hitpoints_units);

  if (damage < 1)
    return;

  if (army->acc_hitpoints_units + army->acc_hitpoints_defenseSystems == 0)
    return;

  damage_per_hitpoint = damage /
    (army->acc_hitpoints_units + army->acc_hitpoints_defenseSystems);

  for (i = 0; i < MAX_UNIT; ++i) {
    unit = &army->units[i];

    army_unit_inflictRangeDamage(unit,
			    unit->acc_hitpoints * damage_per_hitpoint,
			    army->effect_ranged_damage_resistance_factor);
  }
}

static void army_propagateArealDamage (Army *army, double damage, int isWar, int defenderIsPlayer)
{
  int i;
  Army_unit *unit;
  double damage_per_hitpoint;

  debug(DEBUG_BATTLE,
	"Called army's propagate areal damage: Damage %g Hitpoints %d",
	damage, army->acc_hitpoints_defenseSystems);

  if (damage < 1)
    return;

  if (army->acc_hitpoints_defenseSystems == 0)
    return;

  damage_per_hitpoint = damage / army->acc_hitpoints_defenseSystems;

  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    unit = &army->defenseSystems[i];
    if(unit->warpoints > 0 && isWar == 0 && defenderIsPlayer != 0)
      continue;

    army_unit_inflictDamage(unit,
			    unit->acc_hitpoints * damage_per_hitpoint,
			    army->effect_defenserate_factor);
  }
}

//////////////////////////////////////////////////////////////////////////////
//////////////////////////// battle functions ////////////////////////////////

/**
 * intialize battle struct and all armies
 */
Battle *battle_create (int num_defenders, int num_attackers)
{
  Battle *battle = xcalloc(1, sizeof (Battle));
  int i;

  battle->size_defenders = num_defenders;
  battle->size_attackers = num_attackers;

  /////////////////////////// create unit arrays /////////////////////////////

  battle->defenders = xcalloc(num_defenders, sizeof (Army));

  for (i = 0; i < num_defenders; ++i)
    army_init(&battle->defenders[i]);

  battle->attackers = xcalloc(num_attackers, sizeof (Army));

  for (i = 0; i < num_attackers; ++i)
    army_init(&battle->attackers[i]);

  return memory_pool_add(battle, (void (*)(void *)) battle_free);
}

/**
 * free battle struct and all armies
 */
void battle_free (Battle *battle)
{
  int i;

  if (!battle)                     // do nothing, if not initialised
    return;

  if (battle->defenders) {         // free only, if set earlier
    for (i = 0; i < battle->size_defenders; ++i)
      army_destroy(&battle->defenders[i]);
    free(battle->defenders);
  }
  if (battle->attackers) {
    for (i = 0; i < battle->size_attackers; ++i)
      army_destroy(&battle->attackers[i]);
    free(battle->attackers);
  }
  free(battle);
}

/**
 * recalc all battle values for all armies
 */
void battle_recalc (Battle *battle)
{
  int i;

  // reset attacker and defender battle values to zero

  battle->attackers_acc_range = 0;
  battle->attackers_acc_areal = 0;
  battle->attackers_acc_melee = 0;

  battle->attackers_acc_hitpoints_units = 0;
  battle->attackers_acc_hitpoints_defenseSystems = 0;

  battle->defenders_acc_range = 0;
  battle->defenders_acc_areal = 0;
  battle->defenders_acc_melee = 0;

  battle->defenders_acc_hitpoints_units = 0;
  battle->defenders_acc_hitpoints_defenseSystems = 0;

  battle->attackers_acc_points = 0;
  battle->defenders_acc_units_points = 0;
  battle->defenders_acc_defense_points = 0;

  // recalc each army and accumulate values into battle values

  for (i=0; i < battle->size_defenders; ++i) {
    army_recalc(&battle->defenders[i]);   // recalc defenders

    battle->defenders_acc_range +=       // and accumulate their values
      battle->defenders[i].acc_range;
    battle->defenders_acc_areal +=
      battle->defenders[i].acc_areal;
    battle->defenders_acc_melee +=
      battle->defenders[i].acc_melee;

    battle->defenders_acc_hitpoints_units +=
      battle->defenders[i].acc_hitpoints_units;
    battle->defenders_acc_hitpoints_defenseSystems +=
      battle->defenders[i].acc_hitpoints_defenseSystems;

    battle->defenders_acc_units_points +=
	battle->defenders[i].acc_army_units_points;

    battle->defenders_acc_defense_points +=
	battle->defenders[i].acc_army_defense_points;
  }
  for (i=0; i < battle->size_attackers; ++i) {// recalc attackers
    army_recalc(&battle->attackers[i]);

    battle->attackers_acc_range +=       // and accumulate their values
      battle->attackers[i].acc_range;
    battle->attackers_acc_areal +=
      battle->attackers[i].acc_areal;
    battle->attackers_acc_melee +=
      battle->attackers[i].acc_melee;

    battle->attackers_acc_hitpoints_units +=
      battle->attackers[i].acc_hitpoints_units;
    battle->attackers_acc_hitpoints_defenseSystems +=
      battle->attackers[i].acc_hitpoints_defenseSystems;

    battle->attackers_acc_points +=
	battle->attackers[i].acc_army_units_points;
  }
}

/**
 * copy values from before battle to after battle
 */
void battle_copyBeforeToAfter (Battle *battle)
{
  int i;

  for (i=0; i < battle->size_defenders; ++i)
    army_copyBeforeToAfter(&battle->defenders[i]);
  for (i=0; i < battle->size_attackers; ++i)
    army_copyBeforeToAfter(&battle->attackers[i]);
}

void battle_rememberBattleValues (Battle *battle)
{
  battle->size_defenders_before = battle->size_defenders;
  battle->size_attackers_before = battle->size_attackers;

  battle->attackers_acc_hitpoints_units_before =
    battle->attackers_acc_hitpoints_units;
  battle->attackers_acc_hitpoints_defenseSystems_before =
     battle->attackers_acc_hitpoints_defenseSystems;

  battle->attackers_acc_range_before = battle->attackers_acc_range;
  battle->attackers_acc_areal_before = battle->attackers_acc_areal;
  battle->attackers_acc_melee_before = battle->attackers_acc_melee;

  battle->defenders_acc_hitpoints_units_before =
    battle->defenders_acc_hitpoints_units;
  battle->defenders_acc_hitpoints_defenseSystems_before =
    battle->defenders_acc_hitpoints_defenseSystems;

  battle->defenders_acc_range_before = battle->defenders_acc_range;
  battle->defenders_acc_areal_before = battle->defenders_acc_areal;
  battle->defenders_acc_melee_before = battle->defenders_acc_melee;

  battle->defenders_acc_units_points_before = battle->defenders_acc_units_points;
  battle->defenders_acc_defense_points_before = battle->defenders_acc_defense_points;
  battle->attackers_acc_points_before = battle->attackers_acc_points;
}

// copy army values

static void battle_propagateNormalDamage (Battle *battle, int array_flag,
					  double damage)
{
  int i;
  int size;
  Army* armies;
  int acc_hitpoints;
  double damage_per_hitpoint;

  if (damage < 1)
   return;

  if (array_flag == FLAG_DEFENDER) {
    size = battle->size_defenders;
    armies = battle->defenders;
    acc_hitpoints =
      battle->defenders_acc_hitpoints_units +
      battle->defenders_acc_hitpoints_defenseSystems;
  }
  else {
    size = battle->size_attackers;
    armies = battle->attackers;
    acc_hitpoints =
      battle->attackers_acc_hitpoints_units +
      battle->attackers_acc_hitpoints_defenseSystems;
  }

  debug(DEBUG_BATTLE,
	"Called battle's propagate normal damage: Damage %g Hitpoints %d",
	damage, acc_hitpoints);

  if (acc_hitpoints == 0)
    return;

  damage_per_hitpoint = damage / acc_hitpoints;

  for (i = 0; i < size; ++i)
    army_propagateNormalDamage(&armies[i],
			       damage_per_hitpoint *
			       (armies[i].acc_hitpoints_units +
				armies[i].acc_hitpoints_defenseSystems));

}

static void battle_propagateRangeDamage (Battle *battle, int array_flag,
					  double damage)
{
  int i;
  int size;
  Army* armies;
  int acc_hitpoints;
  double damage_per_hitpoint;

  if (damage < 1)
   return;

  if (array_flag == FLAG_DEFENDER) {
    size = battle->size_defenders;
    armies = battle->defenders;
    acc_hitpoints =
      battle->defenders_acc_hitpoints_units +
      battle->defenders_acc_hitpoints_defenseSystems;
  }
  else {
    size = battle->size_attackers;
    armies = battle->attackers;
    acc_hitpoints =
      battle->attackers_acc_hitpoints_units +
      battle->attackers_acc_hitpoints_defenseSystems;
  }

  debug(DEBUG_BATTLE,
	"Called battle's propagate range damage: Damage %g Hitpoints %d",
	damage, acc_hitpoints);

  if (acc_hitpoints == 0)
    return;

  damage_per_hitpoint = damage / acc_hitpoints;

  for (i = 0; i < size; ++i)
    army_propagateRangeDamage(&armies[i],
      damage_per_hitpoint *
      (armies[i].acc_hitpoints_units +
       armies[i].acc_hitpoints_defenseSystems));
}

static void battle_propagateArealDamage (Battle *battle, int array_flag,
					 double damage)
{
  int i;
  int size;
  Army* armies;
  int acc_hitpoints;
  double damage_per_hitpoint;

  if (damage < 1)
    return ;

  if (array_flag == FLAG_DEFENDER) {
    size = battle->size_defenders;
    armies = battle->defenders;
    acc_hitpoints = battle->defenders_acc_hitpoints_defenseSystems;
  }
  else {
    size = battle->size_attackers;
    armies = battle->attackers;
    acc_hitpoints = battle->attackers_acc_hitpoints_defenseSystems;
  }

  debug(DEBUG_BATTLE,
	"Called battle's propagate areal damage: Damage %g Hitpoints %d",
	damage, acc_hitpoints);

  if (acc_hitpoints == 0)
    return;

  damage_per_hitpoint = damage / acc_hitpoints;

  for (i = 0; i < size; ++i)
    army_propagateArealDamage(&armies[i],
			      damage_per_hitpoint *
			      (armies[i].acc_hitpoints_defenseSystems), battle->isWar, battle->defenderIsPlayer);

}

//////////////////////////////////////////////////////////////////////////////
//////////////////////////// calculate battle ////////////////////////////////

/**
 * battle has three rounds: 1. range combat, 2. areal and melee combat rounds
 *                          3. steal resources
 *
 * this function calculates the inflicted damage in the first two rounds
 * and calls the propagation function approprietly. this is the right
 * place to modify the attack values and inflicted damages by chance
 * or every other method.
 */
Battle *calcBattleResult (Battle *battle, const struct Cave *cave, int get_resources)
{
  double q;
  int i, j;
  int round;

  debug(DEBUG_BATTLE, "entering calc battle");

  battle_copyBeforeToAfter(battle);     // intialize survivors

  debug(DEBUG_BATTLE, "call recalc");

  battle_recalc(battle);                // calculate all battle - values
  battle_rememberBattleValues(battle);

  ///////////// first round: range battle ///////////////////////////////////

  // range_battle

  debug(DEBUG_BATTLE, "starting first battle round");

  debug(DEBUG_BATTLE, "Attacker: Range %d Hitpoints %d, "
		      "Defender: Range %d Hitpoints-Units %d Hitpoints-DS %d",
	battle->attackers_acc_range,
	battle->attackers_acc_hitpoints_defenseSystems +
	battle->attackers_acc_hitpoints_units,
	battle->defenders_acc_range,
	battle->defenders_acc_hitpoints_units,
	battle->defenders_acc_hitpoints_defenseSystems);

  battle_propagateRangeDamage(battle, FLAG_DEFENDER,
			       battle->attackers_acc_range
			       * HIT_PROBABILITY_RANGE);
  battle_propagateRangeDamage(battle, FLAG_ATTACKER,
			       battle->defenders_acc_range
			       * HIT_PROBABILITY_RANGE);

  battle_recalc(battle);              // recalculate the survivors' values

  ///////////// second round: areal damage and melee combat /////////////////

  debug(DEBUG_BATTLE, "starting second battle round");

  // areal damage (only attackers can inflict such damage)

  for (round = 0; round < BATTLE_ROUNDS; ++round) {

  debug(DEBUG_BATTLE, "Attacker: Areal %d Hitpoints(DS) %d, "
		      "Defender: Areal %d Hitpoints(DS) %d",
	battle->attackers_acc_areal,
	battle->attackers_acc_hitpoints_defenseSystems,
	battle->defenders_acc_areal,
	battle->defenders_acc_hitpoints_defenseSystems);

  battle_propagateArealDamage(battle, FLAG_DEFENDER,
			      battle->attackers_acc_areal
			      * HIT_PROBABILITY_AREAL);

   // calculate the "overpower" modifier.

   if (battle->attackers_acc_hitpoints_units +
       battle->attackers_acc_hitpoints_defenseSystems == 0)
   {
     q = 1000;
   }
   else if (battle->defenders_acc_hitpoints_units +
            battle->defenders_acc_hitpoints_defenseSystems == 0)
   {
     q = 0.001;
   }
   else
   {
     q = (double) (battle->defenders_acc_hitpoints_units
                 + battle->defenders_acc_hitpoints_defenseSystems) /
         (double) (battle->attackers_acc_hitpoints_units
                 + battle->attackers_acc_hitpoints_defenseSystems);
   }

   battle->overpower_factor = q;

  // melee damage

  debug(DEBUG_BATTLE, "Attacker: Melee %d Hitpoints %d, "
		      "Defender: Melee %d Hitpoints %d, q:(Def/Att) %g",
	battle->attackers_acc_melee,
	battle->attackers_acc_hitpoints_defenseSystems +
	battle->attackers_acc_hitpoints_units,
	battle->defenders_acc_melee,
	battle->defenders_acc_hitpoints_defenseSystems +
	battle->defenders_acc_hitpoints_units, q);

  battle_propagateNormalDamage(battle, FLAG_DEFENDER,
             sqrt(1/q) * battle->attackers_acc_melee
		       * HIT_PROBABILITY_MELEE);
  battle_propagateNormalDamage(battle, FLAG_ATTACKER,
             sqrt(q) * battle->defenders_acc_melee
		     * HIT_PROBABILITY_MELEE);

  battle_recalc(battle);                // recalculate the survivors' values

  }  // iterate battle rounds

  if (!cave) return battle;		/* TODO integrate test_calc better */

  ///////////// cleanup: who has won? ///////////////////////////////////////

  debug(DEBUG_BATTLE,
	"Survivors: Attacker: Hitpoints %d, Defender: Hitpoints %d",
	battle->attackers_acc_hitpoints_defenseSystems +
	battle->attackers_acc_hitpoints_units,
	battle->defenders_acc_hitpoints_defenseSystems +
	battle->defenders_acc_hitpoints_units);

  if (battle->attackers_acc_hitpoints_units +
      battle->attackers_acc_hitpoints_defenseSystems == 0)
  {
    q = 10;
  }
  else
  {
    q = (double) (battle->defenders_acc_hitpoints_units
		+ battle->defenders_acc_hitpoints_defenseSystems) /
	(double) (battle->attackers_acc_hitpoints_units
		+ battle->attackers_acc_hitpoints_defenseSystems);
  }

  battle->winner = q < 1 ? FLAG_ATTACKER : FLAG_DEFENDER;

  ///////////// steal resources /////////////////////////////////////////////
  // ACHTUNG: hier ist 1 angreifer EINGEBRANNT!
  // DEFENDER 0 is the cave owner's army  EINGEBRANNT

  if (battle->winner == FLAG_ATTACKER)
    /* get_resources zeigt an ob man resis mitnehmen darf */
    /* gewonnen, daher bekommt man AQ * 0.5 jeder Ressource des Verteidigers */
    for (i = 0; i < MAX_RESOURCE; ++i) {
      int capacity = 0;
      const struct Resource *resource = (struct Resource *) resource_type[i];
      int safe_storage = function_eval(resource->safeStorage, cave);
      int res_attacker = battle->attackers[0].resourcesBefore[i];
      int res_defender = battle->defenders[0].resourcesBefore[i];

      for (j = 0; j < MAX_UNIT; ++j)
	capacity += battle->attackers[0].units[j].amount_after *
		    ((struct Unit *) unit_type[j])->encumbranceList[i];

      if (!get_resources && res_defender > safe_storage) {
	int amount = (1-q) * 0.5 * res_defender;	/* steal amount */

	if (res_defender - amount < safe_storage)
	  amount = res_defender - safe_storage;
	if (res_attacker + amount > capacity)
	  amount = capacity - res_attacker;

	battle->attackers[0].resourcesAfter[i] += amount;
	battle->defenders[0].resourcesAfter[i] -= amount;
      }
    }
  else
    /* verloren, daher verliert man alle Ressourcen */
    for (i = 0; i < MAX_RESOURCE; ++i) {
      battle->attackers[0].resourcesAfter[i] = 0;
      battle->defenders[0].resourcesAfter[i] +=
	battle->attackers[0].resourcesBefore[i];
    }

  return battle;
}
