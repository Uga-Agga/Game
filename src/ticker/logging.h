/*
 * logging.h - logging and debug facilities
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _LOGGING_H_
#define _LOGGING_H_

#include <stdarg.h>
#include <stdio.h>

/* disable __attribute__ if not GNU C */
#ifndef __GNUC__
#define __attribute__(attr)
#endif

#define LOG_STRING(val)		LOG_STRING_VAL(val)
#define LOG_STRING_VAL(val)	#val
#define LOG_STRLOC		__FILE__ ":" LOG_STRING(__LINE__) ": "

/*
 * set of current debug flags (bitmask)
 */
extern int log_debug_flags;

/*
 * simple log handler data type (opaque)
 */
typedef struct log_handler log_handler_t;

/*
 * Create and destroy log handlers.
 */
extern log_handler_t *log_handler_new (const char *file);
extern log_handler_t *log_handler_new_stream (FILE *stream);
extern void log_handler_free (log_handler_t *handler);

/*
 * Set log handler destination and properties.
 * prefix: string printed before each message (default: NULL)
 * autoflush: flush stream after each message (default: true)
 * timestamp: log timestamp with each message (default: true)
 */
extern void log_handler_set_file (log_handler_t *handler, const char *file);
extern void log_handler_set_stream (log_handler_t *handler, FILE *stream);
extern void log_handler_set_prefix (log_handler_t *handler, const char *prefix);
extern void log_handler_set_autoflush (log_handler_t *handler, int autoflush);
extern void log_handler_set_timestamp (log_handler_t *handler, int timestamp);

/*
 * Log messages to the log handler.
 */
extern void log_handler_log (log_handler_t *handler, const char *fmt, ...)
	__attribute__((format (printf, 2, 3)));
extern void log_handler_vlog (log_handler_t *handler, const char *fmt,
			      va_list ap);
extern void log_handler_flush (log_handler_t *handler);

/*
 * Set the log handler used for a message class.
 */
extern void log_set_debug_handler (log_handler_t *handler);
extern void log_set_warning_handler (log_handler_t *handler);
extern void log_set_error_handler (log_handler_t *handler);

/*
 * Log a message in the corresponding message class. Debug messages
 * are printed only if logging is enabled for the given debug flag.
 * log_error() will also terminate the program immediately.
 */
extern void log_debug (int flag, const char *fmt, ...)
	__attribute__((format (printf, 2, 3)));
extern void log_warning (const char *fmt, ...)
	__attribute__((format (printf, 1, 2)));
extern void log_error (const char *fmt, ...)
	__attribute__((format (printf, 1, 2)));

/*
 * Convenience macros that include code location in log messages.
 */
#ifdef __GNUC__
#define debug(flag, format...)	log_debug(flag, LOG_STRLOC format)
#define warning(format...)	log_warning(LOG_STRLOC format)
#define error(format...)	log_error(LOG_STRLOC "error: " format)
#else
#define debug(flag, ...)	log_debug(flag, LOG_STRLOC __VA_ARGS__)
#define warning(...)		log_warning(LOG_STRLOC __VA_ARGS__)
#define error(...)		log_error(LOG_STRLOC "error: " __VA_ARGS__)
#endif

#endif /* _LOGGING_H_ */
