/*
 * ticker.h - general definitions for the ticker
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _TICKER_H_
#define _TICKER_H_

#define DEBUG_TICKER		(1 << 0)
#define DEBUG_EVENTS		(1 << 1)
#define DEBUG_BATTLE		(1 << 2)
#define DEBUG_UGA_TIME		(1 << 3)
#define DEBUG_TAKEOVER		(1 << 4)
#define DEBUG_FAME		(1 << 5)
#define DEBUG_SQL		(1 << 6)

#define DB_TABLE_ARTEFACT	"Artefact"
#define DB_TABLE_CAVE		"Cave"
#define DB_TABLE_CAVE_TAKEOVER	"Cave_takeover"
#define DB_TABLE_PLAYER		"Player"
#define DB_TABLE_RELATION	"Relation"
#define DB_TABLE_HERO "Hero_new"

#define ID_SCIENCE_UGA		22
#define ID_SCIENCE_AGGA		23
#define ID_SCIENCE_ENZIO	24

#define TAKEOVER_MULTIPLIER_BUILDING 16

#define WONDER_TIME_BASE_FACTOR	40
#define WEATHER_TIME_BASE_FACTOR	30

#endif /* _TICKER_H_ */
