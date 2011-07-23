/*
 * ugatime.h - game time utility functions
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _UGATIME_H_
#define _UGATIME_H_

#include <time.h>

#define STARTING_YEAR     1
#define MONTHS_PER_YEAR  10
#define DAYS_PER_MONTH   24
#define HOURS_PER_DAY    24

#define SPEED_RATIO       24

/* Mon Sep  2 00:00:00 CEST 2002 */
#define START_TIME       1030917600

#define RELIGION_AGGA     0
#define RELIGION_NONE     1
#define RELIGION_UGA      2
#define RELIGION_ENZIO    3

struct ugatime
{
    int hour;
    int day;
    int month;
    int year;
};

struct godbonus
{
    float production;
    float battle;
};

/*
 * Returns the battle bonus for the three different religions at
 * the current ugatime. Don't call free for the return value!
 */
extern const float *get_battle_bonus (void);
extern const struct ugatime *get_ugatime (time_t timeval);
extern const struct godbonus *get_bonus (int god, const struct ugatime *time);

#endif /* _UGATIME_H_ */
