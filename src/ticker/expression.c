/*
 * expression.c - classes for constructing expression terms
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <math.h>	/* fmod */
#include <stdlib.h>
#include <string.h>

#include "expression.h"
#include "logging.h"
#include "memory.h"
#include "scanner.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

/*
 * abstract class for all expressions
 */
#define EXPR_CLASS_OF(obj)	((struct expr_class *) CLASS_OF(obj))

int expr_bool_value (struct expr *this, void *context)
{
    return EXPR_CLASS_OF(this)->bool_value(this, context);
}

double expr_double_value (struct expr *this, void *context)
{
    return EXPR_CLASS_OF(this)->double_value(this, context);
}

const char *expr_string_value (struct expr *this, void *context)
{
    return EXPR_CLASS_OF(this)->string_value(this, context);
}

int expr_type (struct expr *this)
{
    return EXPR_CLASS_OF(this)->type(this);
}

const char *expr_to_string (struct expr *this, void *context)
{
    if (expr_type(this) == TYPE_BOOL)
	return expr_bool_value(this, context) ? "true" : "false";
    else if (expr_type(this) == TYPE_DOUBLE)
	return dstring_str(dstring_new("%g", expr_double_value(this, context)));
    else
	return expr_string_value(this, context);
}

static int _expr_bool_value (struct expr *this, void *context)
{
    error("type error: boolean expected");
    return 0;
}

static double _expr_double_value (struct expr *this, void *context)
{
    error("type error: number expected");
    return 0;
}

static const char *_expr_string_value (struct expr *this, void *context)
{
    error("type error: string expected");
    return NULL;
}

struct class *expr_class_get (void)
{
    static struct expr_class expr_class;
    struct class *class = (struct class *) &expr_class;

    if (class->name == NULL)
    {
	class_init(class, "expr", OBJECT_CLASS,
		   sizeof (struct expr), sizeof expr_class);

	expr_class.bool_value = _expr_bool_value;
	expr_class.double_value = _expr_double_value;
	expr_class.string_value = _expr_string_value;
    }

    return class;
}

/*
 * expression term classes
 */
void double_expr_init (struct double_expr *this, double value)
{
    this->value = value;
}

struct expr *double_expr_new (double value)
{
    struct object *expr = object_new(DOUBLE_EXPR_CLASS);

    double_expr_init(DOUBLE_EXPR(expr), value);
    return EXPR(expr);
}

static double _double_expr_double_value (struct expr *this, void *context)
{
    struct double_expr *expr = DOUBLE_EXPR(this);

    return expr->value;
}

static int _double_expr_type (struct expr *this)
{
    return TYPE_DOUBLE;
}

struct class *double_expr_class_get (void)
{
    static struct expr_class double_expr_class;
    struct class *class = (struct class *) &double_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "double_expr", EXPR_CLASS,
		   sizeof (struct double_expr), sizeof double_expr_class);

	double_expr_class.double_value = _double_expr_double_value;
	double_expr_class.type = _double_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

void string_expr_init (struct string_expr *this, const char *value)
{
    this->value = xstrdup(value);
}

struct expr *string_expr_new (const char *value)
{
    struct object *expr = object_new(STRING_EXPR_CLASS);

    string_expr_init(STRING_EXPR(expr), value);
    return EXPR(expr);
}

static void _string_expr_free (struct object *this)
{
    struct string_expr *expr = STRING_EXPR(this);

    free(expr->value);
    EXPR_CLASS->release(this);
}

static const char *_string_expr_string_value (struct expr *this, void *context)
{
    struct string_expr *expr = STRING_EXPR(this);

    return expr->value;
}

static int _string_expr_type (struct expr *this)
{
    return TYPE_STRING;
}

struct class *string_expr_class_get (void)
{
    static struct expr_class string_expr_class;
    struct class *class = (struct class *) &string_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "string_expr", EXPR_CLASS,
		   sizeof (struct string_expr), sizeof string_expr_class);

	string_expr_class.base.release = _string_expr_free;
	string_expr_class.string_value = _string_expr_string_value;
	string_expr_class.type = _string_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

void symbol_expr_init (struct symbol_expr *this, const char *name)
{
    this->name = xstrdup(name);
}

static void _symbol_expr_free (struct object *this)
{
    struct symbol_expr *expr = SYMBOL_EXPR(this);

    free(expr->name);
    EXPR_CLASS->release(this);
}

