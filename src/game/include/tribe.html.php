<?php
/*
 * tribe.html.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

define('TRIBE_ACTION_JOIN',          1);
define('TRIBE_ACTION_CREATE',        2);
define('TRIBE_ACTION_LEAVE',         3);
define('TRIBE_ACTION_MESSAGE',       4);

function tribe_getContent($playerID, $tribe) {
  global $template, $request, $governmentList;

  // messages
  $messageText = array (
   -14 => array('type' => 'error', 'message' => _('Nicht zulässiges Stammeskürzel oder Passwort.')),
   -13 => array('type' => 'error', 'message' => _('Der Stamm hat schon die maximale Anzahl an Mitgliedern.')),
   -12 => array('type' => 'error', 'message' => _('Der Stamm befindet sich gerade im Krieg und darf daher im Moment keine neuen Mitglieder aufnehmen.')),
   -11 => array('type' => 'error', 'message' => _('Die Stammeszugehörigkeit hat sich erst vor kurzem geändert. Warten Sie, bis die Stammeszugehörigkeit geändert werden darf.')),
   -10 => array('type' => 'error', 'message' => _('Ihr Stamm befindet sich im Krieg. Sie dürfen derzeit nicht austreten.')),
    -9 => array('type' => 'error', 'message' => _('Die Nachricht konnte nicht eingetragen werden.')),
    -8 => array('type' => 'error', 'message' => _('Sie sind der Stammesanführer und konnten nicht entlassen werden.')),
    -7 => array('type' => 'error', 'message' => _('Das Passwort konnte nicht gesetzt werden!')),
    -6 => array('type' => 'error', 'message' => _('Der Stamm konnte nicht angelegt werden.')),
    -5 => array('type' => 'error', 'message' => _('Es gibt schon einen Stamm mit diesem Kürzel.')),
    -4 => array('type' => 'error', 'message' => _('Sie konnten nicht austreten. Vermutlich gehören Sie gar keinem Stamm an.')),
    -3 => array('type' => 'error', 'message' => _('Sie konnten dem Stamm nicht beitreten. Vermutlich sind Sie schon bei einem anderen Stamm Mitglied.')),
    -2 => array('type' => 'error', 'message' => _('Stammeskürzel und Passwort stimmen nicht überein.')),
    -1 => array('type' => 'error', 'message' => _('Bei der Aktion ist ein unerwarteter Datenbankfehler aufgetreten!')),
     1 => array('type' => 'success', 'message' => _('Sie sind dem Stamm beigetreten.')),
     2 => array('type' => 'success', 'message' => _('Sie haben den Stamm verlassen.')),
     3 => array('type' => 'success', 'message' => _('Der Stamm wurde erfolgreich angelegt.')),
     4 => array('type' => 'success', 'message' => _('Sie waren das letzte Mitglied, der Stamm wurde aufgelöst')),
     5 => array('type' => 'success', 'message' => _('Die Nachricht wurde eingetragen')),
    10 => array('type' => 'error', 'message' => _('Dieser Stammesname ist nicht erlaubt!'))
  );

  // process form data
  $messageID = 0;
  $tribeAction =  $request->getVar('tribeAction', 0);
  switch ($tribeAction) {
    case TRIBE_ACTION_JOIN:
      if (tribe_validatePassword($request->getVar('password', '')) && tribe_validateTag($request->getVar('tag', ''))) {
        $messageID = tribe_processJoin($playerID, $request->getVar('tag', ''), $request->getVar('password', ''));
      } else {
        $messageID = tribe_processJoinFailed();
      }
    break;

    case TRIBE_ACTION_CREATE:
      if (tribe_validatePassword($request->getVar('password', '')) && tribe_validateTag($request->getVar('tag', ''))){
        $messageID = tribe_processCreate($playerID, $request->getVar('tag', ''), $request->getVar('password', ''), $request->getVar('restore_rank', 'no') == 'yes');
      } else {
        $messageID = tribe_processCreateFailed();
      }
    break;

    case TRIBE_ACTION_LEAVE:
      $messageID = tribe_processLeave($playerID, $tribe);
    break;

    case TRIBE_ACTION_MESSAGE:
      if (!$request->isPost('messageText', true)) {
        $messageID = -9;
        break;
      }
      $ingame = $request->isPost('ingame');

      if ($ingame){
        $messageID = tribe_processSendTribeIngameMessage($playerID, $tribe, $request->getVar('messageText', true));
      } else {
        $messageID = tribe_processSendTribeMessage($playerID, $tribe, $request->getVar('messageText', true));
      }
    break;
  }

  if ($tribeAction == TRIBE_ACTION_JOIN  || $tribeAction == TRIBE_ACTION_LEAVE || $tribeAction == TRIBE_ACTION_CREATE) {
    // the tribe might have changed
    page_refreshUserData();
    $tribe = $_SESSION['player']->tribe;
  }

// ----------------------------------------------------------------------------
// ------- SECTION FOR PLAYERS WITHOUT MEMBERSHIP -----------------------------

  if (empty($tribe)) {            // not a tribe member
    $template->setFile('tribe.tmpl');
    $template->setShowRresource(false);
  }

// ----------------------------------------------------------------------------
// ------- SECTION FOR TRIBE MEMBERS ------------- ----------------------------
  else {
    if (!($tribeData = tribe_getTribeByTag($tribe))) {
      $template->throwError('Der Stamm konnte nicht geladen werden.');
      return;
    }

    // open template
    $template->setFile('tribeMember.tmpl');
    $template->setShowRresource(false);

    if (tribe_isLeaderOrJuniorLeader($playerID, $tribe)) {
      $template->addVar('is_leader', true);
    }

    if($tribeData['juniorLeaderID']) {
      $juniorAdmin = new Player(getPlayerByID($tribeData['juniorLeaderID']));
    }
    else {
      $juniorAdmin = array();
    }

    $template->addVars(array(
      'tribe_name'   => $tribeData['name'],
      'tribe_tag'    => $tribeData['tag'],
      'leader_name'  => $tribeData['leaderName'],
      'leader_id'    => $tribeData['leaderID'],
      'junior_leader_name' => (isset($juniorAdmin->name)) ? $juniorAdmin->name : '',
      'junior_leader_id'   => (isset($juniorAdmin->playerID)) ? $juniorAdmin->playerID : 0,
      'government_name'    => $governmentList[$tribeData['governmentID']]['name']
    ));

    $targetFacts = array();
    $warTargets = relation_getWarTargetsAndFame($tribe);
    if (sizeof($warTargets)) {
      foreach($warTargets as $target) {
        $targetFact = array(
          'target'         =>  $target['target'],
          'fame_own'       =>  $target['fame_own'],
          'fame_target'    =>  $target['fame_target'],
          'percent_actual' =>  $target['percent_actual']
        );

        if ($target['isForcedSurrenderTheoreticallyPossible']) {
          $targetFact['percent_estimated'] = $target['percent_estimated'];
          if ($target['isForcedSurrenderPracticallyPossible']) {
            $targetFact['class'] = 'enough';
          } else if ($target['isForcedSurrenderPracticallyPossibleForTarget']) {
            $targetFact['class'] = 'less';
          } else {
            $targetFact['class'] = '';
          }
        }

        $targetFacts[] = $targetFact;
      }

      $template->addVar('target_facts', $targetFacts);
    }

    $relationAlly = array();
    $relationsAll = relation_getRelationsForTribe($tribeData['name']);
    if (sizeof($relationsAll)) {
      foreach ($relationsAll['own'] as $name => $relationTribe) {
        if ($relationTribe['relationType'] == RELATION_ALLY) {
          $relationAlly[] = $relationTribe;
        }
      }

      $template->addVar('tribe_relations_ally', $relationAlly);
    }

    // init messages class
    $messagesClass = new Messages;

    $messageAry = array();
    $messages = tribe_getTribeMessages($tribe);
    if (sizeof($messages)) {
      foreach($messages AS $msgID => $messageData) {
        $messageAry[] = array(
          'time'          => $messageData['date'],
          'subject'       => $messageData['messageSubject'],
          'message'       => $messagesClass->p($messageData['messageText']),
        );
      }

      $template->addVar('tribe_messages', $messageAry);
    }
  }

  if ($messageID && isset($messageText[$messageID])) {
    $template->addVar('status_msg', $messageText[$messageID]);
  }

  $template->addVars(array(
    'tribe_action_create'  => TRIBE_ACTION_CREATE,
    'tribe_action_join'    => TRIBE_ACTION_JOIN,
    'tribe_action_leave'   => TRIBE_ACTION_LEAVE,
    'tribe_action_message' => TRIBE_ACTION_MESSAGE,
  ));
}

?>