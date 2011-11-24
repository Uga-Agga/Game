<?php
/*
 * tribeLeaderDetermination.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribeChooseLeader_getContent($playerID, $tribe) {
  global $request, $template;
  global $governmentList, $leaderDeterminationList;

  // open template
  $template->setFile('tribeChooseLeader.tmpl');
  
  if (!($governmentData = government_getGovernmentForTribe($tribe))) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }
  
  $id = $governmentList[$governmentData['governmentID']]['leaderDeterminationID'];
  if ($id == 1) {
    $template->addVar('no_choose', array(
      'message'     =>  _('Ihr habt keinen Einfluss auf die Bestimmung des Stammesanführers.'),
      'name'        => $leaderDeterminationList[$id]['name'],
      'description' => $leaderDeterminationList[$id]['description'],
    ));
  } else {
    $messageText = array(
      -1 => array('type' => 'error', 'message' => _('Die Stimme konnte wegen eines Fehlers nicht abgegeben werden.')),
       1 => array('type' => 'success', 'message' => _('Die Stimme wurde erfolgreich gezählt.'))
    );

    $template->addVar('choose', array(
      'message'     =>  _('Ihr habt keinen Einfluss auf die Bestimmung des Stammesanführers.'),
      'name'        => $leaderDeterminationList[$id]['name'],
      'description' => $leaderDeterminationList[$id]['description'],
    ));

    $data = $request->getVar('data', array('' => ''));
    if ($data) {
      $messageID = leaderChoose_processChoiceUpdate($playerID, $data['playerID'], $tribe);
    }

    $choice = leaderChoose_getVoteOf($playerID);
    $possibleChoices = tribe_getAllMembers($tribe);
    $possibleChoices[0] = array ('name' => _('Keiner'), 'playerID' => 0);
    foreach ($possibleChoices AS $key => $value) {
      if ($key == $choice) {
        $possibleChoices[$key]['selected'] = 'selected="selected"';
      }
    }

    $votes = leaderChoose_getElectionResultsForTribe($tribe);
    foreach ($votes AS $key => $value) {
      if ($key == $choice) {
        $votes[$key]['selected'] = 'selected="selected"';
      }
    }

    $template->addVars(array(
      'leader_choose'    => $votes,
      'possible_choices' => $possibleChoices,
      'relations'        => (isset($relations)) ? $relations : array(),
      'status_msg'       => (isset($messageID)) ? $messageText[$messageID] : '',
    ));
  }
}

function leaderDetermination_electionHandler($playerID, $tribe, $governmentData) {
  global $leaderDeterminationList, $governmentList;


  $content = array (
    "name"    => $leaderDeterminationList[$id]['name'],
    "VOTES"   => $votes,
    "CHOICE"  => $choiceData
    );

  if (array_key_exists($message, $messages))
    $content['MESSAGE/message'] = $messages[$message];

}

?>