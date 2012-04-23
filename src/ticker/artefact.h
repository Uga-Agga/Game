/*
 * artefact.h - handle artefacts
 * Copyright (c) 2003  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _ARTEFACT_H_
#define _ARTEFACT_H_

#include "database.h"
#include "game_rules.h"

#define ARTEFACT_INITIATING  -1
#define ARTEFACT_UNINITIATED  0
#define ARTEFACT_INITIATED  1

typedef struct Artefact_class
{
    int         artefactClassID;
    const char *name;
    const char *resref;
    const char *description;
    const char *description_initiated;
    int         initiationID;
    int         getArtefactBySpy;
    float       effect[MAX_EFFECT];
} Artefact_class;

typedef struct Artefact
{
    int artefactID;
    int artefactClassID;
    int caveID;
    int initiated;
} Artefact;

extern void get_artefact_by_id (db_t *database, int artefactID,
        struct Artefact *artefact);
extern void get_artefact_class_by_id (db_t *database, int artefactClassID,
              struct Artefact_class *artefact_class);

extern void put_artefact_into_cave (db_t *database, int artefactID,
            int caveID);
extern void remove_artefact_from_cave (db_t *database, int artefactID);

extern void initiate_artefact (db_t *database, int artefactID);
extern void uninitiate_artefact (db_t *database, int artefactID);

extern void apply_effects_to_cave (db_t *database, int artefactID);
extern void remove_effects_from_cave (db_t *database, int artefactID);

extern int merge_artefacts_general (db_t *database,
            const struct Artefact *key_artefact,
            struct Artefact *lock_artefact,
            struct Artefact *result_artefact);
extern int merge_artefacts_special (db_t *database,
            const struct Artefact *key_artefact,
            struct Artefact *lock_artefact,
            struct Artefact *result_artefact);

extern int new_artefact (db_t *database, int artefactClassID);

extern void merge_artefacts (db_t *database, int caveID, int keyArtefactID,
           int lockArtefactID, int resultArtefactID);

extern int get_artefact_for_caveID(db_t *database, int caveID, int spyableOnly);

#endif /* _ARTEFACT_H_ */
