<?php
/*
 * ranking.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
 
/*
.    * Wiese: +0.1 Nahrungsfaktor
T    * Wald: +0.1 Holzfaktor
M    * Gebirge: +0.1 Metallfaktor
~    * Sumpf: +0.1 Schwefelfaktor
:    * Geroellwueste: +0.1 Steinfaktor
*/

include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."rules/game.rules.php";

$config = new Config();
$db     = DbConnect();

// set memory limit
ini_set("memory_limit", "32M");

// check for right syntax
if ($argc != 3) die("USAGE:\n{$argv[0]} <mapfilename> starting_positions\n");

// load mapfile
$fp = fopen($argv[1], "r");
if ($fp === FALSE) die("could not open '{$argv[1]}'\n");

// parse mapfile
$terrain = array();
while (($c = fgetc($fp)) != FALSE) {
  if ($c == "\n" || $c == "\r") {
    continue;
  }

  switch ($c) {
    case '.':
            array_push($terrain, 0);	// Plains
            break;
    case 'T':
            array_push($terrain, 1);	// Forest
            break;
    case 'M':
            array_push($terrain, 2);	// Mountains
            break;
    case '~':
            array_push($terrain, 3);	// Swamp
            break;
    case ':':
            array_push($terrain, 4);	// Rubble Desert
            break;
    default:
            die("unknown character: " . $c);
  }  
}
fclose($fp); 
  
// get size of map from db
$size   = getMapSize();
$width  = $size['maxX'] - $size['minX'] + 1;
$height = $size['maxY'] - $size['minY'] + 1;

// chunk the 1D vector thus making it 2D
$temp = sizeof($terrain);
$func = function_exists('array_chunk') ? "array_chunk" : "my_array_chunk";
$terrain = $func($terrain, $width);  

// echo some data about the parsed mapfile
printf("The specified mapfile '%s' contained a total of %d cells.\n".
       "It was chunked to a map of %dx%d which should be %d cells\n",
       $argv[1], $temp, $width, $height, $width*$height);

setTerrain($terrain, $size['minX'], $size['minY']);

setStarting_Positions($argv[2]);

/***************************************************************************/
/* FUNCTIONS                                                               */
/***************************************************************************/
function my_array_chunk($a, $s, $p=false) {
  $r = array();
  $ak = array_keys($a);
  $i = 0;
  $sc = 0;
  for ($x=0;$x<count($ak);$x++) {
    if ($i == $s) {
      $i = 0;
      $sc++;
    }
    $k = ($p) ? $ak[$x] : $i;
    $r[$sc][$k] = $a[$ak[$x]];
    $i++;
  }
  return $r;
}

function getMapSize() {
  global $db;

  $sql = $db->prepare("SELECT MIN(xCoord) as minX, MAX(xCoord) as maxX, MIN(yCoord) as minY, MAX(yCoord) as maxY
                       FROM " . CAVE_TABLE . "");
  if (!$sql->execute()) {
    return 0;
  }

  return $sql->fetch(PDO::FETCH_ASSOC);
}

function setTerrain($terrain, $offsetX, $offsetY) {
  global $db;

  $sqlUpdate = $db->prepare("UPDATE " . CAVE_TABLE . "
                             SET terrain = :terrain
                             WHERE xCoord = :xCoord
                               AND yCoord = :yCoord");

  echo "updating caves' terrain ";
  for ($y = 0; $y < sizeof($terrain); ++$y) {
    for ($x = 0; $x < sizeof($terrain[0]); ++$x) {
      $sqlUpdate->bindValue('terrain', $terrain[$y][$x], PDO::PARAM_INT);
      $sqlUpdate->bindValue('xCoord', ($x + $offsetX), PDO::PARAM_INT);
      $sqlUpdate->bindValue('yCoord', ($y + $offsetY), PDO::PARAM_INT);

      if (!$sqlUpdate->execute()) {
        echo "Fehler beim Eintragen des neuen Terrains!\n";
        return 1;
      }
    }
    echo ".";
  }  
  echo "\n";
}
function setStarting_Positions($limit) {
  global $db;
  
  echo "updating caves Starting_positions \n";
  $sqlUpdate = $db->prepare("UPDATE " . CAVE_TABLE . "
                           SET starting_position = 1
                           ORDER BY RAND() LIMIT :limit");
  $sqlUpdate->bindValue('limit', (int) $limit, PDO::PARAM_INT);
  
  if (!$sqlUpdate->execute()) {
    echo print_r($sqlUpdate->errorInfo());
    echo "Fehler beim Eintragen der Starting_Positions!\n";
    return 1;
  }

  echo "Starting_Positions eingetragen. \n";

}

function stopwatch($start=false) {
  static $starttime;
  
  list($usec, $sec) = explode(" ", microtime());
  $mt = ((float)$usec + (float)$sec);

  if (!empty($start)) {
    return ($starttime = $mt);
  } else {
    return $mt - $starttime;
  }
}

?>
