<?php
/*
 * stringup.png.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("../config.inc.php");
require_once("include/config.inc.php");
require_once("include/params.inc.php");
require_once("include/db.inc.php");
require_once("include/basic.lib.php");

$caveID = request_var('cave_id', 0);
$filename = "temp/$caveID.png";

if (!file_exists($filename)){
  define("NAME_LENGTH", 17);

  $config = new Config();
  $db     = DbConnect();

  $cave = getCaveByID($caveID);    
  if ($cave === 0) exit(1);
  
  $name = unhtmlentities($cave['name']);
  
  if (strlen($name) > NAME_LENGTH)
    $name = substr($name, 0, NAME_LENGTH-2) . "..";
  
  $im = imagecreate(40, 135);
  $white = imagecolorallocate($im, 255, 55, 255);
  $black = imagecolorallocate($im, 0, 0, 0);

  
  imagecolortransparent($im, $white);
  imagestringup($im, 3,  5, 130, $name, 1);
  imagestringup($im, 2, 25, 130, "({$cave['xCoord']}|{$cave['yCoord']})", 1);
  header("Content-type: image/png");
  imagepng($im, "temp/$caveID.png");
  imagedestroy($im);
}
header("Location: $filename");

?>