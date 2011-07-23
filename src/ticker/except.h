/*
 * except.h - macros for exception handling in C
 * Copyright (c) 2002  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _EXCEPT_H_
#define _EXCEPT_H_

#include <setjmp.h>

#ifdef try		/* for WIN32 */
#undef try
#undef catch
#undef throw
#endif

#ifndef __GNUC__	/* non GNU C */
#define __attribute__(attr)
#endif

#define __JMP_MSG_LEN	512

struct __jmp_ctx {
    jmp_buf *ptr;
    void *type;
    char msg[__JMP_MSG_LEN];
};

/*
 * The follwing macros `try', `catch()' and `end_try' can be used for
 * exception handling in C. The try blocks can be nested and multiple
 * catch blocks may be present for each try. For example:
 *
 *  try {
 *	...
 *	throw(GENERIC_EXCEPTION, "server not responding");
 *	...
 *  } catch (GENERIC_EXCEPTION) {
 *	...
 *  } end_try;
 *
 * - If you want to leave the try block using a statement such as return,
 *   break, continue or goto, you _must_ call the macro leave_try() first.
 *
 * - If multiple catch blocks are present for a single try block, only
 *   the first one containing a matching type value is executed (the types
 *   are compared using the pointer values and a type of NULL matches any
 *   exception type).
 *
 * - Within a catch block the macros except_type and except_msg should be
 *   used to access the local exception type value and the message string.
 */

#define try	   do { struct __jmp_ctx *__jmp_ctx = __jmp_get_ctx();	\
			jmp_buf __jmp_env, *__jmp_prev = __jmp_ctx->ptr;\
			int __jmp_val;					\
			__jmp_ctx->ptr = &__jmp_env;			\
			__jmp_val = setjmp(__jmp_env);			\
			if (__jmp_val) leave_try();			\
			if (__jmp_val == 0)

#define leave_try()	(void) (__jmp_ctx->ptr = __jmp_prev)

#define catch(type)	else if ((type) == 0 || (type) == except_type)

#define end_try		else throw(except_type, except_msg);		\
			leave_try(); } while (0)

/* these are only valid within a catch block */
#define except_type	((const void *) __jmp_ctx->type)
#define except_msg	((const char *) __jmp_ctx->msg)

/* get current exception context */
extern struct __jmp_ctx *__jmp_get_ctx (void);

/* uncaught exception handler - may be modified */
extern void (*__jmp_handler)(const void *type, const char *msg);

/* throw an exception */
extern void throw (const void *type, const char *msg);
extern void throwf (const void *type, const char *fmt, ...)
	__attribute__((format (printf, 2, 3)));

/* pre-defined exception types */
extern const char GENERIC_EXCEPTION[];
extern const char BAD_ARGUMENT_EXCEPTION[];
extern const char NULL_POINTER_EXCEPTION[];
extern const char OUT_OF_MEMORY_EXCEPTION[];

#endif /* _EXCEPT_H_ */
