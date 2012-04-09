<?php
/*
 * tribe.html.php -
 * Copyright (c) 2004  OGP-Team
 * Copyright (c) 2011-2012  David Unger
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
define('TRIBE_ACTION_DONATE',        5);

function tribe_getContent($i, $y, &$details) {
  global $template;

  // messages
  $messageText = array (
   -22 => array('type' => 'error', 'message' => _('Nicht genug Rohstoffe vorhanden!')),
   -21 => array('type' => 'error', 'message' => _('Eine Rohstoff hat den maximalen Einzahlungswert überschritten!')),
   -20 => array('type' => 'error', 'message' => _('Fehler beim Eintragen ins Stammeslager!')),
   -19 => array('type' => 'info', 'message' => _('Bitte Daten in das Formular eintragen.')),
   -18 => array('type' => 'error', 'message' => _('Du keine Berechtigung eine Nachricht zu schreiben.')),
   -17 => array('type' => 'error', 'message' => _('Du mußt eine Nachricht schreiben um sie versenden zu können.')),
   -16 => array('type' => 'error', 'message' => _('Du bist zur Zeit in keinem Stamm.')),
   -15 => array('type' => 'error', 'message' => _('Du kannst keinen Stamm gründen während du in einem Stamm bist.')),
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
    10 => array('type' => 'error', 'message' => _('Dieser Stammesname ist nicht erlaubt!')), 
    11 => array('type' => 'success', 'message' => _('Einzahlung in das Stammeslager erfolgreich durchgeführt!'))
  );

  if (!$_SESSION['player']->tribe) {
    $template->throwError('Du bist zur Zeit in keinem Stamm.');
  }

  $tribeData = tribe_getTribeByTag($_SESSION['player']->tribe);
  if ($tribeData == null) {
    $template->throwError('Der Stamm konnte nicht geladen werden.');
    return;
  }

  // open template
  $template->setFile('tribeMember.tmpl');
  $template->setShowRresource(true);

  // init auth
  $auth = new auth;
  $userAuth = $auth->getAllTypePermission('tribe', $_SESSION['player']->auth['tribe']);
  $userAuth['isLeader'] = ($tribeData['leaderID'] == $_SESSION['player']->playerID) ? true : false;

  // process form data
  $messageID = 0;
  $tribeAction =  Request::getVar('action', 0);
  switch ($tribeAction) {
    case TRIBE_ACTION_DONATE:
      $value = Request::getVar('value', array('' => ''));
      $messageID = tribe_donateResources($value, $caveID, $caveData);
    break;

    case TRIBE_ACTION_LEAVE:
      if (Request::isPost('cancelOrderConfirm')) {
        $messageID = tribe_processLeave($playerID, $tribe);

        if ($messageID > 0) {
          page_refreshUserData();
          $template->throwMessage($messageText[$messageID]);
        }
      } else {
        $template->addVars(array(
          'confirm_box' => true,
          'confirm_action'  => TRIBE_ACTION_LEAVE,
          'confirm_id'      => false,
          'confirm_mode'    => TRIBE,
          'confirm_msg'     => sprintf(_('Möchtest du den Stamm <span class="bold">%s</span> wirklich verlassen?'), $_SESSION['player']->tribe),
        ));
      }
    break;

    case TRIBE_ACTION_MESSAGE:
      if (!$userAuth['msg_tribe'] && !$userAuth['msg_public'] && !$userAuth['isLeader']) {
        $messageID = -18;
        break;
      }
      
      if (!Request::isPost('messageText', true)) {
        $messageID = -17;
        break;
      }

      if (Request::isPost('ingame') && ($userAuth['msg_public']) || $userAuth['isLeader']) {
        $messageID = tribe_processSendTribeIngameMessage($playerID, $tribe, Request::getVar('messageText', '', true));
      } else if ($userAuth['msg_tribe'] || $userAuth['isLeader']) {
        $messageID = tribe_processSendTribeMessage($playerID, $tribe, Request::getVar('messageText', '', true));
      } else {
        $messageID = -18;
      }
    break;
  }

  /****************************************************************************************************
  *
  * Stammeslager
  *
  ****************************************************************************************************/
  $tribeStorageValues = array();
  foreach ($GLOBALS['resourceTypeList'] as $resourceID => $resource) {
    $tribeStorageValues[$resource->dbFieldName]['name'] = $resource->name;
    $tribeStorageValues[$resource->dbFieldName]['value'] = $tribeData[$resource->dbFieldName];
    $tribeStorageValues[$resource->dbFieldName]['dbFieldName'] = $resource->dbFieldName;
    $tribeStorageValues[$resource->dbFieldName]['maxTribeDonation'] = $resource->maxTribeDonation;
  }

  $lastDonation = tribe_getLastDonationForTribeStorage($_SESSION['player']->playerID);
  $template->addVars(array(
      'showTribeStorageDonations' => ($lastDonation == NULL || time() >= ($lastDonation + TRIBE_STORAGE_DONATION_INTERVAL*60*60)), 
      'tribeStorageValues'        => $tribeStorageValues, 
      'donationInterval'          => TRIBE_STORAGE_DONATION_INTERVAL, 
      'nextDonation'              => (!(time() >= ($lastDonation + TRIBE_STORAGE_DONATION_INTERVAL*60*60)) ? date("d.m.Y H:i:s",$lastDonation+TRIBE_STORAGE_DONATION_INTERVAL*60*60) : NULL)
  ));

  /****************************************************************************************************
  *
  * Einzahlungen
  *
  ****************************************************************************************************/
  $donations = tribe_getTribeStorageDonations($tribeData['tag']);
  $template->addVar('donations', $donations);

  /****************************************************************************************************
  *
  * Auswahl der Regierungsformen
  *
  ****************************************************************************************************/
  $tribeGovernment = government_getGovernmentForTribe($_SESSION['player']->tribe);
  if (empty($tribeGovernment)) {
    $template->throwError('Fehler beim Auslesen der Regierungsform.');
    return;
  }
  $tribeGovernment['name'] = $GLOBALS['governmentList'][$tribeGovernment['governmentID']]['name'];

  if ($userAuth['isLeader'] && $tribeGovernment['isChangeable']) {
    $GovernmentSelect = array();
    foreach($GLOBALS['governmentList'] AS $governmentID => $typeData) {
      $GovernmentSelect[] = array(
        'value'    => $governmentID,
        'selected' => ($governmentID == $tribeGovernment['governmentID'] ? 'selected="selected"' : ''),
        'name'     => $typeData['name']
      );
    }
    $template->addVar('government_select', $GovernmentSelect);
  } else {
    $template->addVar('government_data', array('name' => $tribeGovernment['name'], 'duration' => $tribeGovernment['time']));
  }

  /****************************************************************************************************
  *
  * Auslesen der Stammesnachrichten
  *
  ****************************************************************************************************/
  $messagesClass = new Messages;

  $messageAry = array();
  $messages = tribe_getTribeMessages($_SESSION['player']->tribe);
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

  /****************************************************************************************************
  *
  * Auslesen und Anzeigen der Beziehungen
  *
  ****************************************************************************************************/
  $relationsAll = relation_getRelationsForTribe($_SESSION['player']->tribe);
  $relationsWar = relation_getWarTargetsAndFame($_SESSION['player']->tribe);

  // Allgemein -> Allgemeines
  // Regierung -> Beziehungen
  $relations = $relationAlly = $relations_info = array();
  foreach($relationsAll['own'] AS $target => $targetData) {
    if (in_array($targetData['relationType'], Config::tribeRelationAlly)) {
      $relationAlly[] = $targetData;
    }

    if (!$targetData['changeable']) {
      $relation_info[$target] = array(
        'tag'            => $target,
        'relation'       => $GLOBALS['relationList'][$targetData['relationType']]['name'],
        'duration'       => $targetData['time'],
        'their_relation' => (isset($tribeRelations['other'][$target])) ? $GLOBALS['relationList'][$tribeRelations['other'][$target]['relationType']]['name'] : $GLOBALS['relationList'][0]['name']
      );

      // war?
      if (array_key_exists($target, $tribeWarTargets)) {
        $relations_info[$target]['war']            = true;
        $relations_info[$target]['fame_own']       = $tribeWarTargets[$target]['fame_own'];
        $relations_info[$target]['fame_target']    = $tribeWarTargets[$target]['fame_target'];
        $relations_info[$target]['percent_actual'] = $tribeWarTargets[$target]['percent_actual'];
      }
    } else {
      $relations[$target] = array(
        'tag'            => $target,
        'target_points'  => $targetData['target_rankingPoints'],
        'tribe_points'   => $targetData['tribe_rankingPoints'],
        'their_relation' =>  (isset($tribeRelations['other'][$target])) ? $GLOBALS['relationList'][$tribeRelations['other'][$target]['relationType']]['name'] : $GLOBALS['relationList'][0]['name'],
        'relation_type'  => $targetData['relationType'],
      );

      if (isset($tribeWarTargets[$target])) {
        $relations[$target]['war']            = true;
        $relations[$target]['fame_own']       = $tribeWarTargets[$target]['fame_own'];
        $relations[$target]['fame_target']    = $tribeWarTargets[$target]['fame_target'];
        $relations[$target]['percent_actual'] = $tribeWarTargets[$target]['percent_actual'];

        if ($wartarget['isForcedSurrenderTheoreticallyPossible']) {
          $relations[$target]['isForcePossible'] = true;
          $relations[$target]['percent_estimated'] = $tribeWarTargets[$target]['percent_estimated'];
        }
      }
    }
  }

  foreach($relationsAll['other'] AS $target => $targetData) {
    if (isset($relationsAll['own'][$target])) {
      continue;
    }

    $relations[$target] = array(
      'tag'            => $target,
      'their_relation' => $GLOBALS['relationList'][$relations['other'][$target]['relationType']]['name'],
      'duration'       => $targetData['time'],
      'relation_type'  => 0,
    );
  }

  /****************************************************************************************************
  *
  * Übergabe ans Template
  *
  ****************************************************************************************************/
  if ($messageID && isset($messageText[$messageID])) {
    $template->addVar('status_msg', $messageText[$messageID]);
  }

  $template->addVars(array(
    'tribe_name'        => $tribeData['name'],
    'tribe_tag'         => $tribeData['tag'],
    'tribe_leader_name' => $tribeData['leaderName'],
    'tribe_leader_id'   => $tribeData['leaderID'],
    'tribe_members'     => tribe_getAllMembers($_SESSION['player']->tribe),

    'government_name'   => $GLOBALS['governmentList'][$tribeData['governmentID']]['name'],

    'is_auth'           => $userAuth,

    'relations'         => (isset($relations)) ? $relations : array(),
    'relations_ally'    => $relationAlly,
    'relations_list'    => $GLOBALS['relationList'],
    'relations_info'    => (isset($relations_info)) ? $relations_info : array(),
    'relations_war'     => (!empty($relationsWar)) ? true : false,

    'tribe_action_message' => TRIBE_ACTION_MESSAGE,
    'tribe_action_donate'  => TRIBE_ACTION_DONATE
  ));
}




