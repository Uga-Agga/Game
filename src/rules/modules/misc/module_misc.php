<?php
/*
 * module_mist.php -
 * Copyright (c) 2003  OGP-Team
 * Copyright (c) 2011-2013  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function misc_getMenu() {
  $result[] = array('link' => "?modus=misc&amp;miscID=1", 'content' => "Rohstoffe");
  $result[] = array('link' => "?modus=misc&amp;miscID=2", 'content' => "Erweiterungen");
  $result[] = array('link' => "?modus=misc&amp;miscID=3", 'content' => "Forschungen");
  $result[] = array('link' => "?modus=misc&amp;miscID=4", 'content' => "Verteidigungsanlagen");
  $result[] = array('link' => "?modus=misc&amp;miscID=5", 'content' => "Einheiten");
  $result[] = array('link' => "?modus=misc&amp;miscID=6", 'content' => "Traglasten");
  $result[] = array('link' => "?modus=misc&amp;miscID=7", 'content' => "Wunder");
  $result[] = array('link' => "?modus=misc&amp;miscID=8", 'content' => "Held Fähigkeiten");

  return $result;
}

function misc_getContent(){

  $miscID = request_var('miscID', 5);
  switch ($miscID) {
    case 1:
      $result = getResourcesStats();
      break;
    case 2:
      $result = getBuildingsStats();
      break;
    case 3:
      $result = getSciencesStats();
      break;
    case 4:
      $result = getDefenseStats();
      break;
    case 5:
    default:
      $result = getUnitStats();
      break;
    case 6:
      $result = getUnitsEncumbrance();
      break;
    case 7:
      $result = getWondersStats();
      break;
    case 8:
      $result = getSkillStats();
      break;
  }
  return $result;
}

function getUnitStats(){
  global $template;

  // open template
  $template->setFile('unitStats.tmpl');

  // get a copy of the unitTypeList
  $unitList = $GLOBALS['unitTypeList'];

  // sort that units by names
  usort($unitList, "nameCompare");

  $units = array();
  foreach ($unitList AS $value) {
    if (!$value->nodocumentation) {
      $units[] = array(
        'id'          => $value->unitID,
        'name'        => $value->name,
        'ranking'     => $value->ranking,
        'attackRange' => $value->attackRange,
        'attackAreal' => $value->attackAreal,
        'attackRate'  => $value->attackRate,
        'defenseRate' => $value->defenseRate,
        'RDResist'    => $value->rangedDamageResistance,
        'hitPoints'   => $value->hitPoints,
        'warpoints'   => $value->warpoints,
        'foodCost'    => $value->foodCost,
        'wayCost'     => $value->wayCost,
        'visible'     => $value->visible,
      );
    }
  }

  $template->addVar('unit_list', $units);
}

function getDefenseStats(){
  global $template;

  // open template
  $template->setFile('defensesStats.tmpl');

  // get a copy of the defenseSystemTypeList
  $defensesList = $GLOBALS['defenseSystemTypeList'];

  // sort that copy by names
  usort($defensesList, "nameCompare");

  $defenses = array();
  foreach ($defensesList AS $value) {
    if (!$value->nodocumentation) {
      $defenses[] = array(
        'id'          => $value->defenseSystemID,
        'name'        => $value->name,
        'attackRange' => $value->attackRange,
        'attackRate'  => $value->attackRate,
        'warpoints'   => $value->warPoints,
        'defenseRate' => $value->defenseRate,
        'hitPoints'   => $value->hitPoints,
        'remark'      => $value->remark,
      );
    }
  }

  $template->addVar('defenses_list', $defenses);
}

function getWondersStats() {
  global $template;

  require_once('wonder.inc.php');
  $uaWonderTargetText = WonderTarget::getWonderTargets();

  // open template
  $template->setFile('wondersStats.tmpl');

  // get a copy of the wonderTypeList
  $wondersList = $GLOBALS['wonderTypeList'];

  // sort that copy by names
  usort($wondersList, "nameCompare");

  $wonders = array();
  foreach ($wondersList AS $value) {
    if ($value->nodocumentation || $value->isTribeCaveWonder) {
      continue;
    }

    $wonders[] = array(
      'id'            => $value->wonderID,
      'name'          => $value->name,
      'offensiveness' => $value->offensiveness,
      'chance'        => round(eval('return '.formula_parseBasic($value->chance).';'), 3),
      'target'        => $uaWonderTargetText[$value->target],
      'remark'        => $value->remark,
    );
  }

  $template->addVar('wonders_list', $wonders);
}

function getBuildingsStats(){
  global $template, $buildingTypeList;

  // open template
  $template->setFile('buildingsStats.tmpl');

  // get a copy of the buildingTypeList
  $buildingsList = $GLOBALS['buildingTypeList'];

  // sort that copy by names
  usort($buildingsList, "nameCompare");

  $buildings = array();
  foreach ($buildingsList AS $value) {
    if (!$value->nodocumentation) {
      $buildings[] = array(
        'id'      => $value->buildingID,
        'name'    => $value->name,
        'points'  => $value->ratingValue,
        'remark'  => $value->remark
      );
    }
  }

  $template->addVar('buildings_list', $buildings);
}

function getResourcesStats(){
  global $template;

  // open template
  $template->setFile('resourcesStats.tmpl');

  // get a copy of the $resourceTypeList
  $resourcesList = $GLOBALS['resourceTypeList'];

  // sort that copy by names
  usort($resourcesList, "nameCompare");

  $resources = array();
  foreach ($resourcesList AS $value) {
    if (!$value->nodocumentation){
      $resources[] = array(
        'id'         => $value->resourceID,
        'name'       => $value->name,
        'dbFieldName' => $value->dbFieldName,
        'remark'     => $value->remark,
      );
    }
  }

  $template->addVar('resources_list', $resources);
}

function getSciencesStats(){
  global $template;

  // open template
  $template->setFile('sciencesStats.tmpl');

  // get a copy of the $resourceTypeList
  $sciencesList = $GLOBALS['scienceTypeList'];

  // sort that copy by names
  usort($sciencesList, "nameCompare");

  $sciences = array();
  foreach ($sciencesList AS $value) {
    if (!$value->nodocumentation){
      $dependencies     = array();
      $buildingdep      = array();
      $defensesystemdep = array();
      $resourcedep      = array();
      $sciencedep       = array();
      $unitdep          = array();

      foreach ($value->buildingDepList as $key => $level) {
        if ($level) {
          array_push($buildingdep, array(
            'name'  => $GLOBALS['buildingTypeList'][$key]->name,
            'level' => "&gt;= " . $level
          ));
        }
      }

      foreach ($value->defenseSystemDepList as $key => $level) {
        if ($level) {
          array_push($defensesystemdep, array(
            'name'  => $GLOBALS['defenseSystemTypeList'][$key]->name,
            'level' => "&gt;= " . $level
          ));
        }
      }

      foreach ($value->resourceDepList as $key => $level) {
        if ($level) {
          array_push($resourcedep, array(
            'name'  => $GLOBALS['resourceTypeList'][$key]->name,
            'level' => "&gt;= " . $level
          ));
        }
      }

      foreach ($value->scienceDepList as $key => $level) {
        if ($level) {
          array_push($sciencedep, array(
            'name'  => $GLOBALS['scienceTypeList'][$key]->name,
            'level' => "&gt;= " . $level
          ));
        }
      }

      foreach ($value->unitDepList as $key => $level) {
        if ($level) {
          array_push($unitdep, array(
            'name'  => $GLOBALS['unitTypeList'][$key]->name,
            'level' => "&gt;= " . $level
          ));
        }
      }

      foreach ($value->maxBuildingDepList as $key => $level) {
        if ($level != -1) {
          array_push($buildingdep, array(
            'name'  => $GLOBALS['buildingTypeList'][$key]->name,
            'level' => "&lt;= " . $level
          ));
        }
      }

      foreach ($value->maxDefenseSystemDepList as $key => $level) {
        if ($level != -1) {
          array_push($defensesystemdep, array(
            'name'  => $GLOBALS['defenseSystemTypeList'][$key]->name,
            'level' => "&lt;= " . $level
          ));
        }
      }

      foreach ($value->maxResourceDepList as $key => $level) {
        if ($level != -1) {
          array_push($resourcedep, array(
            'name'  => $GLOBALS['resourceTypeList'][$key]->name,
            'level' => "&lt;= " . $level
          ));
        }
      }

      foreach ($value->maxScienceDepList as $key => $level) {
        if ($level != -1) {
          array_push($sciencedep, array(
            'name'  => $GLOBALS['scienceTypeList'][$key]->name,
            'level' => "&lt;= " . $level
          ));
        }
      }

      foreach ($value->maxUnitDepList as $key => $level) {
        if ($level != -1) {
          array_push($unitdep, array(
            'name'  => $GLOBALS['unitTypeList'][$key]->name,
            'level' => "&lt;= " . $level
          ));
        }
      }

      if (sizeof($buildingdep)) {
        array_push($dependencies, array(
          'name' => _('Erweiterungen'),
          'dep'  => $buildingdep
        ));
      }

      if (sizeof($defensesystemdep)) {
        array_push($dependencies, array(
          'name' => _('Verteidigungsanlagen'),
          'dep'  => $defensesystemdep
        ));
      }

      if (sizeof($resourcedep)) {
        array_push($dependencies, array(
          'name' => _('Rohstoffe'),
          'dep'  => $resourcedep
        ));
      }

      if (sizeof($sciencedep)) {
        array_push($dependencies, array(
          'name' => _('Forschungen'),
          'dep'  => $sciencedep
        ));
      }

      if (sizeof($unitdep)) {
        array_push($dependencies, array(
          'name' => _('Einheiten'),
          'dep'  => $unitdep
        ));
      }

      $sciences[] = array(
        'id'           => $value->scienceID,
        'name'         => $value->name,
        'dbFieldName'  => $value->dbFieldName,
        'description'  => $value->description,
        'dependencies' => $dependencies
      );
    }
  }

  $template->addVar('sciences_list', $sciences);
}



function getUnitsEncumbrance(){
  global $template;

  // open template
  $template->setFile('unitsEncumbrance.tmpl');

  // get a copy of the unitTypeList
  $unitsList = $GLOBALS['unitTypeList'];

  // sort that copy by unit names
  usort($unitsList, "nameCompare");

  $resources = array();
  foreach ($GLOBALS['resourceTypeList'] AS $resource){
    if (!$resource->nodocumentation) {
      $resources[] = array(
        'name'        => $resource->name,
        'dbFieldName' => $resource->dbFieldName
      );
    }
  }
  $template->addVar('header_resource', $resources);

  $units = array();
  foreach ($unitsList AS $unit) {
    if (!$unit->nodocumentation ){
      $encumbrances = array();
      foreach ($GLOBALS['resourceTypeList'] AS $resource) {
        if (!$resource->nodocumentation) {
          $encumbrances[] = array('value' => (isset($unit->encumbranceList[$resource->resourceID])) ? intval($unit->encumbranceList[$resource->resourceID]) : 0);
        }
      }

      $units[] = array(
        'unitID'      => $unit->unitID,
        'name'        => $unit->name,
        'encumbrances' => $encumbrances,
      );
    }
  }

  $template->addVar('units_list', $units);
}

function getSkillStats () {
  global $template;

  // open template
  $template->setFile('skillStats.tmpl');

  // get hero Type names
  $heroTypeNames = array();
  foreach ($GLOBALS['heroTypesList'] as $type) {
    $heroTypeNames[$type['id']] = $type['name'];
  }

  $skills = array();
  foreach ($GLOBALS['heroSkillTypeList'] as $skillID => $skill) {

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

    $skills[] = array(
      'skillID' => $skillID,
      'name' => $skill['name'],
      'costTP' => $skill['costTP'],
      'requiredLevel' => $skill['requiredLevel'],
      'skillFactor' => $skill['skillFactor'],
      'effectList' => $effectList,
      'typeList' => $typeList
    );
  }
  ;
  $template->addVar('skill_list', $skills);
}
?>