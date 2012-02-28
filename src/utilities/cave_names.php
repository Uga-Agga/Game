<?php
/*
 * cave_names.php -
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

ini_set("memory_limit", "32M");

$db     = DbConnect();

$names = swapshuffle(createNames());

$sql = $db->prepare("SELECT COUNT(*) AS num_caves FROM " . CAVE_TABLE . " GROUP BY NULL");
if (!$sql->execute()) {
  echo "Fehler bei der Abfrage der Anzahl der Höhlen. (1.a.)\n";
  return -1;
}
$row = $sql->fetch(PDO::FETCH_ASSOC);
$sql->closeCursor();
$num_caves = $row['num_caves'];

if ($num_caves > sizeof($names)){
  echo "Zu wenig Namen für alle Höhlen. (2.a.)\n";
  return -2;
}

// hier wird davon ausgegangen, dass die H�hlen mit 1 beginnend fortlaufend durchnummeriert sind.
$sqlUpdate = $db->prepare("UPDATE " . CAVE_TABLE . " SET name = :name WHERE caveID = :caveID");
for ($i = 0; $i < $num_caves; ++$i) {
  $sqlUpdate->bindValue('name', $names[$i], PDO::PARAM_STR);
  $sqlUpdate->bindValue('caveID', ($i + 1), PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Ändern des Höhlennamen: (" . ($i + 1) . ") " . $names[$i] . ". (3.a.)\n";
    return -3;
  }
}

//
// Liest aus einer Datei beliebiger Gr��e Strings ein und macht daraus eine Matrix
// Die Datei muss folgendes Format haben:
//   #1\n
//   string\n
//   #2\n
//   string\n

function createNames($dateiname = "namen.txt") {
  $file = fopen($dateiname, "r");

  $switch = "-";

  $namen_a = array();
  $namen_b = array();

  $namenkombi = array();

  $xa = 0;
  $xb = 0;
  while ($line = fgets($file)) {
//    $line = fgets($file, 1024);

    if (substr($line, 0, 2) == "#1") {
      $switch = "a";    
    } else if (substr($line, 0, 2) == "#2") {
      $switch = "b";    
    } else {
      $newLine = substr($line, 0, strlen($line) - 1);

      if ($switch == "a"){
        array_push($namen_a, $newLine);
      } else if ($switch == "b"){
        array_push($namen_b, $newLine);
      }
    }
  }
  fclose($file);

  echo "Vorsilben: " . sizeof($namen_a);
  $namen_a = array_unique($namen_a);
  echo " (" . sizeof($namen_a) . ")\n";

  echo "Nachsilben: " . sizeof($namen_b);
  $namen_b = array_unique($namen_b);
  echo " (" . sizeof($namen_b) . ")\n";

  for ($i = 0; $i < sizeof($namen_a); ++$i) {
    for ($j = 0; $j < sizeof($namen_b); ++$j) {
      array_push($namenkombi, htmlentities($namen_a[$i] . $namen_b[$j]));
    }
  }

  return $namenkombi;
}

function swapshuffle($array) {
  srand ((double) microtime() * 10000000);
  for ($i = 0; $i < sizeof($array); $i++) {
    $from=rand(0, sizeof($array) - 1);
    $old = $array[$i];
    $array[$i] = $array[$from];
    $array[$from] = $old;
  }  
  return $array;
}

?>