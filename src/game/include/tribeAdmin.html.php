<?php
/*
 * tribeAdmin.html.php -
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2012 Georg Pitterle
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribeAdmin_getContent($playerID, $tag, $caveID) {
  global $template;

  // messages
  $messageText = array(
    -38 => array('type' => 'error', 'message' => _('Das Stammeswunder kann nur auf andere Stämme gewundert werden!')),
    -37 => array('type' => 'error', 'message' => _('Das Stammeswunder kann nur auf den eigenen Stamm gewundert werden!')),
    -36 => array('type' => 'error', 'message' => _('Stamm nicht gefunden! Tippfehler?')),
    -35 => array('type' => 'error', 'message' => _('Beim Ausführen des Stammeswunders ist ein Problem aufgetreten!')),
    -34 => array('type' => 'info', 'message' => _('Nur der Stammesführer darf das Stammeswunder wundern!')),
    -33 => array('type' => 'error', 'message' => _('Bitte Stammesnamen eingeben!')),
    -32 => array('type' => 'error', 'message' => _('Die Rechte konnten nicht angewandt werden.')),
    -31 => array('type' => 'error', 'message' => _('Fehler in den Formulardaten!')),
    -30 => array('type' => 'error', 'message' => _('Du hast keine Berechtigung dies zu tun!')),
    -29 => array('type' => 'error', 'message' => _('Ungültiges Passwort! (Mind. 6 Zeichen, ohne Sonderzeichen)')),
    -28 => array('type' => 'error', 'message' => _('Ungültiges Bild oder URL beim Avatar! Wird zurückgesetzt!')),
    -27 => array('type' => 'success', 'message' => _('Das Stammeswunder wurde gewirkt.')),
    -26 => array('type' => 'error', 'message' => _('Das Stammeswunder konnte nicht gewirkt werden.')),
    -25 => array('type' => 'error', 'message' => _('Ihr Kriegsanteil ist nicht hoch genug, um den Gegner zur Aufgabe zu zwingen.')),
    -24 => array('type' => 'error', 'message' => _('Nur in der Demokratie sind solche Wahlen möglich.')),
    -23 => array('type' => 'error', 'message' => _('Sie sind schon Stammesanführer.')),
    -22 => array('type' => 'error', 'message' => _('Dieser Spieler ist nicht im Stamm.')),
    -21 => array('type' => 'error', 'message' => _('Dies darf nur der Stammesanführer tun.')),
    -20 => array('type' => 'error', 'message' => _('Es ist kein gleicher Kriegsgegner vorhanden.')),
    -19 => array('type' => 'error', 'message' => _('Die Beziehung des anderen Stammes erlauben kein Kriegsbündniss.')),
    -18 => array('type' => 'error', 'message' => _('Unsere aktuelle Beziehung erlaubt kein Kriegsbündniss.')),
    -17 => array('type' => 'error', 'message' => _('Der Stamm hat noch nicht genug Mitglieder um Beziehungen eingehen zu dürfen')),
    -16 => array('type' => 'error', 'message' => _('Die Stammeszugehörigkeit hat sich erst vor kurzem geändert. Warten Sie, bis die Stammeszugehörigkeit geändert werden darf.')),
    -15 => array('type' => 'error', 'message' => _('Ihr Stamm befindet sich im Krieg. Sie dürfen derzeit nicht austreten.')),
    -14 => array('type' => 'error', 'message' => _('Die Beziehung wurde nicht geändert, weil der ausgewählte Beziehungstyp bereits eingestellt ist.')),
    -13 => array('type' => 'error', 'message' => _('Eure Untergebenen weigern sich, diese Beziehung gegenüber einem so großen Stamm einzugehen.')),
    -12 => array('type' => 'error', 'message' => _('Eure Untergebenen weigern sich, diese Beziehung gegenüber einem so kleinen Stamm einzugehen.')),
    -11 => array('type' => 'error', 'message' => sprintf(_('Die Moral des Gegners ist noch nicht schlecht genug. Sie muss unter %d sinken. Eine weitere Chance besteht, wenn die Mitgliederzahl des gegnerischen Stammes um 30 Prozent gesunken ist. Das Verhältnis Eurer Rankingpunkte zu denen des Gegners muss sich seit Kriegsbeginn verdoppelt haben.'), RELATION_FORCE_MORAL_THRESHOLD)),
    -10 => array('type' => 'error', 'message' => _('Die zu ändernde Beziehung wurde nicht gefunden!')),
     -9 => array('type' => 'error', 'message' => _('Die Regierung konnte nicht geändert werden, weil sie erst vor kurzem geändert wurde.')),
     -8 => array('type' => 'error', 'message' => _('Die Regierung konnte aufgrund eines Fehlers nicht aktualisiert werden')),
     -7 => array('type' => 'error', 'message' => _('Zu sich selber kann man keine Beziehungen aufnehmen!')),
     -6 => array('type' => 'error', 'message' => _('Den Stamm gibt es nicht!')),
     -5 => array('type' => 'error', 'message' => _('Von der derzeitigen Beziehung kann nicht direkt auf die ausgewählte Beziehungsart gewechselt werden.')),
     -4 => array('type' => 'error', 'message' =>  _('Die Mindestlaufzeit läuft noch!')),
     -3 => array('type' => 'error', 'message' =>  _('Die Beziehung konnte aufgrund eines Fehlers nicht aktualisiert werden.')),
     -2 => array('type' => 'error', 'message' => _('Der Spieler ist ebenfalls Stammesanführer und kann nicht gekickt werden. Er kann nur freiwillig gehen.')),
     -1 => array('type' => 'error', 'message' => _('Der Spieler konnte nicht gekickt werden!')),
      0 => array('type' => 'success', 'message' => _('Die Daten wurden erfolgreich aktualisiert.')),
      1 => array('type' => 'success', 'message' => _('Der Spieler wurde erfolgreich gekickt.')),
      2 => array('type' => 'error', 'message' =>  _('Die Daten konnten gar nicht oder zumindest nicht vollständig aktualisiert werden.')),
      3 => array('type' => 'success', 'message' => _('Die Beziehung wurde umgestellt.')),
      4 => array('type' => 'success', 'message' => _('Die Regierung wurde geändert.')),
      5 => array('type' => 'success', 'message' => _('Die Berechtigungen wurden erfolgreich geändert.')), 
      6 => array('type' => 'info', 'message' => _('Die Götter haben Ihr Flehen nicht erhört! Die eingesetzten Opfergaben sind natürlich dennoch verloren. Mehr Glück beim nächsten Mal!'))
  );

  // open template
  $template->setFile('tribeAdmin.tmpl');
  $template->setShowRresource(false);
  $template->addVar('show_page', true);

  // get the tribe data
  if (!($tribeData = tribe_getTribeByTag($tag))) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }
  $tribeData['description'] = str_replace('<br />', '\n', $tribeData['description']);
  $avatar = @unserialize($tribeData['avatar']);
  $tribeData['avatar'] = $avatar['path'];
  $template->addVar('tribe_data', $tribeData);

  //get member Data
  $memberData = tribe_getAllMembers($tag);
  if (empty($memberData)) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }

  // get leader
  $isLeader = tribe_isLeader($playerID, $tag);
  if ($isLeader) {
    $leaderID = $playerID;
    $juniorLeaderID = tribe_getJuniorLeaderID($tag);
  } else {
    $leaderID = tribe_getLeaderID($tag);
    $juniorLeaderID = $playerID;
  }

  //seems to be leader, but not in tribe  
  if ($isLeader && !is_array($memberData[$leaderID])) {
    tribe_unmakeLeaderJuniorLeader($leaderID, $tag);
  }

  //seems to be juniorleader, but not in tribe  
  if (!$isLeader && !is_array($memberData[$leaderID])) {
    tribe_unmakeJuniorLeader($leaderID, $tag);
  }

  // init auth
  $auth = new auth;

  // check, for security reasons!
  if ((!$auth->checkPermission('tribe', 'change_settings', $_SESSION['player']->auth['tribe']) &&
      !$auth->checkPermission('tribe', 'kick_player', $_SESSION['player']->auth['tribe']) &&
      !$auth->checkPermission('tribe', 'change_relation', $_SESSION['player']->auth['tribe']) &&
      !$isLeader) ||
      !array_key_exists($_SESSION['player']->playerID, $memberData)) {
    $template->throwError('Du hast keine Berechtigung den Stamm zu verwalten!');
    return;
  }

  // get government
  $tribeGovernment = government_getGovernmentForTribe($tag);
  if (empty($tribeGovernment)) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }
  $tribeGovernment['name'] = $GLOBALS['governmentList'][$tribeGovernment['governmentID']]['name'];

  // get relations
  $tribeRelations = relation_getRelationsForTribe($tag);
  if (empty($tribeRelations)) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }

  // get current wars
  $tribeWarTargets = relation_getWarTargetsAndFame($tag);

  $action = Request::getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Stammesinfos ändern
*
****************************************************************************************************/
    case 'update':
      if (!$isLeader || !$auth->checkPermission('tribe', 'change_settings', $_SESSION['player']->auth['tribe'])) {
        $messageID = -30;
        break;
      }

      $password = Request::getVar('tribe_password', '');

      $postData = array(
        'name'        => Request::getVar('tribe_name', '', true),
        'password'    => (!empty($password)) ? $password : $tribeData['password'],
        'avatar'      => Request::getVar('tribe_avatar', ''),
        'description' => Request::getVar('tribe_description', '', true)
      );

      $messageID = tribe_processAdminUpdate($tag, $postData);
      $tribeData = tribe_getTribeByTag($tag);
      $template->addVar('tribe_data', $tribeData);

    break;

