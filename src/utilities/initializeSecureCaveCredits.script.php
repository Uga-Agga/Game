<?php 
/*
 * initializeSecureCaveCredits.script.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

include ("util.inc.php");

include (INC_DIR."db.inc.php");
include (INC_DIR."config.inc.php");

if (!($db_game = DbConnect())) {
  echo "Failed to connect to game db.\n";
  exit(1);
}

$sql = $db_game->prepare("SELECT COUNT(caveID) as count, playerID 
                         FROM ". CAVE_TABLE ."
                         WHERE playerID != 0 
                         GROUP BY playerID");
  
if ($sql->rowCountSelect() == 0) {
  echo "FAILED: no caves.\n";
  exit;
}

if (!$sql->execute()) {
  echo "Error: $query\n";
  exit;
}

$result = $sql->fetchAll();
if (is_array($result)) {
  foreach ($result AS $row) {
    $sql = $db_game->prepare("UPDATE " . PLAYER_TABLE ." 
                         SET secureCaveCredits = 4 - :count");
    $sql->bindValue('count', $row['count'], Pdo::PARAM_INT);
  
    if (!$sql->execute()) {
     echo "FAILED: to set player.\n";
     exit;
    }
    echo "SET: player {$row['playerID']} to 4-{$row['count']}\n";
  }
}

?>