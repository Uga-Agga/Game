<?php
/*
 * spreadVoid.php -
 * Copyright (c) 2004  OGP Team
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
  
  DEFINE("VOID_ID", 5);
  DEFINE("MAX_PROB", 0.5);

  $config = new Config();
  $db     = DbConnect();

  srand ((double)microtime()*100000);

  $sql = $db->prepare("SELECT MAX(xCoord) As maxX, MAX(yCoord) AS maxY, 
                       MIN(xCoord) AS minX, MIN(yCoord) AS minY 
                       FROM " . CAVE_TABLE);

  if (!$sql->execute() || !($row = $sql->fetch())) {
    echo "Query failed:\n";
    echo $query;
    exit;
  }
  $maxX = $row['maxX'];
  $maxY = $row['maxY'];
  $minX = $row['minX'];
  $minY = $row['minY'];

  echo "Map size: $minX -> $maxX x $minY -> $maxY\n";

  $sql = $db->prepare("SELECT xCoord, yCoord 
                       FROM ". CAVE_TABLE ."
                       WHERE terrain = :voidID");
  $sql->bindValue('voidID', VOID_ID, PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "Query failed:\n";
    echo $query;
    exit;
  }
  
  $result = $sql->fetchAll();
  if (empty($result)) {
    echo "#1 Void does not spread this cycle.\n";
    exit;
  }

  $caves = array();
  foreach($result AS $row) {
    $caves[$row['xCoord']][$row['yCoord']] = 1;
  }

  $caves_new  = spreadVoid($caves, $minX, $minY, $maxX, $maxY);
  
  $caves_dif = array();
  if ($caves_new) {
    foreach($caves_new AS $x => $ar) {
      foreach($ar AS $y => $void) {
        if (!isset($caves[$x][$y]) || $caves[$x][$y] != $void) {
          $caves_dif[$x][$y] = 1;
        }
      }
    }
  }

  if (empty($caves_dif)) {
    echo "#2 Void does not spread this cycle.\n";
    exit;
  }

  foreach($caves_dif AS $x => $a) {
    foreach($a AS $y => $void) {
      $sql = $db->prepare("UPDATE ". CAVE_TABLE ."
                          SET terrain = :voidID 
                          WHERE xCoord = :x AND yCoord = :y");
      $sql->bindValue('voidID', VOID_ID, PDO::PARAM_INT);
      $sql->bindValue('x', $x, PDO::PARAM_INT);
      $sql->bindValue('y', $y, PDO::PARAM_INT);

      if (!$sql->execute()) {
        echo "Query failed:\n";
        exit;
      }
    }
  }

function spreadVoid($caves, $minX, $minY, $maxX, $maxY) {
  for ($x = $minX; $x <= $maxX; $x++) {
    for ($y = $minY; $y <= $maxY; $y++) {
      
      if (isset($caves[$x][$y]) && $caves[$x][$y] == 1) {
        $caves_new[$x][$y] = 1;
      } else {
        $count = countVoid($caves, $x, $y);

        // linearly increasing probability
        $prob = (MAX_PROB / 8.) * $count;

        if (rand() / (double)getRandMax() < $prob) {
          $caves_new[$x][$y] = 1;
        }
      }
    }
  }
  return $caves_new;
}

function countVoid($caves, $x, $y) {
  $count = 0;
  for ($i=-1; $i <= 1; $i++) {
    for ($j=-1; $j <= 1; $j++) {
      if ($i != 0 || $j != 0) {
        if (isset($caves[$x+$i][$y+$j])) {
          $count += $caves[$x+$i][$y+$j];
        }
      }
    }
  }
  return $count;
}

function testSpreadVoid() {
  $caves[20][20] = 1;
  $caves[20][21] = 1;
  
  for ($i=1; $i < 5; $i++) {
    $caves_new = spreadVoid($caves, 0,0, 100, 100);

    echo "old: ";
    print_r ($caves);
    echo "new: ";
    print_r ($caves_new);
  
    $caves = $caves_new;
  }
}

?>