static int _symbol_expr_type (struct expr *this)
{
    return TYPE_SYMBOL;
}

struct class *symbol_expr_class_get (void)
{
    static struct expr_class symbol_expr_class;
    struct class *class = (struct class *) &symbol_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "symbol_expr", EXPR_CLASS,
		   sizeof (struct symbol_expr), sizeof symbol_expr_class);

	symbol_expr_class.base.release = _symbol_expr_free;
	symbol_expr_class.type = _symbol_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

void sign_expr_init (struct sign_expr *this, struct expr *value)
{
    this->value = value;
}

static void _sign_expr_free (struct object *this)
{
    struct sign_expr *expr = SIGN_EXPR(this);

    object_free(OBJECT(expr->value));
    EXPR_CLASS->release(this);
}

struct class *sign_expr_class_get (void)
{
    static struct expr_class sign_expr_class;
    struct class *class = (struct class *) &sign_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "sign_expr", EXPR_CLASS,
		   sizeof (struct sign_expr), sizeof sign_expr_class);

	sign_expr_class.base.release = _sign_expr_free;
    }

    return class;
}

/* ------------------------------------------ */

struct expr *not_expr_new (struct expr *value)
{
    struct object *expr = object_new(NOT_EXPR_CLASS);

    sign_expr_init(SIGN_EXPR(expr), value);
    return EXPR(expr);
}

static int _not_expr_bool_value (struct expr *this, void *context)
{
    struct sign_expr *expr = SIGN_EXPR(this);

    return !expr_bool_value(expr->value, context);
}

static int _not_expr_type (struct expr *this)
{
    return TYPE_BOOL;
}

struct class *not_expr_class_get (void)
{
    static struct expr_class not_expr_class;
    struct class *class = (struct class *) &not_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "not_expr", SIGN_EXPR_CLASS,
		   sizeof (struct not_expr), sizeof not_expr_class);

	not_expr_class.bool_value = _not_expr_bool_value;
	not_expr_class.type = _not_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

struct expr *minus_expr_new (struct expr *value)
{
    struct object *expr = object_new(MINUS_EXPR_CLASS);

    sign_expr_init(SIGN_EXPR(expr), value);
    return EXPR(expr);
}

static double _minus_expr_double_value (struct expr *this, void *context)
{
    struct sign_expr *expr = SIGN_EXPR(this);

    return -expr_double_value(expr->value, context);
}

static int _minus_expr_type (struct expr *this)
{
    return TYPE_DOUBLE;
}

struct class *minus_expr_class_get (void)
{
    static struct expr_class minus_expr_class;
    struct class *class = (struct class *) &minus_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "minus_expr", SIGN_EXPR_CLASS,
		   sizeof (struct minus_expr), sizeof minus_expr_class);

	minus_expr_class.double_value = _minus_expr_double_value;
	minus_expr_class.type = _minus_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

void binary_expr_init (struct binary_expr *this, struct expr *left,
		       struct expr *right, int operator)
{
    this->left = left;
    this->right = right;
    this->operator = operator;
}

static void _binary_expr_free (struct object *this)
{
    struct binary_expr *expr = BINARY_EXPR(this);

    object_free(OBJECT(expr->left));
    object_free(OBJECT(expr->right));
    EXPR_CLASS->release(this);
}

struct class *binary_expr_class_get (void)
{
    static struct expr_class binary_expr_class;
    struct class *class = (struct class *) &binary_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "binary_expr", EXPR_CLASS,
		   sizeof (struct binary_expr), sizeof binary_expr_class);

	binary_expr_class.base.release = _binary_expr_free;
    }

    return class;
}

/* ------------------------------------------ */

struct expr *arith_expr_new (struct expr *left, struct expr *right,
			     int operator)
{
    struct object *expr = object_new(ARITH_EXPR_CLASS);

    binary_expr_init(BINARY_EXPR(expr), left, right, operator);
    return EXPR(expr);
}

static double _arith_expr_double_value (struct expr *this, void *context)
{
    struct binary_expr *expr = BINARY_EXPR(this);
    double left = expr_double_value(expr->left, context);
    double right = expr_double_value(expr->right, context);
    double result;

    switch (expr->operator)
    {
	case '+' : result = left + right; break;
	case '-' : result = left - right; break;
	case '*' : result = left * right; break;
	case '/' : result = left / right; break;
	case '%' : result = fmod(left, right); break;
    }

    return result;
}

