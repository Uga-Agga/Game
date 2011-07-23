<?php
/*
 * tribes.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 * 
 * This script updates the tribe table by removing non existent clans and
 * adding missing clans.
 */

global $config;

include("util.inc.php");

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."game_rules.php";
include INC_DIR."tribes.inc.php";
include INC_DIR."message.inc.php";
include INC_DIR."db.functions.php";
include INC_DIR."basic.lib.php";
include INC_DIR."government.rules.php";
include INC_DIR."time.inc.php";
include INC_DIR."relation_list.php";
include INC_DIR."Player.php";
#include INC_DIR."languages/de_DE.php";

$config = new Config();
$db     = DbConnect();

define('ID_WARALLY_RELATION',8);
define('ID_AFTER_WARALLY_RELATION',7);

$untouchableTribes = array();

$untouchableTribes[] = GOD_ALLY;
$untouchableTribes[] = "Multi";
$untouchableTribes[] = QUEST_ALLY;


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
  
  $tribes = tribe_getAllTribes();
  global $db;
  
  if ($tribes < 0) {
    echo "Error retrieving all tribes.\n";
    return -1;
  }

  $deleted_tribes = array();
  $validated_tribes = array();
  $invalidated_tribes = array();
  
  foreach($tribes AS $tag => $data) {
    if (in_array($tag,$untouchableTribes)) {
	continue;
    }
    
    if (($member_count = tribe_getNumberOfMembers($tag, $db)) < 0) 
    {
      echo "Error counting members of tribe {$row['tag']}.\n";
      return -1;
    } 
    
    //Gültige Stämme prüfen auf Membermangel
    if ($data['valid'] && $member_count < TRIBE_MINIMUM_SIZE) 
    {
      if (tribe_SetTribeInvalid($tag, $db)) {
        array_push($invalidated_tribes,$tag);
      } 
      else {
        echo "Error: Couldn´t set invalid for tribe $tag!\n";
      }
    } 
    //Ungültige Stämme prüfen auf Membermangel
    if ((! $data['valid']) && $member_count >= TRIBE_MINIMUM_SIZE) 
    {
      $data['valid'] = TRUE; // damit der Stamm nicht gelöscht wird
      if (tribe_SetTribeValid($tag, $db)) {
        array_push($validated_tribes,$tag);
      } 
      else {
        echo "Error: Couldn´t set valid for tribe $tag!\n";
      }
    }
    
    
    //Ungültige Stämme prüfen auf Löschbarkeit
    if (((! $data['valid']) && $data['ValidationTimeOver']) || ($member_count==0))  
    {
      if (!relation_DeleteRelations($tag,$db)) {
        echo "Error: Couldn't delete relations for tribe $tag!\n";
      }
      
      if ( tribe_deleteTribe($tag, $db)) { // remove '1' to activate del
        array_push($deleted_tribes, $tag.": ".$data['name']);
      }
      else {
        echo "Error: Couldn't delete tribe $tag!\n";
      }
    }
  }
  
  
  echo "The following tribes have been set invalid:\n";
  for ($i = 0; $i < sizeof($invalidated_tribes); ++$i)
  {
    echo $invalidated_tribes[$i] . "  \n";
  } 
 
  echo "The following tribes have been set valid:\n";
  for ($i = 0; $i < sizeof($validated_tribes); ++$i)
  {
    echo $validated_tribes[$i] . "  \n";
  } 
 
  echo "The following tribes have been deleted:\n";
  for ($i = 0; $i < sizeof($deleted_tribes); ++$i)
  {
    echo $deleted_tribes[$i] . "  \n";
  }
}

// ----------------------------------------------------------------------------
// Step 2: Find missing tribes (may happen due to inconsitencies)
{
    
  $sql = $db->prepare("SELECT p.tribe 
                      FROM ". PLAYER_TABLE ." p 
                      LEFT JOIN ". TRIBE_TABLE ." t ON p.tribe LIKE t.tag 
                      WHERE p.tribe NOT LIKE '' 
                      AND t.tag IS NULL 
                      GROUP BY p.tribe 
                      HAVING COUNT(p.tribe) >= :tms");
  $sql->bindValue('tms', TRIBE_MINIMUM_SIZE, PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "Error checking for missing tribes.\n";
    return -2;
  }

  $tribes_created = array();

  while ($row = $sql->fetch()) {

    if (!tribe_createTribe($row['tribe'], $row['tribe'], 0))
    {
      echo 
	"There are players with the tag {$row['tribe']}, ".
	"but I couldn't create this new tribe!\n";
      continue;
    }
    array_push($tribes_created, $row['tribe']);
  }

  echo "The following tribes have been created:\n";
  for ($i = 0; $i < sizeof($tribes_created); ++$i)
  {
    echo $tribes_created[$i] . "<br>\n";
  }
}

// ----------------------------------------------------------------------------
// Step 3: Recalculate the leaders
echo "-- Checking Tribe Leaders --\n";
{
  $tribes = tribe_getAllTribes();
  if ($tribes < 0){
    echo "Error retrieving all tribes.\n";
    return -1;
  }

  foreach($tribes AS $tag => $data) {
    if (($r = tribe_recalcLeader($tag, $data['leaderID'], $data['juniorLeaderID'])) < 0)
    {
      echo "Error recalcing leader for Tribe $tag\n";
      return -1;
    }
    if (is_array($r)) 
    {
      echo "Tribe $tag has a new leader: ".$r[0]." with juniorLeader: ".$r[1]."\n"; 
    }
  }
}
  
// ----------------------------------------------------------------------------
// Step 4 Check Relations
echo "-- Check Relations --\n";
{
  $sql = $db->prepare("SELECT * 
                       FROM ". RELATION_TABLE ."
                       WHERE relationType = :iwr");
  $sql->bindValue('iwr', ID_WARALLY_RELATION, PDO::PARAM_INT);
     
  if (!$sql->execute()) {
    echo "Error checking for war-allies tribes.\n";
    return -2;
  }

  while ($row = $sql->fetch()) {
    if (!relation_haveSameEnemy($row['tribe'], $row['tribe_target'], TRUE,TRUE)) {
      echo "Tear down war-ally : ".$row['tribe']." => ".$row['tribe_target']." " ;
      $update = $db->prepare("UPDATE ". RELATION_TABLE ." 
                               SET relationType = :iafwr 
                               WHERE tribe = :tribe 
                               AND tribe_target = :tribe_target");
      $update->bindValue('iafwr', ID_AFTER_WARALLY_RELATION, PDO::PARAM_INT);
      $update->bindValue('tribe', $row['tribe'], PDO::PARAM_STR);
      $update->bindValue('tribe_target', $row['tribe_target'], PDO::PARAM_STR);
      
      if ($update->execute()) 
        echo "Success\n";
      else      
        echo "FAILED\n";
    }
  }  
}

echo "Tribes end ". date("r") ."   -----------------------\n\n";

?>