<?php
/*
 * tribeRelationList.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribeRelationList_getContent($tribe) {
  global  $config, $db, $template, $relationList;

  // open template
  $template->setFile('tribeRelationList.tmpl');
  $template->setShowRresource(false);
  $template->addVars(array(
    'show_page'  => true,
    'tribe_name' => $tribe,
  ));

  if (empty($tribe)) {
    $template->addVars(array(
      'status_msg' => array('type' => 'error', 'message' => 'Dieser Stamm wurde nicht gefunden.'),
      'show_page'  => false,
    ));
  }

  $relations = relation_getRelationsForTribe($tribe);
  $relationsData = array();
  if (isset($relations['own'])) {
    foreach($relations['own'] AS $target => $relationData) {
      $relationsData[$target] = array (
        'tribe'         => $relationData['tribe_target'],
        'relation_to'   => $relationList[$relationData['relationType']]['name'],
        'relation_from' => (isset($relations['other'][$target]) && $relations['other'][$target]) ? $relationList[$relations['other'][$target]['relationType']]['name'] : $relationList[0]['name'],
      );
    }
  }

  if (isset($relations['other'])) {
    foreach($relations['other'] AS $target => $relationData) {
      // already printed out this relation
      if (isset($relationsData[$target])) {
        continue;
      }

      $relationsData[$target] = array (
        'tribe'         => $relationData['tribe'],
        'relation_to'   => $relationList[$relationData['relationType']]['name'],
        'relation_from' => $relationList[0]['name'],
      );
    }
  }

  $template->addVar('relations_data', $relationsData);
}

?>