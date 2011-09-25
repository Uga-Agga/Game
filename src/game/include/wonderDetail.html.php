<?php
/*
 * wonderDetail.html.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function wonder_getWonderDetailContent($wonderID, $caveData, $method) {

  global
    $buildingTypeList,
    $defenseSystemTypeList,
    $resourceTypeList,
    $scienceTypeList,
    $unitTypeList,
    $wonderTypeList,
    $template,
    $no_resource_flag,
    $config;

  // get wonder target text
  $uaWonderTargetText = WonderTarget::getWonderTargets();

  $no_resource_flag = 1;

  // first check whether that wonder should be displayed...
  $wonder = $wonderTypeList[$wonderID];
  if (!$wonder || ($wonder->nodocumentation &&
       rules_checkDependencies($wonder, $caveData) !== TRUE))
    $wonder = current($wonderTypeList);

  // open template
  if ($method == 'ajax') {
    $shortVersion = true;
    $template->setFile('wonderDetailAjax.tmpl');
  }
  else {
    $shortVersion = false;
    $template->setFile('wonderDetail.tmpl');    
  }

  // iterate ressourcecosts
  $resourcecost = array();
  foreach ($wonder->resourceProductionCost as $resourceID => $function){

    $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
    if ($cost)
      array_push($resourcecost,
     array(
                   'name'        => $resourceTypeList[$resourceID]->name,
                   'dbFieldName' => $resourceTypeList[$resourceID]->dbFieldName,
                   'value'       => $cost));
  }
  // iterate unitcosts
  $unitcost = array();
  foreach ($wonder->unitProductionCost as $unitID => $function){
    $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
    if ($cost)
      array_push($unitcost,
     array(
                   'name'        => $unitTypeList[$unitID]->name,
                   'dbFieldName' => $unitTypeList[$unitID]->dbFieldName,
                   'value'       => $cost));
  }

  $dependencies     = array();
  $buildingdep      = array();
  $defensesystemdep = array();
  $resourcedep      = array();
  $sciencedep       = array();
  $unitdep          = array();

  foreach ($wonder->buildingDepList as $key => $level)
    if ($level)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($wonder->defenseSystemDepList as $key => $level)
    if ($level)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&gt;= " . $level));

  foreach ($wonder->resourceDepList as $key => $level)
    if ($level)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($wonder->scienceDepList as $key => $level)
    if ($level)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&gt;= " . $level));

  foreach ($wonder->unitDepList as $key => $level)
    if ($level)
      array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                 'level' => "&gt;= " . $level));


  foreach ($wonder->maxBuildingDepList as $key => $level)
    if ($level != -1)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($wonder->maxDefenseSystemDepList as $key => $level)
    if ($level != -1)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&lt;= " . $level));

  foreach ($wonder->maxResourceDepList as $key => $level)
    if ($level != -1)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($wonder->maxScienceDepList as $key => $level)
    if ($level != -1)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&lt;= " . $level));

  foreach ($wonder->maxUnitDepList as $key => $level)
    if ($level != -1)
      array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                 'level' => "&lt;= " . $level));

  if (sizeof($buildingdep))
    array_push($dependencies, array('name' => _('Erweiterungen'),
                                    'DEP'  => $wonderdep));

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


  $target = $uaWonderTargetText[$wonder->target];
  $chance = eval('return '. formula_parseToPHP($wonder->chance . ';', '$caveData'));

  $template->addVars(array('name'          => $wonder->name,
                           'ID'   => $wonder->wonderID,
                           'chance'        => $chance,
                           'offensiveness' => $wonder->offensiveness,
                           'target'        => $target,
                           'description'   => $wonder->description,
                           'RESOURCECOST'  => $resourcecost,
                           'UNITCOST'      => $unitcost,
                           'DEPGROUP'      => $dependencies,
                           'rules_path'    => RULES_PATH));

}

?>