static int _arith_expr_type (struct expr *this)
{
    return TYPE_DOUBLE;
}

struct class *arith_expr_class_get (void)
{
    static struct expr_class arith_expr_class;
    struct class *class = (struct class *) &arith_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "arith_expr", BINARY_EXPR_CLASS,
		   sizeof (struct arith_expr), sizeof arith_expr_class);

	arith_expr_class.double_value = _arith_expr_double_value;
	arith_expr_class.type = _arith_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

struct expr *concat_expr_new (struct expr *left, struct expr *right)
{
    struct object *expr = object_new(CONCAT_EXPR_CLASS);

    binary_expr_init(BINARY_EXPR(expr), left, right, '.');
    return EXPR(expr);
}

static const char *_concat_expr_string_value (struct expr *this, void *context)
{
    struct binary_expr *expr = BINARY_EXPR(this);
    dstring_t *ds = dstring_new("%s%s", expr_to_string(expr->left, context),
					expr_to_string(expr->right, context));

    return dstring_str(ds);
}

static int _concat_expr_type (struct expr *this)
{
    return TYPE_STRING;
}

struct class *concat_expr_class_get (void)
{
    static struct expr_class concat_expr_class;
    struct class *class = (struct class *) &concat_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "concat_expr", BINARY_EXPR_CLASS,
		   sizeof (struct concat_expr), sizeof concat_expr_class);

	concat_expr_class.string_value = _concat_expr_string_value;
	concat_expr_class.type = _concat_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

struct expr *relation_expr_new (struct expr *left, struct expr *right,
				int operator)
{
    struct object *expr = object_new(RELATION_EXPR_CLASS);

    binary_expr_init(BINARY_EXPR(expr), left, right, operator);
    return EXPR(expr);
}

static int _relation_expr_bool_value (struct expr *this, void *context)
{
    struct binary_expr *expr = BINARY_EXPR(this);
    int l_type = expr_type(expr->left);
    int r_type = expr_type(expr->right);
    int result;

    if (l_type == TYPE_BOOL || r_type == TYPE_BOOL)
    {
	int left = expr_bool_value(expr->left, context);
	int right = expr_bool_value(expr->right, context);

	switch (expr->operator)
	{
	    case TOKEN_EQUAL  : result = left == right; break;
	    case TOKEN_NOT_EQ : result = left != right; break;
	    case '<'          : result = left <  right; break;
	    case TOKEN_LT_EQ  : result = left <= right; break;
	    case '>'          : result = left >  right; break;
	    case TOKEN_GT_EQ  : result = left >= right; break;
	}
    }
    else if (l_type == TYPE_DOUBLE || r_type == TYPE_DOUBLE)
    {
	double left = expr_double_value(expr->left, context);
	double right = expr_double_value(expr->right, context);

	switch (expr->operator)
	{
	    case TOKEN_EQUAL  : result = left == right; break;
	    case TOKEN_NOT_EQ : result = left != right; break;
	    case '<'          : result = left <  right; break;
	    case TOKEN_LT_EQ  : result = left <= right; break;
	    case '>'          : result = left >  right; break;
	    case TOKEN_GT_EQ  : result = left >= right; break;
	}
    }
    else
    {
	const char *left = expr_string_value(expr->left, context);
	const char *right = expr_string_value(expr->right, context);

	switch (expr->operator)
	{
	    case TOKEN_EQUAL  : result = strcmp(left, right) == 0; break;
	    case TOKEN_NOT_EQ : result = strcmp(left, right) != 0; break;
	    case '<'          : result = strcmp(left, right) <  0; break;
	    case TOKEN_LT_EQ  : result = strcmp(left, right) <= 0; break;
	    case '>'          : result = strcmp(left, right) >  0; break;
	    case TOKEN_GT_EQ  : result = strcmp(left, right) >= 0; break;
	}
    }

    return result;
}

static int _relation_expr_type (struct expr *this)
{
    return TYPE_BOOL;
}

struct class *relation_expr_class_get (void)
{
    static struct expr_class relation_expr_class;
    struct class *class = (struct class *) &relation_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "relation_expr", BINARY_EXPR_CLASS,
		   sizeof (struct relation_expr), sizeof relation_expr_class);

	relation_expr_class.bool_value = _relation_expr_bool_value;
	relation_expr_class.type = _relation_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

