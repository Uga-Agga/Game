/*
 * event_handler.h - event handler functions
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _EVENT_HANDLER_H_
#define _EVENT_HANDLER_H_

#include "database.h"

struct EventTable
{
    const char *table;
    const char *id_field;
    void (*handler)(db_t *database, db_result_t *result);
};

extern const struct EventTable eventTableList[];
extern const int eventTableSize;

/*
 * Copy all sciences from the player table to all caves of a player.
 */
extern void science_update_caves (db_t *database, int player_id);

extern void artefact_handler (db_t *database, db_result_t *result);
extern void building_handler (db_t *database, db_result_t *result);
extern void defense_handler (db_t *database, db_result_t *result);
extern void movement_handler (db_t *database, db_result_t *result);
extern void science_handler (db_t *database, db_result_t *result);
extern void unit_handler (db_t *database, db_result_t *result);
extern void wonderEnd_handler (db_t *database, db_result_t *result);
extern void wonder_handler (db_t *database, db_result_t *result);
extern void weatherEnd_handler (db_t *database, db_result_t *result);
extern void weather_handler (db_t *database, db_result_t *result);
extern void trade_handler (db_t *database, db_result_t *result);

#endif /* _EVENT_HANDLER_H_ */
