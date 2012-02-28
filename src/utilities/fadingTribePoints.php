<?php 
/*
 * deletePlayer.script.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
die(); // script inaktive
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

if (!($db_game = DbConnect())) {
  echo "FADING TRIBE POINTS: Failed to connect to game db.\n";
  exit(1);
}
echo "START FADING TRIBE POINTS";
$db_game->query("UPDATE `RankingTribe` SET " . 
  "`points_rank` = round((cast( `points_rank` AS SIGNED ) + ( 1500 - CAST( `points_rank` AS SIGNED ) ) * 0.025 ))");
echo "END FADING TRIBE POINTS";
?>
