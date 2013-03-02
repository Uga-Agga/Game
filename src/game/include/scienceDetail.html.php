<?php
/*
 * science_detail.html.php -
 * Copyright (c) 2004  OGP-Team
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function science_getScienceDetails($scienceID, $caveData, $method) {
  global $template;

  // open template
  if ($method == 'ajax') {
    $shortVersion = true;
    $template->setFile('scienceDetailAjax.tmpl');
  } else {
    $shortVersion = false;
    $template->setFile('scienceDetail.tmpl');
    $template->setShowResource(false);
  }

  // first check whether that unit should be displayed...
  if (!isset($GLOBALS['scienceTypeList'][$scienceID])) {
    $template->addVar('status_msg', array('type' => 'error', 'message' => _('Die Forschung wurde nicht gefunden oder ist derzeit nicht baubar.')));
    return;
  }
  $science = $GLOBALS['scienceTypeList'][$scienceID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$science->maxLevel};", '$caveData')));
  $maxLevel = ($maxLevel < 0) ? 0 : $maxLevel;
  $maxReadable = formula_parseToReadable($science->maxLevel);

  if ($science->nodocumentation && !$caveData[$science->dbFieldName] && rules_checkDependencies($science, $caveData) !== TRUE) {
    $template->addVar('status_msg', array('type' => 'error', 'message' => _('Die Forschung wurde nicht gefunden oder ist derzeit nicht baubar.')));
    return;
  }

  $currentlevel = $caveData[$science->dbFieldName];
  $levels = $costTimeLvl = array();
  for ($level = $caveData[$science->dbFieldName], $count = 0; $level < $maxLevel && $count < ($shortVersion ? 3 : 10); ++$count, ++$level, ++$caveData[$science->dbFieldName]) {
    $duration = time_formatDuration(eval('return ' . formula_parseToPHP($GLOBALS['scienceTypeList'][$scienceID]->productionTimeFunction.";",'$caveData')) * BUILDING_TIME_BASE_FACTOR);

    // iterate ressourcecosts
    $resourceCost = array();
    foreach ($science->resourceProductionCost as $resourceID => $function) {
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
      if ($cost) {
        array_push($resourceCost, array(
          'name'        => $GLOBALS['resourceTypeList'][$resourceID]->name,
          'dbFieldName' => $GLOBALS['resourceTypeList'][$resourceID]->dbFieldName,
          'value'       => $cost
        ));
      }
    }

    // iterate unitcosts
    $unitCost = array();
    foreach ($science->unitProductionCost as $unitID => $function) {
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
      if ($cost) {
        array_push($unitCost, array(
          'name'        => $GLOBALS['unitTypeList'][$unitID]->name,
          'dbFieldName' => $GLOBALS['unitTypeList'][$unitID]->dbFieldName,
          'value'       => $cost
        ));
      }
    }

    // iterate buildingCost
    $buildingCost = array();
    foreach ($science->buildingProductionCost as $key => $value) {
      if ($value != "" && $value != 0) {
        array_push($buildingCost, array(
          'dbFieldName' => $GLOBALS['buildingTypeList'][$key]->dbFieldName,
          'name'        => $GLOBALS['buildingTypeList'][$key]->name,
          'value'       => ceil(eval('return '.formula_parseToPHP($science->buildingProductionCost[$key] . ';', '$details')))
        ));
      }
    }

    // iterate defenseCost
    $defenseCost = array();
    foreach ($science->defenseProductionCost as $key => $value) {
      if ($value != "" && $value != 0) {
        array_push($defenseCost, array(
          'dbFieldName' => $GLOBALS['defenseSystemTypeList'][$key]->dbFieldName,
          'name'        => $GLOBALS['defenseSystemTypeList'][$key]->name,
          'value'       => ceil(eval('return '.formula_parseToPHP($science->defenseProductionCost[$key] . ';', '$details')))
        ));
      }
    }

    $levels[$count] = array(
      'level' => $level + 1,
      'time'  => $duration,
      'resource_cost' => $resourceCost,
      'unit_cost'     => $unitCost,
      'building_cost' => $buildingCost,
      'defense_cost'  => $defenseCost
    );
  }
  if (sizeof($levels)) {
    $costTimeLvl = array(
      'population' => $caveData['population'],
      'item'       => $levels
    );
  }

  $dependencies     = array();
  $buildingdep      = array();
  $defensesystemdep = array();
  $resourcedep      = array();
  $sciencedep       = array();
  $unitdep          = array();

  foreach ($science->buildingDepList as $key => $level) {
    if ($level) {
      array_push($buildingdep, array(
        'name'  => $GLOBALS['buildingTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($science->defenseSystemDepList as $key => $level) {
    if ($level) {
      array_push($defensesystemdep, array(
        'name'  => $GLOBALS['defenseSystemTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($science->resourceDepList as $key => $level) {
    if ($level) {
      array_push($resourcedep, array(
        'name'  => $GLOBALS['resourceTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($science->scienceDepList as $key => $level) {
    if ($level) {
      array_push($sciencedep, array(
        'name'  => $GLOBALS['scienceTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($science->unitDepList as $key => $level) {
    if ($level) {
      array_push($unitdep, array(
        'name'  => $GLOBALS['unitTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($science->maxBuildingDepList as $key => $level) {
    if ($level != -1) {
      array_push($buildingdep, array(
        'name'  => $GLOBALS['buildingTypeList'][$key]->name,
        'level' => "&lt;= " . $level
      ));
    }
  }

  foreach ($science->maxDefenseSystemDepList as $key => $level) {
    if ($level != -1) {
      array_push($defensesystemdep, array(
        'name'  => $GLOBALS['defenseSystemTypeList'][$key]->name,
        'level' => "&lt;= " . $level
      ));
    }
  }

  foreach ($science->maxResourceDepList as $key => $level) {
    if ($level != -1) {
      array_push($resourcedep, array(
        'name'  => $GLOBALS['resourceTypeList'][$key]->name,
        'level' => "&lt;= " . $level
      ));
    }
  }

  foreach ($science->maxScienceDepList as $key => $level) {
    if ($level != -1) {
      array_push($sciencedep, array(
        'name'  => $GLOBALS['scienceTypeList'][$key]->name,
        'level' => "&lt;= " . $level
      ));
    }
  }

  foreach ($science->maxUnitDepList as $key => $level) {
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

  $template->addVars(array(
    'name'          => $science->name,
    'dbFieldName'   => $science->dbFieldName,
    'description'   => $science->description,
    'maxlevel'      => $maxLevel,
    'maxReadable'   => $maxReadable,
    'currentlevel'  => $currentlevel,
    'cost_time_lvl' => $costTimeLvl,
    'dependencies'  => $dependencies,
    'rules_path'    => RULES_PATH
  ));
}

?>