/*
 * template.c - template system compatible with php-templates
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <ctype.h>
#include <errno.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "expression.h"
#include "hashtable.h"
#include "logging.h"
#include "memory.h"
#include "scanner.h"
#include "template.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
static void xfree (void *ptr) { free(ptr); }
#undef	free
#define free	xfree
#endif

/*
 * template context handling
 */
struct context
{
    struct tmpl_node *node;	/* template node */
    struct context *parent;	/* parent context */
    hashtable_t *bindings;	/* context bindings */
    array_t *children;		/* all sub-contexts */
};

static void context_init (struct context *context, struct tmpl_node *node,
			  struct context *parent)
{
    context->node = node;
    context->parent = parent;
    context->bindings =
	hashtable_new_ex(string_hash, string_equals,
			 (void *(*)(const void *)) xstrdup, free,
			 (void *(*)(const void *)) xstrdup, free);
    context->children = array_new(sizeof (struct context));
}

static void context_copy (struct context *copy, struct context *context,
			  struct context *parent)
{
    struct context *children = array_values(context->children);
    struct context *copy_children;
    int index;

    copy->node = context->node;
    copy->parent = parent;
    copy->bindings = hashtable_copy(context->bindings);
    copy->children = array_copy(context->children);
    copy_children = array_values(copy->children);

    for (index = 0; index < array_len(copy->children); ++index)
	context_copy(&copy_children[index], &children[index], copy);
}

static void context_destroy (struct context *context)
{
    struct context *children = array_values(context->children);
    int index;

    for (index = 0; index < array_len(context->children); ++index)
	context_destroy(&children[index]);
    hashtable_free(context->bindings);
    array_free(context->children);
}

static const char *context_lookup (struct context *context, const char *key)
{
    const char *value;

    do value = hashtable_lookup(context->bindings, key);
    while (value == NULL && (context = context->parent));
    return value;
}

/*
 * class representing template values
 */
struct symbol
{
    struct symbol_expr base;	/* base class */
};

#define SYMBOL(obj)		((struct symbol *) (obj))
#define SYMBOL_CLASS		symbol_class_get()

static struct class *symbol_class_get (void);

static struct expr *symbol_new (const char *name)
{
    struct object *expr = object_new(SYMBOL_CLASS);

    symbol_expr_init(SYMBOL_EXPR(expr), name);
    return EXPR(expr);
}

static int _symbol_bool_value (struct expr *this, void *context)
{
    struct symbol_expr *expr = SYMBOL_EXPR(this);
    const char *value = context_lookup(context, expr->name);

    return value != NULL;
}

static double _symbol_double_value (struct expr *this, void *context)
{
    return atof(expr_string_value(this, context));
}

static const char *_symbol_string_value (struct expr *this, void *context)
{
    struct symbol_expr *expr = SYMBOL_EXPR(this);
    const char *value = context_lookup(context, expr->name);

    return value ? value : "";
}

static struct class *symbol_class_get (void)
{
    static struct expr_class symbol_class;
    struct class *class = (struct class *) &symbol_class;

