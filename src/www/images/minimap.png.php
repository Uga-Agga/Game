<?php
/*
 * minimap.png.php - erzeugt eine Minimap mit einer Markierung für den aktuellen Standpunkt
 * Copyright (c) 2004  Marcus Lunzenauer
 * Copyright (c) 2011  Sascha Lange <salange@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 *  Eingangsparameter:
 *
 *  x    : xKoordinate des aktuellen Standpunkts
 *  y    : yKoordinate des aktuellen Standpunkts
 *  minX :
 *  maxX :
 *  minY :
 *  maxY :
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("../config.inc.php");

require_once("include/config.inc.php");
require_once("include/db.inc.php");
require_once("include/params.inc.php");

define("TERRAIN_MAP", "terrain_map.png");
define("MAP_TIMEOUT", 60*60);


$config = new Config();
$db     = DbConnect();

// which coordinate should be marked
$x = request_var('x', 0);
$y = request_var('y', 0);

// get map size
$size = getMapSize();

if($size == 0)
  return false;

$minX = $size['minX'];
$maxX = $size['maxX'];
$minY = $size['minY'];
$maxY = $size['maxY'];

$width  = $maxX - $minX + 1;
$height = $maxY - $minY + 1;

// make coordinate valid
$x = min($maxX, max($minX, $x));
$y = min($maxY, max($minY, $y));


// get map file's lifetime if existent
$lifetime = -1;

if (file_exists(TERRAIN_MAP)){
  $lifetime = time() - filemtime(TERRAIN_MAP);
}

// get map file's size
if ($lifetime == -1 || $lifetime >= MAP_TIMEOUT){
  $status = createTerrainMap($db);
  if ($status != TRUE) die("could not create map file.");
}
$minimap = loadPNG(TERRAIN_MAP);

// check correct map size
if (imagesx($minimap) != $width || imagesy($minimap) != $height){
  $status = createTerrainMap($db);
  if ($status != TRUE) die("could not create map file.");
}
$minimap = loadPNG(TERRAIN_MAP);


// show minimap
header("Content-type: image/png");
imagearc($minimap, $x - $minX, $y - $minY, 2, 2, 0, 360,imagecolorallocate ($minimap, 0xFF, 0x00, 0x00));
imagepng($minimap);
imagedestroy($minimap);


/* ***** FUNCTIONS ***** */

function getMapSize(){
	global $db;

	if($res = $db->query("SELECT MIN(xCoord) as minX, MAX(xCoord) as maxX, MIN(yCoord) as minY, MAX(yCoord) as maxY FROM Cave")){
	  return $res->fetch();
	}
	return 0;
}

function createTerrainMap($db){

  unlink(TERRAIN_MAP);
  $size = getMapSize();
  
  if($size == 0)
    return false;
  
  $terrainMap = ImageCreate/*TrueColor*/($size['maxX'] - $size['minX'] + 1, $size['maxY'] - $size['minY'] + 1);
  $bg  = imagecolorallocate ($terrainMap, 168, 206, 248);
  imagefilledrectangle ($terrainMap, 0, 0, $size['maxX']-$size['minX']+1, $size['maxY']-$size['minY']+1, $bg);
  $terrain_colour = array(ImageColorAllocate($terrainMap, 0xBB, 0xD8, 0x72),
                          ImageColorAllocate($terrainMap, 0xBC, 0x91, 0x69),
                          ImageColorAllocate($terrainMap, 0x91, 0x99, 0x99),
                          ImageColorAllocate($terrainMap, 0x7E, 0x8C, 0x55),
                          ImageColorAllocate($terrainMap, 0xD8, 0xC1, 0x99),
                          ImageColorAllocate($terrainMap, 0xFF, 0xDE, 0x7A));

  $query = "SELECT xCoord, yCoord, terrain FROM Cave ORDER BY yCoord, xCoord";
  if (!($db_result = $db->query($query))){
    echo "Fehler beim Auslesen des Terrains!\n";
    return false;
  }
  $terrain = array();
  while($row = $db_result->fetch()){
    if(array_key_exists($row['terrain'], $terrain_colour))
      ImageSetPixel($terrainMap, $row['xCoord'] - $size['minX'], $row['yCoord'] - $size['minY'], $terrain_colour[$row['terrain']]);
  }
  ImagePng($terrainMap, TERRAIN_MAP);
  return true;
}

function loadPNG ($imgname) {
    $im = @imagecreatefrompng ($imgname); /* Attempt to open */
    if (!$im) { /* See if it failed */
        $im  = imagecreate (150, 30); /* Create a blank image */
        $bgc = imagecolorallocate ($im, 255, 255, 255);
        $tc  = imagecolorallocate ($im, 0, 0, 0);
        imagefilledrectangle ($im, 0, 0, 150, 30, $bgc);
        /* Output an errmsg */
        imagestring ($im, 1, 5, 5, "Error loading $imgname", $tc);
    }
    return $im;
}

?>