/*
 * scanner.c - test lexical scanner functions
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <assert.h>
#include <string.h>

#include "scanner.h"

static const char *testdata =
    "a._b == len(\"x\\\"\") + 1.7e1\n\"\\\\'\\t\\n\\1234\\x1a\\018\"\n";

void test_scanner (void)
{
    scanner_t *scanner = scanner_new(testdata);

    assert(!scanner_is_at_end(scanner));
    assert(scanner_line_number(scanner) == 1);
    assert(scanner_next_token(scanner) == TOKEN_SYMBOL);
    assert(scanner_token_type(scanner) == TOKEN_SYMBOL);
    assert(strcmp(scanner_token_string_value(scanner), "a") == 0);
    assert(scanner_next_token(scanner) == '.');
    assert(scanner_next_token(scanner) == TOKEN_SYMBOL);
    assert(strcmp(scanner_token_string_value(scanner), "_b") == 0);
    assert(scanner_next_token(scanner) == TOKEN_EQUAL);
    assert(scanner_next_token(scanner) == TOKEN_SYMBOL);
    assert(strcmp(scanner_token_string_value(scanner), "len") == 0);
    assert(scanner_next_token(scanner) == '(');
    assert(scanner_next_token(scanner) == TOKEN_STRING);
    assert(strcmp(scanner_token_string_value(scanner), "x\"") == 0);
    assert(scanner_next_token(scanner) == ')');
    assert(scanner_next_token(scanner) == '+');
    assert(scanner_next_token(scanner) == TOKEN_NUMBER);
    assert(scanner_token_double_value(scanner) == 1.7e1);
    assert(!scanner_is_at_end(scanner));
    assert(scanner_line_number(scanner) == 2);
    assert(scanner_next_token(scanner) == TOKEN_STRING);
    assert(strcmp(scanner_token_string_value(scanner),
		  "\\'\t\n\1234\x1a\018") == 0);
    assert(scanner_is_at_end(scanner));
    assert(scanner_line_number(scanner) == 3);
    assert(scanner_next_token(scanner) == TOKEN_NONE);
    assert(scanner_position(scanner) == strlen(testdata));

    scanner_free(scanner);
}
