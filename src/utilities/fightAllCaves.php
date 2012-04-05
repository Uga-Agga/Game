<?php 
/* 
* fightAllCaves.php - 
* Copyright (c) 2012  David Unger 
* 
* This program is free software; you can redistribute it and/or 
* modify it under the terms of the GNU General Public License as 
* published by the Free Software Foundation; either version 2 of 
* the License, or (at your option) any later version. 
*/

include "util.inc.php";
include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

global $config;

DEFINE("STACK_BATTLE_FAKTOR", 0.25);
DEFINE("LAST_CAVE_ID", 2500);
DEFINE("START_CAVE_ID", 1910);

DEFINE("BATTLE_UNIT", "quest_dummi_1");

$config = new Config();
$db    = DbConnect();

$sql = $db->prepare("INSERT INTO " . EVENT_MOVEMENT_TABLE . "
                      (caveID, source_caveID, target_caveID, movementID, `start`, `end`, speedFactor, " . BATTLE_UNIT . ")
                    VALUES
                      (:caveID, :source_caveID, :target_caveID, 3, NOW(), NOW(), 1, 1)");
for ($i=1; $i<=LAST_CAVE_ID; $i++) {
  if (START_CAVE_ID != $i) {
    $sql->bindParam('caveID', START_CAVE_ID, PDO::PARAM_INT);
    $sql->bindParam('source_caveID', START_CAVE_ID, PDO::PARAM_INT);
    $sql->bindParam('target_caveID', $i, PDO::PARAM_INT);

    if(!$sql->execute()) {
      echo "Error attack cave!n";
      exit(1);
    }
  }
}

$sql = $db->prepare("UPDATE ". CAVE_TABLE ."
                    SET range_damage_factor = range_damage_factor + :range_damage_factor,
                      struct_damage_factor = struct_damage_factor + :struct_damage_factor,
                      melee_damage_factor = melee_damage_factor + :melee_damage_factor
                    WHERE caveID = :caveID");
$sql->bindValue('range_damage_factor', STACK_BATTLE_FAKTOR, PDO::PARAM_INT);
$sql->bindValue('struct_damage_factor', STACK_BATTLE_FAKTOR, PDO::PARAM_INT);
$sql->bindValue('melee_damage_factor', STACK_BATTLE_FAKTOR, PDO::PARAM_INT);
$sql->bindValue('caveID', START_CAVE_ID, PDO::PARAM_INT);
if (!$sql->execute()) {
  echo "Error update cave!n";
  exit;
} 

?>