<?php
/*
 * map.inc.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function getCaveDetailsByCoords($minX, $minY, $maxX, $maxY) {
  global $db;

  $caveDetails = array();
  $sql = $db->prepare("SELECT c.terrain, c.name AS cavename, c.caveID, c.xCoord, c.yCoord, c.secureCave, c.artefacts, c.takeoverable, p.name, p.playerID, p.tribeID, r.name as region, t.tag as tribe, CASE WHEN a.artefact > 0 THEN 1 ELSE 0 END as hasArtefact, CASE WHEN ap.artefact > 0 THEN 1 ELSE 0 END as hasPet
                       FROM ". CAVE_TABLE ." c
                         LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = p.playerID
                         LEFT JOIN ". TRIBE_TABLE ." t ON t.tribeID = p.tribeID
                         LEFT JOIN ". REGIONS_TABLE ." r ON c.regionID = r.regionID
                         LEFT JOIN (SELECT caveID, pet, count(*) as artefact FROM Artefact GROUP BY caveID) a ON a.caveID = c.caveID AND a.pet = 0 AND c.artefacts > 0
                         LEFT JOIN (SELECT caveID, pet, count(*) as artefact FROM Artefact GROUP BY caveID) ap ON ap.caveID = c.caveID AND ap.pet = 1 AND c.artefacts > 0
                       WHERE :minX <= c.xCoord AND c.xCoord <= :maxX
                         AND   :minY <= c.yCoord AND c.yCoord <= :maxY
                       ORDER BY c.yCoord, c.xCoord");
  $sql->bindValue('minX', $minX, PDO::PARAM_INT);
  $sql->bindValue('minY', $minY, PDO::PARAM_INT);
  $sql->bindValue('maxX', $maxX, PDO::PARAM_INT);
  $sql->bindValue('maxY', $maxY, PDO::PARAM_INT);
  if (!$sql->execute()) return array();

  $result = $sql->fetchAll(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (empty($result)) {
    return array();
  }

  return $result;
}

function getCaveDetailsForMiniMap() {
  global $db;

  $caveDetails = array();
  $sql = $db->prepare("SELECT c.terrain, c.name AS cavename, c.caveID, c.xCoord, c.yCoord, c.secureCave, c.artefacts, c.takeoverable, p.name, p.playerID, p.tribeID, r.name as region, t.tag as tribe, CASE WHEN a.artefact > 0 THEN 1 ELSE 0 END as hasArtefact, CASE WHEN ap.artefact > 0 THEN 1 ELSE 0 END as hasPet
                       FROM ". CAVE_TABLE ." c
                         LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = p.playerID
                         LEFT JOIN ". TRIBE_TABLE ." t ON t.tribeID = p.tribeID
                         LEFT JOIN ". REGIONS_TABLE ." r ON c.regionID = r.regionID
                         LEFT JOIN (SELECT caveID, pet, count(*) as artefact FROM Artefact GROUP BY caveID) a ON a.caveID = c.caveID AND a.pet = 0 AND c.artefacts > 0
                         LEFT JOIN (SELECT caveID, pet, count(*) as artefact FROM Artefact GROUP BY caveID) ap ON ap.caveID = c.caveID AND ap.pet = 1 AND c.artefacts > 0
                       ORDER BY c.yCoord, c.xCoord");
  if (!$sql->execute()) return array();

  $result = $sql->fetchAll(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (empty($result)) {
    return array();
  }

  return $result;
}

function getEmptyCell(){
  return array('empty' => array('iterate' => ''));
}

function getCornerCell() {
  return array('corner' => array('iterate' => ''));
}

function getLegendCell($name, $value){
  return array('header' => array('text' => "$name: $value"));
}

function getMapCell($map, $xCoord, $yCoord){
  if (!is_array($map[$xCoord][$yCoord])) {
    return getEmptyCell();
  } else {
    return array('mapcell' => $map[$xCoord][$yCoord]);
  }
}

function determineCoordsFromParameters($caveData, $mapSize) {
  // default Werte: Koordinaten of the given caveData (that is the data of the presently selected own cave)
  $xCoord  = $caveData['xCoord'];
  $yCoord  = $caveData['yCoord'];
  $message = '';

  // wenn in die Minimap geklickt wurde, zoome hinein
  if (($minimap_x = Request::getVar('minimap_x', 0)) &&
      ($minimap_y = Request::getVar('minimap_y', 0)) &&
      ($scaling   = Request::getVar('scaling', 0)) !== 0) {

    $xCoord = Floor($minimap_x * 100 / $scaling) + $mapSize['minX'];
    $yCoord = Floor($minimap_y * 100 / $scaling) + $mapSize['minY'];
  }

  // caveName eingegeben ?
  else if ($caveName = Request::getVar('caveName', '')) {
    $coords = getCaveByName($caveName);
    if (!$coords['xCoord']) {
      $message = sprintf(_('Die Höhle mit dem Namen: "%s" konnte nicht gefunden werden!'), $caveName);
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = sprintf(_('Die Höhle mit dem Namen: "%s" befindet sich in (%d|%d).'), $caveName, $xCoord, $yCoord);
    }
  }

  // caveID eingegeben ?
  else if (($targetCaveID = Request::getVar('targetCaveID', 0)) > 0) {
    $coords = getCaveByID($targetCaveID);
    if ($coords === null) {
      $message = sprintf(_('Die Höhle mit der ID: "%d" konnte nicht gefunden werden!'), $targetCaveID);
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = sprintf(_('Die Höhle mit der ID: "%d" befindet sich in (%d|%d).'), $targetCaveID, $xCoord, $yCoord);
    }
  }

  // Koordinaten eingegeben ?
  else if (Request::getVar('xCoord', 0) && Request::getVar('yCoord', 0)) {
    $xCoord = Request::getVar('xCoord', 0);
    $yCoord = Request::getVar('yCoord', 0);
  }

  // Koordinaten begrenzen
  if ($xCoord < $mapSize['minX']) $xCoord = $mapSize['minX'];
  if ($yCoord < $mapSize['minY']) $yCoord = $mapSize['minY'];
  if ($xCoord > $mapSize['maxX']) $xCoord = $mapSize['maxX'];
  if ($yCoord > $mapSize['maxY']) $yCoord = $mapSize['maxY'];

  return array (
    'xCoord'  => $xCoord,
    'yCoord'  => $yCoord,
    'message' => $message
  );
}

/** given the querried coordinates, calculates the minimal, maximal and center
  coordinates of the caves in the visible map region. Uses the constants MAP_WIDTH
  and MAP_HEIGHT from the configuration to determine size of the region. Also handles
  the case where the actual map in the database has fewer caves than specified in
  MAP_WIDTH and / or MAP_HEIGHT */
