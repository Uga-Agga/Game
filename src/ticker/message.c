/*
 * message.c - generate ticker reports to players
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <math.h>
#include <stdarg.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <mxml.h>

#include "database.h"
#include "hashtable.h"
#include "memory.h"
#include "message.h"
#include "template.h"
#include "hero.h"
#include "artefact.h"
#include "ticker.h"
#include "logging.h"

#define SPY_DEFENSE  0  /*  1 */
#define SPY_UNIT  1  /*  2 */
#define SPY_RESOURCE  2  /*  4 */
#define SPY_BUILDING  3  /*  8 */
#define SPY_SCIENCE  4  /* 16 */
#define MAX_SPY    5

#define drand()    (rand() / (RAND_MAX+1.0))

struct SpyInfo
{
    float chance;  /* total spy chance */
    float quality;  /* average spy quality */
    float weight;  /* ability weight */
};

static log_handler_t *msg_handler;

void message_set_log_handler (log_handler_t *handler)
{
    if (msg_handler)
      log_handler_free(msg_handler);

    msg_handler = handler;
}

static template_t *message_template (const struct Player *player,
             const char *name)
{
    static hashtable_t *templates;
    const char *locale = player->locale ? player->locale : "de_DE";
    dstring_t *file = dstring_new("reports/%s/%s.ihtml", locale, name);
    const char *filename = dstring_str(file);
    template_t *template;

    if (templates == NULL)
      templates = hashtable_new(string_hash, string_equals);

    if ((template = hashtable_lookup(templates, filename)))
    {
      template_clear(template);
    }
      else
    {
      template = template_from_file(filename);
      hashtable_insert(templates, xstrdup(filename), template);
    }

    return template;
}

static void message_new (db_t *database, int msg_class, int recipient,
       const char *subject, const char *text, char *xml)
{
    char timestamp[TIMESTAMP_LEN];

    if (recipient == PLAYER_SYSTEM) return;

    db_query(database,
      "INSERT INTO Message (senderID, recipientID, messageClass,"
      " messageSubject, messageText, messageXML, messageTime) "
      "VALUES (%d, %d, %d, '%s', '%s', '%s', '%s')",
      PLAYER_SYSTEM, recipient, msg_class, subject, text, xml,
      make_timestamp(timestamp, time(NULL)));

      log_handler_log(msg_handler, "--- from id %d to id %d ---\n%s",
        PLAYER_SYSTEM, recipient, text);
}

static char* transform_spy_values (int num, int type) {
  /*type := 0 => defenseSystem, 1 => units, 2 => resources, 3 => buildings, 4 => sciences */
  char *value = "";


  //defenseSystems
  if (type == 0) {
    if (    num <      5) value = "ein kümmerlicher Haufen";
    else if (num <     9) value = "eine Handvoll";
    else if (num <    17) value = "ein Dutzend";
    else if (num <    33) value = "ein Trupp";
    else if (num <    65) value = "eine Schar";
    else value = "eine Menge";
  }

  //units
  if (type == 1) {
    if(     num <     9) value = "eine Handvoll";
    else if (num <    17) value = "ein Dutzend";
    else if (num <    65) value = "eine Schar";
    else if (num <   257) value = "eine Kompanie";
    else if (num <   513) value = "etliche";
    else if (num <  1025) value = "ein Bataillon";
    else if (num <  2049) value = "viele";
    else if (num <  4097) value = "eine Menge";
    else if (num <  6145) value = "eine Legion";
    else if (num <  8193) value = "ein Haufen";
    else if (num < 12289) value = "ein großer Haufen";
    else if (num < 16385) value = "verdammt viele";
    else if (num < 20481) value = "Unmengen";
    else if (num < 32769) value = "eine Streitmacht";
    else if (num < 49153) value = "eine Armee";
    else if (num < 65537) value = "Heerscharen";
    else if (num < 98305) value = "eine haltlose Horde";
    else  value = "eine endlose wogende Masse";
  }

  //resources
  if (type == 2) {
    if (num < 257) value = "fast gar nichts";
    else if (num < 1025) value = "ein winziger Haufen";
    else if (num < 4097) value = "ein kleiner Haufen";
    else if (num < 16385) value = "ein beachtlicher Haufen";
    else if (num < 32769) value = "eine Menge";
    else if (num < 65537) value = "eine große Menge";
    else if (num < 131074) value = "ein Berg";
    else if (num < 262148) value = "ein großer Berg";
    else if (num < 524296) value = "ein riesiger Berg";
    else value = "unglaublicher Überfluss";
  }

  //buildings
  if (type == 3) {
    if      (num <     5) value = "ein kümmerlicher Haufen";
    else if (num <     9) value = "eine Handvoll";
    else if (num <    17) value = "ein Dutzend";
    else if (num <    33) value = "ein Trupp";
    else if (num <    65) value = "eine Schar";
    else value = "eine Menge";
  }

  //sciences
  if (type == 4) {

  }

  return value;
}

static void report_units (template_t *template, int locale_id,
        const int units[])
{
    int type;

    for (type = 0; type < MAX_UNIT; ++type)
      if (units[type] > 0)
        {
          template_iterate(template, "UNITS/UNIT");
          template_set(template, "UNITS/UNIT/name",
           unit_type[type]->name[locale_id]);
          template_set_fmt(template, "UNITS/UNIT/num", "%d", units[type]);
  }
}

static void report_resources (template_t *template, int locale_id,
            const int resources[], const int base_res[])
{
    int type;

    for (type = 0; type < MAX_RESOURCE; ++type)
    {
      int res1 = resources[type];
      int res2 = base_res ? base_res[type] : 0;

      if (res1 - res2 > 0)
      {
          template_iterate(template, "RESOURCES/RESOURCE");
          template_set(template, "RESOURCES/RESOURCE/name",
           resource_type[type]->name[locale_id]);
          template_set_fmt(template, "RESOURCES/RESOURCE/num", "%d",
               res1 - res2);
      }
    }
}

static void report_defenses (template_t *template, int locale_id,
           const int defsys[]) {
int type;

for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
  if (defsys[type] > 0) {
      template_iterate(template, "DEFENSES/DEFENSE");
      template_set(template, "DEFENSES/DEFENSE/name",
       defense_system_type[type]->name[locale_id]);
      template_set_fmt(template, "DEFENSES/DEFENSE/num", "%d",
           defsys[type]);
  }
}

static void report_buildings (template_t *template, int locale_id,
            const int building[]) {
int type;

for (type = 0; type < MAX_BUILDING; ++type)
  if (building[type] > 0) {
      template_iterate(template, "BUILDINGS/BUILDING");
      template_set(template, "BUILDINGS/BUILDING/name",
       building_type[type]->name[locale_id]);
      template_set_fmt(template, "BUILDINGS/BUILDING/num", "%d",
           building[type]);
  }
}

static void report_sciences (template_t *template, int locale_id,
           const int science[]) {
    int type;

for (type = 0; type < MAX_SCIENCE; ++type)
  if (science[type] > 0) {
      template_iterate(template, "SCIENCES/SCIENCE");
      template_set(template, "SCIENCES/SCIENCE/name",
       science_type[type]->name[locale_id]);
      template_set_fmt(template, "SCIENCES/SCIENCE/num", "%d",
           science[type]);
  }
}

static void report_battle_info (template_t *template,
        const Battle *result, int battle_flag)
{
    int acc_range, acc_fort, acc_melee, acc_size;
    float rel_bonus, god_bonus;

    if (battle_flag == FLAG_ATTACKER)
    {
      acc_range = result->attackers_acc_range_before;
      acc_fort  = result->attackers_acc_areal_before;
      acc_melee = result->attackers_acc_melee_before;
      acc_size  = result->attackers_acc_hitpoints_units_before +
            result->attackers_acc_hitpoints_defenseSystems_before;
      rel_bonus = result->attackers[0].relationMultiplicator;
      god_bonus = result->attackers[0].religion_bonus;
    }
    else
    {
      acc_range = result->defenders_acc_range_before;
      acc_fort  = result->defenders_acc_areal_before;
      acc_melee = result->defenders_acc_melee_before;
      acc_size  = result->defenders_acc_hitpoints_units_before +
            result->defenders_acc_hitpoints_defenseSystems_before;
      rel_bonus = result->defenders[0].relationMultiplicator;
      god_bonus = result->defenders[0].religion_bonus;
    }

    template_set_fmt(template, "range", "%d", acc_range);
    template_set_fmt(template, "struct", "%d", acc_fort);
    template_set_fmt(template, "melee", "%d", acc_melee);
    template_set_fmt(template, "size", "%d", acc_size);
    template_set_fmt(template, "relation", "%.2f", rel_bonus);
    template_set_fmt(template, "religion", "%.2f", god_bonus);
}

static void report_army (template_t *template, const char *name,
       const Army_unit *unit) {
  if (unit->amount_before > 0) {
    template_iterate(template, "BEFORE");
    template_set(template, "BEFORE/name", name);
    template_set_fmt(template, "BEFORE/num", "%d", unit->amount_before);

    template_iterate(template, "AFTER");
    template_set(template, "AFTER/name", name);
    template_set_fmt(template, "AFTER/num", "%d", unit->amount_after);
    if (unit->amount_after < unit->amount_before)
        template_set_fmt(template, "AFTER/DELTA/num", "%d",
             unit->amount_after - unit->amount_before);
  }
}

static void report_army_list (template_t *template, int locale_id,
            const Army *army) {
    int type;

    if (army && army->units)
      for (type = 0; type < MAX_UNIT; ++type)
          report_army(template, unit_type[type]->name[locale_id],
          &army->units[type]);

    if (army && army->defenseSystems)
      for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
          report_army(template, defense_system_type[type]->name[locale_id],
          &army->defenseSystems[type]);
}

