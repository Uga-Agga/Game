<?php
/*
 * science.inc.php -
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function science_getQueue($playerID, $caveID) {
  global $db;

  $sql = $db->prepare("SELECT e.* ".
                      "FROM ". EVENT_SCIENCE_TABLE ." e ".
                      "LEFT JOIN ". CAVE_TABLE ." c ON c.caveID = e.caveID ".
                      "WHERE c.caveID IS NOT NULL ".
                      "AND c.playerID = :playerID ".
                      "AND e.caveID = :caveID");
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


function science_cancelOrder($event_scienceID, $caveID) {
  global $db;
  
  $sql = $db->prepare("DELETE FROM ". EVENT_SCIENCE_TABLE ." ".
                      "WHERE event_scienceID = :event_scienceID ".
                      "AND caveID = :caveID");
  $sql->bindValue('event_scienceID', $event_scienceID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 1; // return messageID
  }

  return 0; // return messageID
}

function science_processOrder($scienceID, $caveID, $cave) {

  global $defenseSystemTypeList, $buildingTypeList, $scienceTypeList, $resourceTypeList, $unitTypeList;
  global $config, $db;

  $science = $scienceTypeList[$scienceID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$science->maxLevel};", '$cave')));

  // check, that this science isn't researched in an other cave at the
  // same time
  $sql = $db->prepare("SELECT event_scienceID
                       FROM ". EVENT_SCIENCE_TABLE ."
                       WHERE playerID = :playerID 
                         AND scienceID = :scienceID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('scienceID', $scienceID, PDO::PARAM_INT);
  if ($sql->rowCountSelect() != 0) {
    return 4;
  }

  // check for scienceMaxDeps in Event_Handler
  $dep_count = 0;
  $deps = '';
  foreach ($science->maxScienceDepList as $key => $value) {
    if ($value != -1 && $cave[$scienceTypeList[$key]->dbFieldName] > $value - 1) {
      if ($dep_count)
        $deps .= ", ";

      $deps .= $key;
      $dep_count++;
    }
  }

  if ($dep_count) {
    $sql = $db->prepare("SELECT event_scienceID
                         FROM ". EVENT_SCIENCE_TABLE ."
                         WHERE playerID = :playerID
                           AND scienceID IN ($deps)");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    if ($sql->rowCountSelect() != 0) {
      return 5;
    }
  }

  // take production costs from cave
  if (!processProductionCost($science, $caveID, $cave)) {
    return 2;
  }
  $prodTime = 0;

  // calculate the production time;
  if ($time_formula = $science->productionTimeFunction) {
    $time_eval_formula = formula_parseToPHP($time_formula, '$cave');

    $time_eval_formula="\$prodTime=$time_eval_formula;";
    eval($time_eval_formula);
  }

  $prodTime *= SCIENCE_TIME_BASE_FACTOR;
  $now = time();
  $sql = $db->prepare("INSERT INTO ". EVENT_SCIENCE_TABLE ." (caveID, playerID, scienceID, ".
                   "start, end) VALUES (:caveID, :playerID, :scienceID, :start, :end)");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('scienceID', $scienceID, PDO::PARAM_INT);
  $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('end', time_toDatetime($now + $prodTime), PDO::PARAM_STR);
  if (!$sql->execute() || !$sql->rowCount() == 1) {
    processProductionCostSetBack($science, $caveID, $cave);
    return 2;
  }

  return 3;
}

?>