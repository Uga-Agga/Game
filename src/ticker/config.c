/*
 * config.c - generic configuration file parser
 * Copyright (c) 2004  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <errno.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "config.h"
#include "hashtable.h"
#include "logging.h"
#include "memory.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

#define CONFIG_MAX_LINE_LEN	4096
#define CONFIG_WHITESPACE	" \t\n\r\f\v"

/*
 * Get (and create if necessary) the table of config values.
 */
static hashtable_t *config_get_table (void)
{
    static hashtable_t *config;

    if (config == NULL)
	config = hashtable_new(string_hash, string_equals);

    return config;
}

/*
 * Get a configuration value (logs error and exits if key is not set).
 * The long and double variants also perform a type check on the value.
 */
const char *config_get_value (const char *key)
{
    const char *value = hashtable_lookup(config_get_table(), key);

    if (value == NULL)
	error("config_get_value: no value for key: %s", key);

    return value;
}

long config_get_long_value (const char *key)
{
    const char *value = config_get_value(key);
    char *endptr;
    long result = strtol(value, &endptr, 0);

    if (!value[0] || endptr[0])
	error("config_get_long_value: invalid value for key: %s", key);

    return result;
}

double config_get_double_value (const char *key)
{
    const char *value = config_get_value(key);
    char *endptr;
    double result = strtod(value, &endptr);

    if (!value[0] || endptr[0])
	error("config_get_double_value: invalid value for key: %s", key);

    return result;
}

/*
 * Set a configuration value as a string. This could be used to set up
 * default values before reading config files.
 */
void config_set_value (const char *key, const char *value)
{
    hashtable_t *config = config_get_table();
    char *old_value = hashtable_lookup(config, key);

    if (old_value)
	free(old_value);		/* old key is reused */
    else
	key = xstrdup(key);		/* duplicate the key */

    hashtable_insert(config, key, xstrdup(value));
}

/*
 * Read configuration values from the given file.
 */
void config_read_file (const char *filename)
{
    FILE *config = fopen(filename, "r");
    char line[CONFIG_MAX_LINE_LEN];
    char *key, *value;
    char *ptr, *out;

    if (config == NULL)
	error("config_read_file: %s: %s", filename, strerror(errno));

    while (fgets(line, sizeof line, config))
    {
	key = line + strspn(line, CONFIG_WHITESPACE);
	if (!key[0] || key[0] == '#') continue;

	ptr = key + strcspn(key, CONFIG_WHITESPACE);
	value = ptr + strspn(ptr, CONFIG_WHITESPACE);
	ptr[0] = '\0';

	if (value[0] == '"')
	{
	    out = ++value;
	    for (ptr = out; ptr[0] && ptr[0] != '"'; *out++ = *ptr++)
		if (ptr[0] == '\\' && ptr[1]) ++ptr;
	}
	else
	{
	    out = value + strcspn(value, CONFIG_WHITESPACE);
	}
	out[0] = '\0';

	config_set_value(key, value);
    }

    fclose(config);
}
