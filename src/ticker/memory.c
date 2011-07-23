/*
 * memory.c - memory and string utility functions
 * Copyright (c) 2003  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "logging.h"
#include "memory.h"

#ifdef _REENTRANT
#include <glib.h>
#endif

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
static void xfree (void *ptr) { free(ptr); }
#undef	free
#define free	xfree
#endif

/*
 * Variants of the standard memory allocators using error checks.
 * These functions never return NULL (they call exit() instead).
 */
void *xmalloc (size_t size)
{
    void *addr = malloc(size);

    if (size && !addr) perror("malloc"), exit(EXIT_FAILURE);
    return addr;
}

void *xcalloc (size_t num, size_t size)
{
    void *addr = calloc(num, size);

    if (num * size && !addr) perror("calloc"), exit(EXIT_FAILURE);
    return addr;
}

void *xrealloc (void *addr, size_t size)
{
    addr = realloc(addr, size);

    if (size && !addr) perror("realloc"), exit(EXIT_FAILURE);
    return addr;
}

/*
 * memory chunk/pool data type (supports nested pools and exceptions)
 */
struct memory_chunk
{
    void *addr;
    void (*free_func)(void *ptr);
};

struct memory_pool
{
    struct memory_pool *next;
    struct memory_chunk *chunks;
    size_t num_chunks;
    size_t num_alloc;
};

#ifdef _REENTRANT
static GStaticPrivate head_key = G_STATIC_PRIVATE_INIT;

#define memory_pool_get_head()	g_static_private_get(&head_key)
#define memory_pool_set_head(p)	g_static_private_set(&head_key, p, 0)
#else
static struct memory_pool *head;	/* head of memory_pool list */

#define memory_pool_get_head()	(head)
#define memory_pool_set_head(p)	(head = p)
#endif

/*
 * Functions for memory pool handling. These may be used to register
 * memory areas for automatic deallocation (using memory_pool_add()).
 * memory_pool_add() and memory_pool_replace() return the registered
 * address.
 */
struct memory_pool *memory_pool_new (void)
{
    struct memory_pool *pool = xcalloc(1, sizeof *pool);

    pool->next = memory_pool_get_head();
    memory_pool_set_head(pool);
    return pool;
}

void *memory_pool_add (void *addr, void (*free_func)(void *addr))
{
    struct memory_pool *pool = memory_pool_get_head();

    if (!pool) error("memory_pool_add: no memory pool");
    if (!free_func) return addr;

    if (pool->num_chunks == pool->num_alloc)
    {
	pool->num_alloc += 64;
	pool->chunks =
	    xrealloc(pool->chunks, pool->num_alloc * sizeof pool->chunks[0]);
    }

    pool->chunks[pool->num_chunks].addr = addr;
    pool->chunks[pool->num_chunks].free_func = free_func;
    ++pool->num_chunks;
    return addr;
}

void *memory_pool_replace (void *old_addr, void *new_addr)
{
    struct memory_pool *pool;
    int index;

    for (pool = memory_pool_get_head(); pool; pool = pool->next)
	for (index = 0; index < pool->num_chunks; ++index)
	    if (pool->chunks[index].addr == old_addr)
		return pool->chunks[index].addr = new_addr;

    error("memory_pool_replace: unknown address");
    return NULL;
}

void memory_pool_free (struct memory_pool *pool)
{
    int index;

    if (!pool) error("memory_pool_free: invalid pool");

    while (memory_pool_get_head() != pool)
	memory_pool_free(memory_pool_get_head());
    memory_pool_set_head(pool->next);

    for (index = 0; index < pool->num_chunks; ++index)
	pool->chunks[index].free_func(pool->chunks[index].addr);
    free(pool->chunks);
    free(pool);
}

/*
 * Variants of the standard memory allocators using a memory pool.
 * Do *not* call free() on the result values.
 */
void *mp_malloc (size_t size)
{
    return memory_pool_add(xmalloc(size), free);
}

void *mp_calloc (size_t num, size_t size)
{
    return memory_pool_add(xcalloc(num, size), free);
}

void *mp_realloc (void *addr, size_t size)
{
    return addr ? memory_pool_replace(addr, xrealloc(addr, size))
		: memory_pool_add(xmalloc(size), free);
}

/*
 * strdup() variants (using the corresponding memory allocators).
 */
char *xstrdup (const char *string)
{
    return strcpy(xmalloc(strlen(string) + 1), string);
}

char *mp_strdup (const char *string)
{
    return memory_pool_add(xstrdup(string), free);
}

