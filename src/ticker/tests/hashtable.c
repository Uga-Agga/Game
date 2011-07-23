/*
 * hashtable.c - test hash table functions
 * Copyright (c) 2003  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <assert.h>
#include <stdlib.h>
#include <string.h>

#include "hashtable.h"
#include "memory.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
static void xfree (void *ptr) { free(ptr); }
#undef	free
#define free	xfree
#endif

static struct testdata {
    const char *key;
    const char *value;
    int ok;
} testdata[] = {
    { "zero",  "ZERO"  },
    { "one",   "ONE"   },
    { "two",   "TWO"   },
    { "three", "THREE" },
    { "four",  "FOUR"  },
    { "five",  "FIVE"  },
    { "six",   "SIX"   },
    { "seven", "SEVEN" },
    { "eight", "EIGHT" },
    { "nine",  "NINE"  },
    { "ten",   "TEN"   },
    { "lots",  "LOTS"  },
};

static void test_iterator (hashtable_t *hash, struct testdata *data, int num)
{
    struct iterator *iterator = hashtable_iterator(hash);
    const struct link *link;
    int index;

    assert(hashtable_size(hash) == num);

    while ((link = iterator_next(iterator)))
	for (index = 0; index < num; ++index)
	    if (strcmp(data[index].key, link->key) == 0)
		data[index].ok = 1;

    for (index = 0; index < num; ++index)
	assert(data[index].ok == 1), data[index].ok = 0;
}

static void test_insert (hashtable_t *hash, struct testdata *data, int num)
{
    int index;

    for (index = 0; index < num; ++index)
	hashtable_insert(hash, data[index].key, data[index].value);

    for (index = 0; index < num; ++index)
	assert(hashtable_contains(hash, data[index].key) &&
	       hashtable_lookup(hash, data[index].key) == data[index].value);
}

static void test_remove (hashtable_t *hash, struct testdata *data, int num)
{
    int index;

    for (index = 0; index < num; ++index)
	hashtable_remove(hash, data[index].key);

    for (index = 0; index < num; ++index)
	assert(hashtable_lookup(hash, data[index].key) == NULL);
}

static void test_copy (hashtable_t *hash, struct testdata *data, int num)
{
    hashtable_t *copy = hashtable_copy(hash);
    int index;

    for (index = 0; index < num; ++index)
	assert(hashtable_lookup(copy, data[index].key) == data[index].value);

    hashtable_free(copy);
}

static void test_copy_free (hashtable_t *hash, struct testdata *data, int num)
{
    int index;

    for (index = 0; index < num; ++index)
	hashtable_insert(hash, data[index].key, data[index].value);

    for (index = 0; index < num; ++index)
	assert(strcmp(hashtable_lookup(hash, data[index].key),
		      data[index].value) == 0);

    for (index = 0; index < num; ++index)
	hashtable_insert(hash, data[index].key, data[index].value);

    for (index = 0; index < num; ++index)
	assert(strcmp(hashtable_lookup(hash, data[index].key),
		      data[index].value) == 0);

    hashtable_free(hashtable_copy(hash));

    for (index = 0; index < num; ++index)
	hashtable_remove(hash, data[index].key);
}

void test_hashtable (void)
{
    hashtable_t *hash = hashtable_new(string_hash, string_equals);

    test_insert(hash, testdata, 6);
    test_iterator(hash, testdata, 6);

    test_insert(hash, testdata, 6);
    test_remove(hash, testdata, 6);
    test_iterator(hash, testdata, 0);

    test_insert(hash, testdata, 12);
    test_iterator(hash, testdata, 12);
    test_copy(hash, testdata, 12);

    hashtable_free(hash);

    hash = hashtable_new_ex(string_hash, string_equals,
			    (void *(*)(const void *)) xstrdup, free,
			    (void *(*)(const void *)) xstrdup, free);

    test_copy_free(hash, testdata, 12);
    hashtable_free(hash);
}
