<?php
/*
 * artefact.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

define("ARTEFACT_INITIATING",  -1);
define("ARTEFACT_UNINITIATED",  0);
define("ARTEFACT_INITIATED",    1);

function artefact_getArtefactsReadyForMovement($caveID) {
  global $db;

  $sql = $db->prepare("SELECT * FROM ". ARTEFACT_TABLE ." a 
         LEFT JOIN ". ARTEFACT_CLASS_TABLE ." ac ON a.artefactClassID = ac.artefactClassID 
         WHERE caveID = :caveID AND initiated = ". ARTEFACT_INITIATED);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return array();
  }

  $result = array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    array_push($result, $row);
  }
  return $result;
}

function getArtefactList() {
  global $db;

  $sql = $db->prepare("SELECT 
                       a.artefactID, a.caveID, a.initiated, 
                       ac.name as artefactname, ac.initiationID, 
                       c.name AS cavename, c.xCoord, c.yCoord, 
                       p.playerID, p.name, p.tribe 
                       FROM ". ARTEFACT_TABLE ." a 
                       LEFT JOIN ". ARTEFACT_CLASS_TABLE ." ac ON a.artefactClassID = ac.artefactClassID 
                       LEFT JOIN ". CAVE_TABLE ." c ON a.caveID = c.caveID 
                       LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = p.playerID");

  if (!$sql->execute()) {
    return array();
  }

  $result = array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    array_push($result, $row);
  }
  return $result;
}

function getArtefactMovement($artefactID) {
  global $db;

  $sql = $db->prepare("SELECT source_caveID, target_caveID, movementID, end 
                       FROM ". EVENT_MOVEMENT_TABLE ." WHERE artefactID = :artefactID");
  $sql->bindValue('artefactID', $artefactID, PDO::PARAM_INT);

  if (!$sql->execute() && !($result = $sql->fetch(PDO::FETCH_ASSOC)))
    return array();

  $result['event_end'] = time_formatDatetime($result['end']);

  $sql = $db->prepare("SELECT c.name AS source_cavename, c.xCoord AS source_xCoord, ".
         "c.yCoord AS source_yCoord, ".
         "IF(ISNULL(p.name), '" . _('leere Höhle') . "',p.name) AS source_name, ".
         "p.tribe AS source_tribe, p.playerID AS source_playerID ".
         "FROM ". CAVE_TABLE ." c LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = p.playerID ".
         "WHERE c.caveID = :source_caveID");
  $sql->bindValue('source_caveID', $result['source_caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    return array();
  }
  $result += $sql->fetch(PDO::FETCH_ASSOC);


  $sql = $db->prepare("SELECT c.name AS destination_cavename, c.xCoord AS destination_xCoord, ".
         "c.yCoord AS destination_yCoord, ".
         "IF(ISNULL(p.name), '" . _('leere Höhle') . "',p.name) AS destination_name, ".
         "p.tribe AS destination_tribe, p.playerID AS destination_playerID ".
         "FROM ". CAVE_TABLE ." c LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = p.playerID ".
         "WHERE c.caveID = :caveID");
  $sql->bindValue('caveID', $result['target_caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    return array();
  }
  $result += $sql->fetch(PDO::FETCH_ASSOC);

  return $result;
}

function artefact_getArtefactMovements() {
  global $db;

  // prepare query
  $sql = $db->prepare("SELECT artefactID, source_caveID, target_caveID, movementID, end 
         FROM ". EVENT_MOVEMENT_TABLE ." WHERE artefactID != 0");

  // send it
  if ($sql->rowCountSelect() == 0) return array();
  if (!$sql->execute()) {
    return array();
  }

  // collect movements
  $moves = array();
  $result = $sql->fetchAll();
  foreach ($result AS $row) {
    // format time
    $row['event_end'] = time_formatDatetime($row['end']);

    // prepare query
    $sql = $db->prepare("SELECT c.name AS source_cavename, c.xCoord AS source_xCoord, ".
           "c.yCoord AS source_yCoord, p.name AS source_name, ".
           "p.tribe AS source_tribe, p.playerID AS ".
           "source_playerID FROM ". CAVE_TABLE ." c LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = ".
           "p.playerID WHERE c.caveID = :caveID"); 
    $sql->bindValue('caveID', $row['source_caveID'], PDO::PARAM_INT);

    // send query
    if ($sql->rowCountSelect() == 0) continue;
    if (!$sql->execute()) continue;
    $row += $sql->fetch(PDO::FETCH_ASSOC);

    // prepare query
    $sql = $db->prepare("SELECT c.name AS destination_cavename, c.xCoord AS ".
           "destination_xCoord, c.yCoord AS destination_yCoord, ".
           "p.name AS destination_name, p.tribe AS ".
           "destination_tribe, p.playerID AS destination_playerID FROM ". CAVE_TABLE ." c ".
           "LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = p.playerID WHERE c.caveID = :caveID");
    $sql->bindValue('caveID', $row['target_caveID'], PDO::PARAM_INT);

    // send query
    if ($sql->rowCountSelect() != 1) continue;
    if (!$sql->execute()) continue;
    
    $row += $sql->fetch(PDO::FETCH_ASSOC);

    $moves[$row['artefactID']] = $row;
  }
  return $moves;
}

function artefact_getArtefactInitiationsForCave($caveID) {
  global $db;

  $sql = $db->prepare("SELECT * FROM ". EVENT_ARTEFACT_TABLE ." WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if ($sql->rowCountSelect() == 0) return array();
  if (!$sql->execute()) {
    return array();
  }
  return $sql->fetch(PDO::FETCH_ASSOC);
}

/** get artefact by its id
 */
