/*
 * database.c - database abstraction functions
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <errmsg.h>
#include <mysql.h>
#include <mysqld_error.h>
#include <stdarg.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <unistd.h>	/* sleep */

#include "database.h"
#include "except.h"
#include "hashtable.h"
#include "logging.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

#define MYSQL(database)	((MYSQL *) (database))
#define RETRY_INTERVAL	60

/*
 * database value representation
 */
typedef const char *db_value_t;

/*
 * database session and result datatypes (opaque)
 */
struct db
{
    MYSQL db;			/* MySQL session */
    const char *host;		/* host name */
    const char *user;		/* user name */
    const char *passwd;		/* user password */
    const char *dbname;		/* database name */
};

struct db_result
{
    MYSQL_RES *result;		/* result set */
    MYSQL_ROW row_data;		/* raw row data */
    db_value_t *values;		/* current values */
    hashtable_t *index;		/* column table */
    int columns;		/* column number */
};

/*
 * database exception types
 */
const char SQL_EXCEPTION[] = "SQL exception";
const char DB_EXCEPTION[] = "DB exception";

/*
 * Return largest integral value not greater than x/y.
 */
static long fldiv (long x, long y)
{
    return x < 0 ? (x+1)/y - 1 : x/y;
}

/*
 * timegm() is not part of the ISO C standard, so we provide our own.
 * This function converts a broken-down time structure, expressed as
 * UTC, to calendar time representation. Returns (time_t)(-1) if the
 * time cannot be represented as a time_t value.
 */
static time_t mktime_gm (struct tm *tm)
{
    int yday[] = { 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 };
    int year = tm->tm_year - 100;		/* time base: year 2000 */
    int month = tm->tm_mon;
    int adjust = 7;				/* leap years 1970-1999 */
    long days = 365 * (year + 30);		/* days past 1-Jan-1970 */
    long result;

    if (month < 0 || month > 11) return -1;

    if (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0) && month > 1)
	++adjust;				/* add one if leap year */
    if (year > 0) --year, ++adjust;

    adjust += year / 4 - year / 100 + year / 400;
    days += yday[month] + tm->tm_mday - 1 + adjust;
    result = ((days * 24 + tm->tm_hour) * 60 + tm->tm_min) * 60 + tm->tm_sec;

    return fldiv(result, 24 * 60 * 60) == days ? result : -1;
}

/*
 * Conversion between database time stamps and time_t time values.
 * For make_timestamp() the size of buf must be at least TIMESTAMP_LEN.
 * make_time_gm() and make_timestamp_gm() use UTC (not local time).
 */
time_t make_time (const char *timestamp)
{
    time_t timeval;
    struct tm tm;

    if (sscanf(timestamp, "%4d%2d%2d%2d%2d%2d", &tm.tm_year, &tm.tm_mon,
	       &tm.tm_mday, &tm.tm_hour, &tm.tm_min, &tm.tm_sec) != 6 ||
	tm.tm_year < 0 || tm.tm_mon < 1 || tm.tm_mday < 1)
	throwf(SQL_EXCEPTION, "make_time: invalid timestamp: %s", timestamp);

    tm.tm_year -= 1900;
    tm.tm_mon  -= 1;
    tm.tm_isdst = -1;

    if ((timeval = mktime(&tm)) == (time_t) -1)
	throwf(SQL_EXCEPTION, "make_time: invalid timestamp: %s", timestamp);

    return timeval;
}

char *make_timestamp (char *buf, time_t timeval)
{
    const struct tm *tm = localtime(&timeval);

    strftime(buf, TIMESTAMP_LEN, "%Y%m%d%H%M%S", tm);
    return buf;
}

time_t make_time_gm (const char *timestamp)
{
    time_t timeval;
    struct tm tm;

    if (sscanf(timestamp, "%4d-%2d-%2d %2d:%2d:%2d", &tm.tm_year, &tm.tm_mon,
	       &tm.tm_mday, &tm.tm_hour, &tm.tm_min, &tm.tm_sec) != 6 ||
	tm.tm_year < 0 || tm.tm_mon < 1 || tm.tm_mday < 1)
	throwf(SQL_EXCEPTION, "make_time_gm: invalid timestamp: %s", timestamp);

    tm.tm_year -= 1900;
    tm.tm_mon  -= 1;

    if ((timeval = mktime_gm(&tm)) == (time_t) -1)
	throwf(SQL_EXCEPTION, "make_time_gm: invalid timestamp: %s", timestamp);

    return timeval;
}

char *make_timestamp_gm (char *buf, time_t timeval)
{
    const struct tm *tm = gmtime(&timeval);

    strftime(buf, TIMESTAMP_LEN, "%F %H:%M:%S", tm);
    return buf;
}

