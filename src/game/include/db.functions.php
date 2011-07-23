<?php
/*
 * db.functions.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function check_timestamp($value)
{
  global $config;

  if ($value > 0) return;
  if (! $db_login = new DB($config->DB_LOGIN_HOST,
         $config->DB_LOGIN_USER,
         $config->DB_LOGIN_PWD,
         $config->DB_LOGIN_NAME)) {
    Header("Location: ".$config->DBERROR_URL);
    exit;
  }
  $query =
    "UPDATE Login ".
    "SET multi = 8 ".
    "WHERE loginID = '".$_SESSION['player']->playerID."'";
  $db_login->query($query);
}

function beginner_isCaveProtectedByID($caveID) {
  global $db;
  
  $sql = $db->prepare("SELECT (protection_end > NOW()+0) AS protected ".
                      "FROM ". CAVE_TABLE ." ".
                      "WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  
  if (!$sql->execute() || !($row = $sql->fetch())) {
//  echo $query;
    return 0;
  }
  return $row['protected'];
}

function beginner_endProtection($caveID, $playerID) {
  global $db; 
  
  $sql = $db->prepare("UPDATE ". CAVE_TABLE ." ".
                      "SET protection_end = NOW()+0 ".
                      "WHERE caveID = :caveID ".
                      "AND playerID = :playerID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sql->execute())
    return 0;
  else
    return 1;
}

function beginner_isCaveProtectedByCoord($x, $y) {
  global $db;
  
  $sql = $db->prepare("SELECT (protection_end > NOW()+0) AS protected ".
                      "FROM ". CAVE_TABLE ." ".
                      "WHERE xCoord = :x AND yCoord = :y");
  $sql->bindValue('x', $x, PDO::PARAM_INT);
  $sql->bindValue('y', $y, PDO::PARAM_INT);

  if (!$sql->execute() || !($row = $sql->fetch())) {
//  echo $query;
    return 0;
  }
  return $row['protected'];
}

function cave_isCaveSecureByCoord($x, $y) {
  global $db;
  
  $sql = $db->prepare("SELECT (secureCave OR playerID = 0) as secureCave ".
                      "FROM ". CAVE_TABLE ." ".
                      "WHERE xCoord = :x AND yCoord = :y");
  $sql->bindValue('x', $x, PDO::PARAM_INT);
  $sql->bindValue('y', $y, PDO::PARAM_INT);

  if (!$sql->execute() || !($row = $sql->fetch())) {
//  echo $query;
    return 0;
  }
  return $row['secureCave'];
}

function cave_isCaveSecureByID($caveID) {
  global $db;
  
  $sql = $db->prepare("SELECT (secureCave OR playerID = 0) as secureCave ".
                      "FROM ". CAVE_TABLE ." ".
                      "WHERE caveID = :caveID ");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if (!$sql->execute() || !($row = $sql->fetch())) {
//  echo $query;
    return 0;
  }
  return $row['secureCave'];
}

/** unused */
function copyScienceFromPlayerToAllCaves($playerID) {
  global $db, $scienceTypeList;

  $sql = $db->prepare("SELECT * 
                       FROM ". PLAYER_TABLE . "
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  
  if (!$sql->execute())
    return 0;
    
  if ($sql->rowCount() == 0) {
    return 0;
  }

  $row = $sql->fetch(PDO::FETCH_ASSOC);

  for($i = 0; $i < MAX_SCIENCE; $i++) {
    $set.=
      $scienceTypeList[$i]->dbFieldName." = '".
      $row[$scienceTypeList[$i]->dbFieldName]."', ";
  }
  if (MAX_SCIENCE)
    $set = substr($set, 0, strlen($set) - 2) . " "; // remove last ','

  return $db->query("UPDATE ". CAVE_TABLE." ".
        "SET ".$set." ".
        "WHERE playerID = '$playerID'");
}

?>