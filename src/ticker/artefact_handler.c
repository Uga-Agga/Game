/*
 * artefact_handler.c - handle artefact events
 * Copyright (c) 2003  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
*/

#include <string.h>	   /* memset */

#include "artefact.h"	   /* artefact/artefact_class typedefs */
#include "cave.h"	   /* get_cave_info */
#include "database.h"	   /* db_result_get_int etc. */
#include "event_handler.h" /* function declaration */
#include "except.h"	   /* exception handling */
#include "logging.h"	   /* debug */
#include "message.h"	   /* artefact_report etc. */
#include "ticker.h"	   /* DEBUG_TICKER */

void artefact_handler (db_t *database, db_result_t *result)
{
  int artefactID;
  int caveID;

  struct Cave           cave;
  struct Player         player;
  struct Artefact       artefact;
  struct Artefact_class artefact_class;

  struct Artefact lock_artefact;
  struct Artefact result_artefact;

  debug(DEBUG_TICKER, "entering function artefact_handler()");

  /* get event data */
  artefactID = db_result_get_int(result, "artefactID");
  caveID     = db_result_get_int(result, "caveID");

  /* get Artefact and its class */
  get_artefact_by_id(database, artefactID, &artefact);
  get_artefact_class_by_id(database, artefact.artefactClassID, &artefact_class);
  /* XXX artefact.caveID != 0 here? */
  get_cave_info(database, artefact.caveID, &cave);

  if (cave.player_id)
    get_player_info(database, cave.player_id, &player);
  else	/* System */
    memset(&player, 0, sizeof player);

  initiate_artefact(database, artefactID);
  debug(DEBUG_TICKER, "initiated artefact");

  apply_effects_to_cave(database, artefactID);
  debug(DEBUG_TICKER, "applied effects");

  artefact_report(database, &cave, &player, artefact_class.name_initiated);

  /* merge artefacts */
  lock_artefact.artefactID   = 0;
  result_artefact.artefactID = 0;

  /* try formulas */
  if (merge_artefacts_special(database, &artefact, &lock_artefact,
			      &result_artefact) ||
      merge_artefacts_general(database, &artefact, &lock_artefact,
			      &result_artefact))
    /* formula found */
    artefact_merging_report(database, &cave, &player, &artefact,
			    &lock_artefact, &result_artefact);

  debug(DEBUG_TICKER, "leaving function artefact_handler()");
}
