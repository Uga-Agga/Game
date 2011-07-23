<?php
/*
 * mapstyle.php - outputs a tribe-tailored stylesheet for the map that reflects the relations
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("config.inc.php");

require_once("include/tribes.inc.php");
require_once("include/params.inc.php");
require_once("include/page.inc.php");

page_start();

$stylesheet = "./images/temp/tribe_".$params->SESSION->player->tribe.".css";

if (!file_exists($stylesheet))
  tribe_generateMapStylesheet();

// output the file; readfile wraps it in HTML, header() is too obvious where files are located
header("Content-type: text/css");
$file = @file_get_contents($stylesheet);
echo $file;

?>