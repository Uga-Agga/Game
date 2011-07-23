<?php 
/*
 * tribes.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

global $config, $unitTypeList;

include("util.inc.php");
include (INC_DIR."game_rules.php");
include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

$today = date("d.m.Y");

echo "RUNNING UNIT STATS ON $today...\n";

$config = new Config();

if (!($db = DbConnect())) {
  echo "VICTORY CONDITIONS: Failed to connect to game db.\n";
  exit(1);
}

//////////////// Single Player Ranking ////////////////
// 1. Spieler mehr als doppelt so viele Punkte wie der 2. ?

$sql = $db->prepare("SELECT playerID, name, average 
                    FROM ". RANKING_TABLE ."
                    ORDER BY average DESC 
                    LIMIT 0,2");

if (!$sql->execute() || !($first = $sql->fetch()) || !($second = $sql->fetch()))
{
  echo "$today: TESTING SINGLE PLAYER DOMINATION FAILED\n";
}
else if ($first['average'] > $second['average'] * 2)
{
  echo "$today PLAYER DOMINATION: {$first['name']}({$first['playerID']}) {$first['average']}/{$second['average']}\n";
}
$sql->closeCursor();

//////////////// Tribe Ranking Domination ////////////////
// 1. Stamm mehr Punkte als Stamm 2.-10. zusammen?

$sql = $db->prepare("SELECT tribe, points_rank 
                     FROM ". RANKING_TRIBE_TABLE ."
                     ORDER BY points_rank DESC 
                     LIMIT 0,10");

if (!$sql->execute() || !($first = $sql->fetch()))
{
  echo "$today: TESTING TRIBE DOMINATION FAILED\n";
}
else {
  $sum = 0;
  while($row = $sql->fetch()) {
    $sum += $row['points_rank'];
  }

  if ($first['points_rank'] > $sum)
  {
    echo "$today TRIBE DOMINATION: {$first['tribe']} {$first['points_rank']}/$sum\n";
  }
}

?>