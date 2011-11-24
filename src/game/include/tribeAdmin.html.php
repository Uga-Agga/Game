<?php
/*
 * tribeAdmin.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribeAdmin_getContent($playerID, $tag) {
  global $config, $request, $template;
  global $relationList, $governmentList, $wonderTypeList;

  // messages
  $messageText = array(
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
    -13 => array('type' => 'error', 'message' => _('Eure Untergebenen weigern sich, diese Beziehung gegenüber einem so grßen Stamm einzugehen.')),
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
      4 => array('type' => 'success', 'message' => _('Die Regierung wurde geändert.'))
  );

  // open template
  $template->setFile('tribeAdmin.tmpl');
  $template->setShowRresource(false);
  $template->addVar('show_page', true);

  // check, for security reasons!
  if (!tribe_isLeaderOrJuniorLeader($playerID, $tag)) {
    $template->addVars(array(
      'status_msg' => array('type' => 'error', 'message' => 'Du hast keine Berechtigung den Stamm zu verwalten'),
      'show_page'  => false
    ));
  }

  // get the tribe data
  if (!($tribeData = tribe_getTribeByTag($tag))) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }
  $tribeData['description'] = str_replace('<br />', '\n', $tribeData['description']);
  $avatar = @unserialize($tribeData['avatar']);
  $tribeData['avatar'] = $avatar['path'];
  $template->addVar('tribe_data', $tribeData);

  //get Member Data
  if (!($memberData = tribe_getAllMembers($tag))) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }
  $template->addVar('tribe_members', $memberData);

  // get government
  if (!($tribeGovernment = government_getGovernmentForTribe($tag))) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }
  $tribeGovernment['name'] = $governmentList[$tribeGovernment['governmentID']]['name'];

  // get relations
  if (!($tribeRelations = relation_getRelationsForTribe($tag))) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }

  // get current wars
  $tribeWarTargets = relation_getWarTargetsAndFame($tag);

/****************************************************************************************************
*
* Leader vom Stamm? Oder doch nur JuniorLeader?
*
****************************************************************************************************/
  $isLeader = tribe_isLeader($playerID, $tag);
  if ($isLeader) {
    $template->addVar('is_leader', true);

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

  $action = $request->getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Stammesinfos ändern
*
****************************************************************************************************/

    case 'update':
      $data = $request->getVar('data', array('' => ''));

      $postData = array(
        'name'        => $data['name'],
        'password'    => ($data['password'] != 'password') ? $data['password'] : $tribeData['password'],
        'avatar'      => $data['avatar'],
        'description' => $data['description']
      );
      $messageID = tribe_processAdminUpdate($playerID, $tag, $postData);
      $template->addVar('tribe_data', $postData);

    break;
/****************************************************************************************************
*
* Regierungstyp ändern
*
****************************************************************************************************/
    case 'changeGoverment':
      if (!$isLeader) {
        $messageID = -21;
        break;
      }

      $governmentData = $request->getVar('governmentData', array('' => ''));
      $messageID = government_processGovernmentUpdate($tag, $governmentData);
    break;

/****************************************************************************************************
*
* Junior Leader ändern
*
****************************************************************************************************/
    case 'juniorLeader':
      $juniorLeader = $request->getVar('juniorLeader', array('' => ''));
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
      if (!$isLeader) {
        $messageID = -21;
      } else {
        $messageID = tribe_processKickMember($request->getVar('playerID', 0), $tag);
      }
    break;

/****************************************************************************************************
*
* Krieg? Niederlage? Aktualisieren der Beziehung
*
****************************************************************************************************/
    case 'updateRelation':
      $relationData = $request->getVar('relationData', array('' => ''));
      if ($request->isPost('forceSurrender')) {
        $messageID = relation_forceSurrender($tag, $relationData);
        $tribeRelations = relation_getRelationsForTribe($tag);
        $tribeWarTargets = relation_getWarTargetsAndFame($tag);
      } else {
        $messageID = relation_processRelationUpdate($tag, $relationData);
        $tribeRelations = relation_getRelationsForTribe($tag);
      }
    break;
  }

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
    foreach($governmentList AS $governmentID => $typeData) {
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
        'relation'       => $relationList[$targetData['relationType']]['name'],
        'duration'       => $targetData['time'],
        'their_relation' => (isset($tribeRelations['other'][$target])) ? $relationList[$tribeRelations['other'][$target]['relationType']]['name'] : $relationList[0]['name']
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
        'their_relation' =>  (isset($tribeRelations['other'][$target])) ? $relationList[$tribeRelations['other'][$target]['relationType']]['name'] : $relationList[0]['name'],
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

  $template->addVars(array(
    'relations'      => (isset($relations)) ? $relations : array(),
    'relations_info' => (isset($relation_info)) ? $relation_info : array(),
    'relation_list' => $relationList,
    'status_msg'    => (isset($messageID)) ? $messageText[$messageID] : '',
  ));

return;

/*
  // proccess form data
  if (($relationData = $request->getVar('relationData', array('' => ''))) && $request->getVar('forceSurrender', 0)) {
    $messageID = relation_forceSurrender($tag, $relationData);
*/

  // existing relations towards other clans //////////////////
  foreach($tribeRelations['own'] AS $target => $targetData) {
    
    if (!$targetData['changeable']) {

      continue;
    } else {
      $relation = array('modus_name'=> "modus",
                     'modus'          => TRIBE_ADMIN,
                     'dataarray'      => "relationData",
                     'dataentry'      => "tag",
                     'value'          => $target,
                     'target_points'  => $targetData['target_rankingPoints'],
                     'tribe_points'   => $targetData['tribe_rankingPoints'],
                     'their_relation' => $tribeRelations['other'][$target]
                                         ? $relationList[$tribeRelations['other'][$target]['relationType']]['name']
                                         : $relationList[0]['name'],
                     'caption'        => _('ändern')); 

      // war?

      tmpl_set($template, 'RELATION', $relation);

      tmpl_set($template, 'RELATION/SELECTOR',
               array('dataarray' => "relationData",
                     'dataentry' => "relationID"));

      // check, if it is possible to get or loose fame, and display if true
      /*if ($targetData['attackerReceivesFame'] ||
          $targetData['defenderReceivesFame'] ||
          $tribeRelations['other'][$target]['attackerReceivesFame'] ||
          $tribeRelations['other'][$target]['defenderReceivesFame']) {
        tmpl_set($template, 'RELATION/FAME',
                 array('tribe_fame'   => $targetData['fame'],
                       'target_fame'  => $tribeRelations['other'][$target]['fame'],
                       'tribe_moral'  => $targetData['moral'],
                       'target_moral' => $tribeRelations['other'][$target]['moral']));
      }*/

      foreach($relationList AS $relationType => $typeData) {
        // get relation of target to tr.
        if ($tribeRelations['other'][$tag]) {
          $relationTypeTowardsTribe = $tribeRelations['other'][$tag]['relationType'];
        }
        
        // check, if switch to relationType is possible
        if ($relationTypeTowardsTribe != $relationType &&
            $relationType != $targetData['relationType'] &&
            !relation_isPossible($relationType, $targetData['relationType'])) {
          continue;
        }
        
        tmpl_iterate($template, 'RELATION/SELECTOR/OPTION');
        tmpl_set($template, 'RELATION/SELECTOR/OPTION',
                 array('value'    => $relationType,
                       'selected' => $relationType == $targetData['relationType']
                                     ? "selected"
                                     : "",
                       'text'     => $typeData['name']));
      }

    }
  }
}

?>