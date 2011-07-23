/*
 * terrain - generate terrain landscape for Uga-Agga
 * Copyright (c) 2003  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef DEBUG
#define NDEBUG
#endif

#include <assert.h>
#include <limits.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

#define drand()		(rand() / (RAND_MAX+1.0f))

struct mapdata
{
    int size;		/* width * height  */
    int width;		/* width of map    */
    int height;		/* height of map   */
    unsigned char *map;	/* actual map data */

    int tiles;		/* size of tileset */
    int *tileset;	/* current tileset */
    char *tilemap;	/* current tilemap */

    int types;		/* terrain types   */
    struct {
	int symbol;	/* output symbol   */
	float weight;	/* relative weight */
    } terrain[1];	/* terrain data    */
};

static int rand_terrain (struct mapdata *map)
{
    float val = drand();
    int type;

    for (type = 1; type < map->types; ++type)
	if (val < map->terrain[type].weight) return type;

    assert(0);
    return 0;		/* never reached */
}

static void append_field (struct mapdata *map, int field)
{
    assert(map->tiles < map->size);

    if (map->tilemap[field] == 0)
    {
	map->tilemap[field] = 1;
	map->tileset[map->tiles++] = field;
    }
}

static void remove_tile (struct mapdata *map, int tile)
{
    assert(map->tiles > 0 && tile < map->tiles);

    map->tilemap[map->tileset[tile]] = 0;
    map->tileset[tile] = map->tileset[--map->tiles];
}

static void add_tiles (struct mapdata *map, int field, int x, int y)
{
    unsigned char *mmap = map->map;
    int width = map->width, height = map->height;
    int xmax = width - 1, ymax = height - 1;

    if (x < xmax	     && mmap[field + 1        ] == 0)
	append_field(map, field + 1);
    if (x < xmax && y < ymax && mmap[field + 1 + width] == 0)
	append_field(map, field + 1 + width);
    if (	    y < ymax && mmap[field     + width] == 0)
	append_field(map, field + width);
    if (x > 0	 && y < ymax && mmap[field - 1 + width] == 0)
	append_field(map, field - 1 + width);
    if (x > 0		     && mmap[field - 1        ] == 0)
	append_field(map, field - 1);
    if (x > 0	 && y > 0    && mmap[field - 1 - width] == 0)
	append_field(map, field - 1 - width);
    if (	    y > 0    && mmap[field     - width] == 0)
	append_field(map, field - width);
    if (x < xmax && y > 0    && mmap[field + 1 - width] == 0)
	append_field(map, field + 1 - width);
}

static void seed_set (struct mapdata *map, int level)
{
    int count, field;

    if (level <= 0 || level > map->size / 2)
	fputs("invalid number of seed points\n", stderr), exit(1);

    for (count = 0; count < level; ++count)
    {
	while (map->map[field = drand() * map->size]);
	append_field(map, field);
	map->map[field] = rand_terrain(map);
    }

    while (--count >= 0)
    {
	field = map->tileset[count];
	remove_tile(map, count);
	add_tiles(map, field, field % map->width, field / map->width);
    }
}

static int local_terrain (struct mapdata *map, int field, int x, int y)
{
    int counter[UCHAR_MAX + 1];
    int index, maxindex = 0, max = 0;
    int width = map->width, height = map->height;
    int xmax = width - 1, ymax = height - 1;
    unsigned char *mmap = map->map;

    memset(counter, 0, map->types * sizeof (int));

    if (x < xmax	    ) ++counter[mmap[field + 1        ]];
    if (x < xmax && y < ymax) ++counter[mmap[field + 1 + width]];
    if (	    y < ymax) ++counter[mmap[field     + width]];
    if (x > 0	 && y < ymax) ++counter[mmap[field - 1 + width]];
    if (x > 0		    ) ++counter[mmap[field - 1        ]];
    if (x > 0	 && y > 0   ) ++counter[mmap[field - 1 - width]];
    if (	    y > 0   ) ++counter[mmap[field     - width]];
    if (x < xmax && y > 0   ) ++counter[mmap[field + 1 - width]];

    for (index = 1; index < map->types; ++index)
	if (counter[index] > max)
	    maxindex = index, max = counter[index];

    return maxindex;
}

static void generate (struct mapdata *map)
{
    int width = map->width;

    while (map->tiles)
    {
	int tile = drand() * map->tiles;
	int field = map->tileset[tile];
	int x = field % width, y = field / width;

	assert(map->map[field] == 0);

	remove_tile(map, tile);
	add_tiles(map, field, x, y);
	map->map[field] = local_terrain(map, field, x, y);
    }
}

static void display (struct mapdata *map)
{
    int field = 0;
    int x, y;

    for (y = 0; y < map->height; ++y)
    {
	for (x = 0; x < map->width; ++x)
	    putchar(map->terrain[map->map[field++]].symbol);

	putchar('\n');
    }
}

static struct mapdata *new_map (int width, int height, int types)
{
    struct mapdata *map;

    map = malloc(sizeof *map + types * sizeof map->terrain[0]);
    if (map == NULL) perror("malloc"), exit(1);

    map->size = width * height;
    map->width = width;
    map->height = height;
    map->map = calloc(map->size, sizeof (unsigned char));
    if (map->map == NULL) perror("malloc"), exit(1);

    map->tiles = 0;
    map->tileset = malloc(map->size * sizeof (int));
    if (map->tileset == NULL) perror("malloc"), exit(1);
    map->tilemap = calloc(map->size, sizeof (char));
    if (map->tileset == NULL) perror("malloc"), exit(1);

    map->types = types + 1;
    map->terrain[0].symbol = ' ';
    map->terrain[0].weight = 0;
    return map;
}

static void usage (const char *progname)
{
    fprintf(stderr, "usage: %s [width [height [seed]]] terrain-spec ...\n\n"
		    "where terrain-spec has the form SYMBOL:WEIGHT\n"
		    "  SYMBOL is the output character for this terrain\n"
		    "  WEIGHT is the relative proportion of the terrain\n",
	    progname);
    exit(1);
}

int main (int argc, char *argv[])
{
    struct mapdata *map;
    int width = 100, height = 100;
    int seed_level;
    float sum = 0;
    int index = 1;

    srand(time(NULL));			/* randomize */

    if (index < argc && strchr(argv[index], ':') == NULL)
	width = height = atoi(argv[index++]);
    if (index < argc && strchr(argv[index], ':') == NULL)
	height = atoi(argv[index++]);

    if (index < argc && strchr(argv[index], ':') == NULL)
	seed_level = atoi(argv[index++]);
    else
	seed_level = width * height / 40;

    if (index == argc) usage(argv[0]);
    argc -= index, argv += index;

    map = new_map(width, height, argc);

    for (index = 0; index < argc; ++index)
    {
	char symbol;
	float weight;

	if (sscanf(argv[index], "%c:%f", &symbol, &weight) != 2 || weight < 0)
	    fputs("invalid terrain description\n", stderr), exit(1);

	map->terrain[index + 1].symbol = symbol;
	map->terrain[index + 1].weight = sum += weight;
    }

    if (sum == 0)
	fputs("at least one weight must be > 0\n", stderr), exit(1);

    for (index = 1; index < map->types; ++index)
	map->terrain[index].weight /= sum;

    seed_set(map, seed_level);
    generate(map);
    display(map);

    free(map->tilemap);
    free(map->tileset);
    free(map->map);
    free(map);
    return 0;
}