    if (class->name == NULL)
    {
	class_init(class, "symbol", SYMBOL_EXPR_CLASS,
		   sizeof (struct symbol), sizeof symbol_class);

	symbol_class.bool_value = _symbol_bool_value;
	symbol_class.double_value = _symbol_double_value;
	symbol_class.string_value = _symbol_string_value;
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
 * template expression LL(1) parser
 */
static struct expr *parse_expr (scanner_t *scanner);

/*
 * value: NUMBER | STRING | SYMBOL | '(' expr ')'
 */
static struct expr *parse_value (scanner_t *scanner)
{
    struct expr *result;

    switch (scanner_token_type(scanner))
    {
	case TOKEN_NUMBER:
	    result = double_expr_new(scanner_token_double_value(scanner));
	    break;
	case TOKEN_STRING:
	    result = string_expr_new(scanner_token_string_value(scanner));
	    break;
	case TOKEN_SYMBOL:
	    result = symbol_new(scanner_token_string_value(scanner));
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
 * sign: '!' sign | '+' sign | '-' sign | value
 */
static struct expr *parse_sign (scanner_t *scanner)
{
    struct expr *result;

    switch (scanner_token_type(scanner))
    {
	case '!':
	    scanner_next_token(scanner);
	    result = not_expr_new(parse_sign(scanner));
	    break;
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
 * product: sign | product '*' sign | product '/' sign | product '%' sign
 */
static struct expr *parse_product (scanner_t *scanner)
{
    struct expr *result = parse_sign(scanner);
    int type = scanner_token_type(scanner);

    while (type == '*' || type == '/' || type == '%')
    {
	scanner_next_token(scanner);
	result = arith_expr_new(result, parse_sign(scanner), type);
	type = scanner_token_type(scanner);
    }

    return result;
}

/*
 * sum: product | sum '+' product | sum '-' product
 */
static struct expr *parse_sum (scanner_t *scanner)
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
 * concat: sum | concat '.' sum
 */
static struct expr *parse_concat (scanner_t *scanner)
{
    struct expr *result = parse_sum(scanner);
    int type = scanner_token_type(scanner);

    while (type == '.')
    {
	scanner_next_token(scanner);
	result = concat_expr_new(result, parse_sum(scanner));
	type = scanner_token_type(scanner);
    }

    return result;
}

/*
 * lt_gt: concat | lt_gt '<' concat | lt_gt LT_EQ concat
 *		 | lt_gt '>' concat | lt_gt GT_EQ concat
 */
static struct expr *parse_lt_gt (scanner_t *scanner)
{
    struct expr *result = parse_concat(scanner);
    int type = scanner_token_type(scanner);

    while (type == '<' || type == TOKEN_LT_EQ ||
	   type == '>' || type == TOKEN_GT_EQ)
    {
	scanner_next_token(scanner);
	result = relation_expr_new(result, parse_concat(scanner), type);
	type = scanner_token_type(scanner);
    }

    return result;
}

/*
 * cmp: lt_gt | cmp EQUAL lt_gt | cmp NOT_EQ lt_gt
 */
static struct expr *parse_cmp (scanner_t *scanner)
{
    struct expr *result = parse_lt_gt(scanner);
    int type = scanner_token_type(scanner);

    while (type == TOKEN_EQUAL || type == TOKEN_NOT_EQ)
    {
	scanner_next_token(scanner);
	result = relation_expr_new(result, parse_lt_gt(scanner), type);
	type = scanner_token_type(scanner);
    }

    return result;
}

/*
 * and: cmp | and AND cmp
 */
static struct expr *parse_and (scanner_t *scanner)
{
    struct expr *result = parse_cmp(scanner);
    int type = scanner_token_type(scanner);

    while (type == TOKEN_AND)
    {
	scanner_next_token(scanner);
	result = logic_expr_new(result, parse_cmp(scanner), type);
	type = scanner_token_type(scanner);
    }

    return result;
}

/*
 * or: and | or OR and
 */
static struct expr *parse_or (scanner_t *scanner)
{
    struct expr *result = parse_and(scanner);
    int type = scanner_token_type(scanner);

    while (type == TOKEN_OR)
    {
	scanner_next_token(scanner);
	result = logic_expr_new(result, parse_and(scanner), type);
	type = scanner_token_type(scanner);
    }

    return result;
}

/*
 * expr: or | or '?' expr ':' expr
 */
static struct expr *parse_expr (scanner_t *scanner)
{
    struct expr *result = parse_or(scanner);

    if (scanner_token_type(scanner) == '?')
    {
	struct expr *expr;

	scanner_next_token(scanner);
	expr = parse_expr(scanner);

	if (scanner_token_type(scanner) != ':')
	    syntax_error(scanner, "missing ':'");

	scanner_next_token(scanner);
	result = cond_expr_new(result, expr, parse_expr(scanner));
    }

    return result;
}

/*
 * abstract class for all nodes
 */
struct node
{
    struct object base;		/* base class */
};

struct node_class
{
    struct class base;		/* base class */

    /* evaluate the template */
    void (*eval)(struct node *this, dstring_t *ds, struct context *context);

    /* add child to the node */
    void (*add_child)(struct node *this, struct node *child);

    /* get the named template */
    struct node *(*template)(struct node *this, const char *name);
};

#define NODE(obj)		((struct node *) (obj))
#define NODE_CLASS		node_class_get()
#define NODE_CLASS_OF(obj)	((struct node_class *) CLASS_OF(obj))

static void node_eval (struct node *this, dstring_t *ds,
		       struct context *context)
{
    NODE_CLASS_OF(this)->eval(this, ds, context);
}

static void node_add_child (struct node *this, struct node *child)
{
    NODE_CLASS_OF(this)->add_child(this, child);
}

static struct node *node_template (struct node *this, const char *name)
{
    return NODE_CLASS_OF(this)->template(this, name);
}

static void _node_add_child (struct node *this, struct node *child)
{
    error("node_add_child: invalid operation");
}

static struct node *_node_template (struct node *node, const char *name)
{
    return NULL;
}

static struct class *node_class_get (void)
{
    static struct node_class node_class;
    struct class *class = (struct class *) &node_class;

    if (class->name == NULL)
    {
	class_init(class, "node", OBJECT_CLASS,
		   sizeof (struct node), sizeof node_class);

	node_class.add_child = _node_add_child;
	node_class.template = _node_template;
    }

    return class;
}

/*
 * template node classes
 */
struct string_node
{
    struct node base;		/* base class */
    const char *text;		/* text string */
};

#define STRING_NODE(obj)	((struct string_node *) (obj))
#define STRING_NODE_CLASS	string_node_class_get()

static struct class *string_node_class_get (void);

static void string_node_init (struct string_node *this, const char *text)
{
    this->text = text;
}

static struct node *string_node_new (const char *text)
{
    struct object *node = object_new(STRING_NODE_CLASS);

    string_node_init(STRING_NODE(node), text);
    return NODE(node);
}

static void _string_node_eval (struct node *this, dstring_t *ds,
			       struct context *context)
{
    struct string_node *node = STRING_NODE(this);

    dstring_append(ds, "%s", node->text);
}

static struct class *string_node_class_get (void)
{
    static struct node_class string_node_class;
    struct class *class = (struct class *) &string_node_class;

    if (class->name == NULL)
    {
	class_init(class, "string_node", NODE_CLASS,
		   sizeof (struct string_node), sizeof string_node_class);

	string_node_class.eval = _string_node_eval;
    }

    return class;
}

/* ------------------------------------------ */

struct expr_node
{
    struct node base;		/* base class */
    struct expr *expr;		/* expression */
};

#define EXPR_NODE(obj)		((struct expr_node *) (obj))
#define EXPR_NODE_CLASS		expr_node_class_get()

static struct class *expr_node_class_get (void);

static void expr_node_init (struct expr_node *this, struct expr *expr)
{
    this->expr = expr;
}

static struct node *expr_node_new (struct expr *expr)
{
    struct object *node = object_new(EXPR_NODE_CLASS);

    expr_node_init(EXPR_NODE(node), expr);
    return NODE(node);
}

static void _expr_node_free (struct object *this)
{
    struct expr_node *node = EXPR_NODE(this);

    object_free(OBJECT(node->expr));
    NODE_CLASS->release(this);
}

static void _expr_node_eval (struct node *this, dstring_t *ds,
			     struct context *context)
{
    struct expr_node *node = EXPR_NODE(this);

    dstring_append(ds, "%s", expr_to_string(node->expr, context));
}

static struct class *expr_node_class_get (void)
{
    static struct node_class expr_node_class;
    struct class *class = (struct class *) &expr_node_class;

    if (class->name == NULL)
    {
	class_init(class, "expr_node", NODE_CLASS,
		   sizeof (struct expr_node), sizeof expr_node_class);

	expr_node_class.base.release = _expr_node_free;
	expr_node_class.eval = _expr_node_eval;
    }

    return class;
}

/* ------------------------------------------ */

struct tmpl_node
{
    struct node base;		/* base class */
    const char *name;		/* template name */
    array_t *nodes;		/* child nodes */
};

#define TMPL_NODE(obj)		((struct tmpl_node *) (obj))
#define TMPL_NODE_CLASS		tmpl_node_class_get()

static struct class *tmpl_node_class_get (void);

static void tmpl_node_init (struct tmpl_node *this, const char *name)
{
    this->name = name;
    this->nodes = array_new(sizeof (struct node *));
}

static struct node *tmpl_node_new (const char *name)
{
    struct object *node = object_new(TMPL_NODE_CLASS);

    tmpl_node_init(TMPL_NODE(node), name);
    return NODE(node);
}

static struct node *node_array_subtmpl (array_t *array, const char *name)
{
    struct node **nodes = array_values(array);
    struct node *result = NULL;
    int index;

    for (index = 0; index < array_len(array); ++index)
	if ((result = node_template(nodes[index], name)))
	    break;

    return result;
}

static struct tmpl_node *tmpl_node_subtmpl (struct tmpl_node *node,
					    const char *name)
{
    return TMPL_NODE(node_array_subtmpl(node->nodes, name));
}

static void node_array_free (array_t *array)
{
    struct node **nodes = array_values(array);
    int index;

    for (index = 0; index < array_len(array); ++index)
	object_free(OBJECT(nodes[index]));
    array_free(array);
}

static void _tmpl_node_free (struct object *this)
{
    struct tmpl_node *node = TMPL_NODE(this);

    node_array_free(node->nodes);
    NODE_CLASS->release(this);
}

static void node_array_eval (array_t *array, dstring_t *ds,
			     struct context *context)
{
    struct node **nodes = array_values(array);
    int index;

    for (index = 0; index < array_len(array); ++index)
	node_eval(nodes[index], ds, context);
}

static void _tmpl_node_eval (struct node *this, dstring_t *ds,
			     struct context *context)
{
    struct tmpl_node *node = TMPL_NODE(this);
    struct context *children = array_values(context->children);
    int index;

    for (index = 0; index < array_len(context->children); ++index)
    {
	struct context *context = &children[index];

	if (context->node == node)
	    node_array_eval(node->nodes, ds, context);
    }
}

static void _tmpl_node_add_child (struct node *this, struct node *child)
{
    struct tmpl_node *node = TMPL_NODE(this);

    array_add(node->nodes, &child);
}

static struct node *_tmpl_node_template (struct node *this, const char *name)
{
    struct tmpl_node *node = TMPL_NODE(this);

    return strcmp(node->name, name) == 0 ? this : NULL;
}

static struct class *tmpl_node_class_get (void)
{
    static struct node_class tmpl_node_class;
    struct class *class = (struct class *) &tmpl_node_class;

    if (class->name == NULL)
    {
	class_init(class, "tmpl_node", NODE_CLASS,
		   sizeof (struct tmpl_node), sizeof tmpl_node_class);

	tmpl_node_class.base.release = _tmpl_node_free;
	tmpl_node_class.eval = _tmpl_node_eval;
	tmpl_node_class.add_child = _tmpl_node_add_child;
	tmpl_node_class.template = _tmpl_node_template;
    }

    return class;
}

/* ------------------------------------------ */

struct root_node
{
    struct tmpl_node base;	/* base class */
    char *string;		/* template string */
    int refcount;		/* reference counter */
};

#define ROOT_NODE(obj)		((struct root_node *) (obj))
#define ROOT_NODE_CLASS		root_node_class_get()

static struct class *root_node_class_get (void);

static void root_node_init (struct root_node *this, char *template_str)
{
    tmpl_node_init(TMPL_NODE(this), "");
    this->string = template_str;
    this->refcount = 1;
}

static struct node *root_node_new (char *template_str)
{
    struct object *node = object_new(ROOT_NODE_CLASS);

    root_node_init(ROOT_NODE(node), template_str);
    return NODE(node);
}

static void _root_node_free (struct object *this)
{
    struct root_node *node = ROOT_NODE(this);

    if (--node->refcount == 0)
    {
	free(node->string);
	TMPL_NODE_CLASS->release(this);
    }
}

static struct class *root_node_class_get (void)
{
    static struct node_class root_node_class;
    struct class *class = (struct class *) &root_node_class;

    if (class->name == NULL)
    {
	class_init(class, "root_node", TMPL_NODE_CLASS,
		   sizeof (struct root_node), sizeof root_node_class);

	root_node_class.base.release = _root_node_free;
    }

    return class;
}

/* ------------------------------------------ */

struct cond_node
{
    struct node base;		/* base class */
    struct expr *condition;	/* expression */
    array_t *then_nodes;	/* then child nodes */
    array_t *else_nodes;	/* else child nodes */
};

#define COND_NODE(obj)		((struct cond_node *) (obj))
#define COND_NODE_CLASS		cond_node_class_get()

static struct class *cond_node_class_get (void);

static void cond_node_init (struct cond_node *this, struct expr *condition)
{
    this->condition = condition;
    this->then_nodes = array_new(sizeof (struct node *));
}

static struct node *cond_node_new (struct expr *condition)
{
    struct object *node = object_new(COND_NODE_CLASS);

    cond_node_init(COND_NODE(node), condition);
    return NODE(node);
}

static void cond_node_add_else (struct cond_node *node)
{
    if (node->else_nodes)
	error("cond_node_add_else: duplicate else block");

    node->else_nodes = array_new(sizeof (struct node *));
}

static void _cond_node_free (struct object *this)
{
    struct cond_node *node = COND_NODE(this);

    object_free(OBJECT(node->condition));
    node_array_free(node->then_nodes);
    if (node->else_nodes)
	node_array_free(node->else_nodes);
    NODE_CLASS->release(this);
}

static void _cond_node_eval (struct node *this, dstring_t *ds,
			     struct context *context)
{
    struct cond_node *node = COND_NODE(this);

    if (expr_bool_value(node->condition, context))
	node_array_eval(node->then_nodes, ds, context);
    else if (node->else_nodes)
	node_array_eval(node->else_nodes, ds, context);
}

static void _cond_node_add_child (struct node *this, struct node *child)
{
    struct cond_node *node = COND_NODE(this);

    if (node->else_nodes)
	array_add(node->else_nodes, &child);
    else
	array_add(node->then_nodes, &child);
}

static struct node *_cond_node_template (struct node *this, const char *name)
{
    struct cond_node *node = COND_NODE(this);
    struct node *result = node_array_subtmpl(node->then_nodes, name);

    if (!result && node->else_nodes)
	result = node_array_subtmpl(node->else_nodes, name);

    return result;
}

static struct class *cond_node_class_get (void)
{
    static struct node_class cond_node_class;
    struct class *class = (struct class *) &cond_node_class;

    if (class->name == NULL)
    {
	class_init(class, "cond_node", NODE_CLASS,
		   sizeof (struct cond_node), sizeof cond_node_class);

	cond_node_class.base.release = _cond_node_free;
	cond_node_class.eval = _cond_node_eval;
	cond_node_class.add_child = _cond_node_add_child;
	cond_node_class.template = _cond_node_template;
    }

    return class;
}

/*
 * template markup table
 */
enum TemplateTag
{
    TAG_TMPL_BEGIN,
    TAG_TMPL_END,
    TAG_COND_BEGIN,
    TAG_COND_ELSEIF,
    TAG_COND_ELSE,
    TAG_COND_END,
    TAG_EXPRESSION
};

static const struct markup {
    const char *begin;		/* start string */
    size_t len;			/* start length */
    int end_token;		/* end token type */
    int descend;		/* recursive descend */
    int type;			/* template tag type */
} markup_table[] = {
    { "<tmpl:",	  6, '>', 1, TAG_TMPL_BEGIN  },
    { "</tmpl:",  7, '>', 1, TAG_TMPL_END    },
    { "{if ",	  4, '}', 1, TAG_COND_BEGIN  },
    { "{elseif ", 8, '}', 0, TAG_COND_ELSEIF },
    { "{else}",	  6, 0  , 0, TAG_COND_ELSE   },
    { "{/if}",	  5, 0  , 0, TAG_COND_END    },
    { "{",	  1, '}', 0, TAG_EXPRESSION  },
    { NULL }			/* end markup table */
};

/*
 * Parse the given expression and advance pointer.
 */
static struct expr *parse_string (char **string, int end_token, const char *msg)
{
    scanner_t *scanner = scanner_new(*string);
    struct expr *result = NULL;

    if (scanner_next_token(scanner) == TOKEN_SYMBOL && end_token == '>')
    {
	int symbol_len = scanner_position(scanner);

	scanner_next_token(scanner);
	(*string)[symbol_len] = '\0';
    }
    else
    {
	result = parse_expr(scanner);
    }

    if (scanner_token_type(scanner) != end_token)
	error("parse_template: malformed template: %s", msg);

    *string += scanner_position(scanner);
    scanner_free(scanner);
    return result;
}

/*
 * Parse the given template string and build the node tree.
 */
static char *parse_template (struct node *node, char *string)
{
    char *ptr = string;

    while (*ptr)
    {
	const struct markup *markup;
	struct node *child = NULL;
	struct expr *expr;
	char *next;

	for (markup = markup_table; markup->begin; ++markup)
	    if (strncmp(ptr, markup->begin, markup->len) == 0)
		break;

	if (!markup->begin)
	{
	    ++ptr;
	    continue;
	}

	next = ptr + markup->len;	/* skip the markup */

	if (markup->end_token)
	    expr = parse_string(&next, markup->end_token, ptr);

	if (ptr != string)
	{
	    *ptr = '\0';
	    node_add_child(node, string_node_new(string));
	}

	switch (markup->type)
	{
	    case TAG_TMPL_BEGIN:
		child = tmpl_node_new(ptr + markup->len);
		break;
	    case TAG_COND_BEGIN:
		child = cond_node_new(expr);
		break;
	    case TAG_EXPRESSION:
		child = expr_node_new(expr);
		break;
	    case TAG_TMPL_END:
		if (strcmp(TMPL_NODE(node)->name, ptr + markup->len) != 0)
		    error("parse_template: malformed template at <%s>", ptr+1);
		return next;
	    case TAG_COND_ELSEIF:
		cond_node_add_else(COND_NODE(node));
		child = cond_node_new(expr);
		node_add_child(node, child);
		return parse_template(child, next);
	    case TAG_COND_ELSE:
		cond_node_add_else(COND_NODE(node));
		break;
	    case TAG_COND_END:
		return next;
	}

	if (child)
	{
	    if (markup->descend)
		next = parse_template(child, next);
	    node_add_child(node, child);
	}

	string = ptr = next;
    }

    if (ptr != string)
	node_add_child(node, string_node_new(string));
    return ptr;
}

/*
 * simple template data type (opaque)
 */
struct template
{
    struct tmpl_node *root;	/* root template node */
    struct context context;	/* root context */
    struct context *current;	/* current context */
};

/*
 * Create, copy and destroy templates.
 */
template_t *template_new (const char *string)
{
    template_t *template = xmalloc(sizeof *template);
    char *template_str = xstrdup(string);

    template->root = TMPL_NODE(root_node_new(template_str));
    template->current = &template->context;
    context_init(&template->context, template->root, NULL);
    parse_template(NODE(template->root), template_str);
    return template;
}

template_t *template_from_file (const char *filename)
{
    dstring_t *ds = dstring_new("");
    FILE *file = fopen(filename, "r");
    char buffer[BUFSIZ];
    int len;

    if (file == NULL)
	error("%s: %s", filename, strerror(errno));

    while ((len = fread(buffer, 1, sizeof buffer, file)))
	dstring_append(ds, "%.*s", len, buffer);

    if (ferror(file))
	error("%s: %s", filename, strerror(errno));
    fclose(file);
    return template_new(dstring_str(ds));
}

template_t *template_copy (template_t *template)
{
    template_t *copy = xmalloc(sizeof *copy);
    struct root_node *root = ROOT_NODE(template->root);

    ++root->refcount;
    copy->root = template->root;
    copy->current = &copy->context;
    context_copy(&copy->context, &template->context, NULL);
    return copy;
}

void template_free (template_t *template)
{
    object_free(OBJECT(template->root));
    context_destroy(&template->context);
    free(template);
}

/*
 * Functions for working with templates. template_clear() removes all
 * values and iterations set with template_set() or template_iterate().
 * template_context() changes the current context (similar to chdir()).
 */
const char *template_eval (template_t *template)
{
    dstring_t *ds = dstring_new("");

    node_array_eval(template->root->nodes, ds, &template->context);
    return dstring_str(ds);
}

void template_clear (template_t *template)
{
    template->current = &template->context;
    context_destroy(&template->context);
    context_init(&template->context, template->root, NULL);
}

static int path_symbol (int chr, int first)
{
    return isalnum(chr) || chr == '_' || chr == '.';
}

static scanner_t *path_scanner_new (const char *path)
{
    scanner_t *scanner = scanner_new(path);

    scanner_config_symbol(scanner, path_symbol);
    return scanner;
}

static struct context *context_enter (struct context *context, const char *name)
{
    struct tmpl_node *node;
    struct context *children = array_values(context->children);
    struct context new_context;
    int index;

    if (strcmp(name, ".") == 0)
	return context;
    else if (strcmp(name, "..") == 0)
	return context->parent ? context->parent : context;
    else if ((node = tmpl_node_subtmpl(context->node, name)) == NULL)
	return NULL;

    for (index = array_len(context->children) - 1; index >= 0; --index)
	if (children[index].node == node)
	    return &children[index];

    context_init(&new_context, node, context);
    return array_add(context->children, &new_context);
}

static struct context *template_lookup (template_t *template,
					scanner_t *scanner)
{
    struct context *context = template->current;

    if (scanner_next_token(scanner) == '/')
    {
	scanner_next_token(scanner);
	context = &template->context;
    }

    while (!scanner_is_at_end(scanner))
    {
	context = context_enter(context, scanner_token_string_value(scanner));

	if (context == NULL)
	    return NULL;

	if (scanner_next_token(scanner) != '/')
	    return NULL;

	scanner_next_token(scanner);
    }

    return context;
}

void template_context (template_t *template, const char *path)
{
    scanner_t *scanner = path_scanner_new(path);
    struct context *context = template_lookup(template, scanner);
    const char *name = scanner_token_string_value(scanner);

    if (scanner_token_type(scanner) != TOKEN_NONE)
	context = context_enter(context, name);

    if (context)
	template->current = context;

    scanner_free(scanner);
}

void template_iterate (template_t *template, const char *path)
{
    scanner_t *scanner = path_scanner_new(path);
    struct context *context = template_lookup(template, scanner);
    const char *name = scanner_token_string_value(scanner);
    struct tmpl_node *node;

    if (context && (node = tmpl_node_subtmpl(context->node, name)))
    {
	struct context new_context;

	context_init(&new_context, node, context);
	array_add(context->children, &new_context);
    }

    scanner_free(scanner);
}

const char *template_get (template_t *template, const char *path)
{
    scanner_t *scanner = path_scanner_new(path);
    struct context *context = template_lookup(template, scanner);
    const char *name = scanner_token_string_value(scanner);
    const char *result = NULL;

    if (context)
	result = hashtable_lookup(context->bindings, name);

    scanner_free(scanner);
    return result;
}

void template_unset (template_t *template, const char *path)
{
    scanner_t *scanner = path_scanner_new(path);
    struct context *context = template_lookup(template, scanner);
    const char *name = scanner_token_string_value(scanner);

    if (context)
	hashtable_remove(context->bindings, name);

    scanner_free(scanner);
}

void template_set (template_t *template, const char *path, const char *value)
{
    scanner_t *scanner = path_scanner_new(path);
    struct context *context = template_lookup(template, scanner);
    const char *name = scanner_token_string_value(scanner);

    if (context)
	hashtable_insert(context->bindings, name, value);

    scanner_free(scanner);
}

void template_set_fmt (template_t *template, const char *path,
		       const char *fmt, ...)
{
    dstring_t *ds = dstring_new("");
    va_list ap;

    va_start(ap, fmt);
    dstring_vappend(ds, fmt, ap);
    va_end(ap);

    template_set(template, path, dstring_str(ds));
}
