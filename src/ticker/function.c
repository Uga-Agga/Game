/*
 * function.c - evaluate and convert game rules functions
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <math.h>	/* fabs, ceil, floor, pow, sqrt */
#include <stdio.h>
#include <stdlib.h>

#include "function.h"
#include "game_rules.h"
#include "hashtable.h"
#include "logging.h"
#include "memory.h"
#include "scanner.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

/*
 * table of supported arithmetic functions
 */
static hashtable_t *functions;
static hashtable_t *sql_funcs;

/*
 * arithmetic function implementations
 */
static double func_abs (struct expr *args[], int num_args, void *context)
{
    if (num_args != 1)
	error("abs: wrong number of arguments");

    return fabs(expr_double_value(args[0], context));
}

static double func_ceil (struct expr *args[], int num_args, void *context)
{
    if (num_args != 1)
	error("ceil: wrong number of arguments");

    return ceil(expr_double_value(args[0], context));
}

static double func_floor (struct expr *args[], int num_args, void *context)
{
    if (num_args != 1)
	error("floor: wrong number of arguments");

    return floor(expr_double_value(args[0], context));
}

static double func_max (struct expr *args[], int num_args, void *context)
{
    double max;
    int index;

    if (num_args == 0)
	error("max: too few arguments");

    max = expr_double_value(args[0], context);

    for (index = 1; index < num_args; ++index)
    {
	double value = expr_double_value(args[index], context);

	if (value > max) max = value;
    }

    return max;
}

static double func_min (struct expr *args[], int num_args, void *context)
{
    double min;
    int index;

    if (num_args == 0)
	error("min: too few arguments");

    min = expr_double_value(args[0], context);

    for (index = 1; index < num_args; ++index)
    {
	double value = expr_double_value(args[index], context);

	if (value < min) min = value;
    }

    return min;
}

static double func_pow (struct expr *args[], int num_args, void *context)
{
    if (num_args != 2)
	error("pow: wrong number of arguments");

    return pow(expr_double_value(args[0], context),
	       expr_double_value(args[1], context));
}

static double func_round (struct expr *args[], int num_args, void *context)
{
    if (num_args != 1)
	error("round: wrong number of arguments");

    return floor(expr_double_value(args[0], context) + 0.5);
}

static double func_sgn (struct expr *args[], int num_args, void *context)
{
    double value;

    if (num_args != 1)
	error("sgn: wrong number of arguments");

    value = expr_double_value(args[0], context);
    return (value > 0) - (value < 0);
}

static double func_sqrt (struct expr *args[], int num_args, void *context)
{
    if (num_args != 1)
	error("sqrt: wrong number of arguments");

    return sqrt(expr_double_value(args[0], context));
}

/*
 * Register the set of pre-defined arithmetic functions.
 */
void function_setup (void)
{
    functions = hashtable_new(string_hash, string_equals);
    sql_funcs = hashtable_new(string_hash, string_equals);

    hashtable_insert(functions, "abs",   func_abs);
    hashtable_insert(functions, "ceil",  func_ceil);
    hashtable_insert(functions, "floor", func_floor);
    hashtable_insert(functions, "max",   func_max);
    hashtable_insert(functions, "min",   func_min);
    hashtable_insert(functions, "pow",   func_pow);
    hashtable_insert(functions, "round", func_round);
    hashtable_insert(functions, "sgn",   func_sgn);
    hashtable_insert(functions, "sqrt",  func_sqrt);

    hashtable_insert(sql_funcs, "max", "GREATEST");
    hashtable_insert(sql_funcs, "min", "LEAST");
    hashtable_insert(sql_funcs, "sgn", "SIGN");
}

/*
 * class representing a database column
 */
struct field
{
    struct symbol_expr base;	/* base class */
};

#define FIELD(obj)		((struct field *) (obj))
#define FIELD_CLASS		field_class_get()

static struct class *field_class_get (void);

static struct expr *field_new (const char *name)
{
    struct object *expr = object_new(FIELD_CLASS);

    symbol_expr_init(SYMBOL_EXPR(expr), name);
    return EXPR(expr);
}