static void report_army_table (template_t *template, int locale_id,
             const Battle *result) {

  template_context(template, "/MSG/ATTACK");
  report_army_list(template, locale_id, result->attackers);
  report_battle_info(template, result, FLAG_ATTACKER);

  template_context(template, "/MSG/DEFEND");
  report_army_list(template, locale_id, result->defenders);
  report_battle_info(template, result, FLAG_DEFENDER);

  template_context(template, "/MSG");
}

static void report_army_units (template_t *template, int locale_id,
             const Army *army) {
  if (army && army->units) {
    int units[MAX_UNIT];
    int type;

    for (type = 0; type < MAX_UNIT; ++type)
        units[type] = army->units[type].amount_before;

    report_units(template, locale_id, units);
  }
}

static void get_spy_values (struct SpyInfo *spy, const int att_units[],
          const int def_units[], const int def_defsys[]) {
  float total_spy_chance = 0;
  float anti_spy_chance = 0;
  int spy_type, type;

  memset(spy, 0, MAX_SPY * sizeof spy[0]);

  for (spy_type = 0; spy_type < MAX_SPY; ++spy_type) {
    for (type = 0; type < MAX_UNIT; ++type) {
    const struct Unit *unit = (struct Unit *) unit_type[type];

      if (unit->spyValue & 1 << spy_type) {
        float chance = unit->spyChance * att_units[type];

        spy[spy_type].quality += unit->spyQuality * chance;
        spy[spy_type].chance += chance;
      }
    }
  }

  for (type = 0; type < MAX_UNIT; ++type) {
    const struct Unit *unit = (struct Unit *) unit_type[type];
    const struct BattleUnit *battle_unit = (struct BattleUnit *) unit;

    total_spy_chance += unit->spyChance * att_units[type];
    anti_spy_chance += battle_unit->antiSpyChance * def_units[type];
  }

  for (type = 0; type < MAX_DEFENSESYSTEM; ++type) {
    const struct BattleUnit *battle_unit =
        (struct BattleUnit *) defense_system_type[type];

    anti_spy_chance += battle_unit->antiSpyChance * def_defsys[type];
  }

  for (spy_type = 0; spy_type < MAX_SPY; ++spy_type) {
    float spy_chance = spy[spy_type].chance;

    if (spy_chance > 0)
    {
        spy[spy_type].quality /= spy_chance;
        spy[spy_type].chance /= spy_chance + anti_spy_chance;
        spy[spy_type].weight = spy_chance / total_spy_chance;
    }
  }
}

static float get_spy_chance (const struct SpyInfo *spy) {
  float spy_chance = 0;
  int spy_type;

  for (spy_type = 0; spy_type < MAX_SPY; ++spy_type) {
    if (spy[spy_type].chance > spy_chance)
    spy_chance = spy[spy_type].chance;
  }

  return spy_chance;
}

static float get_spy_quality_army (const Army *army, int num) {
  float spy_quality = 0;
  int index, type;

  for (index = 0; index < num; ++index)
    if (army[index].units)
      for (type = 0; type < MAX_UNIT; ++type) {
        const struct Unit *unit = (struct Unit *) unit_type[type];

        if (army[index].units[type].amount_before > 0 &&
            unit->spyQuality > spy_quality)
            spy_quality = unit->spyQuality;
    }

  return spy_quality;
}

static float get_spy_quality_battle (const Battle *result) {
  float spy_quality =
    get_spy_quality_army(result->attackers, result->size_attackers) -
    get_spy_quality_army(result->defenders, result->size_defenders);

  return spy_quality > 0.2 ? spy_quality : 0.2;
}

static int fuzzy_value (double value, double quality, double chance) {
  int result = chance > drand() ? pow(quality, 1 - 2 * drand()) * value : 0;
  int factor = 1;

  while (result > 999) result /= 10, factor *= 10;
  return result * factor;
}

static double fuzzy_wonder_value (double value, double chance) {
  double factor = drand() * 6 - 3;

  factor = factor < 0 ? 1 / (1 - factor) : 1 + factor;

  return chance > drand() ? value * factor : 0;
}

static void report_fuzzy_size (template_t *template, const Battle *result, int defender_size_guessed) {
  float spy_quality = get_spy_quality_battle(result);

  if (spy_quality > 0)
    template_set_fmt(template, "GUESS/size", "%d", defender_size_guessed);
}

static int guess_values (int guess[], const int value[], int len,
       const struct SpyInfo *spy, int spy_type) {
  float quality = spy[spy_type].quality;
  float chance = spy[spy_type].weight * quality * 1.5;
  int result = 0;
  int type;

  for (type = 0; type < len; ++type)
    if ((guess[type] = fuzzy_value(value[type], quality, chance)))
  result = 1;

  return result;
}

static struct Cave report_spy_info (template_t *template, int locale_id,
           const struct SpyInfo *spy,
           const struct Cave *info,
           int *spyTypes)
{
  struct Cave cave;

  if (guess_values(cave.defense_system, info->defense_system,
       MAX_DEFENSESYSTEM, spy, SPY_DEFENSE)) {
    report_defenses(template, locale_id, cave.defense_system);
    spyTypes[0] = 1;
  }

  if (guess_values(cave.unit, info->unit, MAX_UNIT, spy, SPY_UNIT)) {
    report_units(template, locale_id, cave.unit);
    spyTypes[1] = 1;
  }

  if (guess_values(cave.resource, info->resource, MAX_RESOURCE, spy, SPY_RESOURCE)) {
    report_resources(template, locale_id, cave.resource, NULL);
    spyTypes[2] = 1;
  }

  if (guess_values(cave.building, info->building, MAX_BUILDING, spy, SPY_BUILDING)) {
    report_buildings(template, locale_id, cave.building);
    spyTypes[3] = 1;
  }

  if (info->player_id != PLAYER_SYSTEM &&
      guess_values(cave.science, info->science, MAX_SCIENCE, spy, SPY_SCIENCE)) {
    report_sciences(template, locale_id, cave.science);
    spyTypes[4] = 1;
  }


    /* TODO wonder effects, messages */
    /*  Bei dieser H�hle scheinen wertvolle Rohstoffe zu lagern: */
    /*  Aus sicherer Entfernung sind vage die Umrisse einiger Bauten zu
  erahnen, die anscheinend zur Verteidigung der H�hle gegen Angriffe
  errichtet worden sind: */
    /*  Beim Versuch, sich n�her an die H�hle heranzuschleichen, entdeckt
  ein Kundschafter einige gef�hrlich aussehende Gestalten: */
    /*  Eine Reihe von Geb�uden erregt eure besondere Aufmerksamkeit: */
    /*  Als eure Spione einen Gefangenen verh�ren, berichtet dieser von
  aktuellen Forschungen seines Stammes: */
    /*  Beim St�bern in den Privatgem�chern des gegnerischen Stammesf�hrers
  entdeckt Euer Spion einige Nachrichten: */

    return cave;
}

static const char *message_subject (template_t *template, const char *path,
            const struct Cave *cave) {
  const char *result;

  template_context(template, path);
  template_set(template, "cave", cave->name);
  template_set_fmt(template, "xpos", "%d", cave->xpos);
  template_set_fmt(template, "ypos", "%d", cave->ypos);
  result = template_eval(template);
  template_clear(template);

  return result;
}

/*
 * Note: This implementation relies on the fact that the movement handler
 * passes identical strings (same pointer values) for both player names if
 * the starting and destination cave belong to the same player.
 */
static void message_setup (template_t *template,
         const struct Cave *orig, const struct Player *sender,
         const struct Cave *cave, const struct Player *player)
{
  template_context(template, "MSG");
  template_set(template, "orig", orig->name);
  template_set(template, "cave", cave->name);

  if (sender->name)
    template_set(template, "sender", sender->name);
  if (player->name)
    template_set(template, "player", player->name);
  if (player->name == sender->name)
    template_set(template, "self", "");
}

