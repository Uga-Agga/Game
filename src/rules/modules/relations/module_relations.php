<?php
/*
 * module_relations.php - 
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

function relations_getMenu() {
  $result[] = array('link' => "?modus=relations", 'content' => "Beziehungen");
  return $result;
}

function relations_getContent() {
  global $template, $relationList;

  // open template
  $template->setFile('relations.tmpl');

  foreach($relationList as $relationData) {
    $relationData['otherSideToName'] = (isset($relationList[$relationData['otherSideTo']]['name']) && $relationData['otherSideTo']) ? $relationList[$relationData['otherSideTo']]['name'] : '';
    foreach($relationData['transitions'] as $relationID => $v) {
      $relationData['transitions'][$relationID]['name'] = $relationList[$relationID]['name'];
    }
    $relations[] = $relationData;
  }

  $template->addVar('relation_data', $relations);
}
?>