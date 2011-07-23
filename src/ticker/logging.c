/*
 * logging.c - logging and debug facilities
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <errno.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

#include "logging.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

/*
 * set of current debug flags (bitmask)
 */
int log_debug_flags;

/*
 * simple log handler data type (opaque)
 */
struct log_handler
{
    FILE *stream;
    const char *prefix;
    int close_stream;
    int autoflush;
    int timestamp;
};

/*
 * Create and destroy log handlers.
 */
log_handler_t *log_handler_new (const char *file)
{
    FILE *stream = fopen(file, "a");
    log_handler_t *handler = log_handler_new_stream(stream);

    if (!stream) log_error("%s: %s", file, strerror(errno));

    handler->close_stream = 1;
    return handler;
}

log_handler_t *log_handler_new_stream (FILE *stream)
{
    log_handler_t *handler = calloc(1, sizeof *handler);

    if (!handler) perror("calloc"), exit(EXIT_FAILURE);

    handler->stream = stream;
    handler->autoflush = 1;
    handler->timestamp = 1;
    return handler;
}

void log_handler_free (log_handler_t *handler)
{
    if (handler->close_stream)
	fclose(handler->stream);

    free(handler);
}

/*
 * Set log handler destination and properties.
 * prefix: string printed before each message (default: NULL)
 * autoflush: flush stream after each message (default: true)
 * timestamp: log timestamp with each message (default: true)
 */
void log_handler_set_file (log_handler_t *handler, const char *file)
{
    FILE *stream = fopen(file, "a");

    if (!stream) log_error("%s: %s", file, strerror(errno));

    log_handler_set_stream(handler, stream);
    handler->close_stream = 1;
}

void log_handler_set_stream (log_handler_t *handler, FILE *stream)
{
    if (handler->close_stream)
    {
	fclose(handler->stream);
	handler->close_stream = 0;
    }

    handler->stream = stream;
}

void log_handler_set_prefix (log_handler_t *handler, const char *prefix)
{
    handler->prefix = prefix;
}

void log_handler_set_autoflush (log_handler_t *handler, int autoflush)
{
    handler->autoflush = autoflush;
}

void log_handler_set_timestamp (log_handler_t *handler, int timestamp)
{
    handler->timestamp = timestamp;
}

/*
 * Log messages to the log handler.
 */
void log_handler_log (log_handler_t *handler, const char *fmt, ...)
{
    va_list ap;

    va_start(ap, fmt);
    log_handler_vlog(handler, fmt, ap);
    va_end(ap);
}

void log_handler_vlog (log_handler_t *handler, const char *fmt, va_list ap)
{
    FILE *stream = handler->stream;

    if (handler->timestamp)
    {
	char datebuf[24];		/* YYYY-MM-DD HH:MM:SS */
	time_t timeval = time(NULL);
	struct tm *tm = localtime(&timeval);

	strftime(datebuf, sizeof datebuf, "%F %H:%M:%S ", tm);
	fputs(datebuf, stream);
    }

    if (handler->prefix) fputs(handler->prefix, stream);

    vfprintf(stream, fmt, ap);
    fputc('\n', stream);

    if (handler->autoflush)
	log_handler_flush(handler);
}

void log_handler_flush (log_handler_t *handler)
{
    fflush(handler->stream);

    if (ferror(handler->stream))
	perror("log_handler_flush"), exit(EXIT_FAILURE);
}

/*
 * Set the log handler used for a message class.
 */
static log_handler_t *debug_handler;
static log_handler_t *warning_handler;
static log_handler_t *error_handler;

void log_set_debug_handler (log_handler_t *handler)
{
    if (debug_handler)
	log_handler_free(debug_handler);

    debug_handler = handler;
}

void log_set_warning_handler (log_handler_t *handler)
{
    if (warning_handler)
	log_handler_free(warning_handler);

    warning_handler = handler;
}

void log_set_error_handler (log_handler_t *handler)
{
    if (error_handler)
	log_handler_free(error_handler);

    error_handler = handler;
}

/*
 * Log a message in the corresponding message class. Debug messages
 * are printed only if logging is enabled for the given debug flag.
 * log_error() will also terminate the program immediately.
 */
void log_debug (int flag, const char *fmt, ...)
{
    va_list ap;

    va_start(ap, fmt);

    if (debug_handler && log_debug_flags & flag)
	log_handler_vlog(debug_handler, fmt, ap);

    va_end(ap);
}

void log_warning (const char *fmt, ...)
{
    va_list ap;

    va_start(ap, fmt);

    if (warning_handler)
	log_handler_vlog(warning_handler, fmt, ap);
    else
	vfprintf(stderr, fmt, ap), fputc('\n', stderr);

    va_end(ap);
}

void log_error (const char *fmt, ...)
{
    va_list ap;

    va_start(ap, fmt);

    if (error_handler)
	log_handler_vlog(error_handler, fmt, ap);
    else
	vfprintf(stderr, fmt, ap), fputc('\n', stderr);

    va_end(ap);

    exit(EXIT_FAILURE);
}