static char* trade_report_xml(db_t *database,
       const struct Cave *cave1, const struct Player *player1,
       const struct Cave *cave2, const struct Player *player2,
       const int resources[], const int units[], int artefact,
       int artefact_kill, int IsSender, int heroID) {

  mxml_node_t *xml, *tradereport;
  mxml_node_t *curtime;
  mxml_node_t *source, *target, *player, *tribe;
  mxml_node_t *caveName, *xCoord, *yCoord, *caveID;
  mxml_node_t *Units, *Unit, *name, *value;
  mxml_node_t *Resources, *Resource, *Artefact;
  mxml_node_t *isSender, *hero, *heroSend, *heroDeath;

  char *xmlstring = "";
  int type = 0;

  xml = mxmlNewXML("1.0");
  tradereport = mxmlNewElement(xml, "tradereport");
  curtime = mxmlNewElement(tradereport, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));
  isSender = mxmlNewElement(tradereport, "isSender");
    mxmlNewText(isSender, 0, (const char*) (IsSender ? "true" : "false"));

  source = mxmlNewElement(tradereport, "source");
    player = mxmlNewElement(source, "playerName");
      mxmlNewText(player, 0, (char*) player1->name);
    tribe = mxmlNewElement(source, "tribe");
      mxmlNewText(tribe, 0, (char*) player1->tribe);
    caveName = mxmlNewElement(source, "caveName");
      mxmlNewText(caveName, 0, (char*) cave1->name);
    xCoord = mxmlNewElement(source, "xCoord");
      mxmlNewInteger(xCoord, (int) cave1->xpos);
    yCoord = mxmlNewElement(source, "yCoord");
      mxmlNewInteger(yCoord, (int) cave1->ypos);
    caveID = mxmlNewElement(source, "caveID");
      mxmlNewInteger(caveID, (int) cave1->cave_id);

  target = mxmlNewElement(tradereport, "target");
    player = mxmlNewElement(target, "playerName");
      mxmlNewText(player, 0, (char*) player2->name);
    tribe = mxmlNewElement(target, "tribe");
      mxmlNewText(tribe, 0, (char*) player2->tribe);
    caveName = mxmlNewElement(target, "caveName");
      mxmlNewText(caveName, 0, (char*) cave2->name);
    xCoord = mxmlNewElement(target, "xCoord");
      mxmlNewInteger(xCoord, (int) cave2->xpos);
    yCoord = mxmlNewElement(target, "yCoord");
      mxmlNewInteger(yCoord, (int) cave2->ypos);
    caveID = mxmlNewElement(target, "caveID");
      mxmlNewInteger(caveID, (int) cave2->cave_id);

  //units
  if (units) {
    Units = mxmlNewElement(tradereport, "units");
    for (type = 0; type < MAX_UNIT; ++type) {
      if (units[type] > 0) {
        Unit = mxmlNewElement(Units, "unit");
        name = mxmlNewElement(Unit, "name");
          mxmlNewText(name, 0, (char*) unit_type[type]->name[player1->locale_id]);
        value = mxmlNewElement(Unit, "value");
          mxmlNewInteger(value, (int) units[type]);
      }
    }
  }

  //resources
  if (resources) {
    Resources = mxmlNewElement(tradereport, "resources");
    for (type = 0; type < MAX_RESOURCE; ++type) {
      if (resources[type] > 0) {
        Resource = mxmlNewElement(Resources, "resource");
        name = mxmlNewElement(Resource, "name");
          mxmlNewText(name, 0, (char*) resource_type[type]->name[player1->locale_id]);
        value = mxmlNewElement(Resource, "value");
          mxmlNewInteger(value, (int) resources[type]);
      }
    }
  }

  //artefacts
  if (artefact) {
    const char *artefactName = artefact_name(database, artefact);

    if (artefact_kill) {
      Artefact = mxmlNewElement(tradereport, "artefact_destroy");
        name = mxmlNewElement(Artefact, "name");
          mxmlNewText(name, 0, (char*) artefactName);
    } else {
      Artefact = mxmlNewElement(tradereport, "artefact");
        name = mxmlNewElement(Artefact, "name");
          mxmlNewText(name, 0, (char*) artefactName);
    }
  }

  if (IsSender && heroID) {
    hero = mxmlNewElement(tradereport, "source");
      heroSend = mxmlNewElement(hero, "send");
        mxmlNewText(heroSend, 0, (const char*) (heroID>0 ? "true" : "false"));
      heroDeath = mxmlNewElement(hero, "death");
        mxmlNewText(heroDeath, 0, (const char*) (heroID<0 ? "true" : "false"));
  }

  xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);
  return xmlstring;
}

void trade_report (db_t *database,
       const struct Cave *cave1, const struct Player *player1,
       const struct Cave *cave2, const struct Player *player2,
       const int resources[], const int units[], int artefact, int artefact_kill, int heroID)
{
  template_t *tmpl_trade1 = message_template(player1, "trade1");
  template_t *tmpl_trade2 = message_template(player2, "trade2");
  const char *subject1 = message_subject(tmpl_trade1, "TITLE", cave2);
  const char *subject2 = message_subject(tmpl_trade2, "TITLE", cave2);
  char *xml = "";
  int IsSender = 0;

  message_setup(tmpl_trade1, cave1, player1, cave2, player2);
  message_setup(tmpl_trade2, cave1, player1, cave2, player2);

  if (units)
  {
    report_units(tmpl_trade1, player1->locale_id, units);
    report_units(tmpl_trade2, player2->locale_id, units);
  }

  report_resources(tmpl_trade1, player1->locale_id, resources, NULL);
  report_resources(tmpl_trade2, player2->locale_id, resources, NULL);

  if (artefact)
  {
    const char *name = artefact_name(database, artefact);

    template_set(tmpl_trade1, "ARTEFACT/artefact", name);
    template_set(tmpl_trade2, "ARTEFACT/artefact", name);
  }

  // hero: heroID = -1 --> hero was killed
  if (heroID>0) {
    template_set(tmpl_trade2, "HERO/show", "");
  }

  if (heroID<0) {
    template_set(tmpl_trade1, "HERO_DEAD/show", "");
  }

  if (cave1->player_id == cave2->player_id) {
    IsSender = 1;
  }
  xml = trade_report_xml(database, cave1, player1, cave2, player2, resources, units, artefact, artefact_kill, IsSender, heroID);
  message_new(database, MSG_CLASS_TRADE, cave2->player_id, subject2, template_eval(tmpl_trade2), xml);

  if (cave1->player_id != cave2->player_id) {
    xml = trade_report_xml(database, cave1, player1, cave2, player2, resources, units, artefact, artefact_kill, 1, heroID);
    message_new(database, MSG_CLASS_TRADE, cave1->player_id, subject1, template_eval(tmpl_trade1), xml);
  }
}

static char* return_report_xml (db_t *database,
        const struct Cave *cave1, const struct Player *player1,
        const struct Cave *cave2, const struct Player *player2,
        const int resources[], const int units[], int artefact, int heroID)
{

  mxml_node_t *xml, *returnreport;
  mxml_node_t *curtime;
  mxml_node_t *source, *target, *player, *tribe;
  mxml_node_t *caveName, *xCoord, *yCoord, *caveID;
  mxml_node_t *Units, *Unit, *name, *value;
  mxml_node_t *Resources, *Resource, *Artefact;
  mxml_node_t *hero;

  char *xmlstring = "";
  int type = 0;

  xml = mxmlNewXML("1.0");
  returnreport = mxmlNewElement(xml, "returnreport");
  curtime = mxmlNewElement(returnreport, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));

  source = mxmlNewElement(returnreport, "source");
    player = mxmlNewElement(source, "playerName");
      mxmlNewText(player, 0, (char*) player1->name);
    tribe = mxmlNewElement(source, "tribe");
      mxmlNewText(tribe, 0, (char*) player1->tribe);
    caveName = mxmlNewElement(source, "caveName");
      mxmlNewText(caveName, 0, (char*) cave1->name);
    xCoord = mxmlNewElement(source, "xCoord");
      mxmlNewInteger(xCoord, (int) cave1->xpos);
    yCoord = mxmlNewElement(source, "yCoord");
      mxmlNewInteger(yCoord, (int) cave1->ypos);
    caveID = mxmlNewElement(source, "caveID");
      mxmlNewInteger(caveID, (int) cave1->cave_id);

  target = mxmlNewElement(returnreport, "target");
    player = mxmlNewElement(target, "playerName");
      mxmlNewText(player, 0, (char*) player2->name);
    tribe = mxmlNewElement(target, "tribe");
      mxmlNewText(tribe, 0, (char*) player2->tribe);
    caveName = mxmlNewElement(target, "caveName");
      mxmlNewText(caveName, 0, (char*) cave2->name);
    xCoord = mxmlNewElement(target, "xCoord");
      mxmlNewInteger(xCoord, (int) cave2->xpos);
    yCoord = mxmlNewElement(target, "yCoord");
      mxmlNewInteger(yCoord, (int) cave2->ypos);
    caveID = mxmlNewElement(target, "caveID");
      mxmlNewInteger(caveID, (int) cave2->cave_id);

  //units
  if (units) {
    Units = mxmlNewElement(returnreport, "units");
    for (type = 0; type < MAX_UNIT; ++type) {
      if (units[type] > 0) {
        Unit = mxmlNewElement(Units, "unit");
        name = mxmlNewElement(Unit, "name");
          mxmlNewText(name, 0, (char*) unit_type[type]->name[player1->locale_id]);
        value = mxmlNewElement(Unit, "value");
          mxmlNewInteger(value, (int) units[type]);
      }
    }
  }

  //resources
  if (resources) {
    Resources = mxmlNewElement(returnreport, "resources");
    for (type = 0; type < MAX_RESOURCE; ++type) {
      if (resources[type] > 0) {
        Resource = mxmlNewElement(Resources, "resource");
        name = mxmlNewElement(Resource, "name");
          mxmlNewText(name, 0, (char*) resource_type[type]->name[player1->locale_id]);
        value = mxmlNewElement(Resource, "value");
          mxmlNewInteger(value, (int) resources[type]);
      }
    }
  }

  //artefacts
  if (artefact) {
    const char *artefactName = artefact_name(database, artefact);
    Artefact = mxmlNewElement(returnreport, "artefact");
      name = mxmlNewElement(Artefact, "name");
        mxmlNewText(name, 0, (char*) artefactName);
  }

  // hero
  hero = mxmlNewElement(returnreport, "hero");
    mxmlNewText(hero, 0, (char*) ((heroID > 0) ? "true" : "false"));


  xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);
    return xmlstring;
}

void return_report (db_t *database,
        const struct Cave *cave1, const struct Player *player1,
        const struct Cave *cave2, const struct Player *player2,
        const int resources[], const int units[], int artefact, int heroID)
{
  template_t *tmpl_return = message_template(player2, "return");
  const char *subject = message_subject(tmpl_return, "TITLE", cave2);
  char *xml = "";

  message_setup(tmpl_return, cave1, player1, cave2, player2);

  report_units(tmpl_return, player2->locale_id, units);
  report_resources(tmpl_return, player2->locale_id, resources, NULL);

  if (artefact)
    template_set(tmpl_return, "ARTEFACT/artefact",
       artefact_name(database, artefact));

  if (heroID>0) {
    template_set(tmpl_return, "HERO/show", "");
  }

  xml = return_report_xml(database, cave1, player1, cave2, player2, resources, units, artefact, heroID);

  message_new(database, MSG_CLASS_RETURN,
      cave2->player_id, subject, template_eval(tmpl_return), xml);
}

