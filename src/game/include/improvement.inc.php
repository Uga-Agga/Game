<?php
/*
 * improvement.inc.php - 
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function improvement_getQueue($playerID, $caveID) {
  global $db;

  $sql = $db->prepare("SELECT e.*
                       FROM " . EVENT_EXPANSION_TABLE . " e
                         LEFT JOIN " . CAVE_TABLE . " c ON c.caveID = e.caveID
                       WHERE c.caveID IS NOT NULL
                         AND c.playerID = :playerID
                         AND e.caveID = :caveID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) return false;

  $return = $sql->fetch(PDO::FETCH_ASSOC);
  if (count($return) !== 0) {
    return $return;
  }

  return false;
}

function improvement_cancelOrder($event_expansionID, $caveID) {
  global $db;

  $sql = $db->prepare("DELETE FROM " . EVENT_EXPANSION_TABLE . "
                       WHERE event_expansionID = :event_expansionID
                         AND caveID = :caveID");
  $sql->bindValue('event_expansionID', $event_expansionID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 1;
  }

  return 0;
}

function improvement_toreDownIsPossible($caveID) {
  global $db;

  $sql = $db->prepare("SELECT toreDownTimeout < NOW()+0 AS possible
                       FROM " . CAVE_TABLE . "
                       WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) return false;

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  if (!$row['possible']) {
    return false;
  }
  
  return true;
}

function improvement_Demolishing($buildingID, $caveID, $caveData) {
  global $resourceTypeList, $buildingTypeList, $config, $db;

  $bFieldName = $buildingTypeList[$buildingID]->dbFieldName;

  // can't tear down
  if (!improvement_toreDownIsPossible($caveID)) return 8;

  // no building of that type
  if ($caveData[$bFieldName] < 1) return 7;

  // add resources gain
  /*
  if (is_array($buildingTypeList[$buildingID]->resourceProductionCost)){
    $resources = array();
    foreach ($buildingTypeList[$buildingID]->resourceProductionCost as $key => $value){
      if ($value != "" && $value != "0"){
        $formula     = formula_parseToSQL($value);
        $dbField     = $resourceTypeList[$key]->dbFieldName;
        $maxLevel    = round(eval('return '.formula_parseToPHP("{$resourceTypeList[$key]->maxLevel};", '$caveData')));
        $resources[] = "$dbField = LEAST($maxLevel, $dbField + ($formula) / {$config->IMPROVEMENT_PAY_BACK_DIVISOR})";
      }
    }
    $set .= implode(", ", $resources);
  }
  */
  // ATTENTION: "SET building = building - 1" has to be placed BEFORE
  //            the calculation of the resource return. Otherwise
  //            mysql would calculate the cost of the NEXT step not
  //            of the LAST building step (returns would be too high)...

  $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                       SET {$bFieldName} = {$bFieldName} - 1,
                         toreDownTimeout = (NOW() + INTERVAL ". TORE_DOWN_TIMEOUT ." MINUTE) + 0
                       WHERE caveID = :caveID
                         AND {$bFieldName} > 0");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0)
    return 6;

  return 5;
}

function improvement_processOrder($buildingID, $caveID, $caveData) {
  global $buildingTypeList;
  global $db;

  $sql = $db->prepare("SELECT count(*) as count
                       FROM ". EVENT_EXPANSION_TABLE ." 
                       WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) return 2;
  $return = $sql->fetch();

  if ($return['count'] != 0) {
    return 2;
  }

  $building = $buildingTypeList[$buildingID];

  // take production costs from cave
  if (!processProductionCost($building, $caveID, $caveData)) {
    return 2;
  }
  $prodTime = 0;

  // calculate the production time;
  if ($time_formula = $building->productionTimeFunction) {
    $time_eval_formula = formula_parseToPHP($time_formula, '$caveData');

    $time_eval_formula="\$prodTime=$time_eval_formula;";
    eval($time_eval_formula);
  }

  $prodTime *= BUILDING_TIME_BASE_FACTOR;
  $now = time();
  $sql = $db->prepare("INSERT INTO ". EVENT_EXPANSION_TABLE ." 
                      (caveID, expansionID, start, end) 
                      VALUES (:caveID, :expansionID, :start, :end)");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('expansionID', $buildingID, PDO::PARAM_INT);
  $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('end', time_toDatetime($now + $prodTime), PDO::PARAM_STR);
  
  if (!$sql->execute()) {
    processProductionCostSetBack($building, $caveID, $caveData);
    return 2;
  }

  return 3;
}

?>