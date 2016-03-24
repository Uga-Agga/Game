<?php
/*
 * tribeDetail.html.php -
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011-2014 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribe_getContent($caveID, $tribeID) {
  global $db, $template;

  if (!$tribeID) {
    $template->throwError('Der Stamm wurde nicht gefunden.');
    return;
  }

  // open template
  $template->setFile('tribeDetail.tmpl');
  $template->setShowResource(false);

  $tribe = Tribe::getByID($tribeID);
  if ($tribe == null) {
    $template->throwError('Der Stamm wurde nicht gefunden.');
    return;
  }

  // parse tribe message
  $parser = new parser;
  $tribe['description'] = $parser->p($tribe['description']);

  $ranking = Tribe::getRanking($tribeID);
  $tribe['rank'] = $ranking;

  // leader
  if ($tribe['leaderID'] != 0) {
    $leader = Player::getPlayer($tribe['leaderID']);
    $tribe['leader_name'] = (!is_null($leader)) ? $leader->name : '';
  }

  $template->addVar('tribe_details', $tribe);

  // history
  $template->addVar('tribe_history', Tribe::getHistory($tribeID));

  // player list
  $template->addVar('tribe_player_list', Tribe::getPlayerList($tribeID, true, true));

  // relations
  $relations = TribeRelation::getRelations($tribeID);
  $relationsData = array();
  if (isset($relations['own'])) {
    foreach($relations['own'] AS $target => $relationData) {
      $relationsData[$target] = array (
        'tribe'          => $relationData['targetTag'],
        'tribeID_target' => $relationData['tribeID_target'],
        'relation_to'    => $GLOBALS['relationList'][$relationData['relationType']]['name'],
        'relation_from'  => (isset($relations['other'][$target]) && $relations['other'][$target]) ? $GLOBALS['relationList'][$relations['other'][$target]['relationType']]['name'] : $GLOBALS['relationList'][0]['name'],
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
        'tribe'         => $relationData['targetTag'],
        'tribeID_target' => $relationData['tribeID_target'],
        'relation_to'   => $GLOBALS['relationList'][0]['name'],
        'relation_from' => $GLOBALS['relationList'][$relationData['relationType']]['name'],
      );
    }
  }

  $template->addVar('relations_data', $relationsData);
}

?>