<?php
/*
 * module_wonders.php -
 * Copyright (c) 2003  OGP-Team
 * Copyright (c) 2011  David Unger
 * Copyright (c) 2012 Georg Pitterle
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('hero.inc.php');

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');
function skill_getSelector() {

$skills = array();
foreach ($GLOBALS['heroSkillTypeList'] AS $key => $value) {
$skillID = request_var('skillID', '');

$temp = array(
      'value'       => $value['id'],
      'description' => lib_shorten_html($value['name'], 20)
);

if (isset($_REQUEST['skillID']) && $skillID == $value['id']) {
$temp['selected'] = 'selected="selected"';
}

$skills[] = $temp;
}
usort($skills, "descriptionCompare");

return $skills;
}

function skill_getContent() {
  global $template;
  
  // open template
  $template->setFile('skill.tmpl');
  
  $id = request_var('skillID', '');
  if (!isset($GLOBALS['heroSkillTypeList'][$id])) {
  $skill = $GLOBALS['heroSkillTypeList']['skillFoodFactor'];
  } else {
  $skill = $GLOBALS['heroSkillTypeList'][$id];
  }
  
  
  // get hero Type names
  $heroTypeNames = array();
  foreach ($GLOBALS['heroTypesList'] as $type) {
    $heroTypeNames[$type['id']] = $type['name'];
  }
  
  $typeList = array();
  foreach ($skill['requiredType'] as $type) {
    $typeList[] = $heroTypeNames[$type];
  }
  
  $effectList = array();
  foreach ($GLOBALS['effectTypeList'] as $effect) {
    foreach ($skill['effects'] as $heroEffectName => $heroEffect) {
      if ($effect->dbFieldName == $heroEffectName) {
        $effectList[] = $effect->name;
      }
    }
  }
  
  $template->addVars(array(
      'skill'           => $skill, 
      'typeList'        => $typeList, 
      'effectList'      => $effectList
  ));

}
?>