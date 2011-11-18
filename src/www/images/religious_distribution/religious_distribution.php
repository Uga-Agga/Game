<?php
/*
 * religious_distribution.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("../../config.inc.php");
require_once("include/config.inc.php");
require_once("include/params.inc.php");
require_once("include/db.inc.php");
require_once("include/ranking.inc.php");

$config = new Config();
$db     = DbConnect();

$religions =  ranking_getReligiousDistribution();

$ugaCount = (isset($religions['uga'])) ? $religions['uga'] : 0;
$aggaCount = (isset($religions['agga'])) ? $religions['agga'] : 0;

if (($ugaCount + $aggaCount) != 0) {
  $uga_part = round($ugaCount/($ugaCount + $aggaCount)*100);
  $agga_part = round($aggaCount/($ugaCount + $aggaCount)*100);
} else {
  $uga_part = 0;
  $agga_part = 0;
}

$filename = reldis_getFilename($uga_part, $agga_part);

if (!file_exists($filename)){
  reldis_createFile($filename, $uga_part, $agga_part);
}
header("Location: $filename");


################################################################################
function reldis_getFilename($uga_part, $agga_part){
  return sprintf('../temp/reldis_%d_%d.png', $uga_part, $agga_part);
}

################################################################################
function reldis_createFile($filename, $uga_part, $agga_part) {
  $im_uga   = @ImageCreateFromPng("good.png");
  $im_agga  = @ImageCreateFromPng("bad.png");

  if (!$im_uga || !$im_agga) {
    die ("Cannot Initialize new GD image stream");
  }

  $width      = imagesx($im_uga);

  $sum = $uga_part + $agga_part;

  $uga_part   /= $sum;
  $agga_part  /= $sum;

  $uga_part   *= $width;
  $agga_part  *= $width;

  $im = @ImageCreate($width, $width);
  $white = imagecolorallocate($im, 0xff, 0xff, 0xff);
  imagefill($im, 0, 0, $white);
  imagecolortransparent($im, $white);

  $left = 0;
  imagecopy($im,  $im_uga,   $left, 0, $left, 0, $uga_part + 1,   $width);

  $left += $uga_part;
  imagecopy($im,  $im_agga,  $left, 0, $left, 0, $agga_part + 1,  $width);

  header("Content-type: image/png");
  imagepng($im, $filename);
  imagedestroy($im);
}

?>