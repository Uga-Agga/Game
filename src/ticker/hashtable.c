/*
 * hashtable.c - hash table for storing key-value mappings
 * Copyright (c) 2003  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>
#include <string.h>

#include "hashtable.h"
#include "memory.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

struct iterator			/* iterator status */
{
    hashtable_t *hash;
    int index;			/* current bucket */
    struct link *node;		/* current node ptr */
};

/*
 * simple hash table datatype (opaque)
 */
struct hashtable
{
    size_t size;		/* count of elements */
    size_t alloc;		/* allocated buckets */
    struct link **data;		/* vector of buckets */
    struct iterator iterator;	/* built-in iterator */
    				/* operations on keys */
    unsigned (*hash)(const void *key);
    int (*equals)(const void *key1, const void *key2);
    void *(*key_copy)(const void *key);
    void  (*key_free)(void *key);
    				/* operations on values */
    void *(*value_copy)(const void *value);
    void  (*value_free)(void *value);
};

/*
 * Some pre-defined hashing and comparison functions (for ints, pointers
 * and strings). The comparison functions below all return a boolean value.
 */
unsigned int_hash (const void *key)
{
    return (int) key;
}

int int_equals (const void *key1, const void *key2)
{
    return (int) key1 == (int) key2;
}

unsigned pointer_hash (const void *key)
{
    return (unsigned long) key / sizeof (void *);
}

int pointer_equals (const void *key1, const void *key2)
{
    return key1 == key2;
}

unsigned string_hash (const void *key)
{
    const char *string = key;
    unsigned int result = 0;

    while (*string)
	result = result << 1 ^ *string++;
    return result;
}

int string_equals (const void *key1, const void *key2)
{
    return key1 == key2 || strcmp(key1, key2) == 0;
}

static void *pointer_copy (const void *ptr)
{
    return (void *) ptr;
}

static void pointer_free (void *ptr)
{
}

/*
 * Create, copy and destroy hash tables. If you use the extended version
 * hashtable_new_ex(), the given copy and free functions will be used to
 * maintain copies of the keys and values inside the table, i.e. the keys
 * and values will be duplicated upon insert and released when the entry
 * is removed from the table. Each copy and free function may be NULL to
 * indicate that no copy or free operation should be performed.
 */
hashtable_t *hashtable_new (unsigned (*hash)(const void *key),
			    int (*eq)(const void *key1, const void *key2))
{
    return hashtable_new_ex(hash, eq, NULL, NULL, NULL, NULL);
}

hashtable_t *hashtable_new_ex (unsigned (*hash)(const void *key),
			       int (*eq)(const void *key1, const void *key2),
			       void *(*key_copy)(const void *key),
			       void  (*key_free)(void *key),
			       void *(*value_copy)(const void *value),
			       void  (*value_free)(void *value))
{
    hashtable_t *hashtable = xcalloc(1, sizeof *hashtable);

    hashtable->hash = hash ? hash : pointer_hash;
    hashtable->equals = eq ? eq : pointer_equals;
    hashtable->key_copy = key_copy ? key_copy : pointer_copy;
    hashtable->key_free = key_free ? key_free : pointer_free;
    hashtable->value_copy = value_copy ? value_copy : pointer_copy;
    hashtable->value_free = value_free ? value_free : pointer_free;
    return hashtable;
}

hashtable_t *hashtable_copy (hashtable_t *hash)
{
    hashtable_t *copy =
	hashtable_new_ex(hash->hash, hash->equals, hash->key_copy,
			 hash->key_free, hash->value_copy, hash->value_free);

    if (hash->size)
    {
	int index = hash->alloc;

	copy->size = hash->size;
	copy->alloc = hash->alloc;
	copy->data = xmalloc(hash->alloc * sizeof *hash->data);

	while (--index >= 0)
	{
	    struct link **link_copy = &copy->data[index], *link;

	    for (link = hash->data[index]; link; link = link->next)
	    {
		(*link_copy) = xmalloc(sizeof *link);
		(*link_copy)->key = hash->key_copy(link->key);
		(*link_copy)->value = hash->value_copy(link->value);
		link_copy = &(*link_copy)->next;
	    }

	    *link_copy = NULL;
	}
    }

    return copy;
}

