<?php
/*
 * module_wonders.php -
 * Copyright (c) 2003  OGP-Team
 * Copyright (c) 2011-2013  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('wonder.inc.php');

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');
function wonders_getSelector() {

  $wonders = array();
  foreach ($GLOBALS['wonderTypeList'] AS $key => $value) {
    if ($value->nodocumentation || $value->isTribeCaveWonder) {
      continue;
    }

    $wonderID = request_var('wondersID', 0);

    $temp = array(
      'value'       => $value->wonderID,
      'description' => lib_shorten_html($value->name, 20)
    );

    if (isset($_REQUEST['wondersID']) && $wonderID == $value->wonderID) {
      $temp['selected'] = 'selected="selected"';
    }

    $wonders[] = $temp;
  }
  usort($wonders, "descriptionCompare");

  return $wonders;
}

function wonders_getContent() {
  global $template;

  // open template
  $template->setFile('wonder.tmpl');

  $id = request_var('wondersID', 0);
  if (!isset($GLOBALS['wonderTypeList'][$id]) || $GLOBALS['wonderTypeList'][$id]->nodocumentation || !$GLOBALS['wonderTypeList'][$id]->isTribeCaveWonder) {
    $wonder = $GLOBALS['wonderTypeList'][0];
  } else {
    $wonder = $GLOBALS['wonderTypeList'][$id];
  }

  $uaWonderTargetText = WonderTarget::getWonderTargets();

  $resourceCost = array();
  foreach ($wonder->resourceProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($resourceCost, array(
        'dbFieldName' => $GLOBALS['resourceTypeList'][$key]->dbFieldName,
        'name'        => $GLOBALS['resourceTypeList'][$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $unitCost = array();
  foreach ($wonder->unitProductionCost as $key => $value) {
    if ($value != "" && $value != "0") {
      array_push($unitCost, array(
        'dbFieldName' => $GLOBALS['unitTypeList'][$key]->dbFieldName,
        'name'        => $GLOBALS['unitTypeList'][$key]->name,
        'amount'      => formula_parseToReadable($value)
      ));
    }
  }

  $moreCost = array_merge($unitCost);
  $template->addVars(array(
    'name'           => $wonder->name,
    'offensiveness'  => $wonder->offensiveness,
    'description'    => $wonder->description,
    'chance'         => round(eval('return '.formula_parseBasic($wonder->chance).';'), 3),
    'target'         => $uaWonderTargetText[$wonder->target],
    'resource_cost'  => $resourceCost,
    'dependencies'   => rules_checkDependencies($wonder),
    'more_cost'      => (sizeof($moreCost)) ? $moreCost : false,
  ));
}
?>