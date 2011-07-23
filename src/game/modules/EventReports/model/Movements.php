<?php
/*
 * Movements.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Model.php');

class EventReports_Movements_Model extends Model {

  var $caveID;
  var $caves;
  var $movements;

  function EventReports_Movements_Model($caveID, $caves) {
    $this->caveID = $caveID;
    $this->caves  = $caves;
    $this->initMovements();
  }

  function initMovements(){
    global $db, $unitTypeList;

    // collect caveIDs
    $caveIDs = $this->_collectCaveIDs();

    // prepare query
    $sql = $db->prepare("SELECT * FROM ". EVENT_MOVEMENT_TABLE ." WHERE caveID IN (".implode(', ', $caveIDs) . ")");

    // get records

    if (!$sql->execute()) return page_dberror();

    // iterate through movements
    $this->movements = array();
    while($movement = $sql->fetch(PDO::FETCH_ASSOC))
      $this->movements[] = $movement;
  }

  function _collectCaveIDs() {
    $ids = array();
    foreach ($this->caves as $caveID => $cave)
      $ids[] = $caveID;
    return $ids;
  }

  function getGroupedMovements() {
    global $unitTypeList;

    $result = array();
    foreach ($this->movements as $movement)
      foreach ($unitTypeList as $unitType)
        if ($movement[$unitType->dbFieldName])
          $result[$movement['movementID']][$unitType->dbFieldName][$movement['caveID']] += $movement[$unitType->dbFieldName];

    return $result;
  }
}

?>