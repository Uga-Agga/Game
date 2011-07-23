/*
 * database.h - database abstraction functions
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _DATABASE_H_
#define _DATABASE_H_

#include <time.h>

#include "memory.h"

/* disable __attribute__ if not GNU C */
#ifndef __GNUC__
#define __attribute__(attr)
#endif

#define TIMESTAMP_LEN	20

/*
 * database session and result datatypes (opaque)
 */
typedef struct db db_t;
typedef struct db_result db_result_t;

/*
 * database exception types
 */
extern const char SQL_EXCEPTION[];
extern const char DB_EXCEPTION[];

/*
 * Conversion between database time stamps and time_t time values.
 * For make_timestamp() the size of buf must be at least TIMESTAMP_LEN.
 * make_time_gm() and make_timestamp_gm() use UTC (not local time).
 */
extern time_t make_time (const char *timestamp);
extern char *make_timestamp (char *buf, time_t timeval);

extern time_t make_time_gm (const char *timestamp);
extern char *make_timestamp_gm (char *buf, time_t timeval);

/*
 * Open a new database session to the given database using the specified
 * credentials. Throws a DB_EXCEPTION if the connection attempt fails.
 * Use db_close() to close the session and free all associated resources.
 */
extern db_t *db_connect (const char *host, const char *user,
			 const char *passwd, const char *dbname);
extern void db_close (db_t *database);

/*
 * Run the SQL query given by the format string or dstring and return the
 * result set. Throws an SQL_EXCEPTION if the query fails or DB_EXCEPTION
 * if a database error is encountered.
 */
extern db_result_t *db_query (db_t *database, const char *fmt, ...)
	__attribute__((format (printf, 2, 3)));
extern db_result_t *db_query_dstring (db_t *database, dstring_t *query);

/*
 * Return database status information. Sequences can be used to generate
 * unique ascending numeric keys for primary key columns.
 */
extern long db_affected_rows (db_t *database);
extern long db_sequence_value (db_t *database, const char *sequence);

/*
 * Basic functions for working with results sets. db_result_next_row()
 * advances the cursor to the next row and returns a non-zero value if
 * there is such a row. db_result_seek_row() can be used to position the
 * cursor on an arbitrary row in the result, it will return a non-zero
 * value if this row exists.
 */
extern int db_result_num_columns (db_result_t *result);
extern long db_result_num_rows (db_result_t *result);
extern int db_result_next_row (db_result_t *result);
extern int db_result_seek_row (db_result_t *result, long row);

/*
 * Return the current value of the column with the specified name in
 * the result set. NULL values are returned as zero for numeric types.
 * db_result_is_null() can be used to test whether a column is NULL.
 * Each functions throws an SQL_EXCEPTION if there is no such column.
 */
extern long db_result_get_int (db_result_t *result, const char *name);
extern double db_result_get_double (db_result_t *result, const char *name);
extern time_t db_result_get_gmtime (db_result_t *result, const char *name);
extern time_t db_result_get_time (db_result_t *result, const char *name);
extern const char *db_result_get_string (db_result_t *result, const char *name);
extern int db_result_is_null (db_result_t *result, const char *name);

/*
 * Return the current value of the column at the specified index in
 * the result set. NULL values are returned as zero for numeric types.
 * db_result_is_null_at() can be used to test whether a column is NULL.
 * Each functions throws an SQL_EXCEPTION if there is no such column.
 */
extern long db_result_get_int_at (db_result_t *result, int index);
extern double db_result_get_double_at (db_result_t *result, int index);
extern time_t db_result_get_gmtime_at (db_result_t *result, int index);
extern time_t db_result_get_time_at (db_result_t *result, int index);
extern const char *db_result_get_string_at (db_result_t *result, int index);
extern int db_result_is_null_at (db_result_t *result, int index);

#endif /* _DATABASE_H_*/
