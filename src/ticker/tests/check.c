/*
 * check.c - run all available automatic tests
 * Copyright (c) 2005  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

int main (void)
{
    extern void test_config (void);
    extern void test_except (void);
    extern void test_hashtable (void);
    extern void test_memory (void);
    extern void test_scanner (void);
    extern void test_template (void);

#ifdef DEBUG_MALLOC
    GC_find_leak = 1;
#endif
    test_config();
    test_except();
    test_hashtable();
    test_memory();
    test_scanner();
    test_template();

#ifdef DEBUG_MALLOC
    CHECK_LEAKS();
#endif
    return 0;
}
