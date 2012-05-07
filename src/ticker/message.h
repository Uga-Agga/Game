/*
 * message.h - generate ticker reports to players
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _MESSAGE_H_
#define _MESSAGE_H_

#include "artefact.h"
#include "calc_battle.h"
#include "cave.h"
#include "database.h"
#include "logging.h"
#include "wonder_rules.h"

#define MSG_CLASS_QUEST		1
#define MSG_CLASS_VICTORY	2
#define MSG_CLASS_INFO		3
#define MSG_CLASS_COMPLETED	4
#define MSG_CLASS_TRADE		6
#define MSG_CLASS_RETURN	7
#define MSG_CLASS_WONDER	9
#define MSG_CLASS_USER		10
#define MSG_CLASS_SPY_REPORT	11
#define MSG_CLASS_ARTEFACT	12
#define MSG_CLASS_DEFEAT	20
#define MSG_CLASS_WEATHER	25
#define MSG_CLASS_TRIBEWONDER	26
#define MSG_CLASS_RANDOMEVENT	27
#define MSG_CLASS_HERO 28
#define MSG_CLASS_UGA_AGGA	99
#define MSG_CLASS_ANNOUNCE	1001
#define ARTEFACT_SPY_PROBABILITY 1

struct ReportEntity
{
  const struct GameObject *object;
  double value;
};

struct SpyReportReturnStruct
{
  double value;
  int artefactID;
};

extern void message_set_log_handler (log_handler_t *handler);

extern void trade_report (db_t *database,
	const struct Cave *cave1, const struct Player *player1,
	const struct Cave *cave2, const struct Player *player2,
	const int resources[], const int units[], int artefact, int heroID);

extern void return_report (db_t *database,
    const struct Cave *cave1, const struct Player *player1,
    const struct Cave *cave2, const struct Player *player2,
    const int resources[], const int units[], int artefact, int heroID);

extern void battle_report (db_t *database,
    const struct Cave *cave1, const struct Player *player1,
    const struct Cave *cave2, const struct Player *player2,
    const Battle *result, int artefact, int lost,
    int change_owner, int takeover_multiplier,
    const struct Relation *relation1,
    const struct Relation *relation2,
    int show_warpoints, int attacker_warpoints, int defender_warpoints,
    int heroID, int hero_points_attacker, int hero_points_defender);

extern void protected_report (db_t *database,
    const struct Cave *cave1, const struct Player *player1,
    const struct Cave *cave2, const struct Player *player2);

extern struct SpyReportReturnStruct spy_report (db_t *database,
    struct Cave *cave1, const struct Player *player1,
    struct Cave *cave2, const struct Player *player2,
    const int resources[], const int units[], int *artefact);


extern void artefact_report (db_t *database,
    const struct Cave *cave, const struct Player *player,
    const char *artefact_name);

extern void hero_report (db_t *database,
    const struct Cave *cave, const struct Player *player);

extern void artefact_merging_report (db_t *database,
    const struct Cave *cave, const struct Player *player,
    const struct Artefact *key_artefact,
    const struct Artefact *lock_artefact,
    const struct Artefact *result_artefact);

extern void wonder_report (db_t *database,
    const struct Player *caster,
    const struct Cave *cave, const struct Player *target,
    const struct WonderImpact *impact,
    const struct ReportEntity *values, int num);

extern void merchant_report (db_t *database,
    const struct Player *caster,
    const struct Cave *cave, const struct Player *target,
    const struct WonderImpact *impact,
    const struct ReportEntity *values, int num);

extern void wonder_end_report (db_t *database,
    const struct Player *caster,
    const struct Cave *cave, const struct Player *target,
    const struct WonderImpact *impact,
    const struct ReportEntity *values, int num);

extern void wonder_extend_report (db_t *database,
    const struct Player *caster,
    const struct Cave *cave, const struct Player *target,
    const struct Wonder *wonder,
    const struct WonderImpact *impact);

#endif /* _MESSAGE_H_ */
