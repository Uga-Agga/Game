<?php
/*
 * unit_properties.html.php -
 * Copyright (c) 2004  OGP-Team
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
  global $buildingTypeList, $defenseSystemTypeList, $resourceTypeList, $scienceTypeList, $unitTypeList;

  $details = $caveData;
  // first check whether that unit should be displayed...
  $unit = $unitTypeList[$unitID];
  if (!$unit || ($unit->nodocumentation &&
                 !$caveData[$unit->dbFieldName] &&
                 rules_checkDependencies($unit, $caveData) !== TRUE))
    $unit = current($unitTypeList);

  // open template
  if ($method == 'ajax') {
    $shortVersion = true;
    $template->setFile('unitDetailAjax.tmpl');
  }
  else {
    $shortVersion = false;
    $template->setFile('unitDetail.tmpl');
    $template->setShowRresource(false);    
  }

  $resourceCost = array();
  foreach ($unit->resourceProductionCost as $key => $value)
    if ($value != "" && $value != 0)
      array_push($resourceCost, array('dbFieldName' => $resourceTypeList[$key]->dbFieldName,
                                      'name'        => $resourceTypeList[$key]->name,
                                      'value'       => ceil(eval('return '.formula_parseToPHP($unit->resourceProductionCost[$key] . ';', '$details')))));
  $unitCost = array();
  foreach ($unit->unitProductionCost as $key => $value)
    if ($value != "" && $value != 0)
      array_push($unitCost, array('dbFieldName' => $unitTypeList[$key]->dbFieldName,
                                  'name'        => $unitTypeList[$key]->name,
                                  'value'       => ceil(eval('return '.formula_parseToPHP($unit->unitProductionCost[$key] . ';', '$details')))));

  $buildingCost = array();
  foreach ($unit->buildingProductionCost as $key => $value)
    if ($value != "" && $value != 0)
      array_push($buildingCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                      'name'        => $buildingTypeList[$key]->name,
                                      'value'       => ceil(eval('return '.formula_parseToPHP($unit->buildingProductionCost[$key] . ';', '$details')))));

  $defenseCost = array();
  foreach ($unit->defenseProductionCost as $key => $value)
    if ($value != "" && $value != 0)
      array_push($defenseCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                      'name'        => $defenseSystemTypeList[$key]->name,
                                      'value'       => ceil(eval('return '.formula_parseToPHP($unit->defenseProductionCost[$key] . ';', '$details')))));

  $dependencies     = array();
  $buildingdep      = array();

  if (!$shortVersion) {
    foreach ($unit->buildingDepList as $key => $level)
      if ($level)
        array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                       'level' => "&gt;= " . $level));
    $defensesystemdep = array();
    foreach ($unit->defenseSystemDepList as $key => $level)
      if ($level)
        array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                            'level' => "&gt;= " . $level));
    $resourcedep      = array();
    foreach ($unit->resourceDepList as $key => $level)
      if ($level)
        array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                       'level' => "&gt;= " . $level));
    $sciencedep       = array();
    foreach ($unit->scienceDepList as $key => $level)
      if ($level)
        array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                      'level' => "&gt;= " . $level));
    $unitdep          = array();
    foreach ($unit->unitDepList as $key => $level)
      if ($level)
        array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                   'level' => "&gt;= " . $level));


    foreach ($unit->maxBuildingDepList as $key => $level)
      if ($level != -1)
        array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                       'level' => "&lt;= " . $level));

    foreach ($unit->maxDefenseSystemDepList as $key => $level)
      if ($level != -1)
        array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                            'level' => "&lt;= " . $level));

    foreach ($unit->maxResourceDepList as $key => $level)
      if ($level != -1)
        array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                       'level' => "&lt;= " . $level));

    foreach ($unit->maxScienceDepList as $key => $level)
      if ($level != -1)
        array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                      'level' => "&lt;= " . $level));

    foreach ($unit->maxUnitDepList as $key => $level)
      if ($level != -1)
        array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                   'level' => "&lt;= " . $level));


    if (sizeof($buildingdep))
      array_push($dependencies, array('name' => _('Erweiterungen'),
                                      'DEP'  => $buildingdep));

    if (sizeof($defensesystemdep))
      array_push($dependencies, array('name' => _('Verteidigungsanlagen'),
                                      'DEP'  => $defensesystemdep));

    if (sizeof($resourcedep))
      array_push($dependencies, array('name' => _('Rohstoffe'),
                                      'DEP'  => $resourcedep));

    if (sizeof($sciencedep))
      array_push($dependencies, array('name' => _('Forschungen'),
                                      'DEP'  => $sciencedep));

    if (sizeof($unitdep))
      array_push($dependencies, array('name' => _('Einheiten'),
                                      'DEP'  => $unitdep));
  }

  if ($unit->visible != 1) {
    $template->addVar('INVISIBLE', array('text' => _('unsichtbar')));
  }

  $template->addVars(array(
    'name'          => $unit->name,
    'dbFieldName'   => $unit->dbFieldName,
    'description'   => $unit->description,
    'RESOURCECOST'  => $resourceCost,
    'UNITCOST'      => $unitCost,
    'BUILDINGCOST'  => $buildingCost,
    'DEFENSECOST'   => $defenseCost,
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
    'fuelName'      => $resourceTypeList[1]->dbFieldName,
    'fuelFactor'    => $unit->foodCost,
    'wayCost'       => $unit->wayCost,
    'DEPGROUP'      => $dependencies,
    'duration'      => time_formatDuration(eval('return '.formula_parseToPHP($unit->productionTimeFunction.";", '$details')) * BUILDING_TIME_BASE_FACTOR),
    'rules_path'    => RULES_PATH
  ));
}

?>