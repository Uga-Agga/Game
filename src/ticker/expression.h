/*
 * expression.h - classes for constructing expression terms
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _EXPRESSION_H_
#define _EXPRESSION_H_

#include "object.h"

/*
 * abstract class for all expressions
 */
enum ExprType
{
    TYPE_BOOL,
    TYPE_DOUBLE,
    TYPE_STRING,
    TYPE_SYMBOL
};

struct expr
{
    struct object base;		/* base class */
};

struct expr_class
{
    struct class base;		/* base class */

    /* evaluate the expression */
    int (*bool_value)(struct expr *this, void *context);
    double (*double_value)(struct expr *this, void *context);
    const char *(*string_value)(struct expr *this, void *context);

    /* get type information */
    int (*type)(struct expr *this);
};

#define EXPR(obj)		((struct expr *) (obj))
#define EXPR_CLASS		expr_class_get()

extern struct class *expr_class_get (void);

extern int expr_bool_value (struct expr *this, void *context);
extern double expr_double_value (struct expr *this, void *context);
extern const char *expr_string_value (struct expr *this, void *context);
extern int expr_type (struct expr *this);
extern const char *expr_to_string (struct expr *this, void *context);

/*
 * expression term classes
 */
struct double_expr
{
    struct expr base;		/* base class */
    double value;		/* expr value */
};

#define DOUBLE_EXPR(obj)	((struct double_expr *) (obj))
#define DOUBLE_EXPR_CLASS	double_expr_class_get()

extern struct class *double_expr_class_get (void);

extern struct expr *double_expr_new (double value);
extern void double_expr_init (struct double_expr *this, double value);

/* ------------------------------------------ */

struct string_expr
{
    struct expr base;		/* base class */
    char *value;		/* expr value */
};

#define STRING_EXPR(obj)	((struct string_expr *) (obj))
#define STRING_EXPR_CLASS	string_expr_class_get()

extern struct class *string_expr_class_get (void);

extern struct expr *string_expr_new (const char *value);
extern void string_expr_init (struct string_expr *this, const char *value);

/* ------------------------------------------ */

struct symbol_expr
{
    struct expr base;		/* base class */
    char *name;			/* symbol name */
};

#define SYMBOL_EXPR(obj)	((struct symbol_expr *) (obj))
#define SYMBOL_EXPR_CLASS	symbol_expr_class_get()

extern struct class *symbol_expr_class_get (void);

extern void symbol_expr_init (struct symbol_expr *this, const char *name);

/* ------------------------------------------ */

struct sign_expr
{
    struct expr base;		/* base class */
    struct expr *value;		/* child expr */
};

#define SIGN_EXPR(obj)		((struct sign_expr *) (obj))
#define SIGN_EXPR_CLASS		sign_expr_class_get()

extern struct class *sign_expr_class_get (void);

extern void sign_expr_init (struct sign_expr *this, struct expr *value);

/* ------------------------------------------ */

struct not_expr
{
    struct sign_expr base;	/* base class */
};

#define NOT_EXPR(obj)		((struct not_expr *) (obj))
#define NOT_EXPR_CLASS		not_expr_class_get()

extern struct class *not_expr_class_get (void);

extern struct expr *not_expr_new (struct expr *value);

/* ------------------------------------------ */

struct minus_expr
{
    struct sign_expr base;	/* base class */
};

#define MINUS_EXPR(obj)		((struct minus_expr *) (obj))
#define MINUS_EXPR_CLASS	minus_expr_class_get()

extern struct class *minus_expr_class_get (void);

extern struct expr *minus_expr_new (struct expr *value);

/* ------------------------------------------ */

struct binary_expr
{
    struct expr base;		/* base class */
    struct expr *left;		/* left child */
    struct expr *right;		/* right child */
    int operator;		/* token type */
};

#define BINARY_EXPR(obj)	((struct binary_expr *) (obj))
#define BINARY_EXPR_CLASS	binary_expr_class_get()

extern struct class *binary_expr_class_get (void);

extern void binary_expr_init (struct binary_expr *this, struct expr *left,
			      struct expr *right, int operator);

/* ------------------------------------------ */

struct arith_expr
{
    struct binary_expr base;	/* base class */
};

#define ARITH_EXPR(obj)		((struct arith_expr *) (obj))
#define ARITH_EXPR_CLASS	arith_expr_class_get()

extern struct class *arith_expr_class_get (void);

extern struct expr *arith_expr_new (struct expr *left, struct expr *right,
				    int operator);

/* ------------------------------------------ */

struct concat_expr
{
    struct binary_expr base;	/* base class */
};

#define CONCAT_EXPR(obj)	((struct concat_expr *) (obj))
#define CONCAT_EXPR_CLASS	concat_expr_class_get()

extern struct class *concat_expr_class_get (void);

extern struct expr *concat_expr_new (struct expr *left, struct expr *right);

/* ------------------------------------------ */

struct relation_expr
{
    struct binary_expr base;	/* base class */
};

#define RELATION_EXPR(obj)	((struct relation_expr *) (obj))
#define RELATION_EXPR_CLASS	relation_expr_class_get()

extern struct class *relation_expr_class_get (void);

extern struct expr *relation_expr_new (struct expr *left, struct expr *right,
				       int operator);

/* ------------------------------------------ */

struct logic_expr
{
    struct binary_expr base;	/* base class */
};

#define LOGIC_EXPR(obj)		((struct logic_expr *) (obj))
#define LOGIC_EXPR_CLASS	logic_expr_class_get()

extern struct class *logic_expr_class_get (void);

extern struct expr *logic_expr_new (struct expr *left, struct expr *right,
				    int operator);

/* ------------------------------------------ */

struct cond_expr
{
    struct expr base;		/* base class */
    struct expr *cond;		/* condition */
    struct expr *left;		/* then child */
    struct expr *right;		/* else child */
};

#define COND_EXPR(obj)		((struct cond_expr *) (obj))
#define COND_EXPR_CLASS		cond_expr_class_get()

extern struct class *cond_expr_class_get (void);

extern struct expr *cond_expr_new (struct expr *cond, struct expr *left,
				   struct expr *right);
extern void cond_expr_init (struct cond_expr *this, struct expr *cond,
			    struct expr *left, struct expr *right);

#endif /* _EXPRESSION_H_ */