/****************************************************************************************************
*
* Regierungstyp ändern
*
****************************************************************************************************/
    case 'changeGoverment':
      if (!$isLeader || !$auth->checkPermission('tribe', 'change_relation', $_SESSION['player']->auth['tribe'])) {
        $messageID = -21;
        break;
      }

      $governmentData = Request::getVar('governmentData', array('' => ''));
      $messageID = government_processGovernmentUpdate($tag, $governmentData);
    break;

/****************************************************************************************************
*
* Spielerrechte Ändern
*
****************************************************************************************************/
    case 'changeAuth':
      if (!$isLeader) {
        $messageID = -21;
        break;
      }

      if (!Request::isPost('player_id', true)) {
        $messageID = -31;
        break;
      }

      $userAuth = 0;
      foreach ($auth->perm['tribe'] as $type => $data) {
        $userAuth = $userAuth | Request::getVar($type, 0);
      }

      if ($auth->setPermission('tribe', $userAuth, Request::getVar('player_id', 0))) {
        $messageID = 0;
      } else {
        $messageID = -32;
      }

      $memberData = tribe_getAllMembers($tag);
    break;

/****************************************************************************************************
*
* Junior Leader ändern
*
****************************************************************************************************/
    case 'juniorLeader':
      $juniorLeader = Request::getVar('juniorLeader', array('' => ''));
      $newleadership = array(0 => $leaderID, 1 => $juniorLeader['juniorLeaderID']);

      if (!$isLeader) {
        $messageID = -21;
        break;
      } elseif ($newleadership[1] && !is_array($memberData[$newleadership[1]])) {
        $messageID = -22;
        break;
      } elseif ($newleadership[1] == $newleadership[0]) {
       $messageID = -23;
        break;
      } elseif ($tribeGovernment['governmentID'] <> 2) {
        $messageID = -24;
        break;
      } elseif (!tribe_ChangeLeader($tag, $newleadership, $leaderID, $juniorLeaderID)) {
        $messageID = 2; //success
        break;
      } else {
        $messageID = 0;
      }
    break;

