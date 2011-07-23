/*
 * defense_handler.c - process defense system events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "database.h"
#include "event_handler.h"
#include "except.h"
#include "game_rules.h"
#include "logging.h"
#include "ticker.h"

/*
 * This function is called to update a building's entry in the database.
 * The building gets improved one level, result contains information about
 * which cave the building belongs to and the building's type.
 */
void defense_handler (db_t *database, db_result_t *result)
{
    int objectID;
    int caveID;

    debug(DEBUG_TICKER, "entering function defense_handler()");

    /* get defense system and cave id */
    objectID = db_result_get_int(result, "defenseSystemID");
    caveID   = db_result_get_int(result, "caveID");

    db_query(database, "UPDATE " DB_TABLE_CAVE " SET %s = %s + 1"
		       " WHERE caveID = %d",
	     defense_system_type[objectID]->dbFieldName,
	     defense_system_type[objectID]->dbFieldName,
	     caveID);

    debug(DEBUG_TICKER, "leaving function defense_handler()");
}
