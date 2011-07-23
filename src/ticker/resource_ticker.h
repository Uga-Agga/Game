/*
 * resource_ticker.h - automatic resource production
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _RESOURCE_TICKER_H_
#define _RESOURCE_TICKER_H_

#include <time.h>

#include "database.h"

/* configuration parameters */

extern const char *ticker_state;
extern long tick_interval;

/*
 * Initialize resource events. Last event is taken from state file
 * or current time (if there is no state file).
 */
extern void tick_init (void);

/*
 * Write last resource event timestamp to the state file.
 */
extern void tick_log (void);

/*
 * Return the timestamp of the next resource event.
 */
extern time_t tick_next_event (void);

/*
 * Advance to the next resource event, return timestamp.
 */
extern time_t tick_advance (void);

/*
 * Perform resource update on the cave table.
 */
extern void resource_ticker (db_t *database, time_t timeval);

#endif /* _RESOURCE_TICKER_H_ */
