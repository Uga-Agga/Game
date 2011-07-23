<?php
/*
 * halfgodstats.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

global $config, $unitTypeList;

include "util.inc.php";

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

$halfgods = array("firak", "slavomir", "kirkalot", "shadow_dragon", "trubatsch", "carmi");

echo "RUNNING HALFGOD STATS...\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."formula_parser.inc.php";


$config = new Config();

if (!($db = DbConnect())) {
  echo "HALFGOD STATS: Failed to connect to game db.\n";
  exit(1);
}

foreach($halfgods AS $id => $god) {
  $sql = $db->prepare("SELECT COUNT(playerID) AS n 
                      FROM " . PLAYER_TABLE . "
                      WHERE {$god} > 0");

  if (!$sql->execute()) {
    echo "HALFGOD STATS halfgod $god: COUNT ";
    echo "FAILURE\n";
    exit(1);
  }
  
  if (!($row = $sql->fetch())) {
    echo "HALFGOD STATS halfgod $god: GET COUNT ";
    echo "FAILURE\n";
    exit(1);
  }
  echo "$god: {$row['n']}\n";
}

?>