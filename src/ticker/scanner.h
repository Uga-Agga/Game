/*
 * scanner.h - simple generic lexical scanner
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _SCANNER_H_
#define _SCANNER_H_

/*
 * scanner token types that are not char values
 */
enum TokenType
{
    TOKEN_NONE	 = 0,
    TOKEN_NUMBER = 256,
    TOKEN_STRING,
    TOKEN_SYMBOL,
    TOKEN_EQUAL,	/* == */
    TOKEN_NOT_EQ,	/* != */
    TOKEN_LT_EQ,	/* <= */
    TOKEN_GT_EQ,	/* >= */
    TOKEN_AND,		/* && */
    TOKEN_OR,		/* || */
    TOKEN_ERROR	 = 512
};

/*
 * simple lexical scanner data type (opaque)
 */
typedef struct scanner scanner_t;

/*
 * Create and destroy lexical scanners.
 */
extern scanner_t *scanner_new (const char *text);
extern void scanner_free (scanner_t *scanner);

/*
 * Set the function that determines whether a given character is part of a
 * symbol (the parameter FIRST is true for the first character in a word).
 * The function value NULL restores the default symbol definition.
 */
extern void scanner_config_symbol (scanner_t *scanner,
				   int (*func)(int chr, int first));

/*
 * Get the current scanner text, position, line number, token type and token
 * value. Each call to scanner_next_token() advances the scanner and returns
 * the next token type (TOKEN_NONE at end of input).
 */
extern const char *scanner_text (scanner_t *scanner);
extern int scanner_position (scanner_t *scanner);
extern int scanner_line_number (scanner_t *scanner);
extern int scanner_is_at_end (scanner_t *scanner);
extern int scanner_next_token (scanner_t *scanner);
extern int scanner_token_type (scanner_t *scanner);
extern double scanner_token_double_value (scanner_t *scanner);
extern const char *scanner_token_string_value (scanner_t *scanner);

#endif /* _SCANNER_H_ */
