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

?>