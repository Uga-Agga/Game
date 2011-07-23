/*
 * wonder_rules.h - wonder type declarations and constants
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _WONDER_RULES_H_
#define _WONDER_RULES_H_

#include "game_rules.h"

#define WONDER_CLASS	((struct class *) &wonder_class)
#define WEATHER_CLASS	((struct class *) &weather_class)

enum WonderTargetType
{
    WONDER_TARGET_same,
    WONDER_TARGET_own,
    WONDER_TARGET_other,
    WONDER_TARGET_all
};

enum WonderRandomType
{
    WONDER_RANDOM_default,
    WONDER_RANDOM_gauss		/* not implemented */
};

enum WonderMessageType
{
    WONDER_MESSAGE_none,
    WONDER_MESSAGE_note,
    WONDER_MESSAGE_detailed
};

struct ImpactEffect
{
    float absolute;
    float relative;
    int maxDelta;
    int type;
};

struct WonderImpact
{
    int duration;
    float delay;
    float steal;

    int deactivateTearDown;

    const char *targetMessage;
    int targetMessageType;

    const char *sourceMessage;
    int sourceMessageType;

    const struct ImpactEffect *resources;
    int resourcesAll;

    const struct ImpactEffect *buildings;
    int buildingsAll;

    const struct ImpactEffect *sciences;
    int sciencesAll;

    const struct ImpactEffect *defenseSystems;
    int defenseSystemsAll;

    const struct ImpactEffect *units;
    int unitsAll;

    const struct ImpactEffect *effects;
    int effectsAll;
};

struct Wonder
{
    struct GameObject base;

    int offensive;
    int target;
    int groupid;
    const char *chance;

    const struct WonderImpact *impacts;
    int num_impacts;

    const struct ProductionCost *costs;
    int num_costs;

    const struct Requirement *requirements;
    int num_requirements;
};
struct WeatherImpact
{
    int duration;
    float delay;
    float steal;

    const struct ImpactEffect *effects;
    int effectsAll;
};

struct Weather
{
    struct GameObject base;

    const struct WeatherImpact *impacts;
    int num_impacts;

};
extern const struct class wonder_class;

extern const struct GameObject *wonder_type[];
extern const struct class weather_class;

extern const struct GameObject *weather_type[];
extern const struct GameObject *trade_type[];
#endif /* _WONDER_RULES_H_ */
