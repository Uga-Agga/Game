<?php
/*
 * module_resources.php -
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

function resources_getSelector() {
  global $resourceTypeList;

  $resources = array();
  foreach ($resourceTypeList AS $key => $value) {
    if (!$value->nodocumentation) {
      $resourceID = request_var('resourcesID', 0);

      $temp = array(
        'value'       => $value->resourceID,
        'description' => lib_shorten_html($value->name, 20)
      );

      if (isset($_REQUEST['resourcesID']) && $resourceID == $value->resourceID) {
        $temp['selected'] = 'selected="selected"';
      }

      $resources[] = $temp;
    }
  }
  usort($resources, "descriptionCompare");

  return $resources;
}

function resources_getContent(){
  global $template, $resourceTypeList;

  // open template
  $template->setFile('resource.tmpl');

  $id = request_var('resourcesID', 0);
  if (!isset($resourceTypeList[$id]) || $resourceTypeList[$id]->nodocumentation) {
    $resource = $resourceTypeList[0];
  } else {
    $resource = $resourceTypeList[$id];
  }

  $template->addVars(array(
    'name'         => $resource->name,
    'description'  => '', //$resource->description,
    'production'   => formula_parseToReadable($resource->resProdFunction),
    'max_storage'  => formula_parseToReadable($resource->maxLevel),
    'dbFieldName'  => $resource->dbFieldName,
    'DEPENDENCIES' => rules_checkDependencies($resource)
  ));
}
?>