/*
 * except.c - macros for exception handling in C
 * Copyright (c) 2002  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdarg.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "except.h"

#ifdef _REENTRANT
#include <glib.h>
#endif

/* get current exception context */
struct __jmp_ctx *__jmp_get_ctx (void)
{
#ifdef _REENTRANT
    static GStaticPrivate context_key = G_STATIC_PRIVATE_INIT;
    struct __jmp_ctx *context = g_static_private_get(&context_key);

    if (context == NULL) {
	context = g_malloc0(sizeof *context);
	g_static_private_set(&context_key, context, g_free);
    }

    return context;
#else
    static struct __jmp_ctx context;

    return &context;
#endif
}

/* uncaught exception handler */
static void __jmp_default_handler (const void *type, const char *msg)
{
    fprintf(stderr, "uncaught exception: %s\n", msg);
    abort();
}

/* uncaught exception handler */
void (*__jmp_handler)(const void *, const char *) = __jmp_default_handler;

/* throw an exception */
void throw (const void *type, const char *msg)
{
    struct __jmp_ctx *__jmp_ctx = __jmp_get_ctx();

    __jmp_ctx->type = (void *) type;
    if (msg == NULL)
	__jmp_ctx->msg[0] = '\0';
    else if (__jmp_ctx->msg != msg)
	strncpy(__jmp_ctx->msg, msg, __JMP_MSG_LEN-1);
    if (__jmp_ctx->ptr) longjmp(*__jmp_ctx->ptr, 1);
    if (__jmp_handler) __jmp_handler(type, __jmp_ctx->msg);
}

/* throw an exception */
void throwf (const void *type, const char *fmt, ...)
{
    struct __jmp_ctx *__jmp_ctx = __jmp_get_ctx();
    va_list ap;

    va_start(ap, fmt);
    vsnprintf(__jmp_ctx->msg, __JMP_MSG_LEN, fmt, ap);
    va_end(ap);
    throw(type, __jmp_ctx->msg);
}

/* pre-defined exception types */
const char GENERIC_EXCEPTION[] = "generic exception";
const char BAD_ARGUMENT_EXCEPTION[] = "bad argument exception";
const char NULL_POINTER_EXCEPTION[] = "null pointer exception";
const char OUT_OF_MEMORY_EXCEPTION[] = "out of memory exception";
