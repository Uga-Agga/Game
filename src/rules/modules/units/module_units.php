<?php
/*
 * moduleunits.php -
 * Copyright (c) 2003  OGP-Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function units_getSelector() {
  global $unitTypeList;

  $units = array();
  foreach ($unitTypeList AS $key => $value) {
    if (!$value->nodocumentation) {
      $unitID = request_var('unitsID', 0);

      $temp = array(
        'value'       => $value->unitID,
        'description' => lib_shorten_html($value->name, 20)
      );

      if (isset($_REQUEST['unitsID']) && $unitID == $value->unitID) {
        $temp['selected'] = 'selected="selected"';
      }

      $units[] = $temp;
    }
  }
  usort($units, "descriptionCompare");

  return $units;
}

function units_getContent() {
  global $template, $unitTypeList, $resourceTypeList;

  // open template
  $template->setFile('unit.tmpl');

  $id = request_var('unitsID', 0);
  if (!isset($unitTypeList[$id]) || $unitTypeList[$id]->nodocumentation) {
    $unit = $unitTypeList[0];
  } else {
    $unit = $unitTypeList[$id];
  }

  $resourceCost = array();
  foreach ($unit->resourceProductionCost as $key => $value) {
    if ($value != "" && $value != 0) {
      array_push($resourceCost, array(
        'dbFieldName' => $resourceTypeList[$key]->dbFieldName,
        'name'        => $resourceTypeList[$key]->name,
        'amount'      => $value
      ));
    }
  }

  $unitCost = array();
  foreach ($unit->unitProductionCost as $key => $value) {
    if ($value != "" && $value != 0) {
      array_push($unitCost, array(
        'dbFieldName' => $unitTypeList[$key]->dbFieldName,
        'name'        => $unitTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $defenseCost = array();
  foreach ($unit->defenseProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($defenseCost, array(
        'dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
        'name'        => $defenseSystemTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $buildingCost = array();
  foreach ($unit->buildingProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($externalCost, array(
        'dbFieldName' => $buildingTypeList[$key]->dbFieldName,
        'name'        => $buildingTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $moreCost = array_merge($unitCost, $defenseCost, $buildingCost);
  $template->addVars(array(
    'name'                      => $unit->name,
    'description'               => $unit->description,
    'productionTime'            => "(".formula_parseToReadable($unit->productionTimeFunction).")*".BUILDING_TIME_BASE_FACTOR." (in Sekunden)",
    'rangeAttack'               => $unit->attackRange,
    'arealAttack'               => $unit->attackAreal,
    'attackRate'                => $unit->attackRate,
    'meleeDefenseRate'          => $unit->defenseRate,
    'rangedDefenseRate'         => $unit->rangedDamageResistance,
    'size'                      => $unit->hitPoints,
    'warpoints'                 => $unit->warpoints,
    'antiSpyChance'             => $unit->antiSpyChance,
    'spyChance'                 => $unit->spyChance,
    'spyValue'                  => $unit->spyValue,
    'spyQuality'                => $unit->spyQuality,
    'dbFieldName'               => $unit->dbFieldName,
    'movement_speed'            => $unit->wayCost,
    'movement_cost'             => $unit->foodCost,
    'normalDamageProbabilit'    => 100 * (1-($unit->heavyDamageProbability + $unit->criticalDamageProbability)),
    'heavyDamageProbability'    => 100 * ($unit->heavyDamageProbability),
    'criticalDamageProbability' => 100 * ($unit->criticalDamageProbability),
    'resource_cost'             => $resourceCost,
    'dependencies'              => rules_checkDependencies($unit),
    'more_cost'                 => (sizeof($moreCost)) ? $moreCost : false,
  ));
}
?>