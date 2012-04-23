/*
 * artefact.c - handle artefacts
 * Copyright (c) 2003  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "artefact.h"     /* artefact/artefact_class typedefs */
#include "cave.h"    /* get_effect_list */
#include "database.h"     /* db_result_get_int etc. */
#include "except.h"       /* exception handling */
#include "logging.h"      /* debug function */
#include "memory.h"       /* dstring et.al. */
#include "ticker.h"       /* DB_TABLE_ARTEFACT */

/*
 * Retrieve artefact for the given id.
 */
void get_artefact_by_id (db_t *database, int artefactID,
       struct Artefact *artefact)
{
  dstring_t *ds;
  db_result_t *result;

  ds = dstring_new("SELECT * FROM " DB_TABLE_ARTEFACT
           " WHERE artefactID = %d", artefactID);
  result = db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "get_artefact_by_id: %s", dstring_str(ds));

  /* Bedingung: Artefakt muss vorhanden sein */
  if (db_result_num_rows(result) != 1)
    throw(SQL_EXCEPTION, "get_artefact_by_id: no such artefactID");

  db_result_next_row(result);

  artefact->artefactID      = artefactID;
  artefact->artefactClassID = db_result_get_int(result, "artefactClassID");
  artefact->caveID          = db_result_get_int(result, "caveID");
  artefact->initiated       = db_result_get_int(result, "initiated");
}

/*
 * Retrieve artefact_class for the given id.
 */
void get_artefact_class_by_id (db_t *database, int artefactClassID,
             struct Artefact_class *artefact_class)
{
  db_result_t *result;
  dstring_t *ds;

  ds = dstring_new("SELECT * FROM " DB_TABLE_ARTEFACT_CLASS
           " WHERE artefactClassID = %d", artefactClassID);
  result = db_query_dstring(database, ds);
  debug(DEBUG_ARTEFACT, "get_artefact_class_by_id: %s", dstring_str(ds));

  /* Bedingung: Artefaktklasse muss vorhanden sein */
  if (db_result_num_rows(result) != 1)
    throw(SQL_EXCEPTION, "get_artefact_class_by_id: no such artefactClassID");

  db_result_next_row(result);

  artefact_class->artefactClassID       = artefactClassID;
  artefact_class->name                  = db_result_get_string(result, "name");
  artefact_class->resref                = db_result_get_string(result, "resref");
  artefact_class->description           = db_result_get_string(result, "description");
  artefact_class->description_initiated = db_result_get_string(result, "description_initiated");
  artefact_class->initiationID          = db_result_get_int(result, "initiationID");
  artefact_class->getArtefactBySpy      = db_result_get_int(result, "getArtefactBySpy");
  get_effect_list(result, artefact_class->effect);
}

/*
 * Put artefact into cave after finished movement.
 */
void put_artefact_into_cave (db_t *database, int artefactID, int caveID)
{
  dstring_t *ds;

  ds = dstring_new("UPDATE " DB_TABLE_ARTEFACT " SET caveID = %d "
         "WHERE artefactID = %d "
         "AND caveID = 0 "
         "AND initiated = %d",
     caveID, artefactID, ARTEFACT_UNINITIATED);
  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "put_artefact_into_cave: %s", dstring_str(ds));

  /* Bedingung: Artefakt muss vorhanden sein; darf in keiner anderen Höhle liegen; */
  /*   muss uninitialisiert sein */
  if (db_affected_rows(database) != 1)
    throw(BAD_ARGUMENT_EXCEPTION, "put_artefact_into_cave: no such artefactID or "
                             "artefact already in another cave or not uninitiated");

  ds = dstring_new("UPDATE Cave SET artefacts = artefacts + 1 "
         "WHERE caveID = %d", caveID);
  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "put_artefact_into_cave: %s", dstring_str(ds));

  /* Bedingung: Höhle muss vorhanden sein */
  if (db_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "put_artefact_into_cave: no such caveID");
}

/*
 * User wants to remove the artefact from a cave or another user just robbed
 * that user. Remove the artefact from its cave.
 */
void remove_artefact_from_cave (db_t *database, int artefactID)
{
  struct Artefact artefact;
  dstring_t *ds;

  /* save artefact values; throws exception, if that artefact is missing */
  get_artefact_by_id(database, artefactID, &artefact);

  ds = dstring_new("UPDATE " DB_TABLE_ARTEFACT " SET caveID = 0 "
         "WHERE artefactID = %d", artefactID);
  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "remove_artefact_from_cave: %s", dstring_str(ds));

  ds = dstring_new("UPDATE Cave SET artefacts = artefacts - 1 "
         "WHERE caveID = %d", artefact.caveID);
  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "remove_artefact_from_cave: %s", dstring_str(ds));

  /* Bedingung: Höhle muss vorhanden sein */
  if (db_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "remove_artefact_from_cave: no such caveID");
}

