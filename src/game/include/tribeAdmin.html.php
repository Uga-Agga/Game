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
  global $config, $no_resource_flag, $relationList,
         $governmentList,$wonderTypeList;

  $no_resource_flag = 1;

  
  // check, for security reasons!
  if (!tribe_isLeaderOrJuniorLeader($playerID, $tag))
    page_dberror();

  $isLeader = tribe_isLeader($playerID, $tag);
  $isLeader ? $leaderID=$playerID : $leaderID=tribe_getLeaderID($tag); 
  !$isLeader ? $juniorLeaderID=$playerID : $juniorLeaderID=tribe_getJuniorLeaderID($tag); 
  
  //get Member Data
  
  if (!($memberData = tribe_getAllMembers($tag)))
    page_dberror();

  // get government
  if (!($tribeGovernment = government_getGovernmentForTribe($tag)))
    page_dberror();

  $tribeGovernment['name'] =
    $governmentList[$tribeGovernment['governmentID']]['name'];

  //seems to be leader, but not in tribe  
  if ($isLeader && !is_array($memberData[$leaderID])) {
    tribe_unmakeLeaderJuniorLeader($leaderID, $tag);
  }
   
  //seems to be juniorleader, but not in tribe  
  if (!$isLeader && !is_array($memberData[$leaderID])) {
    tribe_unmakeJuniorLeader($leaderID, $tag);
  }

  // messages
  $messageText = array(-29 => _('Ung&uuml;ltiges Passwort! (Mind. 6 Zeichen, ohne Sonderzeichen)'),
                       -28 => _('Ung&uuml;ltiges Bild oder URL beim Avatar! Wird zur&uuml;ckgesetzt!'),
                       -27 => _('Das Stammeswunder wurde gewirkt.'),
                       -26 => _('Das Stammeswunder konnte nicht gewirkt werden.'),
                       -25 => _('Ihr Kriegsanteil ist nicht hoch genug, um den Gegner zur Aufgabe zu zwingen.'),
                       -24 => _('Nur in der Demokratie sind solche Wahlen m&ouml;glich.'),
                       -23 => _('Sie sind schon Stammesanf&uuml;hrer.'),
                       -22 => _('Dieser Spieler ist nicht im Stamm.'),
                       -21 => _('Dies darf nur der Stammesanf&uuml;hrer tun.'),
                       -20 => _('Es ist kein gleicher Kriegsgegner vorhanden.'),  	                    
                       -19 => _('Die Beziehung des anderen Stammes erlauben kein Kriegsb&uuml;ndniss.'), 
                       -18 => _('Unsere aktuelle Beziehung erlaubt kein Kriegsb&uuml;ndniss.'), 
                       -17 => _('Der Stamm hat noch nicht genug Mitglieder um Beziehungen eingehen zu d&uuml;rfen'),
                       -16 => _('Die Stammeszugeh&ouml;rigkeit hat sich erst vor kurzem ge&auml;ndert. Warten Sie, bis die Stammeszugeh&ouml;rigkeit ge&auml;ndert werden darf.'),
                       -15 => _('Ihr Stamm befindet sich im Krieg. Sie d&uuml;rfen derzeit nicht austreten.'),
                       -14 => _('Die Beziehung wurde nicht ge&auml;ndert, weil der ausgew&auml;hlte Beziehungstyp bereits eingestellt ist.'),
                       -13 => _('Eure Untergebenen weigern sich, diese Beziehung gegen&uuml;ber einem so gro&szlig;en Stamm einzugehen.'),
                       -12 => _('Eure Untergebenen weigern sich, diese Beziehung gegen&uuml;ber einem so kleinen Stamm einzugehen.'),
                       -11 => sprintf(_('Die Moral des Gegners ist noch nicht schlecht genug. Sie muss unter %d sinken. Eine weitere Chance besteht, wenn die Mitgliederzahl des gegnerischen Stammes um 30 Prozent gesunken ist. Das Verh&auml;ltnis Eurer Rankingpunkte zu denen des Gegners muss sich seit Kriegsbeginn verdoppelt haben.'),
                                      RELATION_FORCE_MORAL_THRESHOLD),
                       -10 => _('Die zu &auml;ndernde Beziehung wurde nicht gefunden!'),
                        -9 => _('Die Regierung konnte nicht ge&auml;ndert werden, weil sie erst vor kurzem ge&auml;ndert wurde.'),
                        -8 => _('Die Regierung konnte aufgrund eines Fehlers nicht aktualisiert werden'),
                        -7 => _('Zu sich selber kann man keine Beziehungen aufnehmen!'),
                        -6 => _('Den Stamm gibt es nicht!'),
                        -5 => _('Von der derzeitigen Beziehung kann nicht direkt auf die ausgew&auml;hlte Beziehungsart gewechselt werden.'),
                        -4 => _('Die Mindestlaufzeit l&auml;uft noch!'),
                        -3 => _('Die Beziehung konnte aufgrund eines Fehlers nicht aktualisiert werden.'),
                        -2 => _('Der Spieler ist ebenfalls Stammesanf&uuml;hrer und kann nicht gekickt werden. Er kann nur freiwillig gehen.'),
                        -1 => _('Der Spieler konnte nicht gekickt werden!'),
                         0 => _('Die Daten wurden erfolgreich aktualisiert.'),
                         1 => _('Der Spieler wurde erfolgreich gekickt.'),
                         2 => _('Die Daten konnten gar nicht oder zumindest nicht vollst&auml;ndig aktualisiert werden.'),
                         3 => _('Die Beziehung wurde umgestellt.'),
                         4 => _('Die Regierung wurde ge&auml;ndert.'));

  // proccess form data
  if (($relationData = request_var('relationData', array('' => ''))) && request_var('forceSurrender', 0)) {
    $messageID = relation_forceSurrender($tag, $relationData);

  } else if (($relationData = request_var('relationData', array('' => ''))) && !request_var('forceSurrender', 0)) {

    $messageID = relation_processRelationUpdate($tag,
                                                $relationData);
  
  } else if ($data = request_var('data', array('' => ''))) {
    $postData = array('name'        => $data['name'],
                      'password'    => $data['password'],
                      'avatar'      => $data['avatar'],
                      'description' => $data['description']);
    $messageID = tribe_processAdminUpdate($playerID, $tag, $postData);

  } else if (request_var('kick', 0)) {
    if (!$isLeader) {
      $messageID = -21;
    } else {
      $messageID = tribe_processKickMember(request_var('playerID', 0), $tag);
    }

  } else if ($governmentData = request_var('governmentData', array('' => ''))) {
    if (!$isLeader) {
      $messageID = -21;
    } else {
      $messageID =
        government_processGovernmentUpdate($tag, $governmentData);
    }
  } else if ($juniorLeader = request_var('juniorLeader', array('' => ''))) {
    $newleadership = array( 0 => $leaderID, 1 => $juniorLeader['juniorLeaderID']); 
    if (!$isLeader) {
      $messageID = -21;
    } elseif ($newleadership[1] && !is_array($memberData[$newleadership[1]])) {
      $messageID = -22;
    } elseif ($newleadership[1] == $newleadership[0]) {
       $messageID = -23;
    } elseif ($tribeGovernment['governmentID'] <> 2) {
       $messageID = -24;
    } elseif (tribe_ChangeLeader($tag,
                              $newleadership,
                              $leaderID,
                              $juniorLeaderID)) {
      $messageID = 0; //success
    } else {
      $messageID = 2; //something went wrong
    } 
  }

  // get the tribe data
  if (!($tribeData = tribe_getTribeByTag($tag)))
    page_dberror();

  $tribeData['description'] = str_replace('<br />', '',
                                          $tribeData['description']);

  // get relations
  if (!($tribeRelations = relation_getRelationsForTribe($tag)))
    page_dberror();
    
  // get current wars
  $tribeWarTargets = relation_getWarTargetsAndFame($tag);

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'tribeAdmin.ihtml');

  // Show a special message
  if (isset($messageID)) {
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
  }

  // show the profile's data
  tmpl_set($template, 'modus_name', 'modus');
  tmpl_set($template, 'modus_value', TRIBE_ADMIN);

  ////////////// user data //////////////////////

  tmpl_set($template, 'DATA_GROUP/heading', _('Stammesdaten'));

  tmpl_set($template, 'DATA_GROUP/ENTRY_INFO/name',  _('Tag'));
  tmpl_set($template, 'DATA_GROUP/ENTRY_INFO/value', $tribeData['tag']);
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INFO');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      _('Name'));
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'name');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $tribeData['name']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '20');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '90');
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      _('Password'));
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'password');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $tribeData['password']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '15');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '15');
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');
  
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      _('Avatar URL <br /><small>(max. Breite: '.MAX_AVATAR_WIDTH.', max. H&ouml;he: '.MAX_AVATAR_HEIGHT .')</small>'));
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'avatar');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $tribeData['avatar']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '20');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '90');

  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/name',      _('Beschreibung'));
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/dataentry', 'description');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/value',     $tribeData['description']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/cols',      '25');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/rows',      '8');


  ////////////// junioLeader ////////////////////
  if ($isLeader && ($tribeGovernment['governmentID']==2)) {
    tmpl_set($template, 'JUNIORADMIN',
             array('modus_name'=> "modus",
                   'modus'     => TRIBE_ADMIN,
                   'caption'   => _('W&auml;hlen'),
                   'SELECTOR'  => array('dataarray' => 'juniorLeader',
                                        'dataentry' => 'juniorLeaderID')));
    foreach($memberData AS $playerID => $playerData) {
      if ($leaderID==$playerID) {
        continue; 
      };
      tmpl_iterate($template, 'JUNIORADMIN/SELECTOR/OPTION');
      tmpl_set($template, 'JUNIORADMIN/SELECTOR/OPTION',
             array("value"             => $playerID, 
                      'selected' => ($tribeData['juniorLeaderID'] == $playerID ? "selected" : ""),
                      'text' => $playerData['name']));
    };
    tmpl_iterate($template, 'JUNIORADMIN/SELECTOR/OPTION');
    tmpl_set($template, 'JUNIORADMIN/SELECTOR/OPTION',
         array("value"             => 0, 
                  'selected' => ($tribeData['juniorLeaderID'] == 0 ? "selected" : ""),
                  'text' => _('keinen Stellvertreter w&auml;hlen')));
              
  }


  ////////////// government /////////////////////
  if ($isLeader) {
    if ($tribeGovernment['isChangeable']) {
      tmpl_set($template, 'GOVERNMENT',
               array('modus_name'=> "modus",
                     'modus'     => TRIBE_ADMIN,
                     'caption'   => _('&auml;ndern'),
                     'SELECTOR'  => array('dataarray' => 'governmentData',
                                          'dataentry' => 'governmentID')));
  
      foreach($governmentList AS $governmentID => $typeData) {
        tmpl_iterate($template, 'GOVERNMENT/SELECTOR/OPTION');
        tmpl_set($template, 'GOVERNMENT/SELECTOR/OPTION',
                 array ('value' => $governmentID,
                        'selected' =>
                          ($governmentID == $tribeGovernment['governmentID']
                          ? "selected" : ""),
                        'text' => $typeData['name']));
      }
    
    } else {
      tmpl_set($template, 'GOVERNMENT_INFO',
               array('name'     => $tribeGovernment['name'],
                     'duration' => $tribeGovernment['time']));
    }
  }

  ////////////// tribewonder //////////////////////
  /*
	init_Wonders();
  $tribewonderExists = False;
  for ($i = 0; $i < sizeof($wonderTypeList); $i++){
  	 $wonder = $wonderTypeList[$i];
     if ($wonder->groupID<>3) 
     	 continue;
     $tribewonderExists = True;
     tmpl_iterate($template,'TRIBEWONDER/OPTION');	 
     tmpl_set($template, 'TRIBEWONDER/OPTION',
           array('text'     => $wonder->name,
                 'value'     => $i));
  };
  if ($tribewonderExists) {
    tmpl_set($template, 'TRIBEWONDER/caption',_('erwirken'));
    tmpl_set($template, 'TRIBEWONDER/modus_name',"modus");
    tmpl_set($template, 'TRIBEWONDER/modus', TRIBE_ADMIN);
  };
	*/
   ////////////// relations //////////////////////

  tmpl_set($template, 'RELATION_NEW',
           array('modus_name' => "modus",
                 'modus'     => TRIBE_ADMIN,
                 'dataarray' => "relationData",
                 'dataentry' => "tag",
                 'value'     => (array_key_exists('tag', $relationData)) ? $relationData['tag'] : "",
                 'size'      => 8,
                 'maxlength' => 8,
                 'caption'   => _('&auml;ndern')));

  tmpl_set($template, 'RELATION_NEW/SELECTOR',
           array('dataarray' => "relationData",
                 'dataentry' => "relationID"));

  foreach($relationList AS $relationID => $typeData) {

    tmpl_iterate($template, 'RELATION_NEW/SELECTOR/OPTION');
    tmpl_set($template, 'RELATION_NEW/SELECTOR/OPTION',
             array('value' => $relationID,
                   'selected' =>
                     ($relationID == (array_key_exists('relationID', $relationData) ? $relationData['relationID'] : "")
                     ? "selected" : ""),
                   'text' => $typeData['name']));
  }

  // existing relations towards other clans //////////////////
  foreach($tribeRelations['own'] AS $target => $targetData) {
    
    if (!$targetData['changeable']) {
      // relation, that couldn't be changed at the moment
      tmpl_iterate($template, 'RELATION_INFO');
      $relation_info = array('tag' => $target,
                     'relation' => $relationList[$targetData['relationType']]['name'],
                     'duration' => $targetData['time'],
                     'their_relation' => ($tribeRelations['other'][$target]
                       ? $relationList[$tribeRelations['other'][$target]['relationType']]['name'] : $relationList[0]['name']));
      // war?
      if(array_key_exists($target, $tribeWarTargets)) {
        $relation_info["WAR/fame_own"] = $tribeWarTargets[$target]["fame_own"]; 
        $relation_info["WAR/fame_target"] = $tribeWarTargets[$target]["fame_target"]; 
        $relation_info["WAR/percent_actual"] = $tribeWarTargets[$target]["percent_actual"]; 
      }
      tmpl_set($template, 'RELATION_INFO', $relation_info);

      // check, if it is possible to get or loose fame, and display if true
      /*if ($targetData['attackerReceivesFame'] ||
          $targetData['defenderReceivesFame'] ||
          $tribeRelations['other'][$target]['attackerReceivesFame'] ||
          $tribeRelations['other'][$target]['defenderReceivesFame']) {
        tmpl_set($template, 'RELATION_INFO/FAME',
                 array('tribe_fame'   => $targetData['fame'],
                       'target_fame'  => $tribeRelations['other'][$target]['fame'],
                       'tribe_moral'  => $targetData['moral'],
                       'target_moral' => $tribeRelations['other'][$target]['moral']));
      }*/

      continue;
    } else {
      // relation, that is changeable
      tmpl_iterate($template, 'RELATION');

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
                     'caption'        => _('&auml;ndern')); 

      // war?
      if($tribeWarTargets[$target]){
        $wartarget = $tribeWarTargets[$target];  
        $relation["WAR/fame_own"] = $wartarget["fame_own"]; 
        $relation["WAR/fame_target"] = $wartarget["fame_target"]; 
        $relation["WAR/percent_actual"] = $wartarget["percent_actual"];
        if($wartarget["isForcedSurrenderTheoreticallyPossible"]){
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


  ////////////// memberliste ////////////////////
  
  foreach($memberData AS $playerID => $playerData) {
   if ($isLeader) {
      tmpl_iterate($template, 'MEMBERADMIN');
      tmpl_set($template, 'MEMBERADMIN',
               array("name"             => $playerData['name'],
                     "lastAction"       => $playerData['lastAction'],
                     "player_link"      => "modus=".PLAYER_DETAIL."&detailID=$playerID",
                     "player_kick_link" => "modus=".TRIBE_ADMIN."&playerID=$playerID&kick=1"));
   } else {                  
      tmpl_iterate($template, 'MEMBERJUNIORADMIN');
      tmpl_set($template, 'MEMBERJUNIORADMIN',
               array("name"             => $playerData['name'],
                     "lastAction"       => $playerData['lastAction'],
                     "player_link"      => "modus=".PLAYER_DETAIL."&detailID=$playerID"));
   }
  }

  ////////////// delete tribe ////////////////////
  if ($isLeader) {
    tmpl_set($template, 'DELETE/modus_name', 'modus');
    tmpl_set($template, 'DELETE/modus', TRIBE_DELETE);
    tmpl_set($template, 'DELETE/heading', _('Stamm aufl&ouml;sen'));
    tmpl_set($template, 'DELETE/text', _('Den gesamten Stamm aufl&ouml;sen. Alle Mitglieder sind danach stammeslos.'));
    tmpl_set($template, 'DELETE/caption', sprintf(_('%s aufl&ouml;sen'), $tag));
  }
  
  
  
  return tmpl_parse($template);
}

?>