static char* battle_report_xml (db_t *database,
        const struct Cave *cave1, const struct Player *player1,
        const struct Cave *cave2, const struct Player *player2,
        const Battle *result, int artefact, int lost,
        int change_owner, int takeover_multiplier,
        const struct Relation *relation1,
        const struct Relation *relation2,
        int show_warpoints, int attacker_warpoints, int defender_warpoints, int show_defender,
        int defender_size_guessed, int IsAttacker,
        int hero_points_attacker, int hero_points_defender)
{
  mxml_node_t *xml;
  mxml_node_t *battlereport;
  mxml_node_t *attacker, *defender;
  mxml_node_t *winner, *takeover, *takeoverMultiplier, *changeOwner;
  mxml_node_t *caveName, *xCoord, *yCoord, *caveID;
  mxml_node_t *player, *tribe;
  mxml_node_t *curtime;
  mxml_node_t *units, *unit, *name, *before, *after, *delta, *guess;
  mxml_node_t *defenseSystems, *defenseSystem;
  mxml_node_t *battleValues, *range, *areal, *melee, *size, *religion, *relation;
  mxml_node_t *attackerWarpoints, *defenderWarpoints;
  mxml_node_t *plunder, *resource, *num, *resourcesLost;
  mxml_node_t *Artefact, *Lost;
  mxml_node_t *selfAttack, *isAttacker;
  mxml_node_t *hero, *points, *heal, *death;

  int type = 0;
  char *xmlstring = "";
  char rel_bonus[100];
  char god_bonus[100];
  const Army *army;
  const Army_unit *armyunit, *armydef;

  xml = mxmlNewXML("1.0");
  battlereport = mxmlNewElement(xml, "battlereport");
  curtime = mxmlNewElement(battlereport, "timestamp");
    mxmlNewInteger(curtime, (int) time(NULL));
  isAttacker = mxmlNewElement(battlereport, "isAttacker");
    mxmlNewText(isAttacker, 0, (const char*) (IsAttacker ? "true" : "false"));
  winner = mxmlNewElement(battlereport, "winner");
    mxmlNewText(winner, 0, (const char*) (result->winner == FLAG_ATTACKER ? "attacker" : "defender"));

  selfAttack = mxmlNewElement(battlereport, "selfAttack");
    mxmlNewText(selfAttack, 0, (const char*) (player1->name == player2->name ? "true" : "false"));

  takeover = mxmlNewElement(battlereport, "takeover");
    mxmlNewText(takeover, 0, (const char*) (takeover_multiplier ? "true" : "false"));

  if (takeover_multiplier) {
    takeoverMultiplier = mxmlNewElement(battlereport, "takeoverMultiplier");
      mxmlNewInteger(takeoverMultiplier, (int) takeover_multiplier);
    changeOwner = mxmlNewElement(battlereport, "changeOwner");
      mxmlNewText(changeOwner, 0, (const char*) (change_owner ? "true" : "false"));
  }

//attacker
  // header
  attacker = mxmlNewElement(battlereport, "attacker");
    player = mxmlNewElement(attacker, "playerName");
      mxmlNewText(player, 0, (char*) player1->name);
    tribe = mxmlNewElement(attacker, "tribe");
      mxmlNewText(tribe, 0, (char*) player1->tribe);
    caveName = mxmlNewElement(attacker, "caveName"),
      mxmlNewText(caveName, 0, (char*) cave1->name);
    xCoord = mxmlNewElement(attacker, "xCoord");
      mxmlNewInteger(xCoord, (int) cave1->xpos);
    yCoord = mxmlNewElement(attacker, "yCoord");
      mxmlNewInteger(yCoord, (int) cave1->ypos);
    caveID = mxmlNewElement(attacker, "caveID");
      mxmlNewInteger(caveID, (int) cave1->cave_id);

    // units
    army = result->attackers;
    if (army && army->units) {
      units = mxmlNewElement(attacker, "units");
      for (type = 0; type < MAX_UNIT; ++type) {
        armyunit = &army->units[type];
        if (armyunit->amount_before > 0) {
          unit = mxmlNewElement(units, "unit");
            name = mxmlNewElement(unit, "name");
              mxmlNewText(name, 0, (char*) unit_type[type]->name[player1->locale_id]);
            before = mxmlNewElement(unit, "before");
              mxmlNewInteger(before, (int) armyunit->amount_before);
            after = mxmlNewElement(unit, "after");
              mxmlNewInteger(after, (int) armyunit->amount_after);
            delta = mxmlNewElement(unit, "delta");
              mxmlNewInteger(delta, (int) armyunit->amount_before - armyunit->amount_after);
        }
      }
    }

    //battleValues
    sprintf(rel_bonus, "%.2lf", (float) result->attackers[0].relationMultiplicator);
    sprintf(god_bonus, "%.2lf", (float) result->attackers[0].religion_bonus);
      
    battleValues = mxmlNewElement(attacker, "battleValues");
      range = mxmlNewElement(battleValues, "range");
        mxmlNewInteger(range, (int) result->attackers_acc_range_before);
      melee = mxmlNewElement(battleValues, "melee");
        mxmlNewInteger(melee, (int) result->attackers_acc_melee_before);
      areal = mxmlNewElement(battleValues, "areal");
        mxmlNewInteger(areal, (int) result->attackers_acc_areal_before);
      size = mxmlNewElement(battleValues, "size");
        mxmlNewInteger(size, (int) result->attackers_acc_hitpoints_units_before +
                                   result->attackers_acc_hitpoints_defenseSystems_before);
      relation = mxmlNewElement(battleValues, "relation");
        mxmlNewText(relation, 0, (char*) rel_bonus);
      religion = mxmlNewElement(battleValues, "religion");
        mxmlNewText(religion, 0, (char*) god_bonus);

    //hero
    if (result->attackers->heroFights && IsAttacker) {
      hero = mxmlNewElement(attacker, "hero");
        points = mxmlNewElement(hero, "points");
          mxmlNewInteger(points, (int) hero_points_attacker);
        heal = mxmlNewElement(hero, "heal");
          mxmlNewInteger(heal, (int) abs(result->attackers_acc_hitpoints_units_before - result->attackers_acc_hitpoints_units));
        death = mxmlNewElement(hero, "death");
          mxmlNewText(death, 0, (const char*) (result->attackers_hero_died ? "true" : "false"));
    }

// defender
  // header
  defender = mxmlNewElement(battlereport, "defender");
    player = mxmlNewElement(defender, "playerName");
      mxmlNewText(player, 0, (char*) player2->name);
    tribe = mxmlNewElement(defender, "tribe");
      mxmlNewText(tribe, 0, (char*) player2->tribe);
    caveName = mxmlNewElement(defender, "caveName"),
      mxmlNewText(caveName, 0, (char*) cave2->name);
    xCoord = mxmlNewElement(defender, "xCoord");
      mxmlNewInteger(xCoord, (int) cave2->xpos);
    yCoord = mxmlNewElement(defender, "yCoord");
      mxmlNewInteger(yCoord, (int) cave2->ypos);
    caveID = mxmlNewElement(defender, "caveID");
      mxmlNewInteger(caveID, (int) cave2->cave_id);

    // guess value
    if (!show_defender) {
      float spy_quality = get_spy_quality_battle(result);
      int def_size = result->defenders_acc_hitpoints_units_before +
             result->defenders_acc_hitpoints_defenseSystems_before;

      guess = mxmlNewElement(defender, "guessSize");
        mxmlNewInteger(guess, fuzzy_value(def_size, spy_quality, 1.0) / 100 * 100);
    }

    if (show_defender) {
    // units
    army = result->defenders;
    if (army && army->units) {
      units = mxmlNewElement(defender, "units");
      for (type = 0; type < MAX_UNIT; ++type) {
        armyunit = &army->units[type];
        if (armyunit->amount_before > 0) {
          unit = mxmlNewElement(units, "unit");
            name = mxmlNewElement(unit, "name");
              mxmlNewText(name, 0, (char*) unit_type[type]->name[player1->locale_id]);
            before = mxmlNewElement(unit, "before");
              mxmlNewInteger(before, (int) armyunit->amount_before);
            after = mxmlNewElement(unit, "after");
              mxmlNewInteger(after, (int) armyunit->amount_after);
            delta = mxmlNewElement(unit, "delta");
              mxmlNewInteger(delta, (int) armyunit->amount_before - armyunit->amount_after);
        }
      }
    }

    //defense Systems
    if (army && army->defenseSystems) {
      defenseSystems = mxmlNewElement(defender, "defenseSystems");
      for (type = 0; type < MAX_DEFENSESYSTEM; ++type) {
        armydef = &army->defenseSystems[type];
        if (armydef->amount_before > 0) {
          defenseSystem = mxmlNewElement(defenseSystems, "defenseSystem");
            name = mxmlNewElement(defenseSystem, "name");
              mxmlNewText(name, 0, (char*) defense_system_type[type]->name[player1->locale_id]);
            before = mxmlNewElement(defenseSystem, "before");
              mxmlNewInteger(before, (int) armydef->amount_before);
            after = mxmlNewElement(defenseSystem, "after");
              mxmlNewInteger(after, (int) armydef->amount_after);
            delta = mxmlNewElement(defenseSystem, "delta");
              mxmlNewInteger(delta, (int) armydef->amount_before - armydef->amount_after);
        }
      }
    }

    //battleValues
    sprintf(rel_bonus, "%.2lf", (float) result->defenders[0].relationMultiplicator);
    sprintf(god_bonus, "%.2lf", (float) result->defenders[0].religion_bonus);

    battleValues = mxmlNewElement(defender, "battleValues");
      range = mxmlNewElement(battleValues, "range");
        mxmlNewInteger(range, (int) result->defenders_acc_range_before);
      melee = mxmlNewElement(battleValues, "melee");
        mxmlNewInteger(melee, (int) result->defenders_acc_melee_before);
      areal = mxmlNewElement(battleValues, "areal");
        mxmlNewInteger(areal, (int) result->defenders_acc_areal_before);
      size = mxmlNewElement(battleValues, "size");
        mxmlNewInteger(size, (int) result->defenders_acc_hitpoints_units_before +
                                   result->defenders_acc_hitpoints_defenseSystems_before);
      relation = mxmlNewElement(battleValues, "relation");
        mxmlNewText(relation, 0, (char*) rel_bonus);
      religion = mxmlNewElement(battleValues, "religion");
        mxmlNewText(religion, 0, (char*) god_bonus);
    }

    if (result->defenders->heroFights && !IsAttacker) {
      hero = mxmlNewElement(defender, "hero");
        points = mxmlNewElement(hero, "points");
          mxmlNewInteger(points, (int) hero_points_defender);
        heal = mxmlNewElement(hero, "heal");
          mxmlNewInteger(heal, (int) abs(result->defenders_acc_hitpoints_units_before - result->defenders_acc_hitpoints_units));
        death = mxmlNewElement(hero, "death");
          mxmlNewText(death, 0, (const char*) (result->defenders_hero_died ? "true" : "false"));
    }

    // warpoints
    if (show_warpoints) {
      attackerWarpoints = mxmlNewElement(battlereport, "attackerWarpoints");
        mxmlNewInteger(attackerWarpoints, (int) attacker_warpoints);
      defenderWarpoints = mxmlNewElement(battlereport, "defenderWarpoints");
        mxmlNewInteger(defenderWarpoints, (int) defender_warpoints);
    }

    // plunder
    if (result->winner == FLAG_ATTACKER) { // attacker wins and takes resources away
      plunder = mxmlNewElement(attacker, "plunder");
      for (type = 0; type < MAX_RESOURCE; ++type) {
        int res1 = result->attackers->resourcesAfter[type];
        int res2 = result->attackers->resourcesBefore[type];
        int resDelta = res1 - res2;
        if (resDelta > 0) {
          resource = mxmlNewElement(plunder, "resource");
            name = mxmlNewElement(resource, "name");
              mxmlNewText(name, 0, (char*) resource_type[type]->name[player1->locale_id]);
            if (!IsAttacker) {
              before = mxmlNewElement(resource, "before");
                mxmlNewInteger(before, result->defenders->resourcesBefore[type]);
            }
            delta = mxmlNewElement(resource, "delta");
              mxmlNewInteger(delta, (int) res1 - res2);

        }
      }
    } else { //attacker loses an leaves resources behind
      resourcesLost = mxmlNewElement(attacker, "resourcesLost");
      for (type = 0; type < MAX_RESOURCE; ++type) {
        if (result->attackers->resourcesBefore[type] > 0 ) {
          resource = mxmlNewElement(resourcesLost, "resource");
            name = mxmlNewElement(resource, "name");
              mxmlNewText(name, 0, (char*) resource_type[type]->name[player1->locale_id]);
            num = mxmlNewElement(resource, "num");
              mxmlNewInteger(num, (int) result->attackers->resourcesBefore[type]);

        }
      }
    }

    // artefact
    if (artefact) {
      const char *artefactName = artefact_name(database, artefact);
      Artefact = mxmlNewElement(battlereport, "artefact");
        name = mxmlNewElement(Artefact, "name");
          mxmlNewText(name, 0, (char*) artefactName);
        Lost = mxmlNewElement(Artefact, "lost");
          mxmlNewText(Lost, 0, (const char*) (lost ? "true" : "false"));
    }

  xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);

  return xmlstring;
}


