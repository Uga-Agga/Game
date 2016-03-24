<?php
/*
 * takeover_set_caves.php -
 * Copyright (c) 2013  David Unger <unger.dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

// include necessary files
include "util.inc.php";
include INC_DIR . "config.inc.php";
include INC_DIR . "db.inc.php";
include INC_DIR . "rules/game.rules.php";
include INC_DIR . "basic.lib.php";

$db     = DbConnect();

// config!
$maxCaves = 1200;
$cavesData = array(
  array('count' => 400, 'lvl' =>  1, 'values' => array('boxer' =>    50, 'gatherer' =>  2, 'sleeping_place' =>  0, 'storage_cave' => 0, 'hunter' =>  0, 'woodcutter' =>  0, 'quarryman' =>  0, 'smelter' => 0, 'sulfur_miner' => 0, 'barracks' =>  0)),
  array('count' => 250, 'lvl' =>  2, 'values' => array('boxer' =>   100, 'gatherer' =>  5, 'sleeping_place' =>  0, 'storage_cave' => 0, 'hunter' =>  0, 'woodcutter' =>  0, 'quarryman' =>  0, 'smelter' => 0, 'sulfur_miner' => 0, 'barracks' =>  0)),
  array('count' => 200, 'lvl' =>  3, 'values' => array('boxer' =>   250, 'gatherer' => 10, 'sleeping_place' =>  1, 'storage_cave' => 0, 'hunter' =>  0, 'woodcutter' =>  0, 'quarryman' =>  0, 'smelter' => 0, 'sulfur_miner' => 0, 'barracks' =>  0)),
  array('count' => 160, 'lvl' =>  4, 'values' => array('boxer' =>   500, 'gatherer' => 12, 'sleeping_place' =>  3, 'storage_cave' => 0, 'hunter' =>  0, 'woodcutter' =>  0, 'quarryman' =>  0, 'smelter' => 0, 'sulfur_miner' => 0, 'barracks' =>  0)),
  array('count' => 120, 'lvl' =>  5, 'values' => array('boxer' =>  1000, 'gatherer' => 15, 'sleeping_place' =>  3, 'storage_cave' => 1, 'hunter' =>  2, 'woodcutter' =>  2, 'quarryman' =>  2, 'smelter' => 0, 'sulfur_miner' => 0, 'barracks' =>  1)),
  array('count' =>  90, 'lvl' =>  6, 'values' => array('boxer' =>  2500, 'gatherer' => 15, 'sleeping_place' =>  5, 'storage_cave' => 2, 'hunter' =>  5, 'woodcutter' =>  5, 'quarryman' =>  5, 'smelter' => 0, 'sulfur_miner' => 0, 'barracks' =>  2)),
  array('count' =>  45, 'lvl' =>  7, 'values' => array('boxer' =>  5000, 'gatherer' => 15, 'sleeping_place' =>  5, 'storage_cave' => 4, 'hunter' => 10, 'woodcutter' => 10, 'quarryman' => 10, 'smelter' => 0, 'sulfur_miner' => 0, 'barracks' =>  6)),
  array('count' =>  30, 'lvl' =>  8, 'values' => array('boxer' => 10000, 'gatherer' => 15, 'sleeping_place' =>  7, 'storage_cave' => 6, 'hunter' => 10, 'woodcutter' => 10, 'quarryman' => 10, 'smelter' => 2, 'sulfur_miner' => 0, 'barracks' =>  8)),
  array('count' =>  15, 'lvl' =>  9, 'values' => array('boxer' => 15000, 'gatherer' => 15, 'sleeping_place' => 10, 'storage_cave' => 7, 'hunter' => 12, 'woodcutter' => 12, 'quarryman' => 12, 'smelter' => 2, 'sulfur_miner' => 2, 'barracks' => 10)),
  array('count' =>  10, 'lvl' => 10, 'values' => array('boxer' => 30000, 'gatherer' => 20, 'sleeping_place' => 10, 'storage_cave' => 7, 'hunter' => 15, 'woodcutter' => 15, 'quarryman' => 15, 'smelter' => 5, 'sulfur_miner' => 5, 'barracks' => 12)),
);

foreach ($cavesData AS $caveData) {
  $values = array();
  foreach ($caveData['values'] as $value => $data) {
    $fields[] = $value . ' = '  . $data;
  }

  $sql = $db->prepare("UPDATE " . CAVE_TABLE . " SET
                      " . implode(", ", $fields) . ", takeover_level = :takeover_level
                      WHERE playerID = 0
                        AND takeoverable = 0
                        AND takeover_level = 0
                        AND starting_position = 0
                        AND terrain BETWEEN 0 AND 4
                      ORDER BY RAND()
                      LIMIT :limit");
  $sql->bindValue('takeover_level', $caveData['lvl'], PDO::PARAM_INT);
  $sql->bindValue('limit', $caveData['count'], PDO::PARAM_INT);
  if (!$sql->execute());
}

?>