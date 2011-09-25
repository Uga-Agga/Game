<?php
/*
 * defense.html.php -
 * Copyright (c) 2004  OGP Team
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
  $sql = $db->prepare("SELECT e.* FROM ". EVENT_DEFENSE_SYSTEM_TABLE ." e
                         LEFT JOIN ". CAVE_TABLE ." c ON c.caveID = e.caveID
                      WHERE c.caveID IS NOT NULL AND c.playerID = :playerID
                        AND e.caveID = :caveID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return 0;
  }
  
  if (!($result = $sql->fetch())) {
    return 0;
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
                       WHERE event_defenseSystemID = :dfSID 
                         AND caveID = :caveID");
  $sql->bindValue('dfSID', $event_defenseSystemID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  // execute query
  if($sql->execute()) {
    return 1;
  }

  return 0;
}


################################################################################

/**
 *
 */

function defense_demolishingPossible($caveID) {
  global $db;

  // prepare query
  $sql = $db->prepare("SELECT toreDownTimeout < NOW()+0 AS possible ".
                   "FROM ". CAVE_TABLE ." WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  // execute query
  if (!$sql->execute())
    return 0;

  if (!($row = $sql->fetch()) || !$row["possible"])
    return 0;

  return 1;
}


################################################################################

/**
 *
 */

function defense_Demolishing($defenseID, $caveID, $cave) {

  global $resourceTypeList, $defenseSystemTypeList, $db;

  $dbFieldName = $defenseSystemTypeList[$defenseID]->dbFieldName;

  // can't demolish
  if (!defense_demolishingPossible($caveID)) {
    return 2;
  }

  // no defenseSystem of that type
  if ($cave[$dbFieldName] < 1) {
    return 3;
  }

//  $query = "UPDATE Cave ";
//  $where = "WHERE caveID = '$caveID' ".
//           "AND {$dbFieldName} > 0 ";
//
//  // add resources gain
//  /*
//  if (is_array($defenseSystemTypeList[$defenseID]->resourceProductionCost)){
//    $resources = array();
//    foreach ($defenseSystemTypeList[$defenseID]->resourceProductionCost as $key => $value){
//      if ($value != "" && $value != "0"){
//        $formula     = formula_parseToSQL($value);
//        $dbField     = $resourceTypeList[$key]->dbFieldName;
//        $maxLevel    = round(eval('return '.formula_parseToPHP("{$resourceTypeList[$key]->maxLevel};", '$cave')));
//        $resources[] = "$dbField = LEAST($maxLevel, $dbField + ($formula) / {$config->DEFENSESYSTEM_PAY_BACK_DIVISOR})";
//      }
//    }
//    $set .= implode(", ", $resources);
//  }
//  */
//
//  // ATTENTION: "SET defenseSystem = defenseSystem - 1" has to be placed BEFORE
//  //            the calculation of the resource return. Otherwise
//  //            mysql would calculate the cost of the NEXT step not
//  //            of the LAST defenseSystem step (returns would be too high)...
//  $query .= "SET {$dbFieldName} = {$dbFieldName} - 1, ".
//            "toreDownTimeout = (NOW() + INTERVAL ".
//            TORE_DOWN_TIMEOUT." MINUTE)+0 ";
            
  $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                       SET {$dbFieldName} = {$dbFieldName} - 1,
                       toreDownTimeout = (NOW() + INTERVAL :toreDownTime MINUTE) + 0
                       WHERE caveID = :caveID 
                       AND {$dbFieldName} > 0");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindVAlue('toreDownTime', TORE_DOWN_TIMEOUT, PDO::PARAM_INT);

  if (!$sql->execute() || !$sql->rowCount() == 1) {
    return 4;
  }

  return 5;
}



################################################################################

/**
 *
 */

function defense_processOrder($defenseID, $caveID, $cave) {
  global $defenseSystemTypeList, $unitTypeList, $buildingTypeList, $scienceTypeList, $resourceTypeList;
  global $db;

  $external = $defenseSystemTypeList[$defenseID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$external->maxLevel};", '$cave')));

  // take production costs from cave
  if (!processProductionCost($external, $caveID, $cave))
    return 6;

  // calculate the production time;
  $prodTime = 0;
  if ($time_formula = $external->productionTimeFunction) {
    $time_eval_formula = formula_parseToPHP($time_formula, '$cave');

    $time_eval_formula="\$prodTime = $time_eval_formula;";
    eval($time_eval_formula);
  }

  $prodTime *= DEFENSESYSTEM_TIME_BASE_FACTOR;
  $now = time();
  $sql = $db->prepare("INSERT INTO ". EVENT_DEFENSE_SYSTEM_TABLE ." (caveID, defenseSystemID, ".
                   "start, end) VALUES (:caveID, :defenseID, :start, :end)");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('defenseID', $defenseID, PDO::PARAM_INT);
  $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('end', time_toDatetime($now + $prodTime), PDO::PARAM_STR);

  if (!$sql->execute()) {
    //give production costs back
    processProductionCostSetBack($external, $caveID, $cave);
    return 6;
  }

  return 7;
}

?>