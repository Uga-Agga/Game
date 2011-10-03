<?php
/*
 * takeover_cave_supply.php -
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
include INC_DIR."game_rules.php";

$config = new Config();
$db     = DbConnect();

$SUPPLYFACTOR = 3; // this factor means: how many people should compete for one cave


// first delete all takeoverable flags from caves taken over
$queryCount = $db->exec("UPDATE " . CAVE_TABLE . " SET takeoverable = 0 WHERE playerID != 0");
if ($queryCount === false) {
  echo "Could not cleanup.\n";
  return -1;
}
echo "Cleanup: " . ( "0" + $queryCount) . " Flags gelöscht.\n";

// get regions
$sql = $db->prepare("SELECT * FROM " . REGIONS_TABLE . " WHERE startRegion = 1");
if (!$sql->execute()) {
  echo "Could not get regions.\n";
  return -1;
}

$regions = array();
while($region = $sql->fetch(PDO::FETCH_ASSOC)) {
  $regions[$region['regionID']] = $region;
}

foreach ($regions as $regionID => $region) {
  // get demand
  $sql = $db->prepare("SELECT COUNT(*) AS num_caves, takeover_max_caves
                       FROM " . CAVE_TABLE . " c
                       LEFT JOIN " . PLAYER_TABLE . " p ON p.playerID = c.playerID
                       WHERE c.playerID != 0
                         AND c.starting_position = 1
                         AND c.regionID = :regionID
                       GROUP BY c.playerID
                       HAVING num_caves < takeover_max_caves");
  $sql->bindValue('regionID', $regionID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    echo "Could not calculate demand.\n";
    return -1;
  }
  $demand = $sql->fetchAll(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  $demand = count($demand);

  // get supply
  $sql = $db->prepare("SELECT *
                       FROM " . CAVE_TABLE . " c
                       WHERE c.takeoverable = 1
                         AND c.playerID = 0
                         AND c.regionID = :regionID");
  $sql->bindValue('regionID', $regionID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    echo "Could not calculate supply.\n";
    return -1;
  }
  $supply = $sql->fetchAll(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  $supply = count($supply);

  echo "Region :                    " . $region['name'] . "\n".
       "Angebot:                    " . $supply . "\n".
       "Nachfrage:                  " . $demand . "\n";
  $demand = (int) ($demand / $SUPPLYFACTOR);
  echo "zu befriedigende Nachfrage: $demand\n";

  if ($supply < $demand) {
    // supply to low, get more caves
    // how many more caves are needed:
    $diff = $demand - $supply;

    echo "Es fehlen noch $diff Höhlen!\n";

    // first get all the caves with the takeoverable = 2 (those are caves given up or freed by the deleteInactives script)
    $sql = $db->prepare("SELECT caveID
                         FROM " . CAVE_TABLE . "
                         WHERE playerID = 0
                           AND starting_position = 0
                           AND takeoverable = 2
                           AND regionID = :regionID
                         ORDER BY RAND() LIMIT :limit");
    $sql->bindValue('regionID', $regionID, PDO::PARAM_INT);
    $sql->bindValue('limit', $diff, PDO::PARAM_INT);
    if (!$sql->execute()) {
      echo "Could not get new supply.\n";
      return -1;
    }
    $rows = $sql->fetchAll(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    $count_new_caves = 0;
    $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                         SET takeoverable = 1
                         WHERE playerID = 0
                           AND caveID = :caveID");
    foreach($rows as $row){
      $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);
  
      if (!$sql->execute()) {
        echo "Could not update.\n";
        return -1;
      } else {
        $count_new_caves++;
      }
    }

    // get new diff
    $diff -= $count_new_caves;

    // hmm, there was not enough given up caves to satisfy the demand,
    // get some of those wastes (takeoverable = 0 and playerID = 0)
    if ($diff > 0){
      $sql = $db->prepare("SELECT caveID
                           FROM " . CAVE_TABLE . "
                           WHERE playerID = 0
                             AND starting_position = 0
                             AND takeoverable = 0
                             AND regionID = :regionID
                           ORDER BY RAND() LIMIT :limit");
      $sql->bindValue('regionID', $regionID, PDO::PARAM_INT);
      $sql->bindValue('limit', $diff, PDO::PARAM_INT);
      if (!$sql->execute()) {
        echo "Could not get new supply.\n";
        return -1;
      }
      $rows = $sql->fetchAll(PDO::FETCH_ASSOC);
      $sql->closeCursor();

      $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                           SET takeoverable = 1
                           WHERE playerID = 0
                             AND caveID = :caveID");
      foreach($rows as $row){
        $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);
        if (!$sql->execute()) {
          echo "Could not update.\n";
          return -1;
        } else {
          $count_new_caves++;
        }    
      }
    }

    echo "Es wurden $count_new_caves weitere Höhlen freigegeben!\n";
  } else {
    echo "Angebotsüberschuss: " . ($supply['count'] - $demand) . "\n";
  }
  
  echo "\n";
}

?>