/*
 * Open a new database session to the given database using the specified
 * credentials. Throws a DB_EXCEPTION if the connection attempt fails.
 * Use db_close() to close the session and free all associated resources.
 */
db_t *db_connect (const char *host, const char *user, const char *passwd,
		  const char *dbname)
{
    db_t *db = xmalloc(sizeof *db);

    mysql_init(MYSQL(db));
    db->host = host;
    db->user = user;
    db->passwd = passwd;
    db->dbname = dbname;

    mysql_options(MYSQL(db), MYSQL_SET_CHARSET_NAME, "utf8");
    if (!mysql_real_connect(MYSQL(db), host, user, passwd, dbname, 0, NULL, 0))
	throwf(DB_EXCEPTION, "db_connect: %s", mysql_error(MYSQL(db)));

    mysql_query(MYSQL(db), "SET sql_mode = 'NO_UNSIGNED_SUBTRACTION'");


    return db;
}

void db_close (db_t *database)
{
    mysql_close(MYSQL(database));
    free(database);
}

/*
 * Handle MySQL query error. There are three kinds of errors:
 * - temporary failures, e.g. connection lost (wait and retry)
 * - errors affecting all queries, e.g. table crashed (halt ticker)
 * - errors caused by a specific query, e.g. syntax error (block event)
 */
static void handle_query_error (MYSQL *db, const char *query)
{
    const void *exception;

    switch (mysql_errno(db))
    {
	case ER_DISK_FULL:
	case ER_OUTOFMEMORY:
	case ER_CON_COUNT_ERROR:
	case ER_OUT_OF_RESOURCES:
	case ER_BAD_HOST_ERROR:
	case ER_SERVER_SHUTDOWN:
	case CR_CONNECTION_ERROR:
	case CR_CONN_HOST_ERROR:
	case CR_UNKNOWN_HOST:
	case CR_SERVER_GONE_ERROR:
	case CR_SERVER_LOST:
	    exception = NULL;
	    break;

	case ER_CANT_CREATE_FILE:
	case ER_CANT_DELETE_FILE:
	case ER_CANT_FIND_SYSTEM_REC:
	case ER_CANT_GET_STAT:
	case ER_CANT_GET_WD:
	case ER_CANT_LOCK:
	case ER_CANT_OPEN_FILE:
	case ER_FILE_NOT_FOUND:
	case ER_CANT_READ_DIR:
	case ER_CANT_SET_WD:
	case ER_ERROR_ON_CLOSE:
	case ER_ERROR_ON_READ:
	case ER_ERROR_ON_WRITE:
	case ER_FILE_USED:
	case ER_GET_ERRNO:
	case ER_ILLEGAL_HA:
	case ER_KEY_NOT_FOUND:
	case ER_NOT_FORM_FILE:
	case ER_NOT_KEYFILE:
	case ER_OLD_KEYFILE:
	case ER_OPEN_AS_READONLY:
	case ER_UNEXPECTED_EOF:
	case ER_FILE_EXISTS_ERROR:
	case ER_NO_UNIQUE_LOGFILE:
	case ER_RECORD_FILE_FULL:
	case ER_CRASHED_ON_USAGE:
	case ER_CRASHED_ON_REPAIR:
	case CR_SOCKET_CREATE_ERROR:
	case CR_IPSOCK_ERROR:
	case CR_OUT_OF_MEMORY:
	case CR_WRONG_HOST_INFO:
	    exception = DB_EXCEPTION;
	    break;

	default:
	    exception = SQL_EXCEPTION;
    }

    if (exception)
	throwf(exception, "db_query: %s: %s", mysql_error(db), query);

    warning("db_query: %s", mysql_error(db));

    if (sleep(RETRY_INTERVAL) != 0)
	throw(SQL_EXCEPTION, "db_query: connection failure, giving up");
}

/*
 * Create a new database result structure from a MySQL result set.
 */
static db_result_t *db_result_new (MYSQL_RES *result_set)
{
    int columns = mysql_num_fields(result_set);
    MYSQL_FIELD *fields = mysql_fetch_fields(result_set);
    db_result_t *result = xmalloc(sizeof *result);
    db_value_t *values = xmalloc(columns * sizeof values[0]);
    int index;

    result->result = result_set;
    result->row_data = NULL;
    result->values = values;
    result->index = hashtable_new(string_hash, string_equals);
    result->columns = columns;

    for (index = 0; index < columns; ++index)
	hashtable_insert(result->index, fields[index].name, &values[index]);

    return result;
}

static void db_result_free (db_result_t *result)
{
    hashtable_free(result->index);
    mysql_free_result(result->result);
    free(result->values);
    free(result);
}

/*
 * Run the SQL query given by the format string or dstring and return the
 * result set. Throws an SQL_EXCEPTION if the query fails or DB_EXCEPTION
 * if a database error is encountered.
 */
