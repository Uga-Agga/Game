<?php
/*
 * unitaction.inc.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Movement.php');

function reverseMovementEvent($caveID, $eventID) {
  global $db;

  // get movements
  $ua_movements = Movement::getMovements();

  // get current time
  $now = time();

  // get movement
  $sql = $db->prepare("SELECT * FROM ". EVENT_MOVEMENT_TABLE ." 
                      WHERE event_movementID = :eventID");
  $sql->bindValue('eventID', $eventID, PDO::PARAM_INT);

  if ($sql->rowCountSelect() == 0) return 1;
  if (!$sql->execute())
    return 1;
  $move = $sql->fetch(PDO::FETCH_ASSOC);

  // check movement

  // blocked
  if ($move['blocked'])
    return 1;

  // not reversable
  if ($ua_movements[$move['movementID']]->returnID == -1)
    return 1;

  // not own movement
  if ($caveID != $move['caveID'])
    return 1;

  // expired
  if (time_fromDatetime($move['end']) < $now)
    return 1;

  // build query
  $start = time_fromDatetime($move['start']);
  $end   = time_fromDatetime($move['end']);
  $diff  = $now - $start;

  $sql = $db->prepare("UPDATE ". EVENT_MOVEMENT_TABLE ." SET source_caveID = target_caveID, ".
                   "target_caveID = caveID, ".
                   "movementID = :returnID, end = :endTime, start = :startTime ".
                   "WHERE blocked = 0 AND caveID = source_caveID AND ".
                   "caveID = :caveID AND event_movementID = :movementID");
  $sql->bindValue('returnID', $ua_movements[$move['movementID']]->returnID, PDO::PARAM_INT);
  $sql->bindValue('endTime', time_toDatetime($now + $diff), PDO::PARAM_STR);
  $sql->bindValue('startTime', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_STR);
  $sql->bindValue('movementID', $eventID, PDO::PARAM_STR);

  $sql->execute();
  
  return intval($sql->rowCount() != 1);
}

// hilfsfkt
function filterZeros($val) {
  return !empty( $val );
}

// hilfsfkt
function checkFormValues($val) {
  return (int) $val;
}

function setMovementEvent($caveID, $caveData,  $targetX, $targetY, $unit, $resource, $movementID, $reqFood, $absDuration, $artefactID, $heroID, $caveSpeedFactor) {
  global $db;

  // ziel-hoehlenID holen
  $sql = $db->prepare("SELECT caveID FROM ". CAVE_TABLE ." 
                       WHERE xCoord = :targetX AND yCoord = :targetY");
  $sql->bindValue('targetX', $targetX, PDO::PARAM_INT);
  $sql->bindVAlue('targetY', $targetY, PDO::PARAM_INT);

  if ($sql->rowCountSelect() != 1) return 1;
  if (!$sql->execute()) return 1;

  $row = $sql->fetch();
  $sql->closeCursor();
  $targetCaveID = $row['caveID'];

  // updates fuer cave basteln
  $update = "UPDATE ". CAVE_TABLE ." ";
  $updateRollback = "UPDATE ". CAVE_TABLE ." ";

  $where = "WHERE caveID = $caveID ";
  $whereRollback = "WHERE caveID = $caveID ";

  $set = $setRollback = array();

  foreach ($unit as $unitID => $value) {
    if( !empty( $value )) {
      $set[] = $GLOBALS['unitTypeList'][$unitID]->dbFieldName." = ".$GLOBALS['unitTypeList'][$unitID]->dbFieldName." - $value ";
      $setRollback[] = $GLOBALS['unitTypeList'][$unitID]->dbFieldName." = ".$GLOBALS['unitTypeList'][$unitID]->dbFieldName." + $value ";
      $where .= "AND ".$GLOBALS['unitTypeList'][$unitID]->dbFieldName." >= $value ";
      $where .= "AND $value >= 0 "; // check for values bigger 0!
    }
  }

  foreach ($resource as $resourceID => $value) {
    $value_to_check = $value;
    if ($resourceID == GameConstants::FUEL_RESOURCE_ID)
      $value += $reqFood;

    if (!empty($value) || !empty($value_to_check)) {
      $set[] = $GLOBALS['resourceTypeList'][$resourceID]->dbFieldName." = ".$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName." - $value ";
      $setRollback[] = $GLOBALS['resourceTypeList'][$resourceID]->dbFieldName." = ".$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName." + $value ";
      $where .= "AND ".$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName." >= $value ";
      if (!empty($value_to_check)) {
        $where .= "AND $value_to_check >= 0 ";
      }
    }
  }

  $update = $update."SET ".implode(", ", $set).$where;
  $updateRollback = $updateRollback."SET ".implode(", ", $setRollback).$whereRollback;

  if (!$db->exec($update)) {
    return 2;
  }

  // remove the artefact if any
  if ($artefactID > 0){

    // TODO: what should happen, if the first succedes but one of the other fails afterwards
    if (!artefact_removeEffectsFromCave($artefactID)) {
      $db->query($updateRollback);
      return 3;
    }

    if (!artefact_uninitiateArtefact($artefactID)) {
      $db->query($updateRollback);
      return 3;
    }

    if (!artefact_removeArtefactFromCave($artefactID)) {
      $db->query($updateRollback);
      return 3;
    }
  }
  
  // remove hero if any
  if ($heroID > 0){
    if (!hero_removeHeroFromCave($heroID))
      return 3;
  }

  // insert fuer movement_event basteln
  $now = time();
  $insert = "INSERT INTO ". EVENT_MOVEMENT_TABLE ." (caveID, source_caveID, target_caveID, movementID, `start`, `end`, artefactID, heroID, speedFactor, exposeChance, ";
  $i = 0;
  foreach ($unit as $uID => $val) {
    if (!empty($val)){
      if ($i++ != 0) $insert .= " , ";
      $insert .= $GLOBALS['unitTypeList'][$uID]->dbFieldName;
    }
  }

  foreach ($resource as $rID => $val) {
    if (!empty($val)) {
      $insert .= " , ".$GLOBALS['resourceTypeList'][$rID]->dbFieldName;
    }
  }
  $speedFactor = getMaxSpeedFactor($unit) * $caveSpeedFactor;

  // determine expose chance
  $exposeChance = (double)rand() / (double)getRandMax();

  $insert .= sprintf(" ) VALUES (%d, %d, %d, %d, ".
                     "'%s', '%s', %d, %d, %f, %f, ",
                     $caveID, $caveID, $targetCaveID, $movementID,
                     time_toDatetime($now),
                     time_toDatetime($now + $absDuration * 60),
                     $artefactID, $heroID, $speedFactor, $exposeChance);

  $i = 0;
  foreach($unit as $val) {
    if (!empty($val)) {
      if ($i++ != 0) $insert .= " , ";
      $insert .= $val;
    }
  }

  foreach ($resource as $val) {
    if (!empty($val)) $insert .= " , ".$val;
  }

  $insert .= " )";

  if(!$db->exec($insert)) {
    // update rueckgaengig machen
    $db->query($updateRollback);
    return 3;
  }
  
  return 0;
}

?>