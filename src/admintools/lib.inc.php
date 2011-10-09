<?php
/*
 * lib.inc.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function descriptionCompare($a, $b){
  return strcmp($a['description'], $b['description']);
}

function nameCompare($a, $b){
  return strcmp($a->name, $b->name);
}

function lib_getMenu($active_modules){
  foreach ($active_modules AS $active_module)
    if ($temp_menu = $active_module->getMenu())
      $menu[] = $temp_menu;
  return $menu;
}
function lib_checkModus(&$modus, $modules){

  global $module_cfg;

  foreach ($modules as $module)
    if ($module->checkModus($modus))
      return $module;

  $module =& $modules[$module_cfg['default_module']];
  $temp = $module->getModi();
  $modus = current($temp);
  return $module;
}

function &lib_getActiveModules(){

  global $module_cfg, $cfg;

  $modules = array();

  foreach ($module_cfg['modules'] as $module_class){
    if (file_exists("./modules/$module_class/$module_class.php")){
      require_once("./modules/$module_class/$module_class.php");
      $temp = new $module_class();

      if ($temp->isActive())
        $modules[$module_class] = $temp;
    }
  }

  // usort modules
  uasort($modules, create_function('$a, $b', 'if ($a->getWeight() == $b->getWeight()) return 0; return ($a->getWeight() < $b->getWeight()) ? -1 : 1;'));

  return $modules;
}

function lib_shorten_html($string, $length){
  $temp = lib_unhtmlentities($string);
  if (strlen($temp) > $length)
    return htmlentities(substr($temp, 0, $length)) . "...";
  return $string;
}

function lib_unhtmlentities($string){
  static $trans_tbl;

  if (empty($trans_tbl)){
    $trans_tbl = get_html_translation_table (HTML_ENTITIES);
    $trans_tbl = array_flip ($trans_tbl);
  }
  return strtr ($string, $trans_tbl);
}

function &lib_bb_code(&$content){
  $pattern[$i=0]     = '/\[([BI])\](.*?)\[\/\1\]/is';
  $replacement[$i++] = '<\1>\2</\1>';

  $pattern[$i]       = '/\[A=(.*?)\](.*?)\[\/a\]/is';
  $replacement[$i++] = '<a href="\1" target="_blank">\2</a>';

  $pattern[$i]       = '/\[IMG\](.*?)\[\/IMG\]/is';
  $replacement[$i++] = '<img class="padded" align="left" src="\1">';

  $pattern[$i]       = '/\[P\]/i';
  $replacement[$i++] = '<p>';

  $pattern[$i]       = '/\[BR\]/i';
  $replacement[$i++] = '<br>';

  $pattern[$i]       = '/\[INC\](.*?)\[\/INC\]/ise';
  $replacement[$i++] = 'join("", file("\1"))';

  return preg_replace($pattern, $replacement, $content);
}

function &lib_bb_decode(&$content){
  $pattern[$i=0]     = '/\<([BI])\>(.*?)\<\/\1\>/is';
  $replacement[$i++] = '[\1]\2[/\1]';

  $pattern[$i]       = '/\<a href=\"(.*?)\"\ target=\"\_blank\">(.*?)\<\/a\>/is';
  $replacement[$i++] = '[a=\1]\2[/a]';

  $pattern[$i]       = '/\<img class=\"padded\" align=\"left\" src=\"(.*?)\"\>/is';
  $replacement[$i++] = '[img]\1[/img]';

  $pattern[$i]       = '/\<P\>/i';
  $replacement[$i++] = '[p]';

  $pattern[$i]       = '/\<BR\>/i';
  $replacement[$i++] = '[br]';

// Umwandlung nicht möglich
//  $pattern[$i]       = '/\[INC\](.*?)\[\/INC\]/ise';
//  $replacement[$i++] = 'join("", file("\1"))';

  return preg_replace($pattern, $replacement, $content);
}

/* ***************************************************************************/
/* **** GET PLAYER FUNCTIONS ***** *******************************************/
/* ***************************************************************************/

/** This function returns a players data
 */
function getPlayerByName($db, $name){

  $query = "SELECT * FROM Player WHERE name = '$name'";
  $result = $db->query($query);
  if ($result && !$result->isEmpty())
    return $result->nextRow(MYSQL_ASSOC);
  return array();
}

/* ***************************************************************************/
/* **** GET TRIBE FUNCTIONS ***** ********************************************/
/* ***************************************************************************/

/** This function returns a tribe's data
 */
function getTribeByTag($db, $tag){

  $query = "SELECT * FROM Tribe WHERE tag = '$tag'";
  $result = $db->query($query);
  if ($result && !$result->isEmpty())
    return $result->nextRow(MYSQL_ASSOC);
  return array();
}

/* ***************************************************************************/
/* **** GET TRIBE FUNCTIONS ***** ********************************************/
/* ***************************************************************************/

/** This function returns the cave data for a given caveID
 */
function getCaveByID($db, $caveID){

  $query = "SELECT * FROM Cave WHERE caveID = " . intval($caveID);
  $result = $db->query($query);
  if ($result) return $result->nextRow(MYSQL_ASSOC);
  return array();
}

/** This function returns the cave data for given cave coordinates
 */
function getCaveByCoords($db, $xCoord, $yCoord){

  $query = "SELECT * FROM Cave WHERE xCoord = '$xCoord' AND yCoord = '$yCoord'";
  $res =$db->query($query);
  if($res && !$res->isEmpty())
    return $res->nextRow(MYSQL_ASSOC);
  return array();
}
?>