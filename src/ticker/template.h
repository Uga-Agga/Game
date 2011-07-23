/*
 * template.h - template system compatible with php-templates
 * Copyright (c) 2003  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _TEMPLATE_H_
#define _TEMPLATE_H_

/* disable __attribute__ if not GNU C */
#ifndef __GNUC__
#define __attribute__(attr)
#endif

/*
 * simple template data type (opaque)
 */
typedef struct template template_t;

/*
 * Create, copy and destroy templates.
 */
extern template_t *template_new (const char *string);
extern template_t *template_from_file (const char *filename);
extern template_t *template_copy (template_t *template);
extern void template_free (template_t *template);

/*
 * Functions for working with templates. template_clear() removes all
 * values and iterations set with template_set() or template_iterate().
 * template_context() changes the current context (similar to chdir()).
 */
extern const char *template_eval (template_t *template);
extern const char *template_get (template_t *template, const char *path);

extern void template_clear (template_t *template);
extern void template_context (template_t *template, const char *path);
extern void template_iterate (template_t *template, const char *path);
extern void template_unset (template_t *template, const char *path);
extern void template_set (template_t *template, const char *path,
			  const char *value);
extern void template_set_fmt (template_t *template, const char *path,
			      const char *fmt, ...)
	__attribute__((format (printf, 3, 4)));

#endif /* _TEMPLATE_H_ */
