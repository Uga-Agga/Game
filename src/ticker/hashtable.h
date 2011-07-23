/*
 * hashtable.h - hash table for storing key-value mappings
 * Copyright (c) 2003  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _HASHTABLE_H_
#define _HASHTABLE_H_

#include <stddef.h>

/*
 * simple hash table data type (opaque)
 */
typedef struct hashtable hashtable_t;

/*
 * Some pre-defined hashing and comparison functions (for ints, pointers
 * and strings). The comparison functions below all return a boolean value.
 */
extern unsigned int_hash (const void *key);
extern int int_equals (const void *key1, const void *key2);

extern unsigned pointer_hash (const void *key);
extern int pointer_equals (const void *key1, const void *key2);

extern unsigned string_hash (const void *key);
extern int string_equals (const void *key1, const void *key2);

/*
 * Create, copy and destroy hash tables. If you use the extended version
 * hashtable_new_ex(), the given copy and free functions will be used to
 * maintain copies of the keys and values inside the table, i.e. the keys
 * and values will be duplicated upon insert and released when the entry
 * is removed from the table. Each copy and free function may be NULL to
 * indicate that no copy or free operation should be performed.
 */
extern hashtable_t *hashtable_new (unsigned (*hash)(const void *key),
			 int (*eq)(const void *key1, const void *key2));
extern hashtable_t *hashtable_new_ex (unsigned (*hash)(const void *key),
			    int (*eq)(const void *key1, const void *key2),
			    void *(*key_copy)(const void *key),
			    void  (*key_free)(void *key),
			    void *(*value_copy)(const void *value),
			    void  (*value_free)(void *value));
extern hashtable_t *hashtable_copy (hashtable_t *hash);
extern void hashtable_free (hashtable_t *hash);

/*
 * Functions for working with hash tables. hashtable_size() returns the
 * number of mappings in the table, hashtable_lookup() tries to look up a
 * key and returns the corresponding value (or NULL if it is not found).
 * hashtable_insert() returns the replaced value (or NULL if the key was
 * not yet in the table) and hashtable_remove() returns the removed value.
 */
extern size_t hashtable_size (hashtable_t *hash);
extern int hashtable_contains (hashtable_t *hash, const void *key);
extern void *hashtable_lookup (hashtable_t *hash, const void *key);
extern void *hashtable_insert (hashtable_t *hash, const void *key,
			       const void *value);
extern void *hashtable_remove (hashtable_t *hash, const void *key);

/*
 * hash table entry (required for iteration)
 */
struct link
{
    struct link *next;
    void *key;
    void *value;
};

/*
 * Obtain an iterator over all the hash table's entries. The iterator
 * becomes invalid when the hash table is modified in any way.
 */
extern struct iterator *hashtable_iterator (hashtable_t *hash);
extern const struct link *iterator_next (struct iterator *iterator);

#endif /* _HASHTABLE_H_ */
