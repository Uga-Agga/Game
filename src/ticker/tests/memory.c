/*
 * memory.c - test memory and string utility functions
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

static void test_memory_recursive (int level)
{
    struct memory_pool *pool = memory_pool_new();
    array_t *array = array_new(sizeof (char *));
    dstring_t *buf = dstring_new("");
    array_t *copy;
    int i;

    for (i = 0; i < 1000; ++i)
    {
	dstring_t *ds = dstring_new("hello world");
	char *foobar = "foobar";

	mp_realloc(mp_malloc(1000), 2000);
	array_add(array, &foobar);
	array_remove_index(array, i / 2);
	array_add(array, &foobar);

	dstring_truncate(ds, 2000);
	dstring_truncate(ds, 5);
	foobar = dstring_str(ds);
	array_set_index(array, i, &foobar);
	dstring_append(buf, "%s", foobar);

	assert(*(char **) array_get_index(array, i) == dstring_str(ds));
    }

    copy = array_copy(array);

    assert(dstring_len(buf) == strlen(dstring_str(buf)));
    assert(dstring_len(buf) == 5 * i);
    assert(array_len(array) == i);
    assert(array_len(array) == array_len(copy));
    assert(!memcmp(array_values(array), array_values(copy), i*sizeof (char *)));

    if (level > 0) test_memory_recursive(--level);

    array_free(copy);
    array_free(array);
    memory_pool_free(pool);
}

static int datacmp (const char **v1, const char **v2)
{
    return strcmp(*v1, *v2);
}

static void test_array_sort (void)
{
    const char *data[] = {
	"zero", "one", "two", "three", "four", "five", "six", "seven"
    };
    array_t *array = array_new(sizeof (char *));
    const char **values;
    int index;

    array_sort(array, (int (*)(const void *, const void *)) datacmp);

    for (index = 0; index < sizeof data / sizeof data[0]; ++index)
	array_add(array, &data[index]);

    array_sort(array, (int (*)(const void *, const void *)) datacmp);
    values = array_values(array);

    for (index = 1; index < array_len(array); ++index)
	assert(strcmp(values[index-1], values[index]) <= 0);

    array_free(array);
}

void test_memory (void)
{
    test_memory_recursive(4);
    test_array_sort();
}
