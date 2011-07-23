/*
 * object.h - minimal object class definition
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _OBJECT_H_
#define _OBJECT_H_

#include <stddef.h>

/*
 * minimal object class
 */
struct object
{
    struct class *class;
};

struct class
{
    /* class meta data */
    const char *name;
    struct class *super_class;

    size_t object_size;
    size_t class_size;

    /* free this object */
    void (*release)(struct object *this);
};

#define OBJECT(obj)		((struct object *) (obj))
#define OBJECT_CLASS		((struct class *) &object_class)

#define CLASS_OF(obj)		OBJECT(obj)->class
#define KIND_OF(obj, type)	object_is_kind_of(CLASS_OF(obj), type)
#define MEMBER_OF(obj, type)	(CLASS_OF(obj) == type)

extern const struct class object_class;

/*
 * Initialize a new class at runtime.
 */
extern void class_init (struct class *class, const char *name,
			struct class *super, size_t object_size,
			size_t class_size);

/*
 * Basic functions for working with objects.
 */
extern struct object *object_new (struct class *class);

extern int object_is_kind_of (struct object *this, struct class *class);

extern void object_free (struct object *this);

#endif /* _OBJECT_H_ */
