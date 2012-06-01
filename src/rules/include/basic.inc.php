<?php
/*
 * basic.inc.php -
 * Copyright (c) 2011  David Unger
 * Copyright (c) 2012 Georg Pitterle
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function lib_getActiveModules() {
  global $module_cfg;

  // filter active modules
  $modules = array_filter($module_cfg['modules'], create_function('$var', 'return $var["active"];'));

  // usort modules
  uasort($modules, create_function('$a, $b', 'if ($a["weight"] == $b["weight"]) return 0; return ($a["weight"] > $b["weight"]) ? -1 : 1;'));

  // require the modules
  array_walk($modules, create_function('$value, $key', 'require_once("./modules/$key/module_$key.php");'));

  return $modules;
}

function lib_shorten_html($string, $length) {
  if (strlen($string) > $length) {
    return substr($string, 0, $length) . "..";
  }

  return $string;
}

function lib_unhtmlentities($string) {
  static $trans_tbl;

  if (empty($trans_tbl)){
    $trans_tbl = get_html_translation_table (HTML_ENTITIES);
    $trans_tbl = array_flip ($trans_tbl);
  }
  return strtr ($string, $trans_tbl);
}

function descriptionCompare($a, $b) {
  return strcmp($a['description'], $b['description']);
}

function nameCompare($a, $b) {
  return strcmp($a->name, $b->name);
}

?>