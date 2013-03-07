<?php
/*
 * tribes.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This script updates the tribe table by removing non existent clans and
 * adding missing clans.
 */


include("util.inc.php");

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."rules/game.rules.php";
include INC_DIR."tribes.inc.php";
include INC_DIR."chat.inc.php";
include INC_DIR."auth.inc.php";
include INC_DIR."message.inc.php";
include INC_DIR."db.functions.php";
include INC_DIR."basic.lib.php";
include INC_DIR."rules/government.rules.php";
include INC_DIR."time.inc.php";
include INC_DIR."rules/relation.list.php";
include INC_DIR."Player.php";
#include INC_DIR."languages/de_DE.php";

$db     = DbConnect();

define('ID_WARALLY_RELATION',8);
define('ID_AFTER_WARALLY_RELATION',7);

$untouchableTribes = array();
$untouchableTribes[] = Tribe::getID(GOD_ALLY);
$untouchableTribes[] = Tribe::getID(QUEST_ALLY);

echo "---------------------------------------------------------------------\n";
echo "- TRIBES LOG FILE ---------------------------------------------------\n";
echo "  vom " . date("r") . "\n";

// Script works in four steps:
// ----------------------------------------------------------------------------
// 1. delete non existing clans
// 2. add missing clans (> MINIMUM_SIZE members with same tag)
// 3. recalc leaders
// 4. check for relations
// ----------------------------------------------------------------------------


echo "-- Checking Tribes --\n";
// ----------------------------------------------------------------------------
// Step 1: Start checking tribes for reaching minimum members requirement
{

  $tribes = Tribe::getAllTribes();
  global $db;

  if ($tribes < 0) {
    echo "Error retrieving all tribes.\n";
    return -1;
  }

  $deleted_tribes = array();
  $validated_tribes = array();
  $invalidated_tribes = array();

  foreach($tribes as $tribeID => $data) {
    if (in_array($tribeID, $untouchableTribes)) {
      continue;
    }

    if (($member_count = Tribe::getMemberCount($tribeID)) < 0)
    {
      echo "Error counting members of tribe {$data['tag']}.\n";
      return -1;
    }

    //Gültige Stämme prüfen auf Membermangel
    if ($data['valid'] && $member_count < TRIBE_MINIMUM_SIZE)
    {
      if (Tribe::setInvalid($tribeID)) {
        array_push($invalidated_tribes, $tribeID);
      }
      else {
        echo "Error: Couldn't set invalid for tribe {$data['tag']}!\n";
      }
    }

    //Ungültige Stämme prüfen auf Membermangel
    if ((!$data['valid']) && $member_count >= TRIBE_MINIMUM_SIZE)
    {
      $data['valid'] = true; // damit der Stamm nicht gelöscht wird
      if (Tribe::setValid($tribeID)) {
        array_push($validated_tribes, $tribeID);
      }
      else {
        echo "Error: Couldn't set valid for tribe {$data['tag']}!\n";
      }
    }

    //Ungültige Stämme prüfen auf Löschbarkeit
    if (((!$data['valid']) && $data['ValidationTimeOver']) || ($member_count==0))
    {
      if (!TribeRelation::deleteRelations($tribeID)) {
        echo "Error: Couldn't delete relations for tribe {$data['tag']}!\n";
      }

      if (Tribe::deleteTribe($data, 1)) { // remove '1' to activate del
        array_push($deleted_tribes, $data['tag'].": ".$data['name']);
      }
      else {
        echo "Error: Couldn't delete tribe {$data['tag']}!\n";
      }
    }
  }


  echo "The following tribes have been set invalid:\n";
  for ($i = 0; $i < sizeof($invalidated_tribes); ++$i)
  {
    echo $tribes[$invalidated_tribes[$i]]['tag'] . "  \n";
  }

  echo "The following tribes have been set valid:\n";
  for ($i = 0; $i < sizeof($validated_tribes); ++$i)
  {
    echo $tribes[$validated_tribes[$i]]['tag'] . "  \n";
  }

  echo "The following tribes have been deleted:\n";
  for ($i = 0; $i < sizeof($deleted_tribes); ++$i)
  {
    echo $deleted_tribes[$i] . "  \n";
  }
}

// ----------------------------------------------------------------------------
// Step 2: Recalculate the leaders
echo "-- Checking Tribe Leaders --\n";
{
  $tribes = Tribe::getAllTribes();
  if ($tribes < 0){
    echo "Error retrieving all tribes.\n";
    return -1;
  }

  foreach($tribes AS $tribeID => $data) {
    if (($r = TribeLeader::recalcLeader($tribeID, $data['leaderID'])) < 0) {
      echo "Error recalcing leader for Tribe {$data['tag']}\n";
      return -1;
    }
    if ($r > 0) {
      echo "Tribe {$data['tag']} has a new leader: $r\n";
    }
  }
}

// ----------------------------------------------------------------------------
// Step 3 Check Relations
echo "-- Check Relations --\n";
{
  $sql = $db->prepare("SELECT *
                       FROM ". TRIBE_ELECTION_TABLE ."
                       WHERE relationType = :iwr");
  $sql->bindValue('iwr', ID_WARALLY_RELATION, PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "Error checking for war-allies tribes.\n";
    return -2;
  }

  while ($row = $sql->fetch()) {
    if (!TribeRelation::hasSameEnemy($row['tribeID'], $row['tribeID_target'], true, true)) {
      echo "Tear down war-ally : ".$row['tribeID']." => ".$row['tribeID_target']." " ;
      $update = $db->prepare("UPDATE ". RELATION_TABLE ."
                              SET relationType = :iafwr
                              WHERE tribeID = :tribeID
                                AND tribeID_target = :tribeID_target");
      $update->bindValue('iafwr', ID_AFTER_WARALLY_RELATION, PDO::PARAM_INT);
      $update->bindValue('tribeID', $row['tribe'], PDO::PARAM_INT);
      $update->bindValue('tribeID_target', $row['tribeID_target'], PDO::PARAM_INT);

      if ($update->execute())
        echo "Success\n";
      else
        echo "FAILED\n";
    }
  }
}

echo "Tribes end ". date("r") ."   -----------------------\n\n";

?>