/*
 * Initiating finished. Now set the status of the artefact to
 * ARTEFACT_INITIATED.
 */
void initiate_artefact (db_t *database, int artefactID)
{
  struct Artefact artefact;
  dstring_t *ds;

  /* get artefact values; throws exception, if that artefact is missing */
  get_artefact_by_id(database, artefactID, &artefact);

  /* Bedingung: muss gerade eingeweiht werden */
  if (artefact.initiated != ARTEFACT_INITIATING)
    throw(BAD_ARGUMENT_EXCEPTION, "initiate_artefact: artefact was not initiating");

  /* Bedingung: muss in einer Höhle liegen */
  if (artefact.caveID == 0)
    throw(BAD_ARGUMENT_EXCEPTION, "initiate_artefact: artefact was not in a cave");

  ds = dstring_new("UPDATE " DB_TABLE_ARTEFACT " SET initiated = %d WHERE artefactID = %d AND caveID = %d",
     ARTEFACT_INITIATED, artefact.artefactID, artefact.caveID);
  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "initiate_artefact: %s", dstring_str(ds));

  /* Bedingung: Artefakt und Höhle müssen existieren */
  if (db_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "initiate_artefact: no such artefactID or caveID");
}

/*
 * User wants to remove the artefact from a cave or another user just robbed
 * that user. Uninitiate this artefact.
 */
void uninitiate_artefact (db_t *database, int artefactID)
{
  dstring_t *ds;

  ds = dstring_new("UPDATE " DB_TABLE_ARTEFACT " SET initiated = %d "
         "WHERE artefactID = %d",
     ARTEFACT_UNINITIATED, artefactID);
  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "uninitiate_artefact: %s", dstring_str(ds));

  ds = dstring_new("DELETE FROM Event_artefact WHERE artefactID = %d",
     artefactID);
  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "uninitiate_artefact: %s", dstring_str(ds));
}

/*
 * Status already set to ARTEFACT_INITIATED, now apply the effects.
 */
void apply_effects_to_cave (db_t *database, int artefactID)
{
  struct Artefact       artefact;
  struct Artefact_class artefact_class;
  dstring_t             *ds = dstring_new("UPDATE Cave SET ");
  int                   i;

  /* get artefact values; throws exception, if that artefact is missing */
  get_artefact_by_id(database, artefactID, &artefact);
  /* get artefactClass; throws exception, if that artefactClass is missing */
  get_artefact_class_by_id(database, artefact.artefactClassID, &artefact_class);

  /* Bedingung: muss eingeweiht sein */
  if (artefact.initiated != ARTEFACT_INITIATED)
    throw(BAD_ARGUMENT_EXCEPTION, "initiate_artefact: artefact was not initiated");

  for (i = 0; i < MAX_EFFECT; ++i)
    dstring_append(ds, "%s %s = %s + %f",
                  (i == 0 ? "" : ","),
                  effect_type[i]->dbFieldName,
                  effect_type[i]->dbFieldName,
                  artefact_class.effect[i]);

  dstring_append(ds, " WHERE caveID = %d", artefact.caveID);

  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "apply_effects_to_cave: %s", dstring_str(ds));
}

/*
 * User wants to remove the artefact from a cave or another user just robbed
 * that user. Remove the effects (actually same as apply_effects but with a
 * "-" instead of the "+").
 */
void remove_effects_from_cave (db_t *database, int artefactID)
{
  struct Artefact       artefact;
  struct Artefact_class artefact_class;
  dstring_t             *ds = dstring_new("UPDATE Cave SET ");
  int                   i;

  /* get artefact values; throws exception, if that artefact is missing */
  get_artefact_by_id(database, artefactID, &artefact);
  /* get artefactClass; throws exception, if that artefactClass is missing */
  get_artefact_class_by_id(database, artefact.artefactClassID, &artefact_class);

  /* Wenn das Artefakt nicht mehr eingeweiht ist, m�ssen die Effekte nicht mehr entfernt werden. */
  if (artefact.initiated != ARTEFACT_INITIATED) return;

  for (i = 0; i < MAX_EFFECT; ++i)
    dstring_append(ds, "%s %s = %s - %f",
                  (i == 0 ? "" : ","),
                  effect_type[i]->dbFieldName,
                  effect_type[i]->dbFieldName,
                  artefact_class.effect[i]);

  dstring_append(ds, " WHERE caveID = %d", artefact.caveID);

  db_query_dstring(database, ds);
  debug(DEBUG_ARTEFACT, "remove_effects_from_cave: %s", dstring_str(ds));
}