db_result_t *db_query (db_t *database, const char *fmt, ...)
{
    dstring_t *query = dstring_new("");
    va_list ap;

    va_start(ap, fmt);
    dstring_vappend(query, fmt, ap);
    va_end(ap);
    
    return db_query_dstring(database, query);
}

db_result_t *db_query_dstring (db_t *database, dstring_t *query)
{
    MYSQL *db = MYSQL(database);
    MYSQL_RES *result = NULL;
    db_result_t *retval;

    while (mysql_real_query(db, dstring_str(query), dstring_len(query)) ||
	   ((result = mysql_store_result(db)) == NULL && mysql_errno(db)))
    {
	handle_query_error(db, dstring_str(query));
    }

    if (!result) return NULL;
    
    retval = db_result_new(result);
    return memory_pool_add(retval, (void (*)(void *)) db_result_free);
}

/*
 * Return database status information. Sequences can be used to generate
 * unique ascending numeric keys for primary key columns.
 */
long db_affected_rows (db_t *database)
{
    return mysql_affected_rows(MYSQL(database));
}

long db_sequence_value (db_t *database, const char *sequence)
{
    return mysql_insert_id(MYSQL(database));
}

/*
 * Basic functions for working with results sets. db_result_next_row()
 * advances the cursor to the next row and returns a non-zero value if
 * there is such a row. db_result_seek_row() can be used to position the
 * cursor on an arbitrary row in the result, it will return a non-zero
 * value if this row exists.
 */
int db_result_num_columns (db_result_t *result)
{
    return result->columns;
}

long db_result_num_rows (db_result_t *result)
{
    return mysql_num_rows(result->result);
}

int db_result_next_row (db_result_t *result)
{
    size_t size = result->columns * sizeof (db_value_t);

    if (!(result->row_data = mysql_fetch_row(result->result)))
	return 0;

    memcpy(result->values, result->row_data, size);
    return 1;
}

int db_result_seek_row (db_result_t *result, long row)
{
    mysql_data_seek(result->result, row);
    return db_result_next_row(result);
}

/*
 * Return the current value of the column with the specified name in
 * the result set. NULL values are returned as zero for numeric types.
 * db_result_is_null() can be used to test whether a column is NULL.
 * Each functions throws an SQL_EXCEPTION if there is no such column.
 */
long db_result_get_int (db_result_t *result, const char *name)
{
    const char *value = db_result_get_string(result, name);

    return value ? atol(value) : 0;
}

double db_result_get_double (db_result_t *result, const char *name)
{
    const char *value = db_result_get_string(result, name);

    return value ? atof(value) : 0;
}

time_t db_result_get_gmtime (db_result_t *result, const char *name)
{
    const char *value = db_result_get_string(result, name);

    return value ? make_time_gm(value) : 0;
}

time_t db_result_get_time (db_result_t *result, const char *name)
{
    const char *value = db_result_get_string(result, name);

    return value ? make_time(value) : 0;
}

const char *db_result_get_string (db_result_t *result, const char *name)
{
    db_value_t *value;

    if (!result->row_data)
	throw(SQL_EXCEPTION, "db_result_get: no current row");

    if ((value = hashtable_lookup(result->index, name)) == NULL)
	throwf(SQL_EXCEPTION, "db_result_get: invalid column: %s", name);

    return *value;
}

int db_result_is_null (db_result_t *result, const char *name)
{
    return db_result_get_string(result, name) == NULL;
}

/*
 * Return the current value of the column at the specified index in
 * the result set. NULL values are returned as zero for numeric types.
 * db_result_is_null_at() can be used to test whether a column is NULL.
 * Each functions throws an SQL_EXCEPTION if there is no such column.
 */
long db_result_get_int_at (db_result_t *result, int index)
{
    const char *value = db_result_get_string_at(result, index);

    return value ? atol(value) : 0;
}

double db_result_get_double_at (db_result_t *result, int index)
{
    const char *value = db_result_get_string_at(result, index);

    return value ? atof(value) : 0;
}

time_t db_result_get_gmtime_at (db_result_t *result, int index)
{
    const char *value = db_result_get_string_at(result, index);

    return value ? make_time_gm(value) : 0;
}

time_t db_result_get_time_at (db_result_t *result, int index)
{
    const char *value = db_result_get_string_at(result, index);

    return value ? make_time(value) : 0;
}

const char *db_result_get_string_at (db_result_t *result, int index)
{
    if (!result->row_data)
	throw(SQL_EXCEPTION, "db_result_get: no current row");

    if (index < 0 || index >= result->columns)
	throwf(SQL_EXCEPTION, "db_result_get: invalid index: %d", index);

    return result->values[index];
}

int db_result_is_null_at (db_result_t *result, int index)
{
    return db_result_get_string_at(result, index) == NULL;
}