function calcVisibleMapRegion($mapSize, $xCoord, $yCoord) {
   // correct width und height for the case where the actual map is smaller than what could be displayed
   // in a map section.
  $MAP_WIDTH  = min(MAP_WIDTH,  $mapSize['maxX']-$mapSize['minX']+1); // attention: reads the constant MAP_WIDTH and writes the corrected value to a local variable of same name...
  $MAP_HEIGHT = min(MAP_HEIGHT, $mapSize['maxY']-$mapSize['minY']+1);

  // Nun befinden sich in $xCoord und $yCoord die gesuchten Koordinaten.
  // ermittele nun die linke obere Ecke des Bildausschnittes
  $minX = min(max($xCoord - intval($MAP_WIDTH/2),  $mapSize['minX']), $mapSize['maxX']-$MAP_WIDTH+1);
  $minY = min(max($yCoord - intval($MAP_HEIGHT/2), $mapSize['minY']), $mapSize['maxY']-$MAP_HEIGHT+1);

  // ermittele nun die rechte untere Ecke des Bildausschnittes
  $maxX = $minX + $MAP_WIDTH  - 1;
  $maxY = $minY + $MAP_HEIGHT - 1;

  $centerX = $minX+($maxX-$minX)/2;
  $centerY = $minY+($maxY-$minY)/2;

  return array(
    'minX'  => $minX, 'maxX' => $maxX,
    'minY'  => $minY, 'maxY' => $maxY,
    'width' => $maxX-$minX+1,
    'height' => $maxY-$minY+1,
    'centerX' => $centerX,
    'centerY' => $centerY,
  );
}

?>