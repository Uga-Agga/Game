/*
 * ugatime.c - game time utility functions
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "logging.h"
#include "memory.h"
#include "ticker.h"
#include "ugatime.h"

/*
 * Returns the battle bonus for the three different religions at
 * the current ugatime. Don't call free for the return value!
 */
const float *get_battle_bonus (void)
{
  const struct ugatime *uga_time = get_ugatime(time(NULL));
  float *bonus = mp_malloc(4 * sizeof (float));
  int i;

  debug(DEBUG_UGA_TIME, "ugatime (hour day): %dh %d %d %d",
        uga_time->hour, uga_time->day, uga_time->month, uga_time->year);

  for (i = 0; i < 4; ++i) {
    const struct godbonus *god_bonus = get_bonus(i, uga_time);

    debug(DEBUG_UGA_TIME, "bonus: %g", god_bonus->battle);
    bonus[i] = 1 + god_bonus->battle;
  }

  return bonus;
}

const struct ugatime *get_ugatime (time_t timeval)
{
  struct ugatime *result = mp_malloc(sizeof *result);
  time_t starttime = START_TIME;
  long hours, days, months;

  hours = difftime(timeval, starttime) * SPEED_RATIO / (60 * 60);
  days = hours / HOURS_PER_DAY;
  months = days / DAYS_PER_MONTH;

  result->hour = hours % HOURS_PER_DAY;
  result->day = days % DAYS_PER_MONTH + 1;
  result->month = months % MONTHS_PER_YEAR + 1;
  result->year = months / MONTHS_PER_YEAR + STARTING_YEAR;

  return result;
}

const struct godbonus *get_bonus (int god, const struct ugatime *time)
{
  static const struct godbonus bonus[][4] = {
    // AGGA       NONE       UGA         ENZIO
    {{.00, .15},{.00, .00},{.00, .00},{.00, -0.1}}, // Agga
    {{.00, .10},{.00, .00},{.00, .00},{.00, -0.1}}, // Eisigkeit
    {{.00, .00},{.00, .00},{.10, .00},{.00, -0.1}}, // Schnehbrandh
    {{.00, .00},{.00, .00},{.15, .00},{.00, -0.1}}, // Binenschtich
    {{.00, .00},{.00, .00},{.20, .00},{.00, -0.1}}, // Brrunfhd
    {{.00, .00},{.00, .00},{.25, .00},{.00, -0.1}}, // Uga
    {{.00, .00},{.00, .00},{.30, .00},{.00, -0.1}}, // Ernte
    {{.00, .30},{.00, .00},{.00, .00},{.00, -0.1}}, // Duesternis
    {{.00, .25},{.00, .00},{.00, .00},{.00, -0.1}}, // Verderb
    {{.00, .20},{.00, .00},{.00, .00},{.00, -0.1}}, // Frrost
  };
  return &bonus[time->month - 1][god];
}
