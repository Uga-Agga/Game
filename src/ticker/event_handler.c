/*
 * event_handler.c - list of the database event tables
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "event_handler.h"

const struct EventTable eventTableList[] = {
    {
	.table    = "Event_expansion",
	.id_field = "event_expansionID",
	.handler  = building_handler
    }, {
	.table    = "Event_unit",
	.id_field = "event_unitID",
	.handler  = unit_handler
    }, {
	.table    = "Event_movement",
	.id_field = "event_movementID",
	.handler  = movement_handler
    }, {
	.table    = "Event_science",
	.id_field = "event_scienceID",
	.handler  = science_handler
    }, {
	.table    = "Event_defenseSystem",
	.id_field = "event_defenseSystemID",
	.handler  = defense_handler
    }, {
	.table    = "Event_wonder",
	.id_field = "event_wonderID",
	.handler  = wonder_handler
    }, {
	.table    = "Event_wonderEnd",
	.id_field = "activeWonderID",
	.handler  = wonderEnd_handler
    }, {
	.table    = "Event_artefact",
	.id_field = "event_artefactID",
	.handler  = artefact_handler
    }, {
        .table    = "Event_weather",
	.id_field = "event_weatherID",
	.handler  = weather_handler
    }, {
        .table    = "Event_trade",
	.id_field = "event_tradeID",
	.handler  = trade_handler
    }, { 
        .table    = "Event_weatherEnd",
	.id_field = "activeWeatherID",
	.handler  = weatherEnd_handler
	}, {
        .table    = "Event_hero",
  .id_field = "event_heroID",
  .handler  = hero_handler
  }
};

const int eventTableSize = sizeof eventTableList / sizeof eventTableList[0];
