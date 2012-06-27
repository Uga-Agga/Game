<?php
/*
 * movement.lib.php - routines for movement processing
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

/** This function computes the distance between two caves using their IDs.
 */
function getDistanceByID($srcID, $destID){

  $srcCave  = getCaveByID($srcID);
  $destCave = getCaveByID($destID);

  return getDistanceByCoords($srcCave['xCoord'],  $srcCave['yCoord'],
                             $destCave['xCoord'], $destCave['yCoord']);
}

/** This function computes the distance between two caves using their Coords.
 */
function getDistanceByCoords($srcX, $srcY, $tarX, $tarY){

  /* Using torus edge conditions */
  $size = getMapSize();
  $dim_x = ($size['maxX'] - $size['minX'] + 1)/2;
  $dim_y = ($size['maxY'] - $size['minY'] + 1)/2;

  $xmin = $dim_x - abs(abs($srcX - $tarX) - $dim_x);
  $ymin = $dim_y - abs(abs($srcY - $tarY) - $dim_y);

  return sqrt($xmin * $xmin + $ymin * $ymin);

}

/** This function computes the vision range of a given cave.
 *  The measuring unit of the returned value is 'caves'.
 *  For example a returned value of 3 equals a vision range of
 *  three caves in any direction.
 */
function getVisionRange($cave_data){
  return eval('return '.formula_parseToPHP(GameConstants::WATCHTOWER_VISION_RANGE.";", '$cave_data'));
}

/** This function computes the amount of food needed to move with
 *  given units from one cave to its direct neighbour.
 */
function calcRequiredFood($units){
  $foodPerCave = 0;
  foreach ($units as $unitID => $amount) {
    $foodPerCave += $GLOBALS['unitTypeList'][$unitID]->foodCost * $amount;
  }

  return $foodPerCave;
}

/** This function computes the greatest speed factor of a given
 *  set of units. A greater speed factor means a slower movement.
 */
function getMaxSpeedFactor($units){
  $maxSpeed = 0;

  foreach ($units as $unitID => $amount) {
    if ($amount > 0 && $GLOBALS['unitTypeList'][$unitID]->wayCost > $maxSpeed) {
      $maxSpeed = $GLOBALS['unitTypeList'][$unitID]->wayCost;
    }
  }

  return $maxSpeed;
}

function calcFogUnit($unitCount) {
  if ($_SESSION['player']->tribe == GOD_ALLY) {
    return $unitCount;
  }

  if ($unitCount < 9) return 'eine Handvoll';
  else if ($unitCount < 17) return 'ein Dutzend';
  else if ($unitCount < 65) return 'eine Schar';
  else if ($unitCount < 257) return 'eine Kompanie';
  else if ($unitCount < 513) return 'etliche';
  else if ($unitCount < 1025) return 'ein Bataillon ';
  else if ($unitCount < 2049) return 'viele';
  else if ($unitCount < 4097)  return 'eine Menge';
  else if ($unitCount < 6145) return 'eine Legion';
  else if ($unitCount < 8193) return 'ein Haufen';
  else if ($unitCount < 12289) return 'ein großer Haufen';
  else if ($unitCount < 16385) return 'verdammt viele';
  else if ($unitCount < 20481) return 'Unmengen';
  else if ($unitCount < 32769) return 'eine Streitmacht ';
  else if ($unitCount < 49153) return 'eine Armee';
  else if ($unitCount < 65537) return 'Heerscharen';
  else if ($unitCount < 98305) return 'eine haltlose Horde ';
  else return 'eine endlose wogende Masse';
}

function calcFogResource($resourceCount) {
  if ($_SESSION['player']->tribe == GOD_ALLY) {
    return $resourceCount;
  }

  if ($resourceCount < 257) return 'fast gar nichts';
  else if ($resourceCount < 1025) return 'ein winziger Haufen';
  else if ($resourceCount < 4097) return 'ein kleiner Haufen';
  else if ($resourceCount < 16385) return 'ein beachtlicher Haufen';
  else if ($resourceCount < 32769) return 'eine Menge';
  else if ($resourceCount < 65537) return 'eine große Menge';
  else if ($resourceCount < 131074) return 'ein Berg';
  else if ($resourceCount < 262148) return 'ein großer Berg';
  else if ($resourceCount < 524296) return 'ein riesiger Berg';
  else  return 'unglaublicher Überfluss';
}

?>