void battle_report (db_t *database,
        const struct Cave *cave1, const struct Player *player1,
        const struct Cave *cave2, const struct Player *player2,
        const Battle *result, int artefact, int lost,
        int change_owner, int takeover_multiplier,
        const struct Relation *relation1,
        const struct Relation *relation2,
        int show_warpoints, int attacker_warpoints, int defender_warpoints,
        int heroID, int hero_points_attacker, int hero_points_defender)
{
  template_t *template1, *template2;
  const char *subject1, *subject2;
  int msg_class1 = MSG_CLASS_DEFEAT;
  int msg_class2 = MSG_CLASS_VICTORY;
  char *xml1, *xml2;

  if (takeover_multiplier)
  {
    template1 = message_template(player1, "takeover1");
    template2 = message_template(player2, "takeover2");
  }
  else
  {
    template1 = message_template(player1, "battle1");
    template2 = message_template(player2, "battle2");
  }

  if (result->winner == FLAG_ATTACKER) {
    msg_class1 = MSG_CLASS_VICTORY;
    msg_class2 = MSG_CLASS_DEFEAT;
    subject1 = message_subject(template1, "TITLE_WIN", cave2);
    subject2 = message_subject(template2, "TITLE_LOSE", cave2);
  } else {
    subject1 = message_subject(template1, "TITLE_LOSE", cave2);
    subject2 = message_subject(template2, "TITLE_WIN", cave2);
  }

  message_setup(template1, cave1, player1, cave2, player2);
  message_setup(template2, cave1, player1, cave2, player2);

  if (result->winner == FLAG_ATTACKER) {
    template_set(template1, "att_won", "");
    template_set(template2, "att_won", "");

    if (change_owner) {
        template_set(template1, "takeover", "");
        template_set(template2, "takeover", "");
    }
  }

  if (show_warpoints)
  {
    template_set_fmt(template1, "WARPOINTS/PointsOwn", "%d",
     attacker_warpoints);
    template_set_fmt(template1, "WARPOINTS/PointsOther", "%d",
     defender_warpoints);
    template_set_fmt(template2, "WARPOINTS/PointsOwn", "%d",
     defender_warpoints);
    template_set_fmt(template2, "WARPOINTS/PointsOther", "%d",
     attacker_warpoints);
  }

  template_set_fmt(template1, "factor", "%d", takeover_multiplier);
  template_set_fmt(template2, "factor", "%d", takeover_multiplier);

  // calculate one single estimate for the defender's size for all cases where it's needed
  float spy_quality = get_spy_quality_battle(result);
  int def_size = result->defenders_acc_hitpoints_units_before +
     result->defenders_acc_hitpoints_defenseSystems_before;
  int defender_size_guessed = fuzzy_value(def_size, spy_quality, 1.0) / 100 * 100;

  /* attackers_acc_hitpoints_units is actually the army size */
  if (result->attackers_acc_hitpoints_units == 0) {
    report_fuzzy_size(template1, result, defender_size_guessed);
    report_army_units(template1, player1->locale_id, result->attackers);
    xml1 = battle_report_xml(database,
                cave1, player1,
                cave2, player2,
                result, artefact, lost,
                change_owner, takeover_multiplier,
                relation1,
                relation2,
                show_warpoints, attacker_warpoints, defender_warpoints, 0, defender_size_guessed, 1,
                hero_points_attacker, hero_points_defender);
  } else {
    report_army_table(template1, player1->locale_id, result);
    xml1 = battle_report_xml(database,
                cave1, player1,
                cave2, player2,
                result, artefact, lost,
                change_owner, takeover_multiplier,
                relation1,
                relation2,
                show_warpoints, attacker_warpoints, defender_warpoints, 1, defender_size_guessed, 1,
                hero_points_attacker, hero_points_defender);
  }

  report_army_table(template2, player2->locale_id, result);
  xml2 = battle_report_xml(database,
              cave1, player1,
              cave2, player2,
              result, artefact, lost,
              change_owner, takeover_multiplier,
              relation1,
              relation2,
              show_warpoints, attacker_warpoints, defender_warpoints, 1, defender_size_guessed, 0,
              hero_points_attacker, hero_points_defender);

  if (result->winner == FLAG_ATTACKER) {
    report_resources(template1, player1->locale_id,
     result->attackers->resourcesBefore, NULL);
    report_resources(template2, player2->locale_id,
     result->defenders->resourcesBefore, NULL);
  }

  template_context(template1, "PLUNDER");
  template_context(template2, "PLUNDER");

  if (result->winner == FLAG_ATTACKER) {
    report_resources(template1, player1->locale_id,
     result->attackers->resourcesAfter,
     result->attackers->resourcesBefore);
    report_resources(template2, player2->locale_id,
     result->attackers->resourcesAfter,
     result->attackers->resourcesBefore);
  } else {
    report_resources(template1, player1->locale_id,
     result->attackers->resourcesBefore, NULL);
    report_resources(template2, player2->locale_id,
     result->attackers->resourcesBefore, NULL);
  }
  template_context(template1, "/MSG");
  template_context(template2, "/MSG");

  if (artefact) {
    const char *name = artefact_name(database, artefact);

    if (!lost) {
        template_set(template1, "ARTEFACT/artefact", name);
        template_set(template2, "ARTEFACT/artefact", name);
    } else {
        template_set(template1, "ARTEFACT_LOST/artefact", name);
        template_set(template2, "ARTEFACT_LOST/artefact", name);
    }
  }

  // hero attacker message
  if (result->attackers->heroFights) {
    if (result->attackers_hero_died != 0) {
      template_set(template1, "HERO/hero_dead", "");
    }

    template_set_fmt(template1, "HERO/hero_points_attacker", "%d", hero_points_attacker);
    template_set_fmt(template1, "HERO/healPoints_attacker", "%d", abs(result->attackers_acc_hitpoints_units_before - result->attackers_acc_hitpoints_units));
  }

  // hero defender message
  if (result->defenders->heroFights) {
    if (result->defenders_hero_died != 0) {
      template_set(template2, "HERO/hero_dead", "");
    }

    template_set_fmt(template2, "HERO/hero_points_defender", "%d", hero_points_defender);
    template_set_fmt(template2, "HERO/healPoints_defender", "%d", abs(result->defenders_acc_hitpoints_units_before - result->defenders_acc_hitpoints_units));
  }

  message_new(database, msg_class1, cave1->player_id,
  subject1, template_eval(template1), xml1);
  message_new(database, msg_class2, cave2->player_id,
  subject2, template_eval(template2), xml2);
}

