<?php
/*
 * defense.html.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');



function defense_getQueue($playerID, $caveID) {
  global $db;

  // prepare query
  $sql = $db->prepare("SELECT e.* 
                       FROM ". EVENT_DEFENSE_SYSTEM_TABLE ." e
                       LEFT JOIN ". CAVE_TABLE ." c ON c.caveID = e.caveID
                       WHERE c.caveID IS NOT NULL AND c.playerID = :playerID
                         AND e.caveID = :caveID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) return null;

  $result = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (empty($result)) {
    return null;
  }

  return $result;
}


################################################################################

/**
 *
 */

function defense_cancelOrder($event_defenseSystemID, $caveID) {
  global $db;

  // prepare query
  $sql = $db->prepare("DELETE FROM ". EVENT_DEFENSE_SYSTEM_TABLE . "
                       WHERE event_defenseSystemID = :defenseSystemID 
                         AND caveID = :caveID");
  $sql->bindValue('defenseSystemID', $event_defenseSystemID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 0; // return messageID
  }

  return 1; // return messageID
}


################################################################################

/**
 *
 */

function defense_demolishingPossible($caveID) {
  global $db;

  // prepare query
  $sql = $db->prepare("SELECT toreDownTimeout < NOW()+0 AS possible
                       FROM ". CAVE_TABLE ."
                       WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) return false;

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (!$row['possible']) {
    return false;
  }

  return true;
}


################################################################################

/**
 *
 */

function defense_Demolishing($defenseID, $caveID, $cave) {
  global $db;

  $dbFieldName = $GLOBALS['defenseSystemTypeList'][$defenseID]->dbFieldName;

  // can't demolish
  if (!defense_demolishingPossible($caveID)) {
    return 2;
  }

  // no defenseSystem of that type
  if ($cave[$dbFieldName] < 1) {
    //return 3;
  }

  $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                       SET {$dbFieldName} = {$dbFieldName} - 1,
                         toreDownTimeout = (NOW() + INTERVAL :toreDownTime MINUTE) + 0
                       WHERE caveID = :caveID 
                         AND {$dbFieldName} > 0");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindVAlue('toreDownTime', TORE_DOWN_TIMEOUT, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 4;
  }

  return 5;
}



################################################################################

/**
 *
 */

function defense_processOrder($defenseID, $caveID, $cave) {
  global $db;

  // take production costs from cave
  if (!processProductionCost($GLOBALS['defenseSystemTypeList'][$defenseID], $caveID, $cave)) {
    return 6;
  }

  // calculate the production time;
  $prodTime = 0;
  if ($time_formula = $GLOBALS['defenseSystemTypeList'][$defenseID]->productionTimeFunction) {
    $time_eval_formula = formula_parseToPHP($time_formula, '$cave');

    $time_eval_formula="\$prodTime = $time_eval_formula;";
    eval($time_eval_formula);
  }

  $prodTime *= DEFENSESYSTEM_TIME_BASE_FACTOR;
  $now = time();
  $sql = $db->prepare("INSERT INTO ". EVENT_DEFENSE_SYSTEM_TABLE ." 
                         (caveID, defenseSystemID, start, end)
                       VALUES
                         (:caveID, :defenseID, :start, :end)");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('defenseID', $defenseID, PDO::PARAM_INT);
  $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('end', time_toDatetime($now + $prodTime), PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    //give production costs back
    processProductionCostSetBack($GLOBALS['defenseSystemTypeList'][$defenseID], $caveID, $cave);
    return 6;
  }

  return 7;
}

?>