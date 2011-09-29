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

function tribeLeaderDetermination_getContent($playerID, $tribe) {
  global $template, $governmentList, $leaderDeterminationList;

  $template->throwError('Diese Seite wird noch überarbeitet.');
  return;

  $no_resource_flag = 1;

  if (!($governmentData = government_getGovernmentForTribe($tribe)))
    page_dberror();

  $handlers[1]  = "leaderDetermination_infoHandler";
  $handlers[2]  = "leaderDetermination_electionHandler";

  $templates[1] = "leaderDeterminationInfo.ihtml";
  $templates[2] = "leaderDeterminationElection.ihtml";

  $id = $governmentList[$governmentData['governmentID']]['leaderDeterminationID'];

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . $templates[$id]);


  if (!($templateData =
  $handlers[$id]($playerID,
           $tribe,
           $governmentData))) page_dberror();

  tmpl_set($template, 'LEADERDETERMINATION', $templateData);

  return tmpl_parse($template);
}

function leaderDetermination_infoHandler($playerID,
           $tribe,
           $governmentData) {
  global $leaderDeterminationList, $governmentList;

  $id = $governmentList[$governmentData['governmentID']]['leaderDeterminationID'];

  $content = array(
    "message"     => _('Sie haben keinen Einfluss auf die Bestimmung des Stammesanf&uuml;hrers.'),
    "name"        => $leaderDeterminationList[$id]['name'],
    "description" => $leaderDeterminationList[$id]['description']
    );

  return $content;
}

function leaderDetermination_electionHandler($playerID,
               $tribe,
               $governmentData) {
  global $leaderDeterminationList, $governmentList;

  $messages = array(
    -1 => _('Die Stimme konnte wegen eines Fehlers nicht abgegeben werden.'),
     1 => _('Die Stimme wurde erfolgreich gez&auml;hlt.')
    );

  $id = $governmentList[$governmentData['governmentID']]['leaderDeterminationID'];


  if ($data = request_var('data', array('' => ''))) {
    $message = leaderDetermination_processChoiceUpdate($playerID,
                   $data['playerID'],
                   $tribe);
  }

  $votes =
    leaderDetermination_getElectionResultsForTribe($tribe);

  $choice =
    leaderDetermination_getVoteOf($playerID);

  $possibleChoices =
    tribe_getAllMembers($tribe);

  $possibleChoices[0] = array (
    "name"     => _('Keiner'),
    "playerID" => 0
    );

  foreach($possibleChoices AS $key => $value) {
    if ($key == $choice) {
      $possibleChoices[$key]['selected'] = "selected";
    }
  }

  $choiceData = array (
    "modus_name" => "modus",
    "modus"      => TRIBE_LEADER_DETERMINATION,
    "dataarray"  => "data",
    "dataentry"  => "playerID",
    "OPTION"     => $possibleChoices,
    "caption"    => _('W&auml;hlen')
    );

  $content = array (
    "name"    => $leaderDeterminationList[$id]['name'],
    "VOTES"   => $votes,
    "CHOICE"  => $choiceData
    );

  if (array_key_exists($message, $messages))
    $content['MESSAGE/message'] = $messages[$message];

  return $content;
}

?>