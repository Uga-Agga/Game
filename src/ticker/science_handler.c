/*
 * science_handler.c - process science events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "cave.h"
#include "database.h"
#include "event_handler.h"
#include "except.h"
#include "logging.h"
#include "memory.h"
#include "ticker.h"

/*
 * Copy all sciences from the player table to all caves of a player.
 */
void science_update_caves (db_t *database, int player_id)
{
    struct Player player;
    dstring_t *ds;
    int type;

    /* get the player data */
    get_player_info(database, player_id, &player);

    ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

    for (type = 0; type < MAX_SCIENCE; ++type)
	dstring_append(ds, "%s%s = %d", type > 0 ? "," : "",
		science_type[type]->dbFieldName, player.science[type]);

    dstring_append(ds, " WHERE playerID = %d", player_id);

    db_query_dstring(database, ds);
}

/*
 * This function is called to update a science entry in the database.
 * The science gets improved one level, result contains information
 * about which player the science belongs to and the science type.
 */
void science_handler (db_t *database, db_result_t *result)
{
    int objectID;
    int playerID;

    debug(DEBUG_TICKER, "entering function science_handler()");

    /* get science and player id */
    objectID = db_result_get_int(result, "scienceID");
    playerID = db_result_get_int(result, "playerID");

    db_query(database, "UPDATE " DB_TABLE_PLAYER " SET %s = %s + 1"
		       " WHERE playerID = %d",
	     science_type[objectID]->dbFieldName,
	     science_type[objectID]->dbFieldName,
	     playerID);

    science_update_caves(database, playerID);

    debug(DEBUG_TICKER, "leaving function science_handler()");
}