int new_artefact (db_t *database, int artefactClassID)
{
  db_result_t *result;
  dstring_t *ds;

  /* get artefact class */
  ds = dstring_new("SELECT * FROM " DB_TABLE_ARTEFACT_CLASS
            "WHERE artefactClassID = %d", artefactClassID);
  result = db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "new_artefact: %s", dstring_str(ds));

  /* no such class */
  if (db_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "new_artefact: no such artefact class");

  ds = dstring_new("INSERT INTO " DB_TABLE_ARTEFACT
         " (artefactClassID, caveID, initiated)"
         " VALUES (%d, 0, 0)", artefactClassID);
  db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "new_artefact: %s", dstring_str(ds));

  /* successfully inserted? */
  if (db_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "new_artefact: could not insert artefact");

  return db_sequence_value(database, "Artefact_artefactID_seq");
}

/*
 * merge_artefacts tries to merge a key and a lock artefact into a result
 * artefact.
 * Throws exception if needed conditions are not as they should have been.
 */
void merge_artefacts (db_t *database, int caveID, int keyArtefactID,
          int lockArtefactID, int resultArtefactID)
{
  /* first remove the key */
  remove_effects_from_cave(database,  keyArtefactID);
  uninitiate_artefact(database,       keyArtefactID);
  remove_artefact_from_cave(database, keyArtefactID);
  debug(DEBUG_TICKER, "merge_artefacts: removed key artefact [id %d]",
        keyArtefactID);

  /* then remove the lock */
  if (lockArtefactID != 0 && lockArtefactID != keyArtefactID) {

    remove_effects_from_cave(database,  lockArtefactID);
    uninitiate_artefact(database,       lockArtefactID);
    remove_artefact_from_cave(database, lockArtefactID);
    debug(DEBUG_TICKER, "merge_artefacts: removed lock artefact [id %d]",
          lockArtefactID);

  } else {
    debug(DEBUG_TICKER, "merge_artefacts: no lock artefact needed");
  }

  /* now put the result into the cave */
  if (resultArtefactID != 0) {

    put_artefact_into_cave(database, resultArtefactID, caveID);
    debug(DEBUG_TICKER,
          "merge_artefacts: put result artefact [id %d] into cave [id %d]",
          resultArtefactID, caveID);

  } else {
    debug(DEBUG_TICKER, "merge_artefacts: no result artefact");
  }
}

/*
 * merge_artefacts_special
 * Throws exception if needed conditions are not as they should have been.
 */
int merge_artefacts_special (db_t *database,
           const struct Artefact *key_artefact,
           struct Artefact *lock_artefact,
           struct Artefact *result_artefact)
{
  db_result_t *result;
  db_result_t *temp_result;
  int row;
  dstring_t *ds;

  /* get merging formulas */
  ds = dstring_new("SELECT * FROM Artefact_merge_special "
            "WHERE keyID = %d", key_artefact->artefactID);
  result = db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "merge_artefact_special: %s", dstring_str(ds));

  /* check for a suitable merging formula */
  while ((row = db_result_next_row(result)))
  {
    /* some special cases:
     *
     * lockID == 0 || keyID == lockID
     * no lock artefact needed; key artefact transforms directly
     *
     * resultID == 0
     * key and lock artefacts just vanish
     */

    /* lock artefact */
    lock_artefact->artefactID = db_result_get_int(result, "lockID");

    /* special cases: lockID == 0 || keyID == lockID (no lock required) */
    if (lock_artefact->artefactID == 0 ||
  lock_artefact->artefactID == key_artefact->artefactID)
      break;

    /* get lock_artefact */
    /* throws exception, if that artefact is missing */
    get_artefact_by_id(database, lock_artefact->artefactID, lock_artefact);

    /* check: key and lock have to be in the same cave and initiated */
    if (lock_artefact->caveID == key_artefact->caveID &&
  lock_artefact->initiated == ARTEFACT_INITIATED)
      break;
  }

  if (row)
  {
    /* result artefact */
    result_artefact->artefactID = db_result_get_int(result, "resultID");

    /* special case: resultID == 0 */
    if (result_artefact->artefactID != 0)
    {
      /* get result_artefact */
      /* throws exception, if that artefact is missing */
      get_artefact_by_id(database, result_artefact->artefactID, result_artefact);

      /* check: result_artefact must not be in any cave */
      if (result_artefact->caveID != 0)
        throwf(BAD_ARGUMENT_EXCEPTION,
         "merge_artefacts_special: result artefact %d is in cave %d",
               result_artefact->artefactID, result_artefact->caveID);

      /* result_artefact must not be in any movement */
      temp_result = db_query(database, "SELECT * FROM Event_movement"
               " WHERE artefactID = %d",
           result_artefact->artefactID);

      if (db_result_num_rows(temp_result) != 0)
        throwf(BAD_ARGUMENT_EXCEPTION,
         "merge_artefacts_special: result artefact %d is moving",
               result_artefact->artefactID);

      /* check: result_artefact has to be uninitiated */
      /* XXX can this ever happen (it is not in a cave)? */
      if (result_artefact->initiated != ARTEFACT_UNINITIATED)
        uninitiate_artefact(database, result_artefact->artefactID);
    }

    /* now merge them */
    merge_artefacts(database,
                    key_artefact->caveID,
                    key_artefact->artefactID,
                    lock_artefact->artefactID,
                    result_artefact->artefactID);
    return 1;
  }

  return 0;
}

