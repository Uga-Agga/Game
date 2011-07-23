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

define("TRIBE_ACTION_JOIN",          1);
define("TRIBE_ACTION_CREATE",        2);
define("TRIBE_ACTION_LEAVE",         3);
define("TRIBE_ACTION_MESSAGE",       4);

function tribe_getContent($playerID, $tribe) {
  
  global $no_resource_flag, $governmentList;

  $no_resource_flag = 1;

  // messages
  $messageText = array (
   -14 => _('Nicht zul&auml;ssiges Stammesk&uuml;rzel oder Passwort.'),
   -13 => _('Der Stamm hat schon die maximale Anzahl an Mitgliedern.'),
   -12 => _('Der Stamm befindet sich gerade im Krieg und darf daher im Moment keine neuen Mitglieder aufnehmen.'),
   -11 => _('Die Stammeszugeh&ouml;rigkeit hat sich erst vor kurzem ge&auml;ndert. Warten Sie, bis die Stammeszugeh&ouml;rigkeit ge&auml;ndert werden darf.'),
   -10 => _('Ihr Stamm befindet sich im Krieg. Sie d&uuml;rfen derzeit nicht austreten.'),
    -9 => _('Die Nachricht konnte nicht eingetragen werden.'),
    -8 => _('Sie sind der Stammesanf&uuml;hrer und konnten nicht entfernt werden.'),
    -7 => _('Das Passwort konnte nicht gesetzt werden!'),
    -6 => _('Der Stamm konnte nicht angelegt werden.'),
    -5 => _('Es gibt schon einen Stamm mit diesem K&uuml;rzel;'),
    -4 => _('Sie konnten nicht austreten. Vermutlich geh&ouml;ren Sie gar keinem Stamm an.'),
    -3 => _('Sie konnten dem Stamm nicht beitreten. Vermutlich sind Sie schon bei einem anderen Stamm Mitglied.'),
    -2 => _('Passwort und Stammesk&uuml;rzel stimmen nicht &uuml;berein.'),
    -1 => _('Bei der Aktion ist ein unerwarteter Datenbankfehler aufgetreten!'),
     1 => _('Sie sind dem Stamm beigetreten.'),
     2 => _('Sie haben den Stamm verlassen.'),
     3 => _('Der Stamm wurde erfolgreich angelegt.'),
     4 => _('Sie waren das letzte Mitglied, der Stamm wurde aufgel&ouml;st'),
     5 => _('Die Nachricht wurde eingetragen'),
    10 => _('Dieser Stammesname ist nicht erlaubt!'));

  // process form data

  $messageID = 0;
  if ($tribeAction = request_var('tribeAction', 0)) {
    switch ($tribeAction) {

      case TRIBE_ACTION_JOIN:
        if (tribe_validatePassword(request_var('password', "")) && tribe_validateTag(request_var('tag', ""))) {
          $messageID = tribe_processJoin($playerID, request_var('tag', ""), request_var('password', ""));
        } else {
          $messageID = tribe_processJoinFailed();
        }
        break;

      case TRIBE_ACTION_CREATE:
        if (tribe_validatePassword(request_var('password', "")) && tribe_validateTag(request_var('tag', ""))){
          $messageID = tribe_processCreate($playerID, request_var('tag', ""), request_var('password', ""), request_var('restore_rank', 'no') == 'yes');
        } else {
          $messageID = tribe_processCreateFailed();
        }
        break;

      case TRIBE_ACTION_LEAVE:
        $messageID = tribe_processLeave($playerID, $tribe);
        break;

      case TRIBE_ACTION_MESSAGE:
        $mesText = request_var('messageText', "");
        $text = (!empty($mesText)) ? $mesText : "";
        $ingame = (request_var('ingame', 0)) ? true : false;

        if ($mesText && $ingame){
          $messageID = tribe_processSendTribeIngameMessage($playerID, $tribe, $text);
        } else if ($mesText && !$ingame){
          $messageID = tribe_processSendTribeMessage($playerID, $tribe, $text);
        }
        break;
    }

    if ($tribeAction == TRIBE_ACTION_JOIN  ||
        $tribeAction == TRIBE_ACTION_LEAVE ||
        $tribeAction == TRIBE_ACTION_CREATE) {

      // the tribe might have changed
      page_refreshUserData();
      $tribe = $_SESSION['player']->tribe;
    }

  }

// ----------------------------------------------------------------------------
// ------- SECTION FOR PLAYERS WITHOUT MEMBERSHIP -----------------------------

  if (!$tribe) {            // not a tribe member
    $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'tribe.ihtml');

    if ($messageID) {
      tmpl_set($template, "MESSAGE/message", $messageText[$messageID]);
    }

    // ------------------------------------------------------------------------
    // ----------- Join existing tribe ----------------------------------------

    tmpl_iterate($template, "FORM");

    $form = array(
      "heading"         => _('Einem Stamm beitreten'),
      "modus_name"      => "modus",
      "modus_value"     => TRIBE,
      "action_name"     => "tribeAction",
      "action_value"    => TRIBE_ACTION_JOIN,

      "TAG/fieldname"   => "tag",
      "TAG/tag_regexp"  => _("Buchstaben, Zahlen, Bindestriche; keine Zahlen oder Bindestrich am Anfang"),
      "TAG/value"       => ($tribe ? $tribe : request_var('tag', "")),
      "TAG/size"        => 8,
      "TAG/maxlength"   => 8,
      "TAG/form_prefix"     => "join_",

      "PASSWORD/fieldname" => "password",
      "PASSWORD/pw_regexp" => _("mind. 6 Buchstaben oder Zahlen"),
      "PASSWORD/value"     => request_var('password', ""),
      "PASSWORD/size"      => 8,
      "PASSWORD/maxlength" => 15,
      "PASSWORD/form_prefix"     => "join_",

      "BUTTON/caption"  => _('Beitreten')
      );

    tmpl_set($template, "FORM", $form);

    // ------------------------------------------------------------------------
    // ----------- Create new tribe -------------------------------------------

    tmpl_iterate($template, "FORM");

    // only change the different values for creation
    $form["heading"]                  = _('Einen neuen Stamm gr&uuml;nden');
    $form["TAG/form_prefix"]          = "create_";
    $form["PASSWORD/form_prefix"]     = "create_";
    $form["action_value"]             = TRIBE_ACTION_CREATE;
    $form["BUTTON/caption"]           = _('Neu gr&uuml;nden');
    $form["RESTORERANKING/fieldName"] = "restore_rank";

    tmpl_set($template, "FORM", $form);
  }


