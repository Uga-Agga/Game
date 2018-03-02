<?php
/*
 * tribe.html.php -
 * Copyright (c) 2004  OGP-Team
 * Copyright (c) 2011-2013  David Unger <unger.dave@gmail.com>
 * Copyright (c) 2011-2013 Georg Pitterle <georg.pitterle@gmail.com>
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
define('TRIBE_ACTION_UPDATE',        6);
define('TRIBE_ACTION_RELATION',      7);
define('TRIBE_ACTION_GOVERMENT',     8);
define('TRIBE_ACTION_CHOOSE_LEADER', 9);
define('TRIBE_ACTION_AUTH',         10);
define('TRIBE_ACTION_WONDER',       11);
define('TRIBE_ACTION_KICK',         12);
define('TRIBE_ACTION_CHAT_ADD',     13);
define('TRIBE_ACTION_CHAT_REMOVE',  14);
define('TRIBE_ACTION_CHAT_LOG',     15);

function tribe_getContent($caveID, &$details) {
  global $template;

  // messages
  $messageText = array (
    -50 => array('type' => 'error', 'message' => _('Der Stammesname ist blockiert und darf nicht benutzt werden.')),
    -49 => array('type' => 'error', 'message' => _('Fehler beim setzen der Log Einstellungen.')),
    -48 => array('type' => 'error', 'message' => _('Der Zielstamm hat noch nicht genug Mitglieder um Beziehungen eingehen zu dürfen')),
    -47 => array('type' => 'error', 'message' => _('Fehler beim entfernen der Spielerrechte.')),
    -46 => array('type' => 'error', 'message' => _('Der Stamm wurde nicht gefunden.')),
    -45 => array('type' => 'error', 'message' => _('Der Spieler wurde nicht gefunden.')),
    -44 => array('type' => 'error', 'message' => _('Beim hinzufügen der Spielerrechte ist ein Fehler aufgetreten.')),
    -43 => array('type' => 'error', 'message' => _('Die Regierung wurde nicht geändert, weil sie sich nicht geändert hat.')),
    -42 => array('type' => 'error', 'message' => _('Ein Rohstoff wurde erst vor kurzen eingelagert. Bitte warte bis du es erneut versucht.')),
    -41 => array('type' => 'error', 'message' => _('Beim kicken das Spielers ist ein Fehler aufgetreten.')),
    -40 => array('type' => 'error', 'message' => _('Der Stamm befindet sich zur Zeit in einem Krieg und es kann kein Spieler gekickt werden.')),
    -39 => array('type' => 'error', 'message' => _('Der Stammesführer kann nicht entlassen werden.')),
    -38 => array('type' => 'error', 'message' => _('Der Spieler wurde in dem Stamm nicht gefunden!')),
    -37 => array('type' => 'error', 'message' => _('Der Zielstamm besitzt nicht die benötigte Vorraussetzung um das Wunder erwirken zu können.')),
    -36 => array('type' => 'error', 'message' => _('Dieses Wunder wurde erst vor kurzen gewundert. Bitte warte etwas bevor du es erneut wunderst.')),
    -35 => array('type' => 'error', 'message' => _('Der gegnerische Stamm hat nicht genug Mitglieder um Stammeswunder bekommen zu können!')),
    -34 => array('type' => 'error', 'message' => _('Ihr Stamm hat nicht genug Mitglieder um Stammeswunder sprechen zu können!')),
    -33 => array('type' => 'error', 'message' => _('Beim erbitten des Stammeswunders ist ein Problem aufgetreten!')),
    -32 => array('type' => 'error', 'message' => _('Das Stammeswunder konnte nicht gewirkt werden.')),
    -31 => array('type' => 'error', 'message' => _('Die Rechte konnten nicht angewandt werden.')),
    -30 => array('type' => 'error', 'message' => _('Fehler in den Formulardaten!')),
    -29 => array('type' => 'error', 'message' => _('Die Stimme konnte wegen eines Fehlers nicht abgegeben werden.')),
    -28 => array('type' => 'error', 'message' => _('Die Regierung konnte nicht geändert werden, weil sie erst vor kurzem geändert wurde.')),
    -27 => array('type' => 'error', 'message' => _('Die Regierung konnte aufgrund eines Fehlers nicht aktualisiert werden')),
    -26 => array('type' => 'error', 'message' => _('Ihr Kriegsanteil ist nicht hoch genug, um den Gegner zur Aufgabe zu zwingen.')),
    -25 => array('type' => 'error', 'message' => _('Eure Untergebenen weigern sich, diese Beziehung gegenüber einem so großen Stamm einzugehen.')),
    -24 => array('type' => 'error', 'message' => _('Eure Untergebenen weigern sich, diese Beziehung gegenüber einem so kleinen Stamm einzugehen.')),
    -23 => array('type' => 'error', 'message' => _('Ihr habt mit dem anderen Stamm keinen gleichen Kriegsgegner.')),
    -22 => array('type' => 'error', 'message' => _('Die Beziehung des anderen Stammes erlauben kein Kriegsbündniss.')),
    -21 => array('type' => 'error', 'message' => _('Unsere aktuelle Beziehung erlaubt kein Kriegsbündniss.')),
    -20 => array('type' => 'error', 'message' => _('Von der derzeitigen Beziehung kann nicht direkt auf die ausgewählte Beziehungsart gewechselt werden.')),
    -19 => array('type' => 'error', 'message' => _('Die Mindestlaufzeit von der derzeitigen Beziehung läuft noch!')),
    -18 => array('type' => 'error', 'message' => _('Die Beziehung wurde nicht geändert, weil der ausgewählte Beziehungstyp bereits eingestellt ist.')),
    -17 => array('type' => 'error', 'message' => _('Die Beziehung konnte aufgrund eines Fehlers nicht aktualisiert werden.')),
    -16 => array('type' => 'error', 'message' => _('Der Stamm hat noch nicht genug Mitglieder um Beziehungen eingehen zu dürfen')),
    -15 => array('type' => 'error', 'message' => _('Den Stamm gibt es nicht!')),
    -14 => array('type' => 'error', 'message' => _('Zu sich selber kann man keine Beziehungen aufnehmen!')),
    -13 => array('type' => 'error', 'message' => _('Ungültiges Bild oder URL beim Avatar! Wird zurückgesetzt!')),
    -12 => array('type' => 'error', 'message' => _('Ungültiges Passwort! (Mind. 6 Zeichen, ohne Sonderzeichen)')),
    -11 => array('type' => 'error', 'message' => _('Fehler beim Eintragen ins Stammeslager!')),
    -10 => array('type' => 'error', 'message' => _('Nicht genug Rohstoffe vorhanden!')),
     -9 => array('type' => 'error', 'message' => _('Eine Rohstoff hat den maximalen Einzahlungswert überschritten!')),
     -8 => array('type' => 'info', 'message' => _('Bitte die gewünscht Menge an Rohstoffen die eingezahlt werden sollen angeben.')),
     -7 => array('type' => 'error', 'message' => _('Die Nachricht konnte nicht verschickt werden.')),
     -6 => array('type' => 'error', 'message' => _('Du mußt eine Nachricht schreiben um sie versenden zu können.')),
     -5 => array('type' => 'error', 'message' => _('Sie konnten nicht austreten. Vermutlich gehören Sie gar keinem Stamm an.')),
     -4 => array('type' => 'error', 'message' => _('Sie sind der Stammesanführer und konnten nicht entlassen werden.')),
     -3 => array('type' => 'error', 'message' => _('Die Stammeszugehörigkeit hat sich erst vor kurzem geändert. Warten Sie, bis die Stammeszugehörigkeit geändert werden darf.')),
     -2 => array('type' => 'error', 'message' => _('Ihr Stamm befindet sich im Krieg. Sie dürfen derzeit nicht austreten.')),
     -1 => array('type' => 'error', 'message' => _('Du hast keine Berechtigung dies zu tun.')),
      1 => array('type' => 'success', 'message' => _('Du hast den Stamm verlassen.')),
      2 => array('type' => 'success', 'message' => _('Du hast den Stamm erfolgreich verlassen.<br />Da du das letzte Mitglied warst, wurde der Stamm aufgelöst.')),
      3 => array('type' => 'success', 'message' => _('Die Nachricht wurde Erfolgreich verschickt.')),
      4 => array('type' => 'success', 'message' => _('Deine Rohstoffe wurden Erfolgreich ins Stammeslager eingezahlt!')),
      5 => array('type' => 'success', 'message' => _('Die Daten wurden erfolgreich aktualisiert.')),
      6 => array('type' => 'error', 'message' =>  _('Die Daten konnten gar nicht oder zumindest nicht vollständig aktualisiert werden.')),
      7 => array('type' => 'success', 'message' => _('Die Beziehung zu dem anderen Stamm wurde erfolgreich geändert.')),
      8 => array('type' => 'success', 'message' => _('Die Regierung des Stammes wurde erfolgreich geändert.')),
      9 => array('type' => 'success', 'message' => _('Die Stimme wurde erfolgreich gezählt.')),
     10 => array('type' => 'success', 'message' => _('Der Spieler hat seine Rechte erfolgreich erhalten.')),
     11 => array('type' => 'info', 'message' => _('Die Götter haben Ihr Flehen nicht erhört! Die eingesetzten Opfergaben sind natürlich dennoch verloren. Mehr Glück beim nächsten Mal!')),
     12 => array('type' => 'success', 'message' => _('Das Erflehen des Wunders scheint Erfolg zu haben.')),
     13 => array('type' => 'success', 'message' => _('Der Spieler wurde erfolgreich gekickt.')),
     14 => array('type' => 'success', 'message' => _('Spielerrechte erfolgreich hinzugefügt. In kürze kann der Spieler den Raum betreten.')),
     15 => array('type' => 'success', 'message' => _('Spielerrechte erfolgreich hinzugefügt. In kürze können die Spieler den Raum betreten.')),
     16 => array('type' => 'success', 'message' => _('Spielerrechte erfolgreich entfernt. In kürze können der/die Spieler den Raum nicht mehr betreten.')),
     17 => array('type' => 'success', 'message' => _('Log Berechtigungen erfolgreich geändert.')),
  );

  if (!$_SESSION['player']->tribeID) {
    tribe_getContentNoTribe($caveID, $details);
    return;
  }

  $tribeID = $_SESSION['player']->tribeID;
  $tribeData = Tribe::getByID($tribeID);
  if ($tribeData == null) {
    $template->throwError('Der Stamm konnte nicht geladen werden.');
    return;
  }
  $tribeMembers = Tribe::getPlayerList($tribeID, true, true, 'p.name');

  // open template
  $template->setFile('tribeMember.tmpl');
  $template->setShowResource(true);

  // init auth
  $auth = new auth;
  $userAuth = $auth->getAllTypePermission('tribe', $_SESSION['player']->auth['tribe']);
  $userAuth['isLeader'] = ($tribeData['leaderID'] == $_SESSION['player']->playerID) ? true : false;

  // init Chat Channel
  $chatRooms = Chat::getRoomsByTribeID($tribeID);

  // process form data
  $tribeAction =  Request::getVar('action', 0);
  switch ($tribeAction) {
/****************************************************************************************************
*
* Spielerrechte Ändern
*
****************************************************************************************************/
    case TRIBE_ACTION_AUTH:
      if (!$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $playerID = Request::getVar('player_id', 0);

      if ($playerID == 0 || !isset($tribeMembers[$playerID])) {
        $messageID = -38;
        break;
      }

      $authID = 0;
      foreach ($auth->perm['tribe'] as $type => $data) {
        $authID = $authID | Request::getVar($type, 0);
      }

      $newUserAuth = $auth->setPermission('tribe', $authID, $playerID);
      if ($newUserAuth !== false) {
        $messageID = 10;
        $tribeMembers[$playerID]['tribeAuth'] = $auth->getAllTypePermission('tribe', $newUserAuth);
      } else {
        $messageID = -31;
      }
    break;

/****************************************************************************************************
*
* Auswahl des Anführers
*
****************************************************************************************************/
    case TRIBE_ACTION_CHOOSE_LEADER:
      $chooseLeaderID = Request::getVar('chooseLeaderID', 0);

      if ($chooseLeaderID != 0 && isset($tribeMembers[$chooseLeaderID])) {
        $messageID = TribeLeader::setChoice($chooseLeaderID, $_SESSION['player']->playerID, $tribeID);
        break;
      } else if ($chooseLeaderID == 0) {
        $messageID = TribeLeader::removeChoice($_SESSION['player']->playerID);
      } else {
        $messageID = -38;
      }
    break;

/****************************************************************************************************
*
* Ressie Spende an den Stamm
*
****************************************************************************************************/
    case TRIBE_ACTION_DONATE:
      $value = Request::getVar('value', array('' => ''));
      $messageID = TribeDonation::setDonations($value, $caveID, $details);

      $tribeData = Tribe::getByID($tribeID);
    break;

/****************************************************************************************************
*
* Regierungstyp ändern
*
****************************************************************************************************/
    case TRIBE_ACTION_GOVERMENT:
      if (!$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      if(!TribeGovernment::isChangeable($tribeID)) {
        $messageID = -28;
        break;
      }

      $governmentID = Request::getVar('governmentID', 0);
      if ($governmentID == 0) {
        $messageID = -27;
        break;
      }

      if ($governmentID == $tribeData['governmentID']) {
        $messageID = -43;
        break;
      }

      $messageID = TribeGovernment::setGovernment($tribeID, $governmentID);
    break;

/****************************************************************************************************
*
* bye bye Spieler
*
****************************************************************************************************/
    case TRIBE_ACTION_KICK:
      if (!$userAuth['kick_member'] && !$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $playerID = Request::getVar('playerID', 0);
      if ($playerID == 0 || !isset($tribeMembers[$playerID])) {
        $messageID = -38;
        break;
      }

      if ($tribeData['leaderID'] == $playerID) {
        $messageID = -39;
        break;
      }

      $messageID = Tribe::processKickMember($playerID, $tribeData);
      if ($messageID > 0) {
        unset($tribeMembers[$playerID]);
      }
    break;

/****************************************************************************************************
*
* Bye Bye Stamm :(
*
****************************************************************************************************/
    case TRIBE_ACTION_LEAVE:
      if (Request::isPost('postConfirm')) {
        $messageID = Tribe::processLeave($_SESSION['player']->playerID, $tribeData);

        if ($messageID > 0) {
          page_refreshUserData();
          $template->addVar('status_msg', $messageText[$messageID]);
          tribe_getContentNoTribe($caveID, $details);
          return;
        }
      } else {
        $template->addVars(array(
          'confirm_box' => true,
          'confirm_action'  => TRIBE_ACTION_LEAVE,
          'confirm_id'      => false,
          'confirm_mode'    => TRIBE,
          'confirm_msg'     => sprintf(_('Möchtest du den Stamm <span class="bold">%s</span> wirklich verlassen?'), $tribeTag),
        ));
      }
    break;

/****************************************************************************************************
*
* paar Spieler informieren über irgendwas
*
****************************************************************************************************/
    case TRIBE_ACTION_MESSAGE:
      if (!$userAuth['msg_tribe'] && !$userAuth['msg_public'] && !$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $message = Request::getVar('messageText', '', true);
      if (empty($message)) {
        $messageID = -6;
        break;
      }

      $button = Request::getVar('button', '');
      $messageType = Request::getVar('messageType', '');
      if ($button == 'ingame' && ($userAuth['msg_public'] || $userAuth['isLeader'])) {
        $messageID = TribeMessage::processSendPlayer($tribeID, $_SESSION['player']->name, $message);
      } else if ($button == 'tribemsg' && $userAuth['msg_tribe'] || $userAuth['isLeader']) {
        $messageID = TribeMessage::processSendIntern($tribeID, $_SESSION['player']->name, $message);
      } else {
        $messageID = -1;
      }
    break;

/****************************************************************************************************
*
* Krieg? Niederlage? Verbünden? Aktualisieren der Beziehung
*
****************************************************************************************************/
    case TRIBE_ACTION_RELATION:
      if (!$userAuth['change_relation'] && !$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $targetTribe = Request::getVar('targetTribe', '');
      $relationID = Request::getVar('relationID', 0);
      if (empty($targetTribe)) {
        $messageID = -15;
        break;
      }

      $targeData = Tribe::getID($targetTribe, true);
      if (empty($targeData)) {
        $messageID = -15;
        break;
      }

      $button = Request::getVar('button', '');
      if ($button == 'forceSurrender') {
        $messageID = TribeRelation::forceSurrender($tribeData, $targeData, $relationID);
      } else {
        $messageID = TribeRelation::processRelationUpdate($tribeData, $targeData, $relationID);
      }
    break;

/****************************************************************************************************
*
* Stammesinfos aktualisieren
*
****************************************************************************************************/
    case TRIBE_ACTION_UPDATE:
      if (!$userAuth['change_settings'] && !$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $password = Request::getVar('inputPassword', '');
      $postData = array(
        'name'        => Request::getVar('inputName', '', true),
        'password'    => (!empty($password)) ? $password : $tribeData['password'],
        'avatar'      => Request::getVar('inputAvatar', ''),
        'description' => Request::getVar('inputDescription', '', true)
      );

      $messageID = Tribe::updateTribeData($tribeID, $postData);
      $tribeData = Tribe::getByID($tribeID);
    break;

/****************************************************************************************************
*
* Stammeswunder?
*
****************************************************************************************************/
    case TRIBE_ACTION_WONDER:
      if (!$userAuth['wonder'] && !$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $wonderID = Request::getVar('wonderID', -1);
      $tribeName = Request::getVar('tribeName', '');

      if ($wonderID == -1) {
        $messageID = -32;
        break;
      }

      if (empty($tribeName)) {
        $messageID = -15;
        break;
      }

      if (isset($tribeData['wonderLocked'][$wonderID]) && $tribeData['wonderLocked'][$wonderID] > time()) {
        $messageID = -36;
        break;
      }

      $messageID = TribeWonder::processWonder($wonderID, $tribeData, $tribeName);
      if ($messageID > 0) {
        TribeWonder::setBlockedTime($tribeID, $wonderID, $tribeData['wonderLocked']);
      }

      if ($messageID == 11 || $messageID == 12) {
        $success = ($messageID == 12) ? 1 : 2;
        wonder_addStatistic($wonderID, $success);
      }

      $tribeData = Tribe::getByID($tribeID);
    break;

/****************************************************************************************************
*
* User/Stammes Rechte für den Chat vergeben
*
****************************************************************************************************/
    case TRIBE_ACTION_CHAT_ADD:
      if (!$userAuth['manage_chat'] && !$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $roomID = Request::getVar('roomID', 0);
      if (empty($roomID) || (!isset($chatRooms[$roomID]))) {
        if (!isset($chatRooms[$roomID])) {
          report_player();
        }

        $messageID = -30;
        break;
      }

      $user = Request::getVar('user', '');
      $tribe = Request::getVar('tribe', '');

      if (!empty($user)) {
        if (Chat::checkUserExists($user)) {
          if (Chat::authAdd($roomID, $user)) {
            $messageID = 14;
            break;
          } else {
            $messageID = -44;
            break;
          }
        } else {
          $messageID = -45;
          break;
        }
      } else if (!empty($tribe)) {
        $chatTribeID = Tribe::getID($tribe);

        if ($tribe !== false) {
          if (Chat::authAddTribe($roomID, $chatTribeID)) {
            $chatRooms = Chat::getRoomsByTribeID($tribeID);
            $messageID = 15;
            break;
          } else {
            $messageID = -44;
            break;
          }
        } else  {
          $messageID = -45;
          break;
        }
      } else {
        $messageID = -30;
        break;
      }
    break;

/****************************************************************************************************
*
* Userrechte entfernen
*
****************************************************************************************************/
    case TRIBE_ACTION_CHAT_REMOVE:
      if (!$userAuth['manage_chat'] && !$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $roomID = Request::getVar('roomID', 0);
      if (empty($roomID) || (!isset($chatRooms[$roomID]))) {
        if (!isset($chatRooms[$roomID])) {
          report_player();
        }

        $messageID = -30;
        break;
      }

      $removeIDs = Request::getVar('removeIDs', array('' => ''));

      if (!empty($removeIDs)) {
        if (Chat::authDel($roomID, $removeIDs)) {
          $chatRooms = Chat::getRoomsByTribeID($tribeID);
          $messageID = 16;
          break;
        } else {
          $messageID = -47;
          break;
        }
      } else {
        $messageID = -30;
        break;
      }
    break;

/****************************************************************************************************
*
* Stammeslog?
*
****************************************************************************************************/
    case TRIBE_ACTION_CHAT_LOG:
      if (!$userAuth['manage_chat'] && !$userAuth['isLeader']) {
        $messageID = -1;
        break;
      }

      $roomID = Request::getVar('roomID', 0);
      $chatLogType = Request::getVar('chatLogType', 0);

      if (empty($roomID) || (!isset($chatRooms[$roomID]))) {
        if (!isset($chatRooms[$roomID])) {
          report_player();
        }

        $messageID = -30;
        break;
      }

      if (!filter_var($chatLogType, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 3)))) {
        $messageID = -30;
        break;
      }
      
      if (!in_array($chatLogType, [1, 2, 3])) {
		$messageID = -48;
      } else {
		if (Chat::setLogType($roomID, $chatLogType)) {
			$chatRooms = Chat::getRoomsByTribeID($tribeID);
			$messageID = 17;
		} else {
			$messageID = -48;
		}
      }
    break;
  }

  /****************************************************************************************************
  *
  * Auswahl der Regierungsformen
  *
  ****************************************************************************************************/
  $tribeGovernment = TribeGovernment::getGovernment($tribeID);
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

  if ($tribeGovernment['governmentID'] == 2) {
    $choice = TribeLeader::getVotes($_SESSION['player']->playerID);
    $votes  = TribeLeader::getElection($tribeID);

    $possibleChoices = $tribeMembers;
    $possibleChoices[0] = array ('name' => _('Keiner'), 'playerID' => 0);
    foreach ($possibleChoices AS $key => $value) {
      if ($key == $choice) {
        $possibleChoices[$key]['selected'] = 'selected="selected"';
      }
    }
    ksort($possibleChoices);

    $template->addVars(array(
      'goverment_votes_list'         => $votes,
      'goverment_choice_list'        => $possibleChoices,
      'goverment_choice_name'        => $GLOBALS['leaderDeterminationList'][$tribeGovernment['governmentID']]['name'],
      'goverment_choice_description' => $GLOBALS['leaderDeterminationList'][$tribeGovernment['governmentID']]['description'],
    ));
  } else {
    $template->addVars(array(
      'choose'                       => false,
      'goverment_choice_message'     =>  _('Ihr habt keinen Einfluss auf die Bestimmung des Stammesanführers.'),
      'goverment_choice_name'        => $GLOBALS['leaderDeterminationList'][$tribeGovernment['governmentID']]['name'],
      'goverment_choice_description' => $GLOBALS['leaderDeterminationList'][$tribeGovernment['governmentID']]['description'],
    ));
  }

  /****************************************************************************************************
  *
  * Auslesen der Stammesnachrichten
  *
  ****************************************************************************************************/
  $messagesClass = new Messages;

  $messageAry = array();
  $messages = TribeMessage::getMessages($tribeID);
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
  $relationsAll = TribeRelation::getRelations($tribeID);
  $relationsWar = TribeRelation::getWarRelations($tribeID);

  // Allgemein -> Allgemeines
  // Regierung -> Beziehungen
  $relations = $relationAlly = $relations_info = array();
  foreach($relationsAll['own'] AS $target => $targetData) {
    if (in_array($targetData['relationType'], Config::$tribeRelationAlly)) {
      $relationAlly[] = $targetData;
    }

    if (!$targetData['changeable']) {
      $relations_info[$target] = array(
        'tag'            => $targetData['targetTag'],
        'relation'       => $GLOBALS['relationList'][$targetData['relationType']]['name'],
        'duration'       => $targetData['time'],
        'their_relation' => (isset($relationsAll['other'][$target])) ? $GLOBALS['relationList'][$relationsAll['other'][$target]['relationType']]['name'] : $GLOBALS['relationList'][0]['name']
      );

      // war?
      if (isset($relationsWar[$target])) {
        $relations_info[$target]['war']            = true;
        $relations_info[$target]['fame_own']       = $relationsWar[$target]['fame_own'];
        $relations_info[$target]['fame_target']    = $relationsWar[$target]['fame_target'];
        $relations_info[$target]['percent_actual'] = $relationsWar[$target]['percent_actual'];
      }
    } else {
      $relations[$target] = array(
        'tag'            => $targetData['targetTag'],
        'target_points'  => $targetData['target_rankingPoints'],
        'tribe_points'   => $targetData['tribe_rankingPoints'],
        'their_relation' =>  (isset($relationsAll['other'][$target])) ? $GLOBALS['relationList'][$relationsAll['other'][$target]['relationType']]['name'] : $GLOBALS['relationList'][0]['name'],
        'relation_type'  => $targetData['relationType'],
      );

      if (isset($relationsWar[$target])) {
        $relations[$target]['war']            = true;
        $relations[$target]['fame_own']       = $relationsWar[$target]['fame_own'];
        $relations[$target]['fame_target']    = $relationsWar[$target]['fame_target'];
        $relations[$target]['percent_actual'] = $relationsWar[$target]['percent_actual'];

        if ($relationsWar[$target]['isForcedSurrenderTheoreticallyPossible']) {
          $relations[$target]['isForcePossible'] = true;
          $relations[$target]['percent_estimated'] = $relationsWar[$target]['percent_estimated'];
        }
      }
    }
  }

  foreach($relationsAll['other'] AS $target => $targetData) {
    if (isset($relationsAll['own'][$target])) {
      continue;
    }

    $relations[$target] = array(
      'tag'            => $targetData['targetTag'],
      'their_relation' => $GLOBALS['relationList'][$relationsAll['other'][$target]['relationType']]['name'],
      'duration'       => $targetData['time'],
      'relation_type'  => 0,
    );
  }

  /****************************************************************************************************
  *
  * Stammeslager
  *
  ****************************************************************************************************/
  $tribeStorageValues = $tribeStorage = array();
  $dontePossible = false;
  foreach ($GLOBALS['resourceTypeList'] as $resourceID => $resource) {
    if ($resource->maxTribeDonation == 0) {
      continue;
    }

    $tribeStorage[$resource->dbFieldName] = $tribeData[$resource->dbFieldName];
    $tribeStorageValues[$resource->dbFieldName]['resourceID'] = $resource->resourceID;
    $tribeStorageValues[$resource->dbFieldName]['name'] = $resource->name;
    $tribeStorageValues[$resource->dbFieldName]['value'] = $tribeData[$resource->dbFieldName];
    $tribeStorageValues[$resource->dbFieldName]['dbFieldName'] = $resource->dbFieldName;
    $tribeStorageValues[$resource->dbFieldName]['maxTribeDonation'] = $resource->maxTribeDonation;

    if (!isset($_SESSION['player']->donateLocked['tribe'][$resource->dbFieldName]) || empty($_SESSION['player']->donateLocked['tribe'][$resource->dbFieldName])) {
      $tribeStorageValues[$resource->dbFieldName]['lastDonate'] = '';
      $tribeStorageValues[$resource->dbFieldName]['donatePossible'] = true;
      $dontePossible = true;
    } else {
      $tribeStorageValues[$resource->dbFieldName]['lastDonate'] = date("d.m. H:i:s", $_SESSION['player']->donateLocked['tribe'][$resource->dbFieldName]);

      if ($_SESSION['player']->donateLocked['tribe'][$resource->dbFieldName] < time()) {
        $tribeStorageValues[$resource->dbFieldName]['donatePossible'] = true;
        $dontePossible = true;
      } else {
        $tribeStorageValues[$resource->dbFieldName]['donatePossible'] = false;
      }
    }
  }

  $template->addVars(array(
      'tribeStorageValues'        => $tribeStorageValues, 
      'donationInterval'          => TRIBE_STORAGE_DONATION_INTERVAL, 
      'showTribeStorageDonations' => $dontePossible
  ));

  /****************************************************************************************************
  *
  * Einzahlungen
  *
  ****************************************************************************************************/
  $donations = TribeDonation::getDonations($tribeID);
  $template->addVar('donations', $donations);

  /****************************************************************************************************
  *
  * Stammeswunder
  *
  ****************************************************************************************************/
  $wonders = array();
  $memberCount = count($tribeMembers);
  foreach ($GLOBALS['wonderTypeList'] as $wonder) {
    // exclude nonTribeWonders
    if (!$wonder->isTribeWonder || $wonder->nodocumentation) {
      continue;
    }

    // multiply costs with number of tribe members
    foreach($wonder->resourceProductionCost as $prodID => $prod) {
      $wonder->resourceProductionCost[$prodID] = $prod * $memberCount;
    }

    foreach($wonder->unitProductionCost as $prodID => $prod) {
      $wonder->unitProductionCost[$prodID] = $prod * $memberCount;
    }
    
    foreach($wonder->buildingProductionCost as $prodID => $prod) {
      $wonder->buildingProductionCost[$prodID] = $prod * $memberCount;
    }

    $wonders[$wonder->wonderID] = array(
      'dbFieldName' => $wonder->wonderID, // Dummy. Wird für die boxCost.tmpl gebraucht.
      'name'        => $wonder->name,
      'wonder_id'   => $wonder->wonderID,
      'description' => $wonder->description
    );
    $wonders[$wonder->wonderID] = array_merge($wonders[$wonder->wonderID], parseCost($wonder, $tribeStorage));

    // show the building link ?!
    if (isset($tribeData['wonderLocked'][$wonder->wonderID]) && $tribeData['wonderLocked'][$wonder->wonderID] > time()) {
      $wonders[$wonder->wonderID]['no_build_msg'] = sprintf(_('Das Wunder ist noch gesperrt bis: %s'), date("d. m. H:i:s", $tribeData['wonderLocked'][$wonder->wonderID]));
    } else if ($wonders[$wonder->wonderID]['notenough']) {
      $wonders[$wonder->wonderID]['no_build_msg'] = _('Zu wenig Rohstoffe');
    } else {
      $wonders[$wonder->wonderID]['build_link'] = true;
    }
  }

  /****************************************************************************************************
  *
  * Chat
  *
  ****************************************************************************************************/
  $userRooms = Chat::getUsersByTribeID($tribeID);

  foreach ($chatRooms as $id => $data) {
    if (!isset($userRooms[$id]) || empty($userRooms[$id])) continue;
    $chatRooms[$id]['auth'] = $userRooms[$id];
  }

  /****************************************************************************************************
  *
  * Übergabe ans Template
  *
  ****************************************************************************************************/
  $template->addVars(array(
    'tribe_name'          => $tribeData['name'],
    'tribe_tag'           => $tribeData['tag'],
    'tribe_avatar'        => $tribeData['avatar'],
    'tribe_description'   => $tribeData['description'],
    'tribe_leader_name'   => $tribeData['leaderName'],
    'tribe_leader_id'     => $tribeData['leaderID'],
    'tribe_members'       => $tribeMembers,
    'tribe_members_count' => strval($memberCount),

    'government_name'     => $GLOBALS['governmentList'][$tribeData['governmentID']]['name'],

    'is_auth'             => $userAuth,

    'relations'           => (isset($relations)) ? $relations : array(),
    'relations_ally'      => $relationAlly,
    'relations_list'      => $GLOBALS['relationList'],
    'relations_info'      => $relations_info,
    'relations_war'       => (!empty($relationsWar)) ? true : false,

    'wonders'             => $wonders,

    'chat_rooms'          => $chatRooms,

    'status_msg'          => (isset($messageID)) ? $messageText[$messageID] : '',

    'tribe_action_auth'          => TRIBE_ACTION_AUTH,
    'tribe_action_chat_add'      => TRIBE_ACTION_CHAT_ADD,
    'tribe_action_chat_remove'   => TRIBE_ACTION_CHAT_REMOVE,
    'tribe_action_choose_leader' => TRIBE_ACTION_CHOOSE_LEADER,
    'tribe_action_donate'        => TRIBE_ACTION_DONATE,
    'tribe_action_goverment'     => TRIBE_ACTION_GOVERMENT,
    'tribe_action_leave'         => TRIBE_ACTION_LEAVE,
    'tribe_action_message'       => TRIBE_ACTION_MESSAGE,
    'tribe_action_relation'      => TRIBE_ACTION_RELATION,
    'tribe_action_update'        => TRIBE_ACTION_UPDATE,
    'tribe_action_wonder'        => TRIBE_ACTION_WONDER,
    'tribe_action_kick'          => TRIBE_ACTION_KICK,
    'tribe_action_chat_log'      => TRIBE_ACTION_CHAT_LOG
  ));
}

function tribe_getContentNoTribe($caveID, &$details) {
  global $template;

  $messageText = array (
   -10 => array('type' => 'error', 'message' => _('Die Stammeszugehörigkeit hat sich erst vor kurzem geändert. Warten Sie, bis die Stammeszugehörigkeit geändert werden darf.')),
    -9 => array('type' => 'error', 'message' => _('Du kannst keinen Stamm gründen während du in einem Stamm bist.')),
    -8 => array('type' => 'error', 'message' => _('Nicht zulässiges Stammeskürzel oder Passwort.')),
    -7 => array('type' => 'error', 'message' => _('Der Stamm hat schon die maximale Anzahl an Mitgliedern.')),
    -6 => array('type' => 'error', 'message' => _('Der Stamm befindet sich gerade im Krieg und darf daher im Moment keine neuen Mitglieder aufnehmen.')),
    -5 => array('type' => 'error', 'message' => _('Der Stamm konnte nicht angelegt werden.')),
    -4 => array('type' => 'error', 'message' => _('Es gibt schon einen Stamm mit diesem Kürzel.')),
    -3 => array('type' => 'error', 'message' => _('Du konntest dem Stamm nicht beitreten. Vermutlich bist du schon bei einem anderen Stamm Mitglied.')),
    -2 => array('type' => 'error', 'message' => _('Dieser Stammesname ist nicht erlaubt!')),
    -1 => array('type' => 'error', 'message' => _('Stammeskürzel und Passwort stimmen nicht überein.')),
     1 => array('type' => 'success', 'message' => _('Du bist dem Stamm beigetreten.')),
     2 => array('type' => 'success', 'message' => _('Der Stamm wurde erfolgreich angelegt.')),
     3 => array('type' => 'success', 'message' => _('Der Stamm wurde erfolgreich angelegt. Jedoch konnte das alte Ranking nicht übernommen werden.')),
  );

  if (!empty($_SESSION['player']->tribeID)) {
    tribe_getContent($caveID, $details);
    return;
  }

  // open template
  $template->setFile('tribe.tmpl');
  $template->setShowResource(false);

  // process form data
  $tribeAction =  Request::getVar('action', 0);
  switch ($tribeAction) {
    case TRIBE_ACTION_JOIN:
      $tag = Request::getVar('inputTag', '');
      $password = Request::getVar('inputPassword', '');

      if (Tribe::validatePassword($password) && Tribe::validateTag($tag)) {
        $messageID = Tribe::processJoin($_SESSION['player'], $tag, $password);
        if ($messageID == 1) {
          $auth = new auth;
          $auth->setPermission('tribe', 0, $_SESSION['player']->playerID);
          page_refreshUserData();

          $template->addVar('status_msg', $messageText[$messageID]);
          tribe_getContent($caveID, $details);
          return;
        }
      } else {
        $messageID = -8;
      }
    break;

    case TRIBE_ACTION_CREATE:
      $tag = Request::getVar('inputTag', '');
      $password = Request::getVar('inputPassword', '');
      $restoreRang = (Request::getVar('inputRestoreRank', 'no') == 'yes') ? true : false;

      if (Tribe::validatePassword($password) && Tribe::validateTag($tag)) {
        $messageID = Tribe::processCreate($_SESSION['player'], $tag, $password, $restoreRang);
      } else {
        $messageID = -8;
      }

      if ($messageID == 2) {
        $auth = new auth;
        $auth->setPermission('tribe', 0, $_SESSION['player']->playerID);
        page_refreshUserData();

        $template->addVar('status_msg', $messageText[$messageID]);
        tribe_getContent($caveID, $details);
        return;
      }
    break;
  }

  /****************************************************************************************************
  *
  * Übergabe ans Template
  *
  ****************************************************************************************************/
  $template->addVars(array(
    'status_msg'           => (isset($messageID)) ? $messageText[$messageID] : '',
    'tribe_action_create'  => TRIBE_ACTION_CREATE,
    'tribe_action_join'    => TRIBE_ACTION_JOIN,
  ));
}

?>