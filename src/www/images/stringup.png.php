<?php
/*
 * stringup.png.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 * Copyright (c) 2011  David Unger
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
if ($caveID == 0) exit(1);

$filename = "temp/$caveID.png";

if (!file_exists($filename)){
  define("NAME_LENGTH", 21);

  $config = new Config();
  $db     = DbConnect();

  $cave = getCaveByID($caveID);
  if ($cave === 0) exit(1);

  $name = unhtmlentities($cave['name']);

  if (strlen($cave['name']) > NAME_LENGTH) {
    $cave['name'] = substr($cave['name'], 0, NAME_LENGTH-2) . "..";
  }

  /* Create imagickdraw object */
  $draw = new ImagickDraw();

  /* Annotate some text */
  $draw->setFontSize(13);
  $draw->annotation(5, 20, "{$cave['name']}");
  $draw->setFontSize(10);
  $draw->annotation(5, 33, "({$cave['xCoord']}|{$cave['yCoord']})");

  /* Create a new imagick object */
  $im = new Imagick();

  /* Create new image. This will be used as fill pattern */
  $im->newImage(120, 40, new ImagickPixel('none'));
  $im->setImageFormat('png');

  /* Draw the ImagickDraw on to the canvas */
  $im->drawImage($draw);
  $im->rotateImage(new ImagickPixel('none'), -90);
  $im->writeImage($filename);
}
header("Location: $filename");

?>