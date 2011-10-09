<?php
/*
 * artefact.lib.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

define("ARTEFACT_INITIATING",  -1);
define("ARTEFACT_UNINITIATED",  0);
define("ARTEFACT_INITIATED",    1);

function artefact_lib_get_artefacts(){
  global $db_game;

  $sql = 'SELECT '.
         'a.artefactID, a.caveID, a.initiated, '.
         'ac.name as artefactname, ac.initiationID, '.
         'c.name AS cavename, c.xCoord, c.yCoord, '.
         'p.playerID, p.name, p.tribe, ' .
         'e.event_movementID ' .
         'FROM Artefact a '.
         'LEFT JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID '.
         'LEFT JOIN Cave c ON a.caveID = c.caveID ' .
         'LEFT JOIN Player p ON c.playerID = p.playerID ' .
         'LEFT JOIN Event_movement e ON a.artefactID = e.artefactID ';

  $dbresult = $db_game->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }

  $result = array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC))
    $result[] = $row;
  return $result;
}

function artefact_getArtefactByID($artefactID){
  global $db_game;

  $sql = 'SELECT a.*, ac.*, e.event_movementID FROM Artefact a '.
         'LEFT JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID '.
         'LEFT JOIN Event_movement e ON a.artefactID = e.artefactID ' .
         'WHERE a.artefactID = ' . $artefactID;
  $dbresult = $db_game->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }
  return $dbresult->nextrow(MYSQL_ASSOC);
}

/** put artefact into cave
 */
function artefact_putArtefactIntoCave($db, $artefactID, $caveID){

  $sql = "UPDATE Artefact SET caveID = {$caveID} WHERE artefactID = {$artefactID}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  $sql = "UPDATE Cave SET artefacts = artefacts + 1 WHERE caveID = {$caveID}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  remove the artefact from its cave
 */
function artefact_removeArtefactFromCave($db, $artefact){

  if (!sizeof($artefact) || !$artefact['artefactID'])
    return FALSE;

  $sql = "UPDATE Artefact SET caveID = 0 WHERE artefactID = {$artefact['artefactID']}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  $sql = "UPDATE Cave SET artefacts = artefacts - 1 WHERE caveID = {$artefact['caveID']}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  remove the effects.
 */
function artefact_removeEffectsFromCave($db, $artefact){
  global $effectTypeList;

  if (!sizeof($artefact) || !$artefact['artefactID']) return FALSE;
  if ($artefact['initiated'] != ARTEFACT_INITIATED) return TRUE;
  if ($artefact['caveID'] == 0) return FALSE;

  $effects = array();
  foreach ($effectTypeList as $effect)
    if ($artefact[$effect->dbFieldName] != 0)
      $effects[] = "{$effect->dbFieldName} = {$effect->dbFieldName} - {$artefact[$effect->dbFieldName]}";

  if (sizeof($effects)){
    $effects = implode(", ", $effects);
    $sql = "UPDATE Cave SET {$effects} WHERE caveID = {$artefact['caveID']}";
    $dbresult = $db->query($sql);
    if (!$dbresult || $db->affected_rows() != 1) return FALSE;
  }

  return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  uninitiate this artefact
 */
function artefact_uninitiateArtefact($db, $artefactID){

  $sql = "UPDATE Artefact SET initiated = " . ARTEFACT_UNINITIATED . " WHERE artefactID = {$artefactID}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  $sql = "DELETE FROM `Event_artefact` WHERE `artefactID` = '$artefactID'";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;
  else return TRUE;
}
