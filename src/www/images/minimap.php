<?php
/*
 * minimap.php -
 * Copyright (c) 2013 David Unger <unger.dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("../config.inc.php");
require_once("include/config.inc.php");
require_once("include/basic.lib.php");
require_once("include/params.inc.php");
require_once("include/db.inc.php");
require_once("include/map.inc.php");
require_once("include/rules/game.rules.php");

$config  = new Config();
$request = new Request();
$db      = DbConnect();
$mapSize = getMapSize();

$width =  600;
$height = 600;

$map = array();
$sql = $db->prepare("SELECT xCoord, yCoord, terrain
                     FROM " . CAVE_TABLE . "
                     ORDER BY yCoord, xCoord");
if (!$sql->execute()) return die('Fehler beim erstellen des Bildes');
while($row = $sql->fetch(PDO::FETCH_ASSOC)){
  $map[$row['xCoord']][$row['yCoord']] = $GLOBALS['terrainList'][$row['terrain']]['color'];
}
$sql->closeCursor();

$squareWidth = ($height/$mapSize['maxX'])-1;
$squareHeight = ($height/$mapSize['maxY'])-1;

$draw = new ImagickDraw();

$i = $j = 0;
$coordWidth = $coordHeight = 1;

foreach ($map as $row) {
  $i++;

  foreach ($row as $col) {
    $j++;
    $draw->setFillColor("rgb(" . $col['r'] . "," . $col['g'] . "," . $col['b'] . ")");
    $draw->rectangle($coordWidth, $coordHeight, $coordWidth+$squareWidth, $coordHeight+$squareHeight);

    $coordHeight = $coordHeight + $squareHeight+1;
  }
  $j = 0;
  $coordWidth = $coordWidth + $squareHeight+1;
  $coordHeight = 1;
}

$image = new Imagick();
$image->newImage($width, $height, new ImagickPixel('none'));
$image->setImageFormat('png');
$image->drawImage($draw);

header('Content-type: image/png');
echo $image;

?>