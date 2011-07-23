/*
 * check_config.c - check config.xml for syntax errors
 * Copyright (c) 2006  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "function.h"
#include "game_rules.h"
#include "wonder_rules.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

static struct Cave cave;

double db_result_get_double (db_result_t *result, const char *name)
{
    return 0;
}

static void check_define (void)
{
    function_eval(MOVEMENT_COST, &cave);
    function_eval(MOVEMENT_SPEED, &cave);
    function_eval(EXPOSE_INVISIBLE, &cave);
    function_eval(WATCH_TOWER_RANGE, &cave);
    function_eval(WONDER_RESISTANCE, &cave);
}

static void check_object (const struct GameObject *object)
{
    function_eval(object->maxLevel, &cave);
}

static void check_resource (const struct Resource *object)
{
    function_eval(object->production, &cave);
    function_eval(object->safeStorage, &cave);
}

static void check_expansion (const struct Expansion *object)
{
    int cost;

    function_eval(object->productionTime, &cave);

    for (cost = 0; cost < object->num_costs; ++cost)
	function_eval(object->costs[cost].cost, &cave);
}

static void check_wonder (const struct Wonder *object)
{
    int cost;

    function_eval(object->chance, &cave);

    for (cost = 0; cost < object->num_costs; ++cost)
	function_eval(object->costs[cost].cost, &cave);
}

int main (int argc, char *argv[])
{
    int type;

#ifdef DEBUG_MALLOC
    GC_find_leak = 1;
#endif

    /* init function parser */
    function_setup();

    check_define();

    for (type = 0; type < MAX_RESOURCE; ++type)
    {
	check_object(resource_type[type]);
	check_resource((struct Resource *) resource_type[type]);
    }

    for (type = 0; type < MAX_BUILDING; ++type)
    {
	check_object(building_type[type]);
	check_expansion((struct Expansion *) building_type[type]);
    }

    for (type = 0; type < MAX_SCIENCE; ++type)
    {
	check_object(science_type[type]);
	check_expansion((struct Expansion *) science_type[type]);
    }

    for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
    {
	check_object(defense_system_type[type]);
	check_expansion((struct Expansion *) defense_system_type[type]);
    }

    for (type = 0; type < MAX_UNIT; ++type)
    {
	check_expansion((struct Expansion *) unit_type[type]);
    }

    for (type = 0; type < MAX_WONDER; ++type)
    {
	check_wonder((struct Wonder *) wonder_type[type]);
    }

#ifdef DEBUG_MALLOC
    CHECK_LEAKS();
#endif
    return 0;
}
