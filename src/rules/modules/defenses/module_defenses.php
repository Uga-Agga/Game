<?php
/*
 * module_defenses.php -
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

function defenses_getSelector() {
  
  $defenseSystemTypeList = $GLOBALS['defenseSystemTypeList'];

  $defenses = array();
  foreach ($defenseSystemTypeList AS $key => $value) {
    if (!$value->nodocumentation) {
      $defensesID = request_var('defensesID', 0);

      $temp = array(
        'value'       => $value->defenseSystemID,
        'description' => lib_shorten_html($value->name, 20)
      );

      if (isset($_REQUEST['defensesID']) && $defensesID == $value->defenseSystemID) {
        $temp['selected'] = 'selected="selected"';
      }

      $defenses[] = $temp;
    }
  }
  usort($defenses, "descriptionCompare");

  return $defenses;
}

function defenses_getContent(){
  global $template;
  
  $defenseSystemTypeList = $GLOBALS['defenseSystemTypeList'];
  $resourceTypeList = $GLOBALS['resourceTypeList'];
  $unitTypeList = $GLOBALS['unitTypeList'];

  // open template
  $template->setFile('defenseSystem.tmpl');

  $id = request_var('defensesID', 0);
  if (!isset($defenseSystemTypeList[$id]) || $defenseSystemTypeList[$id]->nodocumentation) {
    $defenseSystem = $defenseSystemTypeList[0];
  } else {
    $defenseSystem = $defenseSystemTypeList[$id];
  }

  $resourceCost = array();
  foreach ($defenseSystem->resourceProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($resourceCost, array(
        'dbFieldName' => $resourceTypeList[$key]->dbFieldName,
        'name'        => $resourceTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $unitCost = array();
  foreach ($defenseSystem->unitProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($unitCost, array(
        'dbFieldName' => $unitTypeList[$key]->dbFieldName,
        'name'        => $unitTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $defenseCost = array();
  foreach ($defenseSystem->defenseProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($defenseCost, array(
        'dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
        'name'        => $defenseSystemTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $buildingCost = array();
  foreach ($defenseSystem->buildingProductionCost as $key => $value) {
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
    'name'           => $defenseSystem->name,
    'description'    => $defenseSystem->description,
    'maximum'        => formula_parseToReadable($defenseSystem->maxLevel),
    'productionTime' => "(".formula_parseToReadable($defenseSystem->productionTimeFunction) . ")*". DEFENSESYSTEM_TIME_BASE_FACTOR . " (in Sekunden)",
    'rangeAttack'    => $defenseSystem->attackRange,
    'attackRate'     => $defenseSystem->attackRate,
    'defenseRate'    => $defenseSystem->defenseRate,
    'size'           => $defenseSystem->hitPoints,
    'antiSpyChance'  => $defenseSystem->antiSpyChance,
    'dbFieldName'    => $defenseSystem->dbFieldName,
    'warpoints'      => $defenseSystem->warPoints,
    'resource_cost'  => $resourceCost,
    'dependencies'   => rules_checkDependencies($defenseSystem),
    'more_cost'      => (sizeof($moreCost)) ? $moreCost : false,
  ));
}
?>