void protected_report (db_t *database,
           const struct Cave *cave1, const struct Player *player1,
           const struct Cave *cave2, const struct Player *player2)
{
  template_t *tmpl_protected1 = message_template(player1, "protected1");
  template_t *tmpl_protected2 = message_template(player2, "protected2");
  const char *subject1 = message_subject(tmpl_protected1, "TITLE", cave2);
  const char *subject2 = message_subject(tmpl_protected2, "TITLE", cave2);
  char *xml = "";

  message_setup(tmpl_protected1, cave1, player1, cave2, player2);
  message_setup(tmpl_protected2, cave1, player1, cave2, player2);

  message_new(database, MSG_CLASS_INFO, cave1->player_id,
  subject1, template_eval(tmpl_protected1), xml);
  message_new(database, MSG_CLASS_INFO, cave2->player_id,
  subject2, template_eval(tmpl_protected2), xml);
}

static char* spy_report_xml(db_t *database,
    const struct Cave *cave1, const struct Player *player1,
    const struct Cave *cave2, const struct Player *player2,
    const int resources[], const int units[], int artefact,
    int spyTypes[], const struct Cave cave, int stolenArtefactID,
    int stolenArtefactLost, const int dead_units[], int IsAttacker,
    const char *Status)
{

  mxml_node_t *xml;
  mxml_node_t *spyreport;
  mxml_node_t *curtime, *isAttacker, *status;
  mxml_node_t *source, *target, *player, *tribe;
  mxml_node_t *caveName, *xCoord, *yCoord;
  mxml_node_t *Units, *Unit, *name, *value;
  mxml_node_t *DefenseSystems, *DefenseSystem, *Resources, *Resource,
              *Buildings, *Building, *Sciences, *Science;
  mxml_node_t *Artefact, *Lost, *deadUnits;

  int type;
  char *xmlstring = "";

  xml = mxmlNewXML("1.0");
  spyreport = mxmlNewElement(xml, "spyreport");
  curtime = mxmlNewElement(spyreport, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));
  isAttacker = mxmlNewElement(spyreport, "isAttacker");
    mxmlNewText(isAttacker, 0, (const char*) (IsAttacker ? "true" : "false"));
  status = mxmlNewElement(spyreport, "status");
    mxmlNewText(status, 0, (char*) Status);

  if (IsAttacker) {
    source = mxmlNewElement(spyreport, "source");
      player = mxmlNewElement(source, "playerName");
        mxmlNewText(player, 0, (char*) player1->name);
      tribe = mxmlNewElement(source, "tribe");
        mxmlNewText(tribe, 0, (char*) player1->tribe);
      caveName = mxmlNewElement(source, "caveName");
        mxmlNewText(caveName, 0, (char*) cave1->name);
      xCoord = mxmlNewElement(source, "xCoord");
        mxmlNewInteger(xCoord, (int) cave1->xpos);
      yCoord = mxmlNewElement(source, "yCoord");
        mxmlNewInteger(yCoord, (int) cave1->ypos);
  }

  target = mxmlNewElement(spyreport, "target");
      player = mxmlNewElement(target, "playerName");
        mxmlNewText(player, 0, (char*) player2->name);
      tribe = mxmlNewElement(target, "tribe");
        mxmlNewText(tribe, 0, (char*) player2->tribe);
      caveName = mxmlNewElement(target, "caveName");
        mxmlNewText(caveName, 0, (char*) cave2->name);
      xCoord = mxmlNewElement(target, "xCoord");
        mxmlNewInteger(xCoord, (int) cave2->xpos);
      yCoord = mxmlNewElement(target, "yCoord");
        mxmlNewInteger(yCoord, (int) cave2->ypos);

  if (IsAttacker) {
    //defenseSystems
    if (spyTypes[0] == 1) {
      DefenseSystems = mxmlNewElement(spyreport, "defenseSystems");
      for (type = 0; type < MAX_DEFENSESYSTEM; ++type) {
        if (cave.defense_system[type] > 0) {
          DefenseSystem = mxmlNewElement(DefenseSystems, "defenseSystem");
          name = mxmlNewElement(DefenseSystem, "name");
            mxmlNewText(name, 0, (char*) defense_system_type[type]->name[player1->locale_id]);
          value = mxmlNewElement(DefenseSystem, "value");
            mxmlNewText(value, 0, (char*) transform_spy_values(cave.defense_system[type], 0));
        }
      }
    }

    //units
    if (spyTypes[1] == 1) {
      Units = mxmlNewElement(spyreport, "units");
      for (type = 0; type < MAX_UNIT; ++type) {
        if (cave.unit[type] > 0) {
          Unit = mxmlNewElement(Units, "unit");
          name = mxmlNewElement(Unit, "name");
            mxmlNewText(name, 0, (char*) unit_type[type]->name[player1->locale_id]);
          value = mxmlNewElement(Unit, "value");
            mxmlNewText(value, 0, (char*) transform_spy_values(cave.unit[type], 1));
        }
      }
    }

    //resources
    if (spyTypes[2] == 1) {
      Resources = mxmlNewElement(spyreport, "resources");
      for (type = 0; type < MAX_RESOURCE; ++type) {
        if (cave.resource[type] > 0) {
          Resource = mxmlNewElement(Resources, "resource");
          name = mxmlNewElement(Resource, "name");
            mxmlNewText(name, 0, (char*) resource_type[type]->name[player1->locale_id]);
          value = mxmlNewElement(Resource, "value");
            mxmlNewText(value, 0, (char*) transform_spy_values(cave.resource[type], 2));
        }
      }
    }

    //buildings
    if (spyTypes[3] == 1) {
      Buildings = mxmlNewElement(spyreport, "buildings");
      for (type = 0; type < MAX_BUILDING; ++type) {
        if (cave.building[type] > 0 ) {
          Building = mxmlNewElement(Buildings, "building");
          name = mxmlNewElement(Building, "name");
            mxmlNewText(name, 0, (char*) building_type[type]->name[player1->locale_id]);
          value = mxmlNewElement(Building, "value");
            mxmlNewText(value, 0, (char*) transform_spy_values(cave.building[type], 3));
        }
      }
    }

    //sciences
    if (spyTypes[4] == 1) {
      Sciences = mxmlNewElement(spyreport, "sciences");
      for (type = 0; type < MAX_SCIENCE; ++type) {
        if (cave.science[type] > 0) {
          Science = mxmlNewElement(Sciences, "science");
          name = mxmlNewElement(Science, "name");
            mxmlNewText(name, 0, (char*) science_type[type]->name[player1->locale_id]);
          value = mxmlNewElement(Science, "value");
            mxmlNewInteger(value, (int) cave.science[type]);
        }
      }
    }
  } // end IsAttacker

  if (!IsAttacker) {
    //units
    Units = mxmlNewElement(spyreport, "units");
    for (type = 0; type < MAX_UNIT; ++type) {
      if (units[type] > 0) {
        Unit = mxmlNewElement(Units, "unit");
        name = mxmlNewElement(Unit, "name");
          mxmlNewText(name, 0, (char*) unit_type[type]->name[player1->locale_id]);
        value = mxmlNewElement(Unit, "value");
          mxmlNewText(value, 0, (char*) transform_spy_values(cave.unit[type], 1));
      }
    }
  }

  // dead units
  deadUnits = mxmlNewElement(spyreport, "deadUnits");
  for (type = 0; type < MAX_UNIT; ++type) {
    if (dead_units[type] > 0) {
      Unit = mxmlNewElement(deadUnits, "deadUnit");
        name = mxmlNewElement(Unit, "name");
          mxmlNewText(name, 0, (char*) unit_type[type]->name[player1->locale_id]);
        value = mxmlNewElement(Unit, "value");
          mxmlNewInteger(value, dead_units[type]);
    }
  }

  // lost carried artefact
  if (artefact) {
    const char* ArtefactName = artefact_name(database, artefact);
        Artefact = mxmlNewElement(spyreport, "artefactLost");
          name = mxmlNewElement(Artefact, "name");
            mxmlNewText(name, 0, (char*) ArtefactName);
  }

  // stolen artefact
  if (stolenArtefactID) {
    const char* ArtefactName = artefact_name(database, stolenArtefactID);
    Artefact = mxmlNewElement(spyreport, "artefactStolen");
      name = mxmlNewElement(Artefact, "name");
        mxmlNewText(name, 0, (char*) ArtefactName);
      Lost = mxmlNewElement(Artefact, "lost");
        mxmlNewText(Lost, 0, (const char*) (stolenArtefactLost ? "true" : "false"));
  }

  xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);
  return xmlstring;
}

