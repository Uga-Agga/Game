<?php 
/*
 * inconsistency.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

global $config;

include "util.inc.php";

include (INC_DIR."tribes.inc.php");
include (INC_DIR."config.inc.php");
include (INC_DIR."db.inc.php");
include(INC_DIR."basic.lib.php");


$config = new Config();

if (!($db_login = db_connectToLoginDB())) {
  echo "DELETE PLAYER $playerID: Failed to connect to login db.\n";
  exit(1);
}

if (!($db_game = DbConnect())) {
  echo "DELETE PLAYER $playerID: Failed to connect to game db.\n";
  exit(1);
}

$sql = $db_login->prepare("SELECT LoginID, user FROM Login");
if (!$sql->execute()) {
  echo "DELETE PLAYER $playerID: No such Logins\n";
  exit(1);
}

$result = $sql->fetchAll();
if (is_array($result)) {
  foreach ($result AS $row) {
    $sql = $db_game->prepare("SELECT * FROM ". PLAYER_TABLE . "
                              WHERE (playerID = :playerID 
                              AND name NOT LIKE :user) 
                              OR (playerID != :playerID AND name LIKE :user)");
    $sql->bindValue('playerID', $row['LoginID'], PDO::PARAM_INT);
    $sql->bindValue('user', $row['user'], PDO::PARAM_STR);
  
    if ($sql->rowCountSelect() == 0) {
      echo "FAILED: {$row['user']}\n";
    }
    
    $sql = $db_game->prepare("SELECT * FROM " . PLAYER_TABLE . "
                            WHERE playerID = :playerID
                            AND name LIKE :user");
    $sql->bindValue('playerID', $row['LoginID'], PDO::PARAM_INT);
    $sql->bindValue('user', $row['user'], PDO::PARAM_STR);
  
    if ($sql->rowCountSelect() == 0) {
      echo "DELETE: {$row['user']} \n";
      $sql = $db_login->prepare("DELETE FROM Login 
                                 WHERE loginID = :loginID");
      $sql->bindValue('loginID', $row['LoginID'], PDO::PARAM_INT);
      
      if (!$sql->execute()) echo "FAILED!\n";
    }
  
  }
}

?>