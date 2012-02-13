<?php
/*
 * module_building.php - 
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

function buildings_getSelector() {
  global $buildingTypeList;

  $buildings = array();
  foreach ($buildingTypeList AS $key => $value) {
    if (!$value->nodocumentation) {
      $buildingID = request_var('buildingsID', 0);

      $temp = array(
        'value'       => $value->buildingID,
        'description' => lib_shorten_html($value->name, 20)
      );

      if (isset($_REQUEST['buildingsID']) && $buildingID == $value->buildingID) {
        $temp['selected'] = 'selected="selected"';
      }

      $buildings[] = $temp;
    }
  }
  usort($buildings, "descriptionCompare");

  return $buildings;
}

function buildings_getContent() {
  global $template, $buildingTypeList, $resourceTypeList, $unitTypeList;

  // open template
  $template->setFile('building.tmpl');

  $id = request_var('buildingsID', 0);
  if (!isset($buildingTypeList[$id]) || $buildingTypeList[$id]->nodocumentation) {
    $building = $buildingTypeList[0];
  } else {
    $building = $buildingTypeList[$id];
  }

  $resourceCost = array();
  foreach ($building->resourceProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($resourceCost, array(
        'dbFieldName' => $resourceTypeList[$key]->dbFieldName,
        'name'        => $resourceTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $unitCost = array();
  foreach ($building->unitProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($unitCost, array(
        'dbFieldName' => $unitTypeList[$key]->dbFieldName,
        'name'        => $unitTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $defenseCost = array();
  foreach ($building->defenseProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($defenseCost, array(
        'dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
        'name'        => $defenseSystemTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $buildingCost = array();
  foreach ($building->buildingProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($buildingCost, array(
        'dbFieldName' => $buildingTypeList[$key]->dbFieldName,
        'name'        => $buildingTypeList[$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $moreCost = array_merge($unitCost, $defenseCost, $buildingCost);
  $template->addVars(array(
    'name'           => $building->name,
    'description'    => $building->description,
    'maximum'        => formula_parseToReadable($building->maxLevel),
    'productionTime' => "(".formula_parseToReadable($building->productionTimeFunction) . ")*". BUILDING_TIME_BASE_FACTOR . " (in Sekunden)" ,
    'dbFieldName'    => $building->dbFieldName,
    'resource_cost'  => $resourceCost,
    'dependencies'   => rules_checkDependencies($building),
    'more_cost'      => (sizeof($moreCost)) ? $moreCost : false,
  ));
}
?>