struct expr *logic_expr_new (struct expr *left, struct expr *right,
			     int operator)
{
    struct object *expr = object_new(LOGIC_EXPR_CLASS);

    binary_expr_init(BINARY_EXPR(expr), left, right, operator);
    return EXPR(expr);
}

static int _logic_expr_bool_value (struct expr *this, void *context)
{
    struct binary_expr *expr = BINARY_EXPR(this);
    int left = expr_bool_value(expr->left, context);
    int right = expr_bool_value(expr->right, context);
    int result;

    switch (expr->operator)
    {
	case TOKEN_AND  : result = left && right; break;
	case TOKEN_OR   : result = left || right; break;
    }

    return result;
}

static int _logic_expr_type (struct expr *this)
{
    return TYPE_BOOL;
}

struct class *logic_expr_class_get (void)
{
    static struct expr_class logic_expr_class;
    struct class *class = (struct class *) &logic_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "logic_expr", BINARY_EXPR_CLASS,
		   sizeof (struct logic_expr), sizeof logic_expr_class);

	logic_expr_class.bool_value = _logic_expr_bool_value;
	logic_expr_class.type = _logic_expr_type;
    }

    return class;
}

/* ------------------------------------------ */

void cond_expr_init (struct cond_expr *this, struct expr *cond,
		     struct expr *left, struct expr *right)
{
    this->cond = cond;
    this->left = left;
    this->right = right;
}

struct expr *cond_expr_new (struct expr *cond, struct expr *left,
			    struct expr *right)
{
    struct object *expr = object_new(COND_EXPR_CLASS);

    cond_expr_init(COND_EXPR(expr), cond, left, right);
    return EXPR(expr);
}

static void _cond_expr_free (struct object *this)
{
    struct cond_expr *expr = COND_EXPR(this);

    object_free(OBJECT(expr->cond));
    object_free(OBJECT(expr->left));
    object_free(OBJECT(expr->right));
    EXPR_CLASS->release(this);
}

static int _cond_expr_bool_value (struct expr *this, void *context)
{
    struct cond_expr *expr = COND_EXPR(this);

    return expr_bool_value(expr->cond, context) ?
		expr_bool_value(expr->left, context) :
		expr_bool_value(expr->right, context);
}

static double _cond_expr_double_value (struct expr *this, void *context)
{
    struct cond_expr *expr = COND_EXPR(this);

    return expr_bool_value(expr->cond, context) ?
		expr_double_value(expr->left, context) :
		expr_double_value(expr->right, context);
}

static const char *_cond_expr_string_value (struct expr *this, void *context)
{
    struct cond_expr *expr = COND_EXPR(this);

    return expr_bool_value(expr->cond, context) ?
		expr_string_value(expr->left, context) :
		expr_string_value(expr->right, context);
}

static int _cond_expr_type (struct expr *this)
{
    struct cond_expr *expr = COND_EXPR(this);
    int l_type = expr_type(expr->left);
    int r_type = expr_type(expr->right);

    if (l_type != r_type && l_type != TYPE_SYMBOL && r_type != TYPE_SYMBOL)
	error("type error: type mismatch in conditional");

    return l_type == TYPE_BOOL   || r_type == TYPE_BOOL   ? TYPE_BOOL   :
	   l_type == TYPE_DOUBLE || r_type == TYPE_DOUBLE ? TYPE_DOUBLE :
	   l_type == TYPE_STRING || r_type == TYPE_STRING ? TYPE_STRING :
							    TYPE_SYMBOL;
}

struct class *cond_expr_class_get (void)
{
    static struct expr_class cond_expr_class;
    struct class *class = (struct class *) &cond_expr_class;

    if (class->name == NULL)
    {
	class_init(class, "cond_expr", EXPR_CLASS,
		   sizeof (struct cond_expr), sizeof cond_expr_class);

	cond_expr_class.base.release = _cond_expr_free;
	cond_expr_class.bool_value = _cond_expr_bool_value;
	cond_expr_class.double_value = _cond_expr_double_value;
	cond_expr_class.string_value = _cond_expr_string_value;
	cond_expr_class.type = _cond_expr_type;
    }

    return class;
}
