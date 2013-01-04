<?php
/*
 * unitbuild.inc.php -
 * Copyright (c) 2004  OGP-Team
 * Copyright (c) 2011-2012 David Unger <unger-dave@gmail.com>
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
  if (!$sql->execute()) return null;

  $result = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (empty($result)) {
    return null;
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
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 1; // return messageID
  }

  return 0; // return messageID
}

function unit_processOrder($unitID, $quantity, $caveID, $details) {
  global $db;

  if ($quantity == -1) {
    $quantity = MAX_SIMULTAN_BUILDED_UNITS;
    while (!processProductionCost($GLOBALS['unitTypeList'][$unitID], $caveID, $details, $quantity) && $quantity != 0) {
      $quantity--;
    }
    if ($quantity <= 0) {
      return 4;
    }
  } else {
    if ($quantity > MAX_SIMULTAN_BUILDED_UNITS || $quantity <= 0 ) {
      return 4;
    }

    // take the production costs from cave
    if (!processProductionCost($GLOBALS['unitTypeList'][$unitID], $caveID, $details, $quantity)) {
      return 2;
    }
  }

  $prodTime = 0;
  // calculate the production time;
  if ($time_formula = $GLOBALS['unitTypeList'][$unitID]->productionTimeFunction){
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
  if (!$sql->execute() || $sql->rowCount() == 0) {
    processProductionCostSetBack($GLOBALS['unitTypeList'][$unitID], $caveID, $details, $quantity);
    return 2;
  }

  return 3;
}

?>