/*

  switch ($tribeAction) {
    case TRIBE_ACTION_JOIN:
      if (tribe_validatePassword(Request::getVar('password', '')) && tribe_validateTag(Request::getVar('tag', ''))) {
        $messageID = tribe_processJoin($playerID, Request::getVar('tag', ''), Request::getVar('password', ''));
        if ($messageID == 1) {
          $auth->setPermission('tribe', 0, $_SESSION['player']->playerID);
        }
      } else {
        $messageID = tribe_processJoinFailed();
      }
    break;

    case TRIBE_ACTION_CREATE:
      if (!empty($_SESSION['player']->tribe)) {
        $messageID = -15;
        break;
      }

      if (tribe_validatePassword(Request::getVar('password', '')) && tribe_validateTag(Request::getVar('tag', ''))){
        $messageID = tribe_processCreate($playerID, Request::getVar('tag', ''), Request::getVar('password', ''), Request::getVar('restore_rank', 'no') == 'yes');
      } else {
        $messageID = tribe_processCreateFailed();
      }
    break;

  }

  if ($tribeAction == TRIBE_ACTION_JOIN  || $tribeAction == TRIBE_ACTION_LEAVE || $tribeAction == TRIBE_ACTION_CREATE) {
    // the tribe might have changed
    page_refreshUserData();
    $tribe = $_SESSION['player']->tribe;
  }

    }
*/
?>