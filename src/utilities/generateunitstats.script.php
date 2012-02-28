<?php
/*
 * generateunitstats.script.php -
 * Copyright (c) 2003  OGP Team
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

if ($_SERVER['argc'] != 2) {
  echo "Usage: ".$_SERVER['argv'][0]." StatsType\n";
  exit (1);
}


switch($_SERVER['argv'][1]) {
    case 'week':
      define('STATS_TYPE', STATS_WEEK);
      define('STATS_CYCLE', STATS_WEEK_COUNT);
    break;

    case 'day':
      define('STATS_TYPE', STATS_DAY);
      define('STATS_CYCLE', STATS_DAY_COUNT);
    break;

    case 'hour':
    default:
      define('STATS_TYPE', STATS_HOUR);
      define('STATS_CYCLE', STATS_HOUR_COUNT);
    break;
}

echo "RUNNING UNIT STATS...\n";

if (!($db = DbConnect())) {
  echo "GAME UNIT STATS: Failed to connect to game db.\n";
  exit(1);
}

/*
 * get db fields
 */
foreach ($GLOBALS['unitTypeList'] AS $value) {
  
  $UnitFieldsName[$value->dbFieldName] = $value->name;
}

echo "GAME UNIT STATS: Start.\n";

/*
 * get secret player and cace
 */
$sql = $db->prepare("SELECT Cave.caveID ".
         "FROM ". CAVE_TABLE ." ".
           "LEFT JOIN ". PLAYER_TABLE ." ".
             "ON Cave.playerID = Player.playerID ".
         "WHERE Player.noStatistic = 1 OR Cave.noStatistic = 1");

$SecretCave = array();
if ($sql->execute()) {
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $SecretCave[$row['caveID']] = TRUE;
    }
}

$fields = array();
foreach (array_keys($UnitFieldsName) as $name) {
  $fields[] = 'SUM(' . $name . ') as ' . $name;
}

$where = '';
if ($SecretCave) {
  $where = "WHERE caveID NOT IN (" . implode(', ', array_keys($SecretCave)) . ")";
}

/*
 * count units
 */
echo "GAME UNIT DAY STATS: Count Cave Units.\n";
$sql = $db->prepare("SELECT " . implode(', ', $fields) .  " ".
                     "FROM ". CAVE_TABLE ." ".
                     "{$where}");

$CaveUnit = array();
if ($sql->execute()) {
  if ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $CaveUnits = $row;
  }
  else {
    echo "GAME UNIT STATS: FAILURE: Unit Cave.\n";
  }
} else {
    echo "GAME UNIT STATS: FAILURE: Unit Cave.\n";
}
$sql->closeCursor();

echo "GAME UNIT STATS: Count Movement Units.\n";
$sql = $db->prepare("SELECT " . implode(', ', $fields) .  " ".
         "FROM ". EVENT_MOVEMENT_TABLE ." ".
         "{$where}");

$MovementUnits = array();
if ($sql->execute()) {
  if ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $MovementUnits = $row;
  }
  else {
    echo "GAME UNIT STATS: FAILURE: Unit Movement.\n";
  }
} else {
    echo "GAME UNIT STATS: FAILURE: Unit Movement.\n";
}
$sql->closeCursor();

foreach (array_keys($UnitFieldsName) as $name) {
  $statsData[$name] = 0;
  if (isset($CaveUnits[$name]) && $CaveUnits[$name] != 0) {
    $statsData[$name] += $CaveUnits[$name];
  }
  if (isset($MovementUnits[$name]) && $MovementUnits[$name] != 0) {
    $statsData[$name] += $MovementUnits[$name];
  }
}
echo "GAME UNIT STATS: Update Database.\n";

$sqlUpdate = $db->prepare("UPDATE ". STATISTIC_UNIT_TABLE ." ".
       "SET type_sub = type_sub +1 ".
       "WHERE type = " . STATS_TYPE);
$sqlUpdate->execute();

$sqlDelete = $db->prepare("DELETE FROM ". STATISTIC_UNIT_TABLE ." ".
       "WHERE type = " . STATS_TYPE . " ".
         "AND type_sub > " . STATS_CYCLE);
$sqlDelete->execute();

$sqlInsert = $db->prepare("INSERT INTO ". STATISTIC_UNIT_TABLE ." ".
         "(type, type_sub, time, ".implode(', ', array_keys($statsData)).") ".
         "VALUES (" . STATS_TYPE . ", '1', '" . date("YmdHis") . "', " . implode(', ', $statsData) . ")");
$sqlInsert->execute();

echo "GAME UNIT DAY STATS: End.\n";

?>