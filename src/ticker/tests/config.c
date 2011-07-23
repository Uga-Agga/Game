/*
 * config.c - test configuration file parser functions
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <assert.h>
#include <string.h>

#include "config.h"

#define TESTFILE	"ticker/tests/config.data"

void test_config (void)
{
    config_set_value("default", "default");
    config_set_value("string_value", "xx");
    config_read_file(TESTFILE);

    assert(strcmp(config_get_value("default"), "default") == 0);
    assert(strcmp(config_get_value("string_value"), "foobar") == 0);
    assert(config_get_long_value("long_value") == 42);
    assert(config_get_double_value("double_value") == -17.2);
}
