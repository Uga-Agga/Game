<?php
/*
 * createDailySpreadSheet.php -
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

$sql = $db->prepare("SELECT c.caveID, c.xCoord,
                       c.yCoord, CONCAT(\"'\", c.name, \"'\"),
                       c.terrain, CONCAT(\"'\", p.name, \"'\"),
                       CONCAT(\"'\", p.tribe, \"'\"),
                       r.rank
                     FROM " . CAVE_TABLE . " c
                       LEFT JOIN " . PLAYER_TABLE . " p ON p.playerID = c.playerID
                       LEFT JOIN " . RANKING_TABLE . " r ON r.playerID = p.playerID");
if (!$sql->execute()) {
  die("Fehler beim Auslesen.");
}

while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  echo implode($row, "\t") . "\n";
}

?>