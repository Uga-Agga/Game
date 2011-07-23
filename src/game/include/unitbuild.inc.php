<?php
/*
 * unitbuild.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function unit_getQueue($playerID, $caveID) {
  global $db;

  $sql = $db->prepare("SELECT e.* ".
                      "FROM ". EVENT_UNIT_TABLE ." e ".
                      "LEFT JOIN ". CAVE_TABLE ." c ON c.caveID = e.caveID ".
                      "WHERE c.caveID IS NOT NULL ".
                      "AND c.playerID = :playerID ".
                      "AND e.caveID = :caveID");
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


function unit_cancelOrder($event_unitID, $caveID) {
  global $db;
  
  $sql = $db->prepare("DELETE FROM ". EVENT_UNIT_TABLE ."
                       WHERE event_unitID = :event_unitID
                         AND caveID = :caveID");
  $sql->bindValue('event_unitID', $event_unitID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  
  if (!$sql->execute()) return 1; 
  
  return 0;
}

function unit_processOrder($unitID, $quantity, $caveID, $details) {

  global $defenseSystemTypeList,
         $unitTypeList,
         $buildingTypeList,
         $scienceTypeList,
         $resourceTypeList, 
         $db;

  if ($quantity > MAX_SIMULTAN_BUILDED_UNITS || $quantity <= 0 ) {
    return 4;
  }


  // take the production costs from cave
  if (!processProductionCost($unitTypeList[$unitID], $caveID, $details, $quantity)) {
    return 2;
  }

  $prodTime = 0;
  // calculate the production time;
  if ($time_formula = $unitTypeList[$unitID]->productionTimeFunction){
    $time_eval_formula = formula_parseToPHP($time_formula, '$details');
    eval('$prodTime=' . $time_eval_formula . ';');
  }
  $prodTime *= BUILDING_TIME_BASE_FACTOR * $quantity;
  $now = time();
  $sql = $db->prepare("INSERT INTO ". EVENT_UNIT_TABLE ." (caveID, unitID, quantity, ".
                   "start, end) VALUES (:caveID, :unitID, :quantity, :start, :end)");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('unitID', $unitID, PDO::PARAM_INT);
  $sql->bindValue('quantity', $quantity, PDO::PARAM_INT);
  $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('end', time_toDatetime($now + $prodTime), PDO::PARAM_STR);
  
  if (!$sql->execute()) {
    processProductionCostSetBack($unitTypeList[$unitID], $caveID, $details, $quantity);
    return 2;
  }
  return 3;
}

?>