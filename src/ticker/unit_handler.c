/*
 * unit_handler.c - process unit events
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
 * This function is called to update a unit's entry in the database.
 * The unit number is increased by quantity, result contains information
 * about which cave the units belong to and the unit's type and quantity.
 */
void unit_handler (db_t *database, db_result_t *result)
{
    int objectID;
    int caveID;
    int quantity;

    debug(DEBUG_TICKER, "entering function unit_handler()");

    /* get unit and cave id */
    objectID = db_result_get_int(result, "unitID");
    caveID   = db_result_get_int(result, "caveID");

    /* get unit quantity */
    quantity = db_result_get_int(result, "quantity");

    db_query(database, "UPDATE " DB_TABLE_CAVE " SET %s = %s + %d"
		       " WHERE caveID = %d",
	     unit_type[objectID]->dbFieldName,
	     unit_type[objectID]->dbFieldName,
	     quantity, caveID);

    debug(DEBUG_TICKER, "leaving function unit_handler()");
}