/****************************************************************************************************
*
* bye bye Member
*
****************************************************************************************************/
    case 'kick':
      if (!$isLeader || !$auth->checkPermission('tribe', 'kick_player', $_SESSION['player']->auth['tribe'])) {
        $messageID = -30;
      } else {
        $messageID = tribe_processKickMember(Request::getVar('playerID', 0), $tag);
        $memberData = tribe_getAllMembers($tag);
      }
    break;

/****************************************************************************************************
*
* Krieg? Niederlage? Aktualisieren der Beziehung
*
****************************************************************************************************/
    case 'updateRelation':
      if (!$auth->checkPermission('tribe', 'change_relation', $_SESSION['player']->auth['tribe']) && !tribe_isLeader($_SESSION['player']->playerID, $tag)) {
        $messageID = -30;
        break;
      }

      $relationData = Request::getVar('relationData', array('' => ''));
      if (Request::isPost('forceSurrender')) {
        $messageID = relation_forceSurrender($tag, $relationData);
        $tribeRelations = relation_getRelationsForTribe($tag);
        $tribeWarTargets = relation_getWarTargetsAndFame($tag);
      } else {
        $messageID = relation_processRelationUpdate($tag, $relationData);
        $tribeRelations = relation_getRelationsForTribe($tag);
      }
    break;
  