static double _field_double_value (struct expr *this, void *context)
{
    struct symbol_expr *expr = SYMBOL_EXPR(this);
    const struct Cave *cave = context;

    return db_result_get_double(cave->result, expr->name);
}

static int _field_type (struct expr *this)
{
    return TYPE_DOUBLE;
}

static struct class *field_class_get (void)
{
    static struct expr_class field_class;
    struct class *class = (struct class *) &field_class;

    if (class->name == NULL)
    {
	class_init(class, "field", SYMBOL_EXPR_CLASS,
		   sizeof (struct field), sizeof field_class);

	field_class.double_value = _field_double_value;
	field_class.type = _field_type;
    }

    return class;
}

/*
 * function call expression
 */
struct function
{
    struct expr base;		/* base class */
    char *name;			/* function name */
    array_t *args;		/* argument list */
};

#define FUNCTION(obj)		((struct function *) (obj))
#define FUNCTION_CLASS		function_class_get()

static struct class *function_class_get (void);

static void function_init (struct function *this, const char *name)
{
    this->name = xstrdup(name);
    this->args = array_new(sizeof (struct expr *));
}

static struct expr *function_new (const char *name)
{
    struct object *expr = object_new(FUNCTION_CLASS);

    function_init(FUNCTION(expr), name);
    return EXPR(expr);
}

static void function_add_arg (struct function *this, struct expr *arg)
{
    array_add(this->args, &arg);
}

static void _function_free (struct object *this)
{
    struct function *expr = FUNCTION(this);
    struct expr **args = array_values(expr->args);
    int index;

    free(expr->name);
    for (index = 0; index < array_len(expr->args); ++index)
	object_free(OBJECT(args[index]));
    array_free(expr->args);
    EXPR_CLASS->release(this);
}

static double _function_double_value (struct expr *this, void *context)
{
    struct function *expr = FUNCTION(this);
    double (*func)(struct expr *args[], int num_args, void *context) =
	hashtable_lookup(functions, expr->name);

    if (func == NULL)
	error("unknown function: %s", expr->name);

    return func(array_values(expr->args), array_len(expr->args), context);
}

static int _function_type (struct expr *this)
{
    return TYPE_DOUBLE;
}

static struct class *function_class_get (void)
{
    static struct expr_class function_class;
    struct class *class = (struct class *) &function_class;

    if (class->name == NULL)
    {
	class_init(class, "function", EXPR_CLASS,
		   sizeof (struct function), sizeof function_class);

	function_class.base.release = _function_free;
	function_class.double_value = _function_double_value;
	function_class.type = _function_type;
    }

    return class;
}

/*
 * Print syntax error message with context and exit.
 */
static void syntax_error (scanner_t *scanner, const char *msg)
{
    error("syntax error: %s: %s", msg, scanner_text(scanner));
}

/*
 * function expression LL(1) parser
 */
static struct expr *parse_expr (scanner_t *scanner);

/*
 * function: SYMBOL '(' ')' | SYMBOL '(' expr { ',' expr } ')'
 */
static struct expr *parse_function (scanner_t *scanner)
{
    struct expr *result = function_new(scanner_token_string_value(scanner));
    int type;

    if (scanner_next_token(scanner) != '(')
	syntax_error(scanner, "missing '('");

    if (scanner_next_token(scanner) != ')')
    {
	function_add_arg(FUNCTION(result), parse_expr(scanner));
	type = scanner_token_type(scanner);

	while (type == ',')
	{
	    scanner_next_token(scanner);
	    function_add_arg(FUNCTION(result), parse_expr(scanner));
	    type = scanner_token_type(scanner);
	}
    }

    if (scanner_token_type(scanner) != ')')
	syntax_error(scanner, "missing ')'");

    return result;
}

/*
 * value: NUMBER | function | '[' SYMBOL ']' | '(' expr ')'
 */
static struct expr *parse_value (scanner_t *scanner)
{
    struct expr *result;