/*
 * Utility functions for working with dynamic arrays. They can store
 * any number of elements of a fixed size (just like ordinary vectors).
 * array_add() appends a copy of the value to the end of the array
 * and returns the pointer to the new element inside the array.
 */
array_t *array_new (size_t element_size)
{
    array_t *array = xcalloc(1, sizeof *array);

    array->element_size = element_size;
    return array;
}

array_t *array_copy (array_t *array)
{
    size_t length = array->len;
    size_t size = array->element_size;
    array_t *copy = array_new(size);

    if (length)
    {
	copy->len = copy->alloc = length;
	copy->values = xmalloc(length * size);
	memcpy(copy->values, array->values, length * size);
    }

    return copy;
}

void array_free (array_t *array)
{
    free(array->values);
    free(array);
}

void *array_add (array_t *array, const void *value)
{
    size_t size = array->element_size;
    unsigned long addr;

    if (array->len == array->alloc)
    {
	array->alloc += 16;
	array->values = xrealloc(array->values, array->alloc * size);
    }

    addr = (unsigned long) array->values + array->len++ * size;
    return memcpy((void *) addr, value, size);
}

void *array_get_index (array_t *array, int index)
{
    unsigned long addr;

    if (index >= array->len)
	error("array_get_index: index too large");

    addr = (unsigned long) array->values + index * array->element_size;
    return (void *) addr;
}

void array_set_index (array_t *array, int index, const void *value)
{
    unsigned long addr;

    if (index >= array->len)
	error("array_set_index: index too large");

    addr = (unsigned long) array->values + index * array->element_size;
    memcpy((void *) addr, value, array->element_size);
}

void array_remove_index (array_t *array, int index)
{
    size_t size = array->element_size;
    unsigned long addr;

    if (index >= array->len)
	error("array_remove_index: index too large");

    addr = (unsigned long) array->values + index * size;
    memmove((void *) addr, (void *) (addr + size),
	    (--array->len - index) * size);
}

void array_sort (array_t *array, int (*cmp)(const void *v1, const void *v2))
{
    if (array->len)
	qsort(array->values, array->len, array->element_size, cmp);
}

/*
 * Utility functions for working with dynamic strings. These functions
 * allocate memory from a memory pool, so there is no dstring_free().
 *
 * Take care to escape `%'-characters properly for dstring_new() etc.
 * if they should be part of the result string (the `fmt' parameter is
 * a printf-style format string!).
 */
static void dstring_free (dstring_t *ds);

dstring_t *dstring_new (const char *fmt, ...)
{
    dstring_t *ds = xcalloc(1, sizeof *ds);
    va_list ap;

    ds->alloc = 1024;
    ds->text = xmalloc(ds->alloc);
    va_start(ap, fmt);
    dstring_vappend(ds, fmt, ap);
    va_end(ap);
    return memory_pool_add(ds, (void (*)(void *)) dstring_free);
}

static void dstring_free (dstring_t *ds)
{
    free(ds->text);
    free(ds);
}

dstring_t *dstring_set (dstring_t *ds, const char *fmt, ...)
{
    va_list ap;

    ds->len = 0;
    va_start(ap, fmt);
    dstring_vappend(ds, fmt, ap);
    va_end(ap);
    return ds;
}

dstring_t *dstring_append (dstring_t *ds, const char *fmt, ...)
{
    va_list ap;

    va_start(ap, fmt);
    dstring_vappend(ds, fmt, ap);
    va_end(ap);
    return ds;
}

dstring_t *dstring_vappend (dstring_t *ds, const char *fmt, va_list ap)
{
    va_list aq;
    int len;

    for (;;)
    {
	/* x64 Patch
        len = vsnprintf(ds->text + ds->len, ds->alloc - ds->len, fmt, ap);
        */
        va_copy(aq, ap);
        len = vsnprintf(ds->text + ds->len, ds->alloc - ds->len, fmt, aq);
        va_end(aq);

	if (len < 0)
	    /* just guess here if vsnprintf is broken (WIN32) */
	    ds->alloc += 1024;
	else if (len >= ds->alloc - ds->len)
	    /* round allocation size to next multiple of 1024 */
	    ds->alloc = ((ds->len + len) | 1023) + 1;
	else
	    break;

	ds->text = xrealloc(ds->text, ds->alloc);
    }

    ds->len += len;
    return ds;
}

dstring_t *dstring_truncate (dstring_t *ds, size_t len)
{
    if (len < ds->len)
	ds->text[ds->len = len] = '\0';
    return ds;
}