/****************************************************************************************************
*
* Stammeswunder?
*
****************************************************************************************************/
  case 'tribeWonder':
      if (!$isLeader) {
        $messageID = -21;
        break;
      }

      $wonderID = Request::getVar('wonderID', -1);
      $tribeName = Request::getVar('TribeName', '');

      if ($wonderID != -1) {
        if (!empty($tribeName)) {
          $messageID = wonder_processTribeWonder($caveID, $wonderID, $tag, $tribeName);
          $tribeData = tribe_getTribeByTag($tag);
        } else {
          $messageID = -33;
        }
      } else {
        $messageID = -26;
        break;
      }
  } // end action switch

/****************************************************************************************************
*
* Auswahl des JuniorAdmins
*
****************************************************************************************************/
  if ($isLeader && $tribeGovernment['governmentID'] == 2) {
    $JuniorLeaderSelect = array();
    $JuniorLeaderSelect[] = array(
      'value'    => 0,
      'selected' => ($tribeData['juniorLeaderID'] == 0 ? 'selected="selected"' : ''),
      'name'     => _('keinen Stellvertreter wählen')
    );

    foreach($memberData AS $playerID => $playerData) {
      if ($leaderID == $playerID) {
        continue; 
      }

      $JuniorLeaderSelect[] = array(
        'value'    => $playerID,
        'selected' => ($tribeData['juniorLeaderID'] == $playerID ? 'selected="selected"' : ''),
        'name'     => $playerData['name']
      );
    }

    $template->addVar('junior_leader_select', $JuniorLeaderSelect);
  }