function artefact_getArtefactByID($artefactID) {
  global $db;

  $sql = $db->prepare("SELECT * FROM ". ARTEFACT_TABLE ." a 
         LEFT JOIN ". ARTEFACT_CLASS_TABLE ." ac ON a.artefactClassID = ac.artefactClassID 
         WHERE a.artefactID = :artefactID"); 
  $sql->bindValue('artefactID', $artefactID, PDO::PARAM_INT);

  if ($sql->rowCountSelect() != 1) return array();
  if(!$sql->execute()) return array();

  return $sql->fetch(PDO::FETCH_ASSOC);
}


/** get artefacts by caveID
 */
function artefact_getArtefactByCaveID($caveID) {
  global $db;

  // init result
  $result = array();

  // prepare statement
  $sql = $db->prepare("SELECT * FROM ". ARTEFACT_TABLE ." a LEFT JOIN ". ARTEFACT_CLASS_TABLE ." ac 
                 ON a.artefactClassID = ac.artefactClassID 
                 WHERE a.caveID = :caveID"); 
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  // return an empty result on error or an empty row set
  if ($sql->rowCountSelect() == 0) return $result;
  if (!$sql->execute())
    return $result;

  return $result;
}

/** get ritual
 */
function artefact_getRitualByID($ritualID) {
  global $db;
  
  // get ritual
  $sql = $db->prepare("SELECT * FROM ". ARTEFACT_RITUALS_TABLE ." WHERE ritualID = :ritualID");
  $sql->bindValue('ritualID', $ritualID, PDO::PARAM_INT);

  if ($sql->rowCountSelect() != 1) return FALSE;
  if (!$sql->execute()) {
    return FALSE;
  }
  return $sql->fetch(PDO::FETCH_ASSOC);
}

/** put artefact into cave after finished movement.
 */
function artefact_putArtefactIntoCave($artefactID, $caveID) {
  global  $db;

  $sql = $db->prepare("UPDATE Artefact SET caveID = :caveID WHERE artefactID = :artefactID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('artefactID', $artefactID, PDO::PARAM_INT);

  if (!$sql->execute()) return FALSE;

  $sql = $db->prepare("UPDATE Cave SET artefacts = artefacts + 1 WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if (!$sql->execute()) return FALSE;

  return TRUE;
}

/** user wants to initiate artefact. thus he has first to pay the fee successfully
 *  then the status of the artefact can be set to ARTEFACT_INITIATING.
 */
function artefact_beginInitiation($artefact) {
  global $db,
         $resourceTypeList,
         $buildingTypeList,
         $unitTypeList,
         $scienceTypeList,
         $defenseSystemTypeList;

  // Artefakt muss einweihbar sein
  if ($artefact['initiated'] != ARTEFACT_UNINITIATED) {
    return -5;
  }

  // Hol das Einweihungsritual
  $ritual = artefact_getRitualByID($artefact['initiationID']);
  if ($ritual === FALSE)
    return -1;

  // get initiation costs
  $costs = array();
  $temp = array_merge($resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList);
  foreach($temp as $val) {
    if (array_key_exists($val->dbFieldName, $ritual)) {
      if ($ritual[$val->dbFieldName]) {
        $costs[$val->dbFieldName] = $ritual[$val->dbFieldName];
      }
    }
  }

  $set     = array();
  $setBack = array();
  $where   = array("WHERE caveID = '{$artefact['caveID']}'");

  // get all the costs
  foreach ($costs as $key => $value) {
    array_push($set,     "{$key} = {$key} - ({$value})");
    array_push($setBack, "{$key} = {$key} + ({$value})");
    array_push($where,   "{$key} >= ({$value})");
  }

  // generate SQL
  if (sizeof($set)) {
    $set     = implode(", ", $set);
    $set     = "UPDATE ". CAVE_TABLE ." SET $set ";
    $setBack = implode(", ", $setBack);
    $setBack = "UPDATE ". CAVE_TABLE ." SET $setBack WHERE caveID = '{$artefact['caveID']}'";
  }

  $where   = implode(" AND ", $where);

  // substract costs

  //echo "try to substract costs:<br>" . $set.$where . "<br><br>";
  if (!($result = $db->query($set.$where)) || !($result->rowCount() == 1)) {
    return -2;
  }

  // register event
  $now = time();
  $sql = $db->prepare("INSERT INTO ". EVENT_ARTEFACT_TABLE ." " .
                 "(caveID, artefactID, event_typeID, start, end) " .
                 "VALUES (:caveID, :artefactID, 1, :start, :end)");
  $sql->bindValue('caveID', $artefact['caveID'], PDO::PARAM_INT);
  $sql->bindValue('artefactID', $artefact['artefactID'], PDO::PARAM_INT);
  $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('end', time_toDatetime($now + $ritual['duration']), PDO::PARAM_STR);

  if (!$sql->execute()) {
    $db->query($setBack);
    return -3;
  }

  // finally set status to initiating
  $sql = $db->prepare("UPDATE ". ARTEFACT_TABLE ." SET initiated = " . ARTEFACT_INITIATING . " 
                       WHERE artefactID = :artefactID");
  $sql->bindValue('artefactID', $artefact['artefactID'], PDO::PARAM_INT);

  if (!$sql->execute()) return -4;
  return 1;
}

/** initiating finished. now set the status of the artefact to ARTEFACT_INITIATED.
 */
function artefact_initiateArtefact($artefactID) {
  global $db;

  $sql = $db->prepare("UPDATE ". ARTEFACT_TABLE ." SET initiated = " . ARTEFACT_INITIATED . " 
                       WHERE artefactID = :artefactID");
  $sql->bindValue('artefactID', $artefactID, PDO::PARAM_INT);

  if (!$sql->execute()) return FALSE;
  else return TRUE;
}

/** status already set to ARTEFACT_INITIATED, now apply the effects
 */
function artefact_applyEffectsToCave($artefactID) {
  global  $db, $effectTypeList;

  $artefact = artefact_getArtefactByID($artefactID);
  if (sizeof($artefact) == 0) return FALSE;
  if ($artefact['caveID'] == 0) return FALSE;

  $effects = array();
  foreach ($effectTypeList as $effect) {
    array_push($effects, "{$effect->dbFieldName} = {$effect->dbFieldName} + {$artefact[$effect->dbFieldName]}");
  }

  if (sizeof($effects)) {
    $effects = implode(", ", $effects);
    $sql = $db->prepare("UPDATE ". CAVE_TABLE ." SET {$effects} WHERE caveID = :caveID");
    $sql->bindValue('caveID', $artefact['caveID'], PDO::PARAM_INT);

    if (!$sql->execute() || $sql->rowCount() != 1) return FALSE;
  }

  return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  remove the effects.
 */
function artefact_removeEffectsFromCave($artefactID) {
  global  $db, $effectTypeList;

  $artefact = artefact_getArtefactByID($artefactID);
  if (sizeof($artefact) == 0) return FALSE;
  if ($artefact['initiated'] != ARTEFACT_INITIATED) return TRUE;
  if ($artefact['caveID'] == 0) return FALSE;

  $effects = array();
  foreach ($effectTypeList as $effect) {
    if ($artefact[$effect->dbFieldName] != 0){
      array_push($effects, "{$effect->dbFieldName} = {$effect->dbFieldName} - {$artefact[$effect->dbFieldName]}");
    }
  }

  if (sizeof($effects)) {
    $effects = implode(", ", $effects);
    $sql = $db->prepare("UPDATE ". CAVE_TABLE ." SET {$effects} WHERE caveID = :caveID");
    $sql->bindValue('caveID', $artefact['caveID'], PDO::PARAM_INT);

    if (!$sql->execute() || $sql->rowCount() != 1) return FALSE;
  }

  return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  uninitiate this artefact
 */
function artefact_uninitiateArtefact($artefactID) {
  global $db;

  $sql = $db->prepare("UPDATE ". ARTEFACT_TABLE ." SET initiated = " . ARTEFACT_UNINITIATED . " 
                  WHERE artefactID = :artefactID");
  $sql->bindValue('artefactID', $artefactID, PDO::PARAM_INT);

  if (!$sql->execute()) return FALSE;

  $sql = $db->prepare("DELETE FROM ". EVENT_ARTEFACT_TABLE ." WHERE artefactID = artefactID");
  $sql->bindValue('artefactID', $artefactID, PDO::PARAM_INT);

  if (!$sql->execute()) return FALSE;
  else return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  remove the artefact from its cave
 */
function artefact_removeArtefactFromCave($artefactID) {
  global  $db;

  $artefact = artefact_getArtefactByID($artefactID);
  if (sizeof($artefact) == 0) return FALSE;

  $sql = $db->prepare("UPDATE ". ARTEFACT_TABLE ." SET caveID = 0 WHERE artefactID = :artefactID");
  $sql->bindValue('artefactID', $artefact['artefactID'], PDO::PARAM_INT);

  if (!$sql->execute()) return FALSE;

  $sql = $db->prepare("UPDATE ". CAVE_TABLE ." SET artefacts = artefacts - 1 WHERE caveID = :caveID");
  $sql->bindValue('caveID', $artefact['caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) return FALSE;

  return TRUE;
}

/** recalculate artefacts' effects for a given cave
 */
function artefact_recalculateEffects($caveID) {
  global $db, $effectTypeList;

  // init result
  $result = array();
  foreach ($effectTypeList as $effectID => $effect)
    $result[$effectID] = 0;

  // get artefacts
  $artefacts = artefact_getArtefactByCaveID($caveID);
  if (!sizeof($artefacts))
    return $result;

  // iterate through the effects
  foreach ($effectTypeList as $effectID => $effect)
    // iterate through the artefacts
    foreach ($artefacts as $artefact)
      // consider only initiated artefacts
      if (ARTEFACT_INITIATED == $artefact['initiated'])
        $result[$effectID] += $artefact[$effect->dbFieldName];

  return $result;
}

?>