<?php
/*
 * placeArtefact.php -
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

global $config;

DEFINE("VOID_ID", 4);
DEFINE("MAX_PROB", 0.5);

if ($_SERVER['argc'] != 3) {
  echo "Usage: placeArtefact.php artefactClassID number\n";
  exit(1);
}

$artefactClassID = $_SERVER['argv'][1];
$number          = $_SERVER['argv'][2];

$config = new Config();
$db     = DbConnect();
/* alter Code aber nützlich für weitere Runden
  srand ((double)microtime()*100000);

  $query=
    "SELECT MAX(xCoord) As maxX, MAX(yCoord) AS maxY, ".
    "MIN(xCoord) AS minX, MIN(yCoord) AS minY ".
    "FROM Cave ";

  if (!($result = $db->query($query)) || !($row = $result->nextRow())) {
    echo "Query failed:\n";
    echo $query;
    exit;
  }
  $maxX = $row['maxX'];
  $maxY = $row['maxY'];
  $minX = $row['minX'];
  $minY = $row['minY'];

  echo "Map size: $minX -> $maxX x $minY -> $maxY\n";
*/
for ($i=0; $i < $number; $i++) {
  do {  // look randomly for an existing cave
//      $x = (int)(rand() / (double)getRandMax() * ($maxX-$minX)) + $minX;
//      $y = (int)(rand() / (double)getRandMax() * ($maxY-$minY)) + $minY;

    $sql = $db->prepare("SELECT caveID ".
                        "FROM " . CAVE_TABLE . 
                        //  "WHERE TAKEOVERABLE = -99 "
                        " WHERE regionID in (1,3,7,8) 
                        AND playerID = 0 
                        ORDER BY rand() LIMIT 1");
/*"WHERE xCoord = $x AND yCoord = $y"; alter Code */
  } while (!$sql->execute() || !($row = $sql->fetch()));
  $sql->closeCursor();
  
  $caveID = $row['caveID'];
  
  $sql = $db->prepare("INSERT INTO ".ARTEFACT_TABLE ." (artefactClassID, caveID) 
                       VALUES (:artefactClassID, :caveID)");
  $sql->bindValue('artefactClassID', $artefactClassID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  
  if (!$sql->execute()) {
    echo "Couldn't create Artefact!\n";
    exit (1);
  }
  
  $sql = $db->prepare("UPDATE ". CAVE_TABLE ." 
                      SET artefacts=artefacts+1 
                      WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  
  if (!$sql->execute()) {
    echo "Couldn't place Artefact into cave $caveID!\n";
    echo $query."\n";
    exit (1);
  }
  echo "Placed one artefact into Cave $caveID\n";
}


?>