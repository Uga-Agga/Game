<?php
/*
 * digest.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Movement.php');

/*****************************************************************************/
/*                                                                          **/
/*      MOVEMENTS                                                           **/
/*                                                                          **/
/*****************************************************************************/

function digest_getMovements($ownCave, $doNotShow, $showDetails) {
  global $resourceTypeList, $unitTypeList, $EXPOSEINVISIBLE;
  global $db;

  // get movements
  $ua_movements = Movement::getMovements();

  // caveIDs einsammeln
  $caveIDs = implode(', ', array_map(array($db, 'quote'), array_keys($ownCave)));

  // Bewegungen besorgen
  $query = "SELECT *
            FROM " . EVENT_MOVEMENT_TABLE . "
            WHERE source_caveID IN (". $caveIDs .")
              OR target_caveID IN (". $caveIDs .")
            ORDER BY end ASC, event_movementID ASC";

  if (!$sql = $db->query($query)) {
    return array();
  }

  $rows = $sql->fetchAll();
  $sql->closeCursor();

  // bewegungen durchgehen
  $result = array();
  foreach($rows as $row) {

    // "do not show" movements should not be shown
    if (in_array($row['movementID'], $doNotShow)) continue;

    // is own movement?
    $row['isOwnMovement'] = in_array($row['caveID'], array_keys($ownCave));
    /////////////////////////////////
    // SICHTWEITE BESCHRÄNKEN

/* We got some problems, as reverse movements should not ALWAYS be visible.
 * For example a transport reverse movement should be visible, but a
 * spy reverse movement should not...
 * As a work around we will fix it by not showing any adverse reverse movement.
 *
 * The original code is following...

    if (!$row['isOwnMovement']){

      if ($ua_movements[$row['movementID']]->returnID == -1){
        $sichtweite = getVisionRange($ownCave[$row['source_caveID']]) * $row['speedFactor'];
        $distance = time() - (time_fromDatetime($row['end']) - getDistanceByID($srcID, $destID) * $row['speedFactor']);
      } else {
        $sichtweite = getVisionRange($ownCave[$row['target_caveID']]) * $row['speedFactor'];
        $distance = ceil((time_fromDatetime($row['end']) - time())/60);
      }

      if ($sichtweite < $distance) continue;
    }
 */
    // compute visibility
    if (!$row['isOwnMovement']) {
      // don't show adverse reverse movements
      if ($ua_movements[$row['movementID']]->returnID == -1) continue;

      $sichtweite = getVisionRange($ownCave[$row['target_caveID']]) * $row['speedFactor'];
      $distance = ceil((time_fromDatetime($row['end']) - time())/60);
      if ($sichtweite < $distance) continue;
    }
  /////////////////////////////////


    // ***** fremde unsichtbare bewegung *****
    if ($row['isOwnMovement'] == 0) {
      if ($ua_movements[$row['movementID']]->mayBeInvisible) {
        $anzahl_sichtbarer_einheiten = 0;
        foreach ($unitTypeList as $unitType)
        {
          if ($unitType->visible) {
            $anzahl_sichtbarer_einheiten += $row[$unitType->dbFieldName];
          }
        }

        if ($anzahl_sichtbarer_einheiten == 0) {
          continue;
        }
      }
    }

    $tmp = array(
      'event_id'               => $row['event_movementID'],
      'cave_id'                 => $row['caveID'],
      'source_cave_id'          => $row['source_caveID'],
      'target_cave_id'          => $row['target_caveID'],
      'movement_id'             => $row['movementID'],
      'event_start'             => time_fromDatetime($row['start']),
      'event_start_date'        => time_formatDatetime($row['start']),
      'event_end'               => time_fromDatetime($row['end']),
      'event_end_date'          => time_formatDatetime($row['end']),
      'isOwnMovement'           => intval($row['isOwnMovement']),
      'seconds_before_end'      => time_fromDatetime($row['end']) - time(),
      'movement_id_description' => $ua_movements[$row['movementID']]->description
    );

    // Quelldaten
    $source = getCaveNameAndOwnerByCaveID($row['source_caveID']);
    foreach ($source AS $key => $value) {
      $tmp['source_'.$key] = $value;
    }

    // Zieldaten
    $target = getCaveNameAndOwnerByCaveID($row['target_caveID']);
    foreach ($target AS $key => $value) {
      $tmp['target_'.$key] = $value;
    }

    // ***** Einheiten, Rohstoffe und Artefakte *****
    if ($showDetails) {
      // show artefact
      if ($row['artefactID']) {
        $tmp['ARTEFACT'] = artefact_getArtefactByID($row['artefactID']);
      }

      // eval(ExposeInvisible)
      // FIXME (mlunzena): oben holen wir schon bestimmte Höhlendaten,
      //                   das sollte man zusammenfassen..
      $target = getCaveByID($row['target_caveID']);
      $expose = eval('return '.formula_parseToPHP($EXPOSEINVISIBLE.";", '$target'));

      // show units
      $units = array();
      foreach ($unitTypeList as $unit) {

        // this movement does not contain units of that type
        if (!$row[$unit->dbFieldName]) continue;

        // expose invisible units
        //   if it is your own move
        //   if unit is visible
        if (!$row['isOwnMovement'] && !$unit->visible) {

          // if target cave's EXPOSEINVISIBLE is > than exposeChance
          if ($expose <= $row['exposeChance']) {
            // do not expose
            continue;
          } else {
            // do something
            // for example:
            // $row[$unit->dbFieldName] *= 2.0 * (double)rand() / (double)getRandMax();
          }
        }

        $units[] = array(
          'unitID'      => $unit->unitID,
          'dbFieldName' => $unit->dbFieldName,
          'name'        => $unit->name,
          'value'       => ($ua_movements[$row['movementID']]->fogUnit && !$row['isOwnMovement']) ? calcFogUnit($row[$unit->dbFieldName]) : $row[$unit->dbFieldName]
        );
      }

      if (sizeof($units)) {
        $tmp['UNITS'] = $units;
      }

      $resources = array();
      foreach ($resourceTypeList as $resource) {
        if (!$row[$resource->dbFieldName]) continue;

        $resources[] = array(
          'name'       => $resource->name,
          'dbFieldName' => $resource->dbFieldName,
          'value'       => ($ua_movements[$row['movementID']]->fogResource && !$row['isOwnMovement']) ? calcFogResource($row[$resource->dbFieldName]) : $row[$resource->dbFieldName]
        );
      }
      if (sizeof($resources)) $tmp['RESOURCES'] = $resources;

      if ($row['isOwnMovement'] && 
          $ua_movements[$row['movementID']]->returnID != -1 && 
          !$row['artefactID'] && 
          !$row['blocked']) {
        $tmp['CANCEL'] = array("modus" => UNIT_MOVEMENT,
                               "eventID" => $row['event_movementID']);
       }
    }

    $result[] = $tmp;
  }

  return $result;
}


