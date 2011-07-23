/*
 * except.c - test exception handling functions
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <assert.h>
#include <string.h>

#include "except.h"

static const char TEST_EXCEPTION[] = "test exception";

static void test_jmp_handler (const void *type, const char *msg)
{
    assert(type == GENERIC_EXCEPTION);
    assert(strcmp(msg, "generic") == 0);
}

static void test_throw (void)
{
    throw(GENERIC_EXCEPTION, "generic");
    assert(0);
}

static void test_throwf (void)
{
    try {
	throwf(TEST_EXCEPTION, "test: %d", 42);
	assert(0);
    } catch (GENERIC_EXCEPTION) {
	assert(0);
    } end_try;
}

static void test_pass (void)
{
    try {
	test_throwf();
    } catch (TEST_EXCEPTION) {
	throw(except_type, except_msg);
	assert(0);
    } end_try;
}

static void test_leave (void)
{
    try; end_try;

    try {
	try {
	} catch (GENERIC_EXCEPTION) {
	    assert(0);
	} end_try;

	leave_try();
	return;
    } end_try;

    assert(0);
}

static void test_catch (void)
{
    try {
	test_throw();
	assert(0);
    } catch (NULL) {
	assert(except_type == GENERIC_EXCEPTION);
	assert(strcmp(except_msg, "generic") == 0);
    } catch (TEST_EXCEPTION) {
	assert(0);
    } end_try;

    test_leave();

    try {
	test_throwf();
	assert(0);
    } catch (GENERIC_EXCEPTION) {
	assert(0);
    } catch (TEST_EXCEPTION) {
	assert(except_type == TEST_EXCEPTION);
	assert(strcmp(except_msg, "test: 42") == 0);
    } end_try;

    try {
	test_pass();
	assert(0);
    } catch (TEST_EXCEPTION) {
	assert(except_type == TEST_EXCEPTION);
	assert(strcmp(except_msg, "test: 42") == 0);
    } end_try;
}

static void test_handler (void)
{
    void (*handler)(const void *type, const char *msg) = __jmp_handler;

    __jmp_handler = test_jmp_handler;
    throw(GENERIC_EXCEPTION, "generic");
    __jmp_handler = handler;
}

void test_except (void)
{
    test_catch();
    test_handler();
}
