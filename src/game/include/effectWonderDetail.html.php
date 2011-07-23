<?php
/*
 * effectWonderDetail.html.php - show active effects
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function effect_getEffectWonderDetailContent($caveID, $caveData) {

  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $scienceTypeList,
         $unitTypeList,
         $wonderTypeList,
         $effectTypeList,
         $db;

  // don't show the resource bar
  $no_resource_flag = 1;

  // open the template
  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'effectWonderDetail.ihtml');

  $wonders = wonder_getActiveWondersForCaveID($caveID);
  $wondersData = array();
  if ($wonders){
    foreach ($wonders AS $key => $data) {
      if ($wonderTypeList[$data['wonderID']]->groupID == 0 or $wonderTypeList[$data['wonderID']]->groupID == 3) {
        $wonderData = array("name"  =>$wonderTypeList[$data['wonderID']]->name,
                            "end"   =>$data['end_time']);
        $effectsData = array();

        // iterating through effectTypes
        foreach ($effectTypeList AS $effect)
          if ($value = $data[$effect->dbFieldName] + 0)
            $effectsData[] = array("name"  => $effect->name,
                                   "value" => ($value > 0 ? "+" : "") . $value);

        // iterating through resourceTypes
        foreach ($resourceTypeList AS $resource)
          if ($value = $data[$resource->dbFieldName] + 0)
            $effectsData[] = array("name"  => $resource->name,
                                   "value" => ($value > 0 ? "+" : "") . $value);

        // iterating through buildingTypes
        foreach ($buildingTypeList AS $building)
          if ($value = $data[$building->dbFieldName] + 0)
            $effectsData[] = array("name"  => $building->name,
                                   "value" => ($value > 0 ? "+" : "") . $value);

        // iterating through scienceTypes
        foreach ($scienceTypeList AS $science)
          if ($value = $data[$science->dbFieldName] + 0)
            $effectsData[] = array("name"  => $science->name,
                                   "value" => ($value > 0 ? "+" : "") . $value);

        // iterating through unitTypes
        foreach ($unitTypeList AS $unit)
          if ($value = $data[$unit->dbFieldName] + 0)
            $effectsData[] = array("name"  => $unit->name,
                                   "value" => ($value > 0 ? "+" : "") . $value);

        // iterating through defenseSystemTypes
        foreach ($defenseSystemTypeList AS $defenseSystem)
          if ($value = $data[$defenseSystem->dbFieldName] + 0)
            $effectsData[] = array("name"  => $defenseSystem->name,
                                   "value" => ($value > 0 ? "+" : "") . $value);

        $wonderData['EFFECT'] = $effectsData;

        $wondersData[] = $wonderData;
      }
    } // end iterating through active wonders
  }

  $effectsData = array();
  foreach ($effectTypeList AS $data) {
    $value = $caveData[$data->dbFieldName] + 0;
    if ($value)
      $effectsData[] = array("name"  => $data->name,
                             "value" => $value);
  } // end iterating through effectTypes

  $data = array();
  if (!sizeof($wondersData))
    $data['NOWONDER'] = array('dummy' => "");
  else
    $data['WONDER'] = $wondersData;

  if (!sizeof($effectsData))
    $data['NOEFFECT'] = array('dummy' => "");
  else
    $data['EFFECT'] = $effectsData;

  // put user, its session and nogfx flag into session
  $_SESSION['player'] =Player::getPlayer($_SESSION['player']->playerID);

  $data['farmpoints'] = $_SESSION['player']->fame;
  $data['rules_path'] = RULES_PATH;

  tmpl_set($template, "/", $data );

  return tmpl_parse($template);
}

?>