// ----------------------------------------------------------------------------
// ------- SECTION FOR TRIBE MEMBERS ------------- ----------------------------
  else {
    if (!($tribeData = tribe_getTribeByTag($tribe))) {
      return _('Fehler');
    }

    $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'tribeMember.ihtml');

    if ($messageID) {
      tmpl_set($template, "MESSAGE/message", $messageText[$messageID]);
    }

    if (tribe_isLeaderOrJuniorLeader($playerID, $tribe)) {
      $adminData = array(
          "modus_name"        => "modus",
          "modus_value"       => TRIBE_ADMIN,
          "TRIBEMESSAGEFORM"  => array (
          "message_name"      => "messageText",
          "modus_name"        => "modus",
          "modus_value"       => TRIBE,
          "action_name"       => "tribeAction",
          "action_value"      => TRIBE_ACTION_MESSAGE));
      tmpl_set($template, "ADMIN", $adminData);
    }

    $data = array(
      "tag"          => $tribe,
      "name"         => $tribeData['name'],
      "link_tribe"   => "modus=".TRIBE_DETAIL."&amp;tribe=".urlencode(unhtmlentities($tribeData['tag'])),

      "MEMBERS/tag_name"      => "tag",
      "MEMBERS/tag_value"     => $tribe,
      "MEMBERS/modus_name"    => "modus",
      "MEMBERS/modus_value"   => TRIBE_PLAYER_LIST,

      "LEAVE/modus_name"      => "modus",
      "LEAVE/modus_value"     => TRIBE,
      "LEAVE/action_name"     => "tribeAction",
      "LEAVE/action_value"    => TRIBE_ACTION_LEAVE
      );

    if ($tribeData['leaderID']) {
      if($tribeData['juniorLeaderID']) {
        $JuniorAdmin = $targetPlayer = new Player(getPlayerByID($tribeData['juniorLeaderID']));
      }
      else {
        $JuniorAdmin = $targetPlayer = array();
      }

      $leaderData = array (
        "LEADER/name"                 => $tribeData['leaderName'],
        "LEADER/leaderID_name"        => "detailID",
        "LEADER/leaderID_value"       => $tribeData['leaderID'],
        "LEADER/juniorLeaderName"     => (isset($JuniorAdmin->name)) ? $JuniorAdmin->name : '',
        "LEADER/juniorLeaderID_name"  => "detailID",
        "LEADER/juniorLeaderID_value" => (isset($JuniorAdmin->playerID)) ? $JuniorAdmin->playerID : 0,
        "LEADER/modus_name"           => "modus",
        "LEADER/modus_value"          => PLAYER_DETAIL);
        }
    else {
      $leaderData = array ("NOLEADER/message" => _('Ihr Stamm hat zur Zeit keinen Anf&uuml;hrer.'));
    }

    $leaderDeterminationData = array (
      "LEADERDETERMINATION/modus_name"     => "modus",
      "LEADERDETERMINATION/modus_value"    => TRIBE_LEADER_DETERMINATION);

    $governmentData = array("GOVERNMENT/name" => $governmentList[$tribeData['governmentID']]['name']);

    if ($warTargets=relation_getWarTargetsAndFame($tribe)){
      tmpl_set($template, "NORMAL/WAR", array());

      foreach($warTargets as $target){
        $target_facts = array(
          "target"         =>  $target["target"],
          "fame_own"       =>  $target["fame_own"],
          "fame_target"    =>  $target["fame_target"],
          "percent_actual" =>  $target["percent_actual"]
        );

        if ($target["isForcedSurrenderTheoreticallyPossible"]) {
          $target_facts["FORCEDSURRENDER/percent_estimated"] = $target["percent_estimated"];
          if ($target["isForcedSurrenderPracticallyPossible"]) {
            $target_facts["FORCEDSURRENDER/class"] = "enough";
          } else if ($target["isForcedSurrenderPracticallyPossibleForTarget"]) {
            $target_facts["FORCEDSURRENDER/class"] = "less";
          } else {
            $target_facts["FORCEDSURRENDER/class"] = "";
          }
        }

        tmpl_iterate($template, "NORMAL/WAR/TARGET");
        tmpl_set($template, "NORMAL/WAR/TARGET", $target_facts);
      }
    }

    // init messages class
    $messagesClass = new Messages;

    $alternate=0;
    if ($messages = tribe_getTribeMessages($tribe)) {
      foreach($messages AS $messageID => $messageData) {
        $message = array(
          "time"          => $messageData['date'],
          "subject"       => $messageData['messageSubject'],
          "message"       => $messagesClass->p($messageData['messageText']),
          "alternate"     => ((++$alternate % 2) ? 'row_alternate' : ''));

        tmpl_iterate($template, "NORMAL/TRIBEMESSAGE");
        tmpl_set($template, "NORMAL/TRIBEMESSAGE", $message);    
      }
    }

    $data = array_merge($data, $leaderData, $leaderDeterminationData, $governmentData);

    tmpl_set($template, "NORMAL", $data);
  }

  return tmpl_parse($template);
}

?>