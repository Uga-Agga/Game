/*
 * function.h - evaluate and convert game rules functions
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _FUNCTION_H_
#define _FUNCTION_H_

#include "cave.h"
#include "expression.h"

/*
 * Register the set of pre-defined arithmetic functions.
 */
extern void function_setup (void);

/*
 * Parse the string representation of a function. Returns a compiled
 * representation that can be evaluated by expr_double_value(). You must
 * call object_free() to free the result value if it is no longer needed.
 */
extern struct expr *function_parse (const char *function);

/*
 * Evaluate the string representation of a function. Variables are
 * resolved from the given cave.
 */
extern double function_eval (const char *function, const struct Cave *cave);

/*
 * Convert the string representation of a function to an SQL expression
 * (replacing function names as needed).
 */
extern const char *function_to_sql (const char *function);

#endif /* _FUNCTION_H_ */
