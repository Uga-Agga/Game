<?php
/*
 * diminishMap.php - Diminishs map cutting the outer rim
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";


$config = new Config();
$db     = new Db();

// configs
$terrainID = (int) 6;

// find highest and lowest coordinates
$sql = "SELECT 
         MIN(xCoord) AS xMin, 
         MAX(xCoord) AS xMax, 
         MIN(yCoord) AS yMin, 
         MAX(yCoord) AS yMax
               FROM Cave";

if (!($result = $db->query($sql))) {
  echo "Error finding highest and lowest coordinates!\n";
  exit(1);
}

$row =  $result->nextrow(MYSQL_ASSOC);
$xMin = $row['xMin']; $yMin = $row['yMin']; 
$xMax = $row['xMax']; $yMax = $row['yMax'];

// check which row was already processes
$sql = "SELECT
         min(xCoord) AS startCoord
         FROM Cave
         WHERE terrain <> {$terrainID}
         AND yCoord = xCoord";


if (!($result = $db->query($sql))) {
  echo "Error finding starting position!\n";
  exit(1);
}

$row = $result->nextrow(MYSQL_ASSOC);
$startCoord = (int) $row['startCoord'];
echo "Starting Coord = " . $startCoord . "\n";

if ($startCoord >= $xMax/2 || $startCoord >= $yMax/2) {
  echo "No more to diminish!\n";
  exit(0);
}

// set terrains do $terrainID

for ($i = $startCoord; $i <= $xMax - $startCoord +1; $i++) {
  $xCoord =  $i;
  $yCoord = $startCoord;
  $update = "UPDATE Cave SET 
           playerID = 0, 
           `terrain` = {$terrainID} 
           WHERE xCoord = {$xCoord}
           AND yCoord = {$yCoord}";
  if(!($db->query($update))) {
    echo "Error setting new terrain (first row)!\n";
    echo $update . "\n";
    exit(1);
  }
}

for ($i = $startCoord; $i <= $xMax - $startCoord +1; $i++) {
  $limit = $yMax - $startCoord +1;
  $xCoord = $i;
  $yCoord =  $limit;
  $update = "UPDATE Cave SET 
           playerID = 0, 
           `terrain` = {$terrainID} 
           WHERE xCoord = {$xCoord}
           AND yCoord = {$yCoord}";
  if(!($db->query($update))) {
    echo "Error setting new terrain (first row)!\n";
    exit(1);
  }
}

for ($i = $startCoord; $i <= $yMax - $startCoord + 1; $i++) {
  $xCoord =  $startCoord;
  $yCoord =  $i;
  $update = "UPDATE Cave SET 
           playerID = 0, 
           `terrain` = {$terrainID} 
           WHERE xCoord = {$xCoord}
           AND yCoord = {$yCoord}";
  if(!$db->query($update)) {
    echo "Error setting new terrain (first column)!\n";
    exit(1);
  }
}

for ($i = $startCoord; $i <= $yMax - $startCoord + 1; $i++) {
  $limit = $xMax - $startCoord +1;
  $xCoord =  $limit;
  $yCoord =  $i;
  $update = "UPDATE Cave SET 
           playerID = 0, 
           `terrain` = {$terrainID} 
           WHERE xCoord = {$xCoord}
           AND yCoord = {$yCoord}";
  if(!$db->query($update)) {
    echo "Error setting new terrain (last row)!\n";
    exit(1);
  }
}

echo "Done!";

return 1;

?>