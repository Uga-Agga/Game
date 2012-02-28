<?php
/*
 * createCave.php -
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

if ($_SERVER['argc'] != 5) {
  echo "Usage: php createCave.php xMin xMax yMin yMax\n";
  exit(1);
}

$db     = DbConnect();

$sql = $db->prepare("SELECT IF(ISNULL(max(caveID)), 0, max(caveID)) as maxCaveID FROM " . CAVE_TABLE . "");
if (!$sql->execute()) {
  die("Fehler bei der Abfrage.\n");
  return -1;
}
$row = $sql->fetch(PDO::FETCH_ASSOC);
$sql->closeCursor();
$caveID = $row['maxCaveID'] + 1;

echo "Creating caves starting with caveID " . $caveID . "\n";

$sqlUpdate = $db->prepare("INSERT INTO " . CAVE_TABLE . "
                             (caveID, xCoord, yCoord, name)
                           VALUES
                             (:caveID, :xCoord, :yCoord, :name)");

for($i = $_SERVER['argv'][1]; $i < $_SERVER['argv'][2]; $i++) {
  echo ".";
  for($j = $_SERVER['argv'][3]; $j < $_SERVER['argv'][4]; $j++) {
    $sqlUpdate->bindValue('caveID', $caveID, PDO::PARAM_INT);
    $sqlUpdate->bindValue('xCoord', $i, PDO::PARAM_INT);
    $sqlUpdate->bindValue('yCoord', $j, PDO::PARAM_INT);
    $sqlUpdate->bindValue('name', $i."x".$j, PDO::PARAM_STR);

    if (!$sqlUpdate->execute()) {
      die("Fehler beim anlegen der Hoehle mit der id {$caveID}\n");
    }
    $caveID++;
  }
}

echo "\nCreated " . ($caveID - $row['maxCaveID'] - 1) . " caves.\n";

?>