/*
 * merge_artefacts_general
 * Throws exception if needed conditions are not as they should have been.
 */
int merge_artefacts_general (db_t *database,
           const struct Artefact *key_artefact,
           struct Artefact *lock_artefact,
           struct Artefact *result_artefact)
{
  db_result_t *result;
  db_result_t *temp_result;
  int row;
  dstring_t *ds;

  /* now get possible merging formulas */
  ds = dstring_new("SELECT * FROM Artefact_merge_general "
            "WHERE keyClassID = %d",
        key_artefact->artefactClassID);
  result = db_query_dstring(database, ds);

  debug(DEBUG_ARTEFACT, "merge_artefact_general: %s", dstring_str(ds));

  /* check for a suitable merging */
  while ((row = db_result_next_row(result)))
  {
    /* special rules:
     *
     * lockClassID = 0
     * no lock artefact needed
     *
     * keyClassID = lockClassID
     * unlocks if at least one other initiated artefact
     * of the same class exists
     *
     * resultClassID = 0
     * key artefact and one present instance of the lockClass vanish
     */

    /* lock artefact */
    lock_artefact->artefactClassID =
  db_result_get_int(result, "lockClassID");

    if (lock_artefact->artefactClassID == 0)
      break;

    /* implicit checks:
     * - lock artefact has to be different from the key artefact
     * - lock artefact has to be in the same cave as the key artefact
     * - lock artefact has to be initiated
     * - lock artefact has be of the specified class
     */
    ds = dstring_new("SELECT artefactID FROM " DB_TABLE_ARTEFACT
             " WHERE artefactID != %d"
             " AND artefactClassID = %d"
             " AND caveID = %d"
             " AND initiated = %d",
         key_artefact->artefactID,
         lock_artefact->artefactClassID,
         key_artefact->caveID,
         ARTEFACT_INITIATED);
    temp_result = db_query_dstring(database, ds);

    debug(DEBUG_ARTEFACT, "merge_artefact_general: %s", dstring_str(ds));

    /* is there a suitable lock artefact? */
    if (db_result_next_row(temp_result))
    {
      lock_artefact->artefactID = db_result_get_int(temp_result, "artefactID");
      lock_artefact->caveID     = key_artefact->caveID;
      lock_artefact->initiated  = ARTEFACT_INITIATED;
      break;
    }
  }

  if (row)
  {
    /* result artefact */
    result_artefact->artefactClassID =
  db_result_get_int(result, "resultClassID");

    if (result_artefact->artefactClassID != 0)
      result_artefact->artefactID =
    new_artefact(database, result_artefact->artefactClassID);

    /* now merge them */
    merge_artefacts(database,
                    key_artefact->caveID,
                    key_artefact->artefactID,
                    lock_artefact->artefactID,
                    result_artefact->artefactID);
    return 1;
  }

  return 0;
}

int get_artefact_for_caveID(db_t *database, int caveID, int spyableOnly) {
  dstring_t *ds;
  db_result_t *result;
  int artefactID = 0;

  if (spyableOnly) {
    ds = dstring_new("SELECT TOP 1 a.artefactID FROM " DB_TABLE_ARTEFACT
                         " a LEFT JOIN " DB_TABLE_ARTEFACT_CLASS
                         " ac ON a.ArtefactClassID = ac.ArtefactClassID "
                           " WHERE a.caveID = %d AND ac.getArtefactBySpy = 1", caveID);
        db_query_dstring(database, ds);

        artefactID = db_result_get_int(result, "artefactID");

  } else {
    ds = dstring_new("SELECT TOP 1 artefactID FROM " DB_TABLE_ARTEFACT
                     " WHERE caveID = %d", caveID);
    db_query_dstring(database, ds);

    artefactID = db_result_get_int(result, "artefactID");
  }

  debug(DEBUG_ARTEFACT, "get_artefact_for_caveID: %s", dstring_str(ds));

  return artefactID;

}