/*****************************************************************************/
/*                                                                          **/
/*      INITIATIONS                                                         **/
/*                                                                          **/
/*****************************************************************************/


function digest_getInitiationDates($ownCave) {
  global $db;

  $caveIDs = array();
  foreach ($ownCave as $caveID => $value) {
    array_push($caveIDs, "e.caveID = " . (int) $caveID);
  }
  $caveIDs = implode(" OR ", $caveIDs);

  $sql = $db->prepare("SELECT e.event_artefactID, e.caveID, e.artefactID, e.event_typeID, e.start, e.end, ac.name
                       FROM " . EVENT_ARTEFACT_TABLE . " e
                         LEFT JOIN " . ARTEFACT_TABLE . " a ON e.artefactID = a.artefactID
                         LEFT JOIN " . ARTEFACT_CLASS_TABLE . " ac ON a.artefactClassID = ac.artefactClassID
                         WHERE " . $caveIDs . " ORDER BY e.end ASC, e.event_artefactID ASC");

  if (!$sql->execute()) return array();

  // bewegungen durchgehen
  $result = array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $result[] = array(
      'eventID'            => $row['event_artefactID'],
      'event_typeID'       => $row['event_typeID'],
      'name'               => $ownCave[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'artefactID'         => $row['artefactID'],
      'artefactName'       => $row['name'],
      'event_start'        => time_fromDatetime($row['start']),
      'event_end'          => time_fromDatetime($row['end']),
      'event_end_date'     => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time()
    );
  }
  $sql->closeCursor();

  return $result;
}


/*****************************************************************************/
/*                                                                          **/
/*      APPOINTMENTS                                                        **/
/*                                                                          **/
/*****************************************************************************/


function digest_getAppointments($ownCave){

  global $buildingTypeList, $scienceTypeList, $defenseSystemTypeList, $unitTypeList;
  global $db;

  $caveIDs = implode(', ', array_map(array($db, 'quote'), array_keys($ownCave)));

  $allCaves = array();
  $sql = $db->prepare("SELECT *
                       FROM " . CAVE_TABLE . "
                       WHERE caveID IN (" . $caveIDs . ")");

  if (!$sql->execute()) return array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $allCaves[$row['caveID']] = $row;
  }
  $sql->closeCursor();

  $result = array();
  $sql = $db->prepare("SELECT *
                       FROM " . EVENT_UNIT_TABLE . "
                       WHERE caveID IN (" . $caveIDs . ")
                       ORDER BY end ASC, event_unitID ASC");

  if (!$sql->execute()) return array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $result[] = array(
      'event_name'         => $row['quantity'] . "x " . $unitTypeList[$row['unitID']]->name,
      'cave_name'          => $ownCave[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'unit',
      'modus'              => UNIT_BUILDER,
      'eventID'            => $row['event_unitID'],
      'event_start'        => time_fromDatetime($row['start']),
      'event_end'          => time_fromDatetime($row['end']),
      'event_end_date'     => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time());
  }
  $sql->closeCursor();

  $sql = $db->prepare("SELECT *
                       FROM " . EVENT_EXPANSION_TABLE . "
                       WHERE caveID IN (" . $caveIDs . ")
                       ORDER BY end ASC, event_expansionID ASC");
  $sql->bindValue('caveIDs', $caveIDs);
  if (!$sql->execute()) return array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $nextLevel = $allCaves[$row['caveID']][$buildingTypeList[$row['expansionID']]->dbFieldName] +1;
    $result[] = array(
      'event_name'         => $buildingTypeList[$row['expansionID']]->name. " Stufe ". $nextLevel,
      'cave_name'          => $ownCave[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'building',
      'modus'              => IMPROVEMENT_DETAIL,
      'eventID'            => $row['event_expansionID'],
      'event_start'        => time_fromDatetime($row['start']),
      'event_end'          => time_fromDatetime($row['end']),
      'event_end_date'     => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time());
  }
  $sql->closeCursor();

  $sql = $db->prepare("SELECT *
                       FROM " . EVENT_DEFENSE_SYSTEM_TABLE . "
                       WHERE caveID IN (" . $caveIDs . ")
                       ORDER BY end ASC, event_defenseSystemID ASC");

  if (!$sql->execute()) return array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $nextLevel = $allCaves[$row['caveID']][$defenseSystemTypeList[$row['defenseSystemID']]->dbFieldName] +1;
    $result[] = array(
      'event_name'         => $defenseSystemTypeList[$row['defenseSystemID']]->name . " Stufe ". $nextLevel,
      'cave_name'          => $ownCave[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'defense',
      'modus'              => EXTERNAL_BUILDER,
      'eventID'            => $row['event_defenseSystemID'],
      'event_start'        => time_fromDatetime($row['start']),
      'event_end'          => time_fromDatetime($row['end']),
      'event_end_date'     => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time()
    );
  }
  $sql->closeCursor();

  $sql = $db->prepare("SELECT *
                       FROM " . EVENT_SCIENCE_TABLE . "
                       WHERE caveID IN (" . $caveIDs . ")
                       ORDER BY end ASC, event_scienceID ASC");

  if (!$sql->execute()) return array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)){
    $nextLevel = $allCaves[$row['caveID']][$scienceTypeList[$row['scienceID']]->dbFieldName] +1;
    $result[] = array(
      'event_name'         => $scienceTypeList[$row['scienceID']]->name. " Stufe ". $nextLevel,
      'cave_name'          => $ownCave[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'science',
      'modus'              => SCIENCE,
      'eventID'            => $row['event_scienceID'],
      'event_start'        => time_fromDatetime($row['start']),
      'event_end'          => time_fromDatetime($row['end']),
      'event_end_date'     => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time()
    );
  }
  $sql->closeCursor();

  usort($result, "datecmp");
  return $result;
}

// for comparing the dates of appointments
function datecmp($a, $b) {
  if ($a['seconds_before_end'] == $b['seconds_before_end'])
    return 0;
  return ($a['seconds_before_end'] < $b['seconds_before_end']) ? -1 : 1;
}

?>