struct SpyReportReturnStruct spy_report (db_t *database,
       struct Cave *cave1, const struct Player *player1,
       struct Cave *cave2, const struct Player *player2,
       const int resources[], const int units[], int *artefact)
{
  struct SpyReportReturnStruct srrs;
  struct SpyInfo spy[MAX_SPY];
  template_t *tmpl_spy1 = message_template(player1, "spy1");
  template_t *tmpl_spy2 = message_template(player2, "spy2");
  const char *subject1 = message_subject(tmpl_spy1, "TITLE", cave2);
  const char *subject2 = message_subject(tmpl_spy2, "TITLE", cave2);
  double result;
  char *xml = "";
  char *status = "";
  int spyTypes[5] = {0, 0, 0, 0, 0};
  struct Cave cave;
  int artefactID = 0;
  int artefact_def = 0;
  int lostTo = 0;
  int artefact_id = 0;
  int dead_units[MAX_UNIT] = {0};
  int sendDefenderReport = 0;

  message_setup(tmpl_spy1, cave1, player1, cave2, player2);
  message_setup(tmpl_spy2, cave1, player1, cave2, player2);

  get_spy_values(spy, units, cave2->unit, cave2->defense_system);
  result = get_spy_chance(spy);

  srrs.artefactID = 0;

  if (result > drand()) {
    status = "success";

    // getting artefact? no artefact carried?
    if (cave2->artefacts > 0 && ARTEFACT_SPY_PROBABILITY > drand() && *artefact == 0) {
      artefact_def = get_artefact_for_caveID(database, cave2->cave_id, 1);

      if (artefact_def>0) {
        // artefactID -> Rückgabe der Artefact ID wenn es NICHT versprungen ist sonst 0
        // artefact_id -> dummy. Wird hier im content nicht bnötigt!
        // artefact_def -> artefactID was geklaut werden soll (wird oben random aus der Höhle gelesen)
        after_battle_change_artefact_ownership(database, FLAG_ATTACKER, &artefactID, &artefact_id, &artefact_def, cave2->cave_id, cave2, &lostTo);
        srrs.artefactID = artefactID;

        if (lostTo) {
          debug(DEBUG_ARTEFACT, "get_arte_by_spio: artefact jump from %d to %d", cave1->cave_id, lostTo);
        }

        sendDefenderReport = 1;
      }
    }

    result = 1;
    template_set(tmpl_spy1, "report", "");

    cave = report_spy_info(tmpl_spy1, player1->locale_id, spy, cave2, spyTypes);
  } else {
    if (0.5 > drand()) {
      status = "death";

      int type;

      for (type = 0; type < MAX_UNIT; ++type) {
        dead_units[type] = units[type] - (int) (units[type] * result);
      }

      template_context(tmpl_spy1, "DEAD");
      template_context(tmpl_spy2, "DEAD");
      report_units(tmpl_spy1, player1->locale_id, dead_units);
      report_units(tmpl_spy2, player2->locale_id, dead_units);
      template_context(tmpl_spy1, "/MSG");
      template_context(tmpl_spy2, "/MSG");
    } else {
      status = "escape";
      result = 1;
    }

    if (*artefact) {
      const char *lostArtefactName = artefact_name(database, *artefact);

      template_set(tmpl_spy1, "ARTEFACT/artefact", lostArtefactName);
      template_set(tmpl_spy2, "ARTEFACT/artefact", lostArtefactName);
    }

    report_units(tmpl_spy2, player2->locale_id, units);
    report_resources(tmpl_spy2, player2->locale_id, resources, NULL);

    //generate xml
    sendDefenderReport = 1;
  }

  /* Gnerate messages
   *
   * artefact_def anstatt srrs.artefactID benutzt.
   * srrs.artefactID ist 0 wenn das artefact in einer nachbarhöhle verloren gegangen ist. artefact_def hat aber noch die ursprüngliche ID
   * die für den Bericht benötigt wird
   */
  xml = spy_report_xml(database, cave1, player1, cave2, player2, resources, units, *artefact, spyTypes, cave, artefact_def, lostTo, dead_units, 1, status);
  message_new(database, MSG_CLASS_SPY_REPORT, cave1->player_id, subject1, template_eval(tmpl_spy1), xml);
  if (sendDefenderReport) {
    xml = spy_report_xml(database, cave1, player1, cave2, player2, resources, units, *artefact, spyTypes, cave, artefact_def, lostTo, dead_units, 0, status);
    message_new(database, MSG_CLASS_SPY_REPORT, cave2->player_id, subject2, template_eval(tmpl_spy2), xml);
  }

  srrs.value = result;
  return srrs;
}

void artefact_report (db_t *database,
          const struct Cave *cave, const struct Player *player,
          const char *artefact_name)
{
  template_t *tmpl_artefact = message_template(player, "artefact");
  const char *subject = message_subject(tmpl_artefact, "TITLE", cave);
  char *xml = "";

  template_context(tmpl_artefact, "MSG");
  template_set(tmpl_artefact, "cave", cave->name);
  template_set(tmpl_artefact, "artefact", artefact_name);

  message_new(database, MSG_CLASS_ARTEFACT,
      cave->player_id, subject, template_eval(tmpl_artefact), xml);
}

void artefact_merging_report (db_t *database,
            const struct Cave *cave,
            const struct Player *player,
            const struct Artefact *key_artefact,
            const struct Artefact *lock_artefact,
            const struct Artefact *result_artefact)
{
  struct Artefact_class key_artefact_class,
      lock_artefact_class,
      result_artefact_class;
  template_t *tmpl_merge = message_template(player, "merge");
  const char *subject = message_subject(tmpl_merge, "TITLE", cave);
  char *xml = "";

  /* get key artefacts class */
  get_artefact_class_by_id(database, key_artefact->artefactClassID,
         &key_artefact_class);

  /* get lock artefacts class */
  if (lock_artefact->artefactID)
    get_artefact_class_by_id(database, lock_artefact->artefactClassID,
       &lock_artefact_class);

  /* get result artefacts class */
  if (result_artefact->artefactID)
    get_artefact_class_by_id(database, result_artefact->artefactClassID,
       &result_artefact_class);

  template_context(tmpl_merge, "MSG");
  template_set(tmpl_merge, "cave", cave->name);
  template_set(tmpl_merge, "artefact", key_artefact_class.name);

  if (lock_artefact->artefactID)
    template_set(tmpl_merge, "lock_artefact", lock_artefact_class.name);
  if (result_artefact->artefactID)
    template_set(tmpl_merge, "res_artefact", result_artefact_class.name);

  message_new(database, MSG_CLASS_ARTEFACT,
      cave->player_id, subject, template_eval(tmpl_merge), xml);
}

void artefact_zero_food_report (db_t *database,
            const struct Cave *cave, const struct Player *player,
            int artefactID, int caveID)
{
  template_t *tmpl_artefact = message_template(player, "zeroFood");
  const char *subject = message_subject(tmpl_artefact, "TITLE", cave);
  char *xml = "";

  const char* ArtefactName = artefact_name(database, artefactID);

  template_context(tmpl_artefact, "MSG");
  template_set(tmpl_artefact, "artefact", ArtefactName);

  message_new(database, MSG_CLASS_ARTEFACT, cave->player_id, subject, template_eval(tmpl_artefact), xml);
}

static char* hero_report_xml (db_t *database,
          const struct Cave *cave1, const struct Player *player1)
{
  mxml_node_t *xml, *heroreport;
  mxml_node_t *curtime;
  mxml_node_t *player, *tribe;
  mxml_node_t *caveName, *xCoord, *yCoord, *caveID;

  char *xmlstring = "";

  xml = mxmlNewXML("1.0");
  heroreport = mxmlNewElement(xml, "heroreport");
  curtime = mxmlNewElement(heroreport, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));

  player = mxmlNewElement(heroreport, "playerName");
    mxmlNewText(player, 0, (char*) player1->name);
  tribe = mxmlNewElement(heroreport, "tribe");
    mxmlNewText(tribe, 0, (char*) player1->tribe);
  caveName = mxmlNewElement(heroreport, "caveName");
    mxmlNewText(caveName, 0, (char*) cave1->name);
  xCoord = mxmlNewElement(heroreport, "xCoord");
    mxmlNewInteger(xCoord, (int) cave1->xpos);
  yCoord = mxmlNewElement(heroreport, "yCoord");
    mxmlNewInteger(yCoord, (int) cave1->ypos);
  caveID = mxmlNewElement(heroreport, "caveID");
    mxmlNewInteger(caveID, (int) cave1->cave_id);


  xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);

  return xmlstring;
}
void hero_report (db_t *database,
          const struct Cave *cave, const struct Player *player)
{
  template_t *tmpl_hero = message_template(player, "hero");
  const char *subject = message_subject(tmpl_hero, "TITLE", cave);
  char *xml = "";

  template_context(tmpl_hero, "MSG");
  template_set(tmpl_hero, "cave", cave->name);

  xml = hero_report_xml(database,cave, player);

  message_new(database, MSG_CLASS_HERO, player->player_id, subject, template_eval(tmpl_hero), xml);
}

static void wonder_prepare_message (template_t *template,
            const char *message, float steal,
            const struct Cave *cave, int locale_id,
            const struct ReportEntity *values,
            int num, int message_type, mxml_node_t *xml)
{
  int index;
  char value_new[100];

  mxml_node_t *target, *caveName, *xCoord, *yCoord, *caveID;
  mxml_node_t *name, *Value;
  mxml_node_t *valuesGuessed, *wonderMessage, *effects, *effect, *stealPercentage;

  target = mxmlNewElement(xml, "target");
    caveName = mxmlNewElement(target, "caveName");
      mxmlNewText(caveName, 0, (char*) cave->name);
    xCoord = mxmlNewElement(target, "xCoord");
      mxmlNewInteger(xCoord, (int) cave->xpos);
    yCoord = mxmlNewElement(target, "yCoord");
      mxmlNewInteger(yCoord, (int) cave->ypos);
    caveID = mxmlNewElement(target, "caveID");
      mxmlNewInteger(caveID, (int) cave->cave_id);

  template_context(template, "MSG");
  template_set(template, "cave", cave->name);
  template_set(template, "wonder_message", message);

  wonderMessage = mxmlNewElement(xml, "wonderMessage");
    mxmlNewText(wonderMessage, 0, (char*) message);

  valuesGuessed = mxmlNewElement(xml, "valuesGuessed");
  if (message_type == WONDER_MESSAGE_note) {
    template_set(template, "note", "");
    mxmlNewText(valuesGuessed, 0, (char*) "true");
  } else {
    mxmlNewText(valuesGuessed, 0, (char*) "false");
  }

  effects = mxmlNewElement(xml, "effects");
  for (index = 0; index < num; ++index) {
    double value = values[index].value;

    if (message_type == WONDER_MESSAGE_note)
        value = fuzzy_wonder_value(value, 0.6);

    if (value) {
      template_iterate(template, "VALUE");
      template_set(template, "VALUE/name", values[index].object->name[locale_id]);
      template_set_fmt(template, "VALUE/amount", "%+g", value);

      sprintf(value_new, "%+g", (float) value);

      effect = mxmlNewElement(effects, "effect");
      name = mxmlNewElement(effect, "name");
        mxmlNewText(name, 0, (char*) values[index].object->name[locale_id]);
      Value = mxmlNewElement(effect, "value");
        mxmlNewText(Value, 0, (char*) value_new);
    }
  }

  if (steal > 0) {
    template_set_fmt(template, "STOLEN/steal", "%d", (int) (steal * 100));
    stealPercentage = mxmlNewElement(xml, "stealPercentage");
      mxmlNewInteger(stealPercentage, (int) (steal * 100));
  }
}

