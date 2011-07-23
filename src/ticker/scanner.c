/*
 * scanner.c - simple generic lexical scanner
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <ctype.h>
#include <stdlib.h>

#include "memory.h"
#include "scanner.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

/*
 * simple lexical scanner data type (opaque)
 */
struct scanner
{
    const char *text;		/* string to scan */
    int position;		/* current position */
    int line_number;		/* current line number */
    int token_type;		/* current token type */
    double token_value;		/* numeric token value */
    char *token_buffer;		/* string token buffer */
    size_t buf_alloc;		/* allocated bytes */
				/* test for symbol char */
    int (*symbol_char)(int chr, int first);
};

static int symbol_char (int chr, int first);

/*
 * Create and destroy lexical scanners.
 */
scanner_t *scanner_new (const char *text)
{
    scanner_t *scanner = xcalloc(1, sizeof *scanner);

    scanner->text = text;
    scanner->line_number = 1;
    scanner->symbol_char = symbol_char;
    return scanner;
}

void scanner_free (scanner_t *scanner)
{
    free(scanner->token_buffer);
    free(scanner);
}

/*
 * Default definition of symbol token type (simple identifier).
 */
static int symbol_char (int chr, int first)
{
    return (first ? isalpha(chr) : isalnum(chr)) || chr == '_';
}

/*
 * Set the function that determines whether a given character is part of a
 * symbol (the parameter FIRST is true for the first character in a word).
 * The function value NULL restores the default symbol definition.
 */
void scanner_config_symbol (scanner_t *scanner, int (*func)(int chr, int first))
{
    scanner->symbol_char = func ? func : symbol_char;
}

/*
 * Get the current scanner text, position, line number, token type and token
 * value. Each call to scanner_next_token() advances the scanner and returns
 * the next token type (TOKEN_NONE at end of input).
 */
const char *scanner_text (scanner_t *scanner)
{
    return scanner->text;
}

int scanner_position (scanner_t *scanner)
{
    return scanner->position;
}

int scanner_line_number (scanner_t *scanner)
{
    return scanner->line_number;
}

int scanner_is_at_end (scanner_t *scanner)
{
    const char *text = scanner->text;

    while (isspace(text[scanner->position]))
    {
	if (text[scanner->position] == '\n')
	    ++scanner->line_number;
	++scanner->position;
    }

    return text[scanner->position] == '\0';
}

static int hex_value (int chr)
{
    return isdigit(chr) ? chr - '0' : toupper(chr) - 'A' + 10;
}

/*
 * Save text at the current scanner position into the token buffer.
 */
static void scanner_save_token (scanner_t *scanner, size_t len)
{
    const char *str = scanner->text + scanner->position;
    int i, j;

    if (len + 1 > scanner->buf_alloc)
    {
	scanner->buf_alloc = len + 1;
	scanner->token_buffer = xrealloc(scanner->token_buffer, len + 1);
    }

    /* this loop assumes that strlen(str) >= len */
    for (i = j = 0; i < len; ++i)
    {
	int value = str[i];

	if (value == '\\')
	{
	    switch ((value = str[++i]))
	    {
		case 'a': value = '\a'; break;
		case 'b': value = '\b'; break;
		case 'f': value = '\f'; break;
		case 'n': value = '\n'; break;
		case 'r': value = '\r'; break;
		case 't': value = '\t'; break;
		case 'v': value = '\v'; break;
		case 'x':
		    if (isxdigit(str[i+1]))
			value = hex_value(str[++i]);
		    if (isxdigit(str[i+1]))
			value = value << 4 | hex_value(str[++i]);
		    break;
		case '0': case '1': case '2': case '3':
		case '4': case '5': case '6': case '7':
		    value -= '0';
		    if (str[i+1] >= '0' && str[i+1] <= '7')
			value = value << 3 | (str[++i] - '0');
		    if (str[i+1] >= '0' && str[i+1] <= '7')
			value = value << 3 | (str[++i] - '0');
	    }
	}

	scanner->token_buffer[j++] = value;
    }

    scanner->token_buffer[j] = '\0';
}

int scanner_next_token (scanner_t *scanner)
{
    const char *text = scanner->text;
    int (*symbol_char)(int, int) = scanner->symbol_char;
    int at_end = scanner_is_at_end(scanner);
    int pos = scanner->position;
    int chr = text[pos];

    if (at_end)
    {
	scanner->token_type = TOKEN_NONE;
    }
    else if (symbol_char(chr, 1))
    {
	while (symbol_char(chr, 0)) chr = text[++pos];

	scanner->token_type = TOKEN_SYMBOL;
	scanner_save_token(scanner, pos - scanner->position);
    }
    else if (isdigit(chr) || (chr == '.' && isdigit(text[pos+1])))
    {
	char *endptr;

	scanner->token_type = TOKEN_NUMBER;
	scanner->token_value = strtod(text + scanner->position, &endptr);
	pos = endptr - text;
    }
    else if (chr == '"' || chr == '\'')
    {
	int delim = chr;

	chr = text[++pos];

	while (chr && chr != delim)
	{
	    if (chr == '\\' && text[pos+1]) ++pos;
	    chr = text[++pos];
	}

	if (chr == '\0')		/* unterminated string */
	    return TOKEN_ERROR;

	++scanner->position;
	scanner->token_type = TOKEN_STRING;
	scanner_save_token(scanner, pos - scanner->position);
	++pos;
    }
    else if (chr == '=' && text[pos+1] == '=')
    {
	scanner->token_type = TOKEN_EQUAL;
	pos += 2;
    }
    else if (chr == '!' && text[pos+1] == '=')
    {
	scanner->token_type = TOKEN_NOT_EQ;
	pos += 2;
    }
    else if (chr == '<' && text[pos+1] == '=')
    {
	scanner->token_type = TOKEN_LT_EQ;
	pos += 2;
    }
    else if (chr == '>' && text[pos+1] == '=')
    {
	scanner->token_type = TOKEN_GT_EQ;
	pos += 2;
    }
    else if (chr == '&' && text[pos+1] == '&')
    {
	scanner->token_type = TOKEN_AND;
	pos += 2;
    }
    else if (chr == '|' && text[pos+1] == '|')
    {
	scanner->token_type = TOKEN_OR;
	pos += 2;
    }
    else
    {
	scanner->token_type = (unsigned char) chr;
	++pos;
    }

    scanner->position = pos;
    return scanner->token_type;
}

int scanner_token_type (scanner_t *scanner)
{
    return scanner->token_type;
}

double scanner_token_double_value (scanner_t *scanner)
{
    return scanner->token_value;
}

const char *scanner_token_string_value (scanner_t *scanner)
{
    return scanner->token_buffer;
}
