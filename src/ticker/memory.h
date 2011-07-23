/*
 * memory.h - memory and string utility functions
 * Copyright (c) 2003  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _MEMORY_H_
#define _MEMORY_H_

#include <stdarg.h>
#include <stddef.h>

/* disable __attribute__ if not GNU C */
#ifndef __GNUC__
#define __attribute__(attr)
#endif

/*
 * Variants of the standard memory allocators using error checks.
 * These functions never return NULL (they call exit() instead).
 */
extern void *xmalloc (size_t size);
extern void *xcalloc (size_t num, size_t size);
extern void *xrealloc (void *addr, size_t size);

/*
 * Functions for memory pool handling. These may be used to register
 * memory areas for automatic deallocation (using memory_pool_add()).
 * memory_pool_add() and memory_pool_replace() return the registered
 * address.
 */
extern struct memory_pool *memory_pool_new (void);
extern void *memory_pool_add (void *addr, void (*free_func)(void *addr));
extern void *memory_pool_replace (void *old_addr, void *new_addr);
extern void memory_pool_free (struct memory_pool *pool);

/*
 * Variants of the standard memory allocators using a memory pool.
 * Do *not* call free() on the result values.
 */
extern void *mp_malloc (size_t size);
extern void *mp_calloc (size_t num, size_t size);
extern void *mp_realloc (void *addr, size_t size);

/*
 * strdup() variants (using the corresponding memory allocators).
 */
extern char *xstrdup (const char *string);
extern char *mp_strdup (const char *string);

/*
 * simple dynamic array data type
 */
typedef struct array
{
    size_t len;			/* array length */
    size_t alloc;		/* allocated entries */
    size_t element_size;	/* element size */
    void *values;		/* element vector */
} array_t;

/*
 * Utility functions for working with dynamic arrays. They can store
 * any number of elements of a fixed size (just like ordinary vectors).
 * array_add() appends a copy of the value to the end of the array
 * and returns the pointer to the new element inside the array.
 */
#define array_values(array)	((void *) (array)->values)
#define array_len(array)	((size_t) (array)->len)

extern array_t *array_new (size_t element_size);
extern array_t *array_copy (array_t *array);
extern void array_free (array_t *array);

extern void *array_add (array_t *array, const void *value);
extern void *array_get_index (array_t *array, int index);
extern void array_set_index (array_t *array, int index, const void *value);
extern void array_remove_index (array_t *array, int index);

extern void array_sort (array_t *array,
			int (*cmp)(const void *v1, const void *v2));

/*
 * simple dynamic string data type
 */
typedef struct dstring
{
    size_t len;			/* string length */
    size_t alloc;		/* allocated bytes */
    char *text;			/* string contents */
} dstring_t;

/*
 * Utility functions for working with dynamic strings. These functions
 * allocate memory from a memory pool, so there is no dstring_free().
 *
 * Take care to escape `%'-characters properly for dstring_new() etc.
 * if they should be part of the result string (the `fmt' parameter is
 * a printf-style format string!).
 */
#define dstring_str(ds)		((char *) (ds)->text)
#define dstring_len(ds)		((size_t) (ds)->len)

extern dstring_t *dstring_new (const char *fmt, ...)
	__attribute__((format (printf, 1, 2)));
extern dstring_t *dstring_set (dstring_t *ds, const char *fmt, ...)
	__attribute__((format (printf, 2, 3)));
extern dstring_t *dstring_append (dstring_t *ds, const char *fmt, ...)
	__attribute__((format (printf, 2, 3)));
extern dstring_t *dstring_vappend (dstring_t *ds, const char *fmt, va_list ap);
extern dstring_t *dstring_truncate (dstring_t *ds, size_t len);

#endif /* _MEMORY_H_ */
