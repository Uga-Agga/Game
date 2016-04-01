/*
 * chat_handler.c - handle chat events
 * Copyright (c) 2016 David Unger <david@edv-unger.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
*/

/*
  insertPlayer
    player
    name

/opt/ejabberd/sbin/ejabberdctl create_room $tribe hoehle.uga-agga.de uga-agga.de
  addRoom
    tribe

/opt/ejabberd/sbin/ejabberdctl destroy_room $tribe hoehle.uga-agga.de uga-agga.de
  deleteRoom
    tribe

/opt/ejabberd/sbin/ejabberdctl set_room_affiliation $tribe hoehle.uga-agga.de $user@uga-agga.de member
  addRoomPlayer
    tribe
    player

/opt/ejabberd/sbin/ejabberdctl set_room_affiliation $tribe hoehle.uga-agga.de $user@uga-agga.de none
  removeRoomPlayer
    tribe
    player

*/
#include <string.h>	   /* memset */
#include <json/json.h> /* json parser lib */

#include "database.h"	   /* db_result_get_int etc. */
#include "event_handler.h" /* function declaration */
#include "except.h"	   /* exception handling */
#include "logging.h"	   /* debug */
#include "message.h"	   /* hero_report etc. */
#include "ticker.h"	   /* DEBUG_TICKER */

void chat_handler (db_t *database, db_result_t *result)
{
  dstring_t *action;
  dstring_t *data;
  debug(DEBUG_TICKER, "entering function chat_handler()");

  /* get event data */
  action   = db_result_get_string(result, "action");
  data     = db_result_get_string(result, "data");


  debug(DEBUG_TICKER, "leaving function chat_handler()");
}
