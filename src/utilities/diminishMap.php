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
$db     = DbConnect();

// configs
$terrainID = 6;

// find highest and lowest coordinates
$sql = $db->prepare("SELECT 
                     MIN(xCoord) AS xMin, 
                     MAX(xCoord) AS xMax, 
                     MIN(yCoord) AS yMin, 
                     MAX(yCoord) AS yMax
                     FROM " . CAVE_TABLE);
if (!$sql->execute()) {
  echo "Error finding highest and lowest coordinates!\n";
  exit(1);
}
echo $xMin. $xMax. $yMin. $yMax ."\n";
$row =  $sql->fetch(PDO::FETCH_ASSOC);
$xMin = $row['xMin']; $yMin = $row['yMin']; 
$xMax = $row['xMax']; $yMax = $row['yMax'];
echo $xMin.$xMax.$yMin.$yMax;

// check which row was already processes
$sql = $db->prepare("SELECT
                     min(xCoord) AS startCoord
                     FROM ". CAVE_TABLE . "
                     WHERE terrain <> :terrainID
                     AND yCoord = xCoord");
$sql->bindParam('terrainID', $terrainID, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "Error finding starting position!\n";
  exit(1);
}

$row = $sql->fetch(PDO::FETCH_ASSOC);
$startCoord = $row['startCoord'];
echo "Starting Coord = " . $startCoord . "\n";

if ($startCoord >= $xMax/2 || $startCoord >= $yMax/2) {
  echo "No more to diminish!\n";
  exit(0);
}

// set terrains do $terrainID
$sql = $db->prepare("UPDATE " . CAVE_TABLE . " SET 
                     playerID = 0, 
                     `terrain` = {$terrainID} 
                     WHERE xCoord = :xCoord
                     AND yCoord = :yCoord");

for ($i = $startCoord; $i <= $xMax - $startCoord +1; $i++) {
  $sql->bindParam('xCoord', $i, PDO::PARAM_INT);
  $sql->bindParam('yCoord', $startCoord, PDO::PARAM_INT);

  if(!$sql->execute()) {
    echo "Error setting new terrain (first row)!\n";
    exit(1);
  }
}

for ($i = $startCoord; $i <= $xMax - $startCoord +1; $i++) {
  $limit = $yMax - $startCoord +1;
  $sql->bindParam('xCoord', $i, PDO::PARAM_INT);
  $sql->bindParam('yCoord', $limit, PDO::PARAM_INT);

  if(!$sql->execute()) {
    echo "Error setting new terrain (first row)!\n";
    exit(1);
  }
}

for ($i = $startCoord; $i <= $yMax - $startCoord + 1; $i++) {
  $sql->bindParam('xCoord', $startCoord, PDO::PARAM_INT);
  $sql->bindParam('yCoord', $i, PDO::PARAM_INT);

  if(!$sql->execute()) {
    echo "Error setting new terrain (first column)!\n";
    exit(1);
  }
}

for ($i = $startCoord; $i <= $yMax - $startCoord + 1; $i++) {
  $limit = $xMax - $startCoord +1;
  $sql->bindParam('xCoord', $limit, PDO::PARAM_INT);
  $sql->bindParam('yCoord', $i, PDO::PARAM_INT);

  if(!$sql->execute()) {
    echo "Error setting new terrain (last row)!\n";
    exit(1);
  }
}

echo "Done!";

return 1;

?>