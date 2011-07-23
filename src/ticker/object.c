/*
 * object.c - minimal object class definition
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "object.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

/*
 * Initialize a new class at runtime.
 */
void class_init (struct class *class, const char *name, struct class *super,
		 size_t object_size, size_t class_size)
{
    if (super) memcpy(class, super, super->class_size);

    class->name = name;
    class->super_class = super;
    class->object_size = object_size;
    class->class_size = class_size;
}

/*
 * Basic functions for working with objects.
 */
struct object *object_new (struct class *class)
{
    struct object *object = calloc(1, class->object_size);

    if (!object) perror("calloc"), exit(EXIT_FAILURE);

    object->class = class;
    return object;
}

int object_is_kind_of (struct object *this, struct class *class)
{
    struct class *type = this->class;

    while (type != class && (type = type->super_class));
    return type != NULL;
}

void object_free (struct object *this)
{
    if (this) this->class->release(this);
}

static void _object_free (struct object *this)
{
    free(this);
}

const struct class object_class = {
    .name        = "object",
    .super_class = NULL,
    .object_size = sizeof (struct object),
    .class_size  = sizeof (struct class),
    .release     = _object_free
};
