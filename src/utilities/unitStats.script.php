<?php 
/*
 * unitStats.script.php -
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
include INC_DIR."formula_parser.inc.php";

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

echo "RUNNING UNIT STATS...\n";


if (!($db = DbConnect())) {
  echo "UNIT STATS: Failed to connect to game db.\n";
  exit(1);
}

foreach($GLOBALS['unitTypeList'] AS $unitID => $unit) {
  $sql = $db->prepare("SELECT SUM($unit->dbFieldName) AS sum 
                       FROM ". CAVE_TABLE);

  if (!$sql->execute()) {
    echo "UNIT STATS unitID $unitID: COUNT ";
    echo "FAILURE\n";
    echo $sql."\n";
    exit(1);
  }

  if (!($row = $sql->fetch())) {
    echo "UNIT STATS unitID $unitID: GET COUNT ";
    echo "FAILURE\n";
    exit(1);
  }
  echo "$unitID ".$unit->dbFieldName." {$row['sum']}\n";
}

?>