void wonder_report (db_t *database,
        const struct Player *caster,
        const struct Cave *cave, const struct Player *target,
        const struct WonderImpact *impact,
        const struct ReportEntity *values, int num)
{
  char *xmlstring = "";

  if (caster->player_id != target->player_id && impact->sourceMessageType != WONDER_MESSAGE_none) {
    mxml_node_t *xml = mxmlNewXML("1.0");
    mxml_node_t *report;
    mxml_node_t *curtime, *wonderType;
    mxml_node_t *source, *playerName, *playerTribe;
  
    // prepare xml
    report = mxmlNewElement(xml, "wonderreport");
    curtime = mxmlNewElement(report, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));
  
    wonderType = mxmlNewElement(report, "wonderType");
      mxmlNewText(wonderType, 0, (char*) "new");
  
    source = mxmlNewElement(report, "source");
      playerName = mxmlNewElement(source, "playerName");
        mxmlNewText(playerName, 0, (char*) caster->name);
      playerTribe = mxmlNewElement(source, "playerTribe");
        mxmlNewText(playerTribe, 0, (char*) caster->tribe);

    template_t *template = message_template(caster, "wonder");
    const char *subject = message_subject(template, "TITLE", cave);

    wonder_prepare_message(template, impact->sourceMessage, impact->steal, cave, caster->locale_id, values, num, impact->sourceMessageType, report);

    xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);
    mxmlDelete(xml);

    message_new(database, MSG_CLASS_WONDER, caster->player_id, subject, template_eval(template), xmlstring);
  }

  if (impact->targetMessageType != WONDER_MESSAGE_none) {
    mxml_node_t *xml = mxmlNewXML("1.0");
    mxml_node_t *report;
    mxml_node_t *curtime, *wonderType;
    mxml_node_t *source, *playerName, *playerTribe;
  
    // prepare xml
    report = mxmlNewElement(xml, "wonderreport");
    curtime = mxmlNewElement(report, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));
  
    wonderType = mxmlNewElement(report, "wonderType");
      mxmlNewText(wonderType, 0, (char*) "new");
  
    source = mxmlNewElement(report, "source");
      playerName = mxmlNewElement(source, "playerName");
        mxmlNewText(playerName, 0, (char*) caster->name);
      playerTribe = mxmlNewElement(source, "playerTribe");
        mxmlNewText(playerTribe, 0, (char*) caster->tribe);

    template_t *template = message_template(target, "wonder");
    const char *subject = message_subject(template, "TITLE", cave);

    wonder_prepare_message(template, impact->targetMessage, impact->steal, cave, target->locale_id, values, num, impact->targetMessageType, report);

    xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);
    mxmlDelete(xml);

    message_new(database, MSG_CLASS_WONDER, target->player_id, subject, template_eval(template), xmlstring);
  }
}


void merchant_report (db_t *database,
                    const struct Player *caster,
                    const struct Cave *cave, const struct Player *target,
                    const struct WonderImpact *impact,
                    const struct ReportEntity *values, int num)
{
  char *xmlstring = "";
  mxml_node_t *xml = mxmlNewXML("1.0");
  mxml_node_t *report;
  mxml_node_t *curtime, *player, *tribe;

  // prepare xml
  report = mxmlNewElement(xml, "merchantreport");
  curtime = mxmlNewElement(report, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));

  player = mxmlNewElement(report, "caster");
      mxmlNewText(player, 0, (char*) caster->name);
  tribe = mxmlNewElement(report, "tribe");
    mxmlNewText(tribe, 0, (char*) caster->tribe);


  if (impact->targetMessageType != WONDER_MESSAGE_none) {
    template_t *template = message_template(target, "merchant");
    const char *subject = message_subject(template, "TITLE", cave);

    /* TODO localize impact->targetMessage */
    wonder_prepare_message(template, impact->targetMessage, impact->steal, cave, target->locale_id, values, num, impact->targetMessageType, report);

    xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);

    message_new(database, MSG_CLASS_TRADE, target->player_id, subject, template_eval(template), xmlstring);
  }
}



void wonder_end_report (db_t *database,
      const struct Player *caster,
      const struct Cave *cave, const struct Player *target,
      const struct WonderImpact *impact,
      const struct ReportEntity *values, int num)
{
  char *xmlstring = "";

  if (caster->player_id != target->player_id && impact->sourceMessageType != WONDER_MESSAGE_none) {
    mxml_node_t *xml = mxmlNewXML("1.0");
    mxml_node_t *report;
    mxml_node_t *curtime, *wonderType, *source, *caveName, *playerTribe;

    // prepare xml
    report = mxmlNewElement(xml, "wonderEndReport");
    curtime = mxmlNewElement(report, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));

    wonderType = mxmlNewElement(report, "wonderType");
      mxmlNewText(wonderType, 0, (char*) "end");

    source = mxmlNewElement(report, "source");
      caveName = mxmlNewElement(source, "playerName");
        mxmlNewText(caveName, 0, (char*) caster->name);
      playerTribe = mxmlNewElement(source, "playerTribe");
        mxmlNewText(playerTribe, 0, (char*) caster->tribe);

    template_t *template = message_template(caster, "wonder_end");
    const char *subject = message_subject(template, "TITLE", cave);

    wonder_prepare_message(template, "", 0, cave, caster->locale_id, values, num, impact->sourceMessageType, report);

    xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);
    mxmlDelete(xml);

    message_new(database, MSG_CLASS_WONDER, caster->player_id,subject, template_eval(template), xmlstring);
  }

  if (impact->targetMessageType != WONDER_MESSAGE_none) {
    mxml_node_t *xml = mxmlNewXML("1.0");
    mxml_node_t *report;
    mxml_node_t *curtime, *wonderType, *source, *caveName, *playerTribe;

    // prepare xml
    report = mxmlNewElement(xml, "wonderEndReport");
    curtime = mxmlNewElement(report, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));

    wonderType = mxmlNewElement(report, "wonderType");
      mxmlNewText(wonderType, 0, (char*) "end");

    source = mxmlNewElement(report, "source");
      caveName = mxmlNewElement(source, "playerName");
        mxmlNewText(caveName, 0, (char*) caster->name);
      playerTribe = mxmlNewElement(source, "playerTribe");
        mxmlNewText(playerTribe, 0, (char*) caster->tribe);

    template_t *template = message_template(target, "wonder_end");
    const char *subject = message_subject(template, "TITLE", cave);

    wonder_prepare_message(template, "", 0, cave, target->locale_id, values, num, impact->targetMessageType, report);

    xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);
    mxmlDelete(xml);

    message_new(database, MSG_CLASS_WONDER, target->player_id, subject, template_eval(template), xmlstring);
  }
}

static char* wonder_extend_report_xml (db_t *database,
           const struct Player *player,
           const struct Cave *cave,
           const struct Wonder *Wonder)
{
  mxml_node_t *xml = mxmlNewXML("1.0");
  mxml_node_t *report;
  mxml_node_t *curtime, *wonderType;
  mxml_node_t *target, *caveName, *xCoord, *yCoord, *caveID;
  mxml_node_t *wonder;
  const struct GameObject *object = (struct GameObject *) Wonder;
  char *xmlstring = "";

  report = mxmlNewElement(xml, "wonderextendreport");
  curtime = mxmlNewElement(report, "timestamp");
      mxmlNewInteger(curtime, (int) time(NULL));

  wonderType = mxmlNewElement(report, "wonderType");
    mxmlNewText(wonderType, 0, (char*) "extend");

  target = mxmlNewElement(report, "target");
    caveName = mxmlNewElement(target, "caveName");
      mxmlNewText(caveName, 0, (char*) cave->name);
    xCoord = mxmlNewElement(target, "xCoord");
      mxmlNewInteger(xCoord, (int) cave->xpos);
    yCoord = mxmlNewElement(target, "yCoord");
      mxmlNewInteger(yCoord, (int) cave->ypos);
    caveID = mxmlNewElement(target, "caveID");
      mxmlNewInteger(caveID, (int) cave->cave_id);

  wonder = mxmlNewElement(report, "extendedWonderName"); mxmlNewText(wonder, 0, (char*) object->name[player->locale_id]);

  xmlstring = mxmlSaveAllocString(xml, MXML_NO_CALLBACK);

  return xmlstring;
}


static void wonder_extend_report_player (db_t *database,
           const struct Player *player,
           const struct Cave *cave,
           const struct Wonder *wonder)
{
  template_t *template = message_template(player, "wonder_extend");
  const char *subject = message_subject(template, "TITLE", cave);
  const struct GameObject *object = (struct GameObject *) wonder;
  char *xml = "";

  template_context(template, "MSG");
  template_set(template, "cave", cave->name);
  template_set(template, "wonder", object->name[player->locale_id]);

  xml = wonder_extend_report_xml (database, player, cave, wonder);

  message_new(database, MSG_CLASS_WONDER, player->player_id,
  subject, template_eval(template), xml);
}

void wonder_extend_report (db_t *database,
         const struct Player *caster,
         const struct Cave *cave, const struct Player *target,
         const struct Wonder *wonder,
         const struct WonderImpact *impact)
{
  if (caster->player_id != target->player_id && impact->sourceMessageType != WONDER_MESSAGE_none) {
    wonder_extend_report_player(database, caster, cave, wonder);
  }

  if (impact->targetMessageType != WONDER_MESSAGE_none) {
    wonder_extend_report_player(database, target, cave, wonder);
  }
}