/****************************************************************************************************
*
* Auswahl der Regierungsformen
*
****************************************************************************************************/
  if ($isLeader && $tribeGovernment['isChangeable']) {
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
* Beziehungen anzeigen
*
****************************************************************************************************/
  foreach($tribeRelations['own'] AS $target => $targetData) {
    if (!$targetData['changeable']) {
      $relation_info[$target] = array(
        'tag'            => $target,
        'relation'       => $GLOBALS['relationList'][$targetData['relationType']]['name'],
        'duration'       => $targetData['time'],
        'their_relation' => (isset($tribeRelations['other'][$target])) ? $GLOBALS['relationList'][$tribeRelations['other'][$target]['relationType']]['name'] : $GLOBALS['relationList'][0]['name']
      );

      // war?
      if (array_key_exists($target, $tribeWarTargets)) {
        $relation_info[$target]['war'] = true;
        $relation_info[$target]['fame_own'] = $tribeWarTargets[$target]['fame_own'];
        $relation_info[$target]['fame_target'] = $tribeWarTargets[$target]['fame_target'];
        $relation_info[$target]['percent_actual'] = $tribeWarTargets[$target]['percent_actual'];
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
        $wartarget = $tribeWarTargets[$target];  
        $relations[$target]['war']            = true;
        $relations[$target]['fame_own']       = $wartarget['fame_own'];
        $relations[$target]['fame_target']    = $wartarget['fame_target'];
        $relations[$target]['percent_actual'] = $wartarget['percent_actual'];

        if ($wartarget['isForcedSurrenderTheoreticallyPossible']) {
          $relations[$target]['isForcePossible'] = true;
          $relation["WAR/FORCEDSURRENDER/percent_estimated"] = $wartarget["percent_estimated"];
          if($wartarget["isForcedSurrenderPracticallyPossible"]){
            $relation["WAR/FORCEDSURRENDER/class"] = "enough";
          } else if($target["isForcedSurrenderPracticallyPossibleForTarget"]) {
            $relation["WAR/FORCEDSURRENDER/class"] = "less";
          } else {
            $relation["WAR/FORCEDSURRENDER/class"] = "";
          }
        }
      }
    }
  }

  foreach($tribeRelations['other'] AS $target => $targetData) {
    if (isset($tribeRelations['own'][$target])) {
      continue;
    }

    $relations[$target] = array(
      'tag'            => $target,
      'their_relation' => $GLOBALS['relationList'][$tribeRelations['other'][$target]['relationType']]['name'],
      'duration'       => $targetData['time'],
      'relation_type'  => 0,
    );
  }

  $template->addVars(array(
    'relations'      => (isset($relations)) ? $relations : array(),
    'relations_info' => (isset($relation_info)) ? $relation_info : array(),
    'relation_list'  => $GLOBALS['relationList'],
    'status_msg'     => (isset($messageID)) ? $messageText[$messageID] : '',
    'tribe_members'  => $memberData,

    'is_auth_change_settings' => $isLeader || $auth->checkPermission('tribe', 'change_settings', $_SESSION['player']->auth['tribe']),
    'is_auth_kick_player'     => $isLeader || $auth->checkPermission('tribe', 'kick_player', $_SESSION['player']->auth['tribe']),
    'is_auth_change_relation' => $isLeader || $auth->checkPermission('tribe', 'change_relation', $_SESSION['player']->auth['tribe']),
    'is_leader'               => $isLeader
  ));
  
/****************************************************************************************************
*
* Stammeslager
*
****************************************************************************************************/
  
  $tribeStorageValues = array(); $tribeStorage = array();
  foreach ($GLOBALS['resourceTypeList'] as $resourceID => $resource) {
    $tribeStorage[$resource->dbFieldName] = $tribeData[$resource->dbFieldName];
    $tribeStorageValues[$resource->dbFieldName]['name'] = $resource->name;
    $tribeStorageValues[$resource->dbFieldName]['value'] = $tribeData[$resource->dbFieldName];
    $tribeStorageValues[$resource->dbFieldName]['dbFieldName'] = $resource->dbFieldName;
  }
  
  $template->addVars(array(
    'tribeStorage' => $tribeStorageValues
  ));
  
/****************************************************************************************************
*
* Stammeswunder
*
****************************************************************************************************/
  
  $tribeWonders = array();
  foreach ($GLOBALS['wonderTypeList'] as $wonder) {
    
    
    // exclude nonTribeWonders
    if (!$wonder->isTribeWonder || $wonder->nodocumentation) {
      continue;
    }
    
    $result = rules_checkDependencies($wonder, $tribeStorage);
    
    $tribeWonders[$wonder->wonderID] = array(
      'dbFieldName' => $wonder->wonderID, // Dummy. Wird für die boxCost.tmpl gebraucht.
      'name'        => $wonder->name,
      'wonder_id'   => $wonder->wonderID,
      'description' => $wonder->description,
      'same'        => ($wonder->target == 'same') ? true : false
    );
    $tribeWonders[$wonder->wonderID] = array_merge($tribeWonders[$wonder->wonderID], parseCost($wonder, $tribeStorage));
  
    // show the building link ?!
    if ($tribeWonders[$wonder->wonderID]['notenough']) {
      $tribeWonders[$wonder->wonderID]['no_build_msg'] = _('Zu wenig Rohstoffe');
    } else {
      $tribeWonders[$wonder->wonderID]['build_link'] = true;
    }
  }
  
  $template->addVar('tribeWonders', $tribeWonders);
  
}

?>