    switch (scanner_token_type(scanner))
    {
	case TOKEN_NUMBER:
	    result = double_expr_new(scanner_token_double_value(scanner));
	    break;
	case TOKEN_SYMBOL:
	    result = parse_function(scanner);
	    break;
	case '[':
	    if (scanner_next_token(scanner) != TOKEN_SYMBOL)
		syntax_error(scanner, "missing or invalid id");

	    result = field_new(scanner_token_string_value(scanner));
	    scanner_next_token(scanner);

	    if (scanner_token_type(scanner) != ']')
		syntax_error(scanner, "missing ']'");
	    break;
	case '(':
	    scanner_next_token(scanner);
	    result = parse_expr(scanner);

	    if (scanner_token_type(scanner) != ')')
		syntax_error(scanner, "missing ')'");
	    break;
	default:
	    syntax_error(scanner, "invalid symbol");
    }

    scanner_next_token(scanner);
    return result;
}

/*
 * sign: '+' sign | '-' sign | value
 */
static struct expr *parse_sign (scanner_t *scanner)
{
    struct expr *result;

    switch (scanner_token_type(scanner))
    {
	case '+':
	    scanner_next_token(scanner);
	    result = parse_sign(scanner);
	    break;
	case '-':
	    scanner_next_token(scanner);
	    result = minus_expr_new(parse_sign(scanner));
	    break;
	default:
	    result = parse_value(scanner);
    }

    return result;
}

/*
 * product: sign | product '*' sign | product '/' sign
 */
static struct expr *parse_product (scanner_t *scanner)
{
    struct expr *result = parse_sign(scanner);
    int type = scanner_token_type(scanner);

    while (type == '*' || type == '/')
    {
	scanner_next_token(scanner);
	result = arith_expr_new(result, parse_sign(scanner), type);
	type = scanner_token_type(scanner);
    }

    return result;
}

/*
 * expr: product | expr '+' product | expr '-' product
 */
static struct expr *parse_expr (scanner_t *scanner)
{
    struct expr *result = parse_product(scanner);
    int type = scanner_token_type(scanner);

    while (type == '+' || type == '-')
    {
	scanner_next_token(scanner);
	result = arith_expr_new(result, parse_product(scanner), type);
	type = scanner_token_type(scanner);
    }

    return result;
}

/*
 * Parse the string representation of a function. Returns a compiled
 * representation that can be evaluated by expr_double_value(). You must
 * call object_free() to free the result value if it is no longer needed.
 */
struct expr *function_parse (const char *function)
{
    scanner_t *scanner = scanner_new(function);
    struct expr *result;

    scanner_next_token(scanner);
    result = parse_expr(scanner);

    if (scanner_token_type(scanner) != TOKEN_NONE)
	error("function_parse: malformed function: %s", function);

    scanner_free(scanner);
    return result;
}

/*
 * Evaluate the string representation of a function. Variables are
 * resolved from the given cave.
 */
double function_eval (const char *function, const struct Cave *cave)
{
    struct expr *expr = function_parse(function);
    double result = expr_double_value(expr, (void *) cave);

    object_free(OBJECT(expr));
    return result;
}

/*
 * Convert the string representation of a function to an SQL expression
 * (replacing function names as needed).
 */
const char *function_to_sql (const char *function)
{
    scanner_t *scanner = scanner_new(function);
    dstring_t *ds = dstring_new("");
    const char *symbol;
    int type;

    while ((type = scanner_next_token(scanner)) != TOKEN_NONE)
    {
	switch (type)
	{
	    case TOKEN_NUMBER:
		dstring_append(ds, "%g", scanner_token_double_value(scanner));
		break;
	    case TOKEN_SYMBOL:
		symbol = scanner_token_string_value(scanner);
		symbol = hashtable_lookup(sql_funcs, symbol);

		if (symbol)
		    dstring_append(ds, symbol);
		else
		    dstring_append(ds, scanner_token_string_value(scanner));
		break;
	    case '[':
		if (scanner_next_token(scanner) != TOKEN_SYMBOL)
		    syntax_error(scanner, "missing or invalid id");

		dstring_append(ds, scanner_token_string_value(scanner));

		if (scanner_next_token(scanner) != ']')
		    syntax_error(scanner, "missing ']'");
		break;
	    default:
		dstring_append(ds, "%c", scanner_token_type(scanner));
	}
    }

    scanner_free(scanner);
    return dstring_str(ds);
}
