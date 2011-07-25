/*
 * heroRitual_handler.c - handle artefact events
 * Copyright (c) 2003  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
*/

#include <string.h>	   /* memset */

#include "hero.h"	   /* artefact/artefact_class typedefs */
#include "cave.h"	   /* get_cave_info */
#include "database.h"	   /* db_result_get_int etc. */
#include "event_handler.h" /* function declaration */
#include "except.h"	   /* exception handling */
#include "logging.h"	   /* debug */
#include "message.h"	   /* artefact_report etc. */
#include "ticker.h"	   /* DEBUG_TICKER */

void hero_handler (db_t *database, db_result_t *result)
{
  int heroID;
  int caveID;

  struct Cave           cave;
  struct Player         player;
  struct Hero       hero;

  debug(DEBUG_TICKER, "entering function hero_handler()");

  /* get event data */
  heroID = db_result_get_int(result, "heroID");
  caveID     = db_result_get_int(result, "caveID");

  /* get Hero */
  get_hero_by_id(database, heroID, &hero);
  /* XXX hero.caveID != 0 here? */
  get_cave_info(database, hero.caveID, &cave);

  if (cave.player_id)
    get_player_info(database, cave.player_id, &player);
  else	/* System */
    memset(&player, 0, sizeof player);

  reincarnate_hero(database, heroID);
  debug(DEBUG_TICKER, "initiated hero");

  apply_hero_effects_to_cave(database, heroID);
  debug(DEBUG_TICKER, "applied hero effects");

  hero_report(database, &cave, &player);


  debug(DEBUG_TICKER, "leaving function hero_handler()");
}
