<?php
/*
 * ugastart.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("config.inc.php");

require_once("include/page.inc.php");

page_start();

if (isset($params->POST->caveID)){
  // und auch in die Session schreiben
  $_SESSION['caveID'] = intval($params->POST->caveID);
}

// letzten Modus raussuchen, wenn nichts anderes angegeben wurde...
if (!isset($params->POST->modus)){
  if (!empty($params->SESSION->current_modus)){
    $params->POST->modus = $params->SESSION->current_modus;
  } else {
    $params->POST->modus = 'news';
  }
}

// POST Variablen in die URL
$mainparams = array();
foreach ($params->POST as $key => $value){
  if (is_array($value))
    array_push($mainparams, array2url($key, $value));
  else
    array_push($mainparams, $key . "=" . $value);
}

$template = tmpl_open($params->SESSION->player->getTemplatePath() . 'frameset.ihtml');

tmpl_set($template, 'mainparams', implode("&amp;", $mainparams));

$gfx = $params->SESSION->nogfx ? DEFAULT_GFX_PATH : $params->SESSION->player->gfxpath;
echo str_replace ('%gfx%', $gfx, tmpl_parse($template));

page_end();


function array2url($name, $array){
  $str = array();
  foreach($array as $index=>$element)
    $str[] = urlencode($name)."[".urlencode($index)."]=".urlencode($element);
  return implode("&amp;", $str);
}

?>