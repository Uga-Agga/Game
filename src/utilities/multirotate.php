<?php 
/*
 * multirotate.php -
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
include INC_DIR."game_rules.php";
include INC_DIR."time.inc.php";
include INC_DIR."basic.lib.php";



echo "---------------------------------------------------------------------\n";
echo "- MULTI ROTATE  LOG FILE --------------------------------------------\n";
echo "  vom " . date("r") . "\n";



if (!($db_login = db_connectToLoginDB())) {
  echo "Rotate Multi : Failed to connect to login db.\n";
  exit(1);
}
if (!($db_game = DbConnect())) {
  echo "Rotate Multi : Failed to connect to game db.\n";
  exit(1);
}

//alte multies als gel�scht markieren
$sql = $db_login->prepare("UPDATE Login 
                           SET deleted = 1 
                           WHERE multi = 66 
                           AND lastChange < NOW() - INTERVAL 14 DAY");
if (!$sql->execute()) {  
  echo "Rotate Multi : Failed to mark old multis deleted.\n";
  exit(1);
}

//multi mit stati 65 in den stati 66 packen und in den stamm multi packen

$sql = $db_login->prepare("SELECT LoginID, user 
                           FROM Login 
                           WHERE multi = 65 
                           AND deleted = 0");

if(!$sql->execute()) {
  echo "Rotate Multi : Failed to get multis to rotate.\n";
  exit(1);
}

$result = $sql->fetchAll();
if (is_array($result)) {
  foreach($result AS $row) {
    echo "Verschiebe Spieler mit der ID ".$row["LoginID"].": ".$row["user"]."\n";
  
    $sql = $db_game->prepare("SELECT tribe 
                              FROM ". PLAYER_TABLE ." 
                              WHERE name = :user");
    $sql->bindValue('user', $row['user'], PDO::PARAM_STR);
    
    if(!$sql->execute()) {
      echo "Rotate Multi: Failed to get old tribe from Player\n";
      exit(1);
    }
    //Nachricht f�r den alten Stamm erzeugen
    if($row2 = $sql->fetch()) {
      if($row2['tribe'] != "") {
        $time = getUgaAggaTime(time());
        $month = getMonthName($time['month']);
        $sql2 = $db_game->prepare("INSERT INTO ". TRIBE_HISTORY_TABLE ." 
                                   (tribe, timestamp, ingameTime, message) 
                                   VALUES 
                                   (:tribe, NOW(), :timestamp, :message)");
        $sql2->bindValue('tribe', $row2['tribe'], PDO::PARAM_STR);
        $sql2->bindValue('ingameTime', $time['day']. "$month<br>im Jahr ".$time['year'], PDO::PARAM_STR);
        $sql2->bindValue('message', "Spieler ".$row['user']." wurde in den Stamm Multi �berf�hrt", PDO::PARAM_STR);
        
        if(!$sql->execute()) {
          echo "Rotate Multi: Failed to update old tribehistory\n";
          exit(1);
        }
        $sql->closeCursor();
      }
    }
    $sql->closeCursor();
    
    //Player in Multistamm packen
    $sql = $db_game->prepare("UPDATE ". PLAYER_TABLE ." 
                              SET tribe = 'multi' 
                              WHERE name = :user");
    $sql->bindValue('user', $row['user'], PDO::PARAM_STR);
    
    if(!$sql->execute()) {
      echo "Rotate Multi: Failed to update Player\n";
      exit(1);
    }
    //Ue-Hoehlen loeschen
    $sql = $db_game->prepare("UPDATE ". CAVE_TABLE ." 
                         SET playerID = 0 
                         WHERE secureCave = 0 
                         AND playerID = :loginID"); 
    $sql->bindValue('loginID', $row['LoginID'], PDO::PARAM_INT);
    
    if(!$sql->execute()) {
      echo "Rotate Multi: Failed to delete non secure caves\n";
      exit(1);
    }
    //Noobschutz weg
    $sql = $db_game->prepare("UPDATE ". CAVE_TABLE ." 
                              SET protection_end = 20070128222056 
                              WHERE playerID = :loginID");
    $sql->bindValue('loginID', $row['LoginID'], PDO::PARAM_INT);
    
    if(!$sql->execute()) {
      echo "Rotate Multi: Failed to update protection_end\n";
      exit(1);
    }  
    //Login auf 66 stellen
    $sql = $db_login->prepare("UPDATE Login 
                               SET multi = 66 
                               WHERE user = :user");
    $sql->bindValue('user', $row['user'], PDO::PARAM_STR);
    
    if(!$sql->execute()) {
      echo "Rotate Multi: Failed to update Login\n";
      exit(1);
    }
  }
}

?>