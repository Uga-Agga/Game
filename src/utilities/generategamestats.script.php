<?php
/*
 * generategamestats.script.php -
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

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

echo "RUNNING GAME STATS...\n";

if (!($db = DbConnect())) {
  echo "GAME STATS: Failed to connect to game db.\n";
  exit(1);
}

/*
 * get db fields
 */
foreach ($GLOBALS['scienceTypeList'] AS $value)
{
  $ScienceFieldsName[$value->dbFieldName] = $value->name;
}
foreach ($GLOBALS['unitTypeList'] AS $value)
{
  if (!$value->nodocumentation) {
    $UnitFieldsName[$value->dbFieldName] = $value->name;
  }
}

/*
 * get secret player and cace
 */
$sql = $db->prepare("SELECT caveID 
                     FROM ". CAVE_TABLE ."
                       LEFT JOIN ". PLAYER_TABLE . " 
                         ON Cave.playerID = Player.playerID
                     WHERE Player.noStatistic = 1 OR Cave.noStatistic = 1");
if ($sql->execute()) {
    while ($row = $sql->fetch()) {
      $SecretCave[$row['caveID']] = TRUE;
    }
}
$sql->closeCursor();

/*
 * get god stats
 */
echo "GAME STATS: Generate God Stats.\n";
$statsData = array();
foreach (Config::$gods as $God) {
  $statsData[GOD_STATS][$God] =  countPlayerGod($God);
}
unset($God);

/*
 * get halfgod stats
 */
echo "GAME STATS: Generate Halfgod Stats.\n";
foreach (Config::$halfGods as $Halfgod) {
  $statsData[HALFGOD_STATS][$Halfgod] = countPlayerGod($Halfgod);
}
unset($Halfgod);

/*
 * get unit stats
 */
echo "GAME STATS: Unit Stats.\n";
$fields = array();
foreach (array_keys($UnitFieldsName) as $name) {
  $fields[] = 'SUM(' . $name . ') as ' . $name;
}

$where = '';
if ($SecretCave) {
  $where = "WHERE caveID NOT IN (" . implode(', ', array_keys($SecretCave)) . ")";
}

$sql = $db->prepare("SELECT " . implode(', ', $fields) .  " ".
         "FROM ". CAVE_TABLE ." ".
         "{$where}");
$CaveUnit = array();
if ($sql->execute()) {
  if ($row = $sql->fetch()) {
    $CaveUnits = $row;
  }
  else {
    echo "GAME STATS: FAILURE: Unit Cave.\n";
  }
}
else {
  echo "GAME STATS: FAILURE: Unit Cave.\n";
}
$sql->closeCursor();

$sql = $db->prepare("SELECT " . implode(', ', $fields) .  " ".
         "FROM ". EVENT_MOVEMENT_TABLE ." ".
         "{$where}");

$MovementUnits = array();
if ($sql->execute()) {
  if ($row = $sql->fetch()) {
    $MovementUnits = $row;
  }
  else {
    echo "GAME STATS: FAILURE: Unit Movement.\n";
  }
}
else {
  echo "GAME STATS: FAILURE: Unit Movement.\n";
}

foreach (array_keys($UnitFieldsName) as $name) {
   $statsData[UNIT_STATS][$name] = $CaveUnits[$name] + $MovementUnits[$name];
}

/*
 * get storage stats
 */
echo "GAME STATS: Storage Stats.\n";
$where = '';
if ($SecretCave) {
  $where = "AND caveID NOT IN (" . implode(', ', array_keys($SecretCave)) . ")";
}

$sql = $db->prepare("SELECT count(*) AS Count, storage_cave ".
         "FROM ". CAVE_TABLE ." ".
         "WHERE playerID != 0 {$where} ".
         "GROUP BY storage_cave");
$Storage = array();
if ($sql->execute()) {
  while ($row = $sql->fetch()) {
    $statsData[STORAGE_STATS][$row['storage_cave']] =  $row['Count'];
  }
}
else {
  echo "GAME STATS: FAILURE: Storage.\n";
}

/*
 * make it public
 */
echo "GAME STATS: parse into Database.\n";
$DataDB = array();
foreach ($statsData as $type => $data) {
  $sql = $db->prepare("SELECT * ".
                       "FROM ". STATISTIC_TABLE ." ".
                       "WHERE type = {$type}");
  if ($sql->execute()) {
      while ($row = $sql->fetch()) {
        $DataDB[$row['name']] = unserialize($row['value']);
      }
  }
  $sql = $db->prepare("DELETE ".
                       "FROM ". STATISTIC_TABLE ." ".
                       "WHERE type = {$type}");
  $sql->execute();

  foreach ($data as $name => $count)
  {
    if (sizeof($DataDB[$name])) {
      if (sizeof($DataDB[$name]) > 23) {
          $DataDB[$name] = array_slice($DataDB[$name], 1);
      }
      $value = $DataDB[$name];
      array_push($value, $count);
    }
    else {
      $value = array($count);
    }
    $value = serialize($value);

    $sql = $db->prepare("INSERT INTO ". STATISTIC_TABLE ." ".
                       "(type, name, value) ".
                       "VALUES ({$type}, '{$name}', '{$value}')");
    $sql->execute();
  }
}

echo "GAME STATS: Wonder Stats.\n";
$sql->exec("REPLACE INTO ". STATISTIC_TABLE ." (type, name, value)
            SELECT " . WONDER_STATS . ", sc.name, sc.value FROM ". STATISTIC_TABLE ." AS sc WHERE sc.type = " . WONDER_STATS_CACHE);

echo "GAME STATS: Finish.\n";

function countPlayerGod($God) {
  global $db;

  if (empty($God))
  {
    return array();
  }

  $sqlHiddenUser = "";
  if (sizeof(Config::$hiddenUser)) {
    $sqlHiddenUser = "AND name NOT IN ('" . implode("', '", Config::$hiddenUser) . "')";
  }

  $sql = $db->prepare("SELECT COUNT(*) as n ".
           "FROM ". PLAYER_TABLE ." ".
           "WHERE {$God} > 0 {$sqlHiddenUser}");
  
  if ($sql->rowCountSelect() == 0) {
    return array();
  }
  
  if (!$sql->execute()) {
    return array();
  }

  $result = array();
  while (!($result = $sql->fetch())) {
    return array();
  }

  return $result['n'];
}

?>