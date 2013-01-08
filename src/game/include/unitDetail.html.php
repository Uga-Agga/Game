<?php
/*
 * unitDetail.html.php -
 * Copyright (c) 2004  OGP-Team
 * Copyright (c) 2011-2012 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function unit_getUnitDetails($unitID, $caveData, $method) {
  global $template;

  // open template
  if ($method == 'ajax') {
    $shortVersion = true;
    $template->setFile('unitDetail.tmpl');
  }
  else {
    $shortVersion = false;
    $template->setFile('unitDetail.tmpl');
    $template->setShowRresource(false);    
  }

  $details = $caveData;
  // first check whether that unit should be displayed...
  if (!isset($GLOBALS['unitTypeList'][$unitID])) {
    $template->addVar('status_msg', array('type' => 'error', 'message' => _('Die Einheit wurde nicht gefunden oder ist derzeit nicht baubar.')));
    return;
  }
  $unit = $GLOBALS['unitTypeList'][$unitID];

  if ($unit->nodocumentation && !$caveData[$unit->dbFieldName] && rules_checkDependencies($unit, $caveData) !== TRUE) {
    $template->addVar('status_msg', array('type' => 'error', 'message' => _('Die Einheit wurde nicht gefunden oder ist derzeit nicht baubar.')));
    return;
  }

  // iterate ressourcecosts
  $resourceCost = array();
  foreach ($unit->resourceProductionCost as $resourceID => $function) {
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
  foreach ($unit->unitProductionCost as $unitID => $function) {
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
  foreach ($unit->buildingProductionCost as $buildingID => $function) {
    $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
    if ($cost) {
      array_push($buildingCost, array(
        'name'        => $GLOBALS['buildingTypeList'][$buildingID]->name,
        'dbFieldName' => $GLOBALS['buildingTypeList'][$buildingID]->dbFieldName,
        'value'       => $cost
      ));
    }
  }

  // iterate defenseCost
  $defenseCost = array();
  foreach ($unit->defenseProductionCost as $defenseID => $function) {
    $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
    if ($cost) {
      array_push($defenseCost, array(
        'name'        => $GLOBALS['defenseSystemTypeList'][$defenseID]->name,
        'dbFieldName' => $GLOBALS['defenseSystemTypeList'][$defenseID]->dbFieldName,
        'value'       => $cost
      ));
    }
  }

  $dependencies     = array();
  $buildingdep      = array();
  $defensesystemdep = array();
  $resourcedep      = array();
  $sciencedep       = array();
  $unitdep          = array();

  foreach ($unit->buildingDepList as $key => $level) {
    if ($level) {
      array_push($buildingdep, array(
        'name'  => $GLOBALS['buildingTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($unit->defenseSystemDepList as $key => $level) {
    if ($level) {
      array_push($defensesystemdep, array(
        'name'  => $GLOBALS['defenseSystemTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($unit->resourceDepList as $key => $level) {
    if ($level) {
      array_push($resourcedep, array(
        'name'  => $GLOBALS['resourceTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($unit->scienceDepList as $key => $level) {
    if ($level) {
      array_push($sciencedep, array(
        'name'  => $GLOBALS['scienceTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }

  foreach ($unit->unitDepList as $key => $level) {
    if ($level) {
      array_push($unitdep, array(
        'name'  => $GLOBALS['unitTypeList'][$key]->name,
        'level' => "&gt;= " . $level
      ));
    }
  }


  foreach ($unit->maxBuildingDepList as $key => $level) {
    if ($level != -1) {
      array_push($buildingdep, array(
        'name'  => $GLOBALS['buildingTypeList'][$key]->name,
        'level' => "&lt;= " . $level
      ));
    }
  }

  foreach ($unit->maxDefenseSystemDepList as $key => $level) {
    if ($level != -1) {
      array_push($defensesystemdep, array(
        'name'  => $GLOBALS['defenseSystemTypeList'][$key]->name,
        'level' => "&lt;= " . $level
      ));
    }
  }

  foreach ($unit->maxResourceDepList as $key => $level) {
    if ($level != -1) {
      array_push($resourcedep, array(
        'name'  => $GLOBALS['resourceTypeList'][$key]->name,
        'level' => "&lt;= " . $level
      ));
    }
  }

  foreach ($unit->maxScienceDepList as $key => $level) {
    if ($level != -1) {
      array_push($sciencedep, array(
        'name'  => $GLOBALS['scienceTypeList'][$key]->name,
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

  if ($unit->visible != 1) {
    $template->addVar('INVISIBLE', array('text' => _('unsichtbar')));
  }

  $template->addVars(array(
    'name'          => $unit->name,
    'dbFieldName'   => $unit->dbFieldName,
    'description'   => $unit->description,
    'resouce_cost'  => $resourceCost,
    'unit_cost'     => $unitCost,
    'buiding_cost'  => $buildingCost,
    'defense_cost'  => $defenseCost,
    'rangeAttack'   => $unit->attackRange,
    'arealAttack'   => $unit->attackAreal,
    'attackRate'    => $unit->attackRate,
    'rd_Resist'     => $unit->rangedDamageResistance,
    'defenseRate'   => $unit->defenseRate,
    'size'          => $unit->hitPoints,
    'spyValue'      => $unit->spyValue,
    'spyChance'     => $unit->spyChance,
    'spyQuality'    => $unit->spyQuality,
    'antiSpyChance' => $unit->antiSpyChance,
    'fuelName'      => $GLOBALS['resourceTypeList'][1]->dbFieldName,
    'fuelFactor'    => $unit->foodCost,
    'wayCost'       => $unit->wayCost,
    'dependencies'  => $dependencies,
    'duration'      => time_formatDuration(eval('return '.formula_parseToPHP($unit->productionTimeFunction.";", '$details')) * BUILDING_TIME_BASE_FACTOR),
    'rules_path'    => RULES_PATH
  ));
}

?>