void hashtable_free (hashtable_t *hash)
{
    int index = hash->alloc;

    while (--index >= 0)
    {
	struct link *link, *next;

	for (link = hash->data[index]; link; link = next)
	{
	    next = link->next;
	    hash->key_free(link->key);
	    hash->value_free(link->value);
	    free(link);
	}
    }

    free(hash->data);
    free(hash);
}

/*
 * Functions for working with hash tables. hashtable_size() returns the
 * number of mappings in the table, hashtable_lookup() tries to look up a
 * key and returns the corresponding value (or NULL if it is not found).
 * hashtable_insert() returns the replaced value (or NULL if the key was
 * not yet in the table) and hashtable_remove() returns the removed value.
 */
size_t hashtable_size (hashtable_t *hash)
{
    return hash->size;
}

static struct link **hashtable_search (hashtable_t *hash, const void *key)
{
    struct link **link, *next;
    unsigned bucket;

    if (hash->alloc == 0) return NULL;

    bucket = hash->hash(key) % hash->alloc;

    for (link = &hash->data[bucket]; (next = *link); link = &next->next)
	if (hash->equals(key, next->key))
	    break;

    return link;
}

int hashtable_contains (hashtable_t *hash, const void *key)
{
    struct link **link = hashtable_search(hash, key);

    return link && *link;
}

void *hashtable_lookup (hashtable_t *hash, const void *key)
{
    struct link **link = hashtable_search(hash, key);

    return link && *link ? (*link)->value : NULL;
}

static void hashtable_resize (hashtable_t *hash)
{
    struct link **data = hash->data;
    size_t alloc = hash->alloc;
    int index;

    if (alloc == 0) hash->alloc = 1;

    hash->alloc = hash->alloc * 2 + 9;
    hash->data = xcalloc(hash->alloc, sizeof *data);

    for (index = 0; index < alloc; ++index)
    {
	struct link *link, *next;

	for (link = data[index]; link; link = next)
	{
	    struct link **new_link = hashtable_search(hash, link->key);

	    (*new_link) = xmalloc(sizeof *link);
	    (*new_link)->next = NULL;
	    (*new_link)->key = link->key;
	    (*new_link)->value = link->value;

	    next = link->next;
	    free(link);
	}
    }

    free(data);
}

void *hashtable_insert (hashtable_t *hash, const void *key, const void *value)
{
    struct link **link, *next;
    void *result = NULL;

    if (hash->size >= hash->alloc)
	hashtable_resize(hash);

    link = hashtable_search(hash, key);

    if ((next = *link))
    {
	result = next->value;
	hash->value_free(result);
    }
    else
    {
	++hash->size;
	next = *link = xmalloc(sizeof *next);
	next->next = NULL;
	next->key = hash->key_copy(key);
    }

    next->value = hash->value_copy(value);
    return result;
}

void *hashtable_remove (hashtable_t *hash, const void *key)
{
    struct link **link, *next;
    void *result = NULL;

    link = hashtable_search(hash, key);

    if (link && (next = *link))
    {
	--hash->size;
	*link = next->next;
	result = next->value;
	hash->key_free(next->key);
	hash->value_free(result);
	free(next);
    }

    return result;
}

/*
 * Obtain an iterator over all the hash table's entries. The iterator
 * becomes invalid when the hash table is modified in any way.
 */
struct iterator *hashtable_iterator (hashtable_t *hash)
{
    hash->iterator.hash = hash;
    hash->iterator.index = -1;
    hash->iterator.node = NULL;
    return &hash->iterator;
}

const struct link *iterator_next (struct iterator *iterator)
{
    hashtable_t *hash = iterator->hash;

    if (iterator->node && iterator->node->next)
	return iterator->node = iterator->node->next;

    while (++iterator->index < hash->alloc)
	if (hash->data[iterator->index])
	    return iterator->node = hash->data[iterator->index];

    return NULL;
}
