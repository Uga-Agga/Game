/*
 * config.h - generic configuration file parser
 * Copyright (c) 2004  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _CONFIG_H_
#define _CONFIG_H_

/*
 * Get a configuration value (logs error and exits if key is unknown).
 * The long and double variants also perform a type check on the value.
 */
extern const char *config_get_value (const char *key);
extern long config_get_long_value (const char *key);
extern double config_get_double_value (const char *key);

/*
 * Set a configuration value as a string. This could be used to set up
 * default values before reading config files.
 */
extern void config_set_value (const char *key, const char *value);

/*
 * Read configuration values from the given file.
 */
extern void config_read_file (const char *filename);

#endif /* _CONFIG_H_ */
