/*
 * template.c - test template system functions
 * Copyright (c) 2003  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <assert.h>
#include <string.h>

#include "memory.h"
#include "template.h"

static const char *testdata =
    "{baz}<tmpl:FOO>{if x}<tmpl:BAR>{bar}+</tmpl:BAR>-{/if}{foo}</tmpl:FOO>"
    "{if !x}{bar}={foo}<tmpl:BAR>xyzzy</tmpl:BAR>{else}X{/if}::";
static const char *result1a = "42+red+test+-bar-barX::";
static const char *result1b = "barbar=bar::";

static void test_iterate (template_t *template, const char *result)
{
    template_t *template2;

    template_set(template, "/baz", "foo");
    template_unset(template, "./baz");
    template_set(template, "/foo", "bar");
    template_set(template, "FOO/bar", "test");
    template_set(template, "FOO/baz", "frob");
    template_context(template, "/FOO");
    template_set_fmt(template, "BAR/./bar", "%d", 42);
    template_iterate(template, "../FOO/BAR");
    template_set(template, "/FOO/BAR/bar", "baz");
    template_set(template, "/FOO/BAR/bar", "red");
    template_context(template, "..");
    template_iterate(template, "FOO/BAR");
    template_iterate(template, "FOO");
    template2 = template_copy(template);

    assert(template_get(template, "/baz") == NULL);
    assert(strcmp(template_get(template, "/foo"), "bar") == 0);
    assert(strcmp(template_eval(template), result) == 0);
    assert(strcmp(template_eval(template2), result) == 0);
    template_free(template2);
}

static const char *testdata2 =
    "<tmpl:EXPR>"
    "{if i+1>4 && i<(1+10/2)}{i==-4*-1 ? 'foo'.i : \"bar\"}"
    "{elseif !(i<=+4)}+{elseif i==''}..{else}{'-'}{/if}"
    "</tmpl:EXPR>";
static const char *result2 = "----foo4bar++++";

static void test_iterate2 (template_t *template, const char *result)
{
    int i;

    for (i = 0; i < 10; ++i)
    {
	template_iterate(template, "EXPR");
	template_set_fmt(template, "EXPR/i", "%d", i);
    }

    assert(strcmp(template_eval(template), result) == 0);
}

void test_template (void)
{
    struct memory_pool *pool = memory_pool_new();
    template_t *template = template_new(testdata);

    template_set(template, "x", "true");
    test_iterate(template, result1a);
    template_context(template, "/FOO");
    template_clear(template);
    test_iterate(template, result1b);
    template_free(template);

    template = template_new(testdata2);
    test_iterate2(template, result2);
    template_free(template);

    memory_pool_free(pool);
}
