<?php
/*
 * defense.html.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011-2012 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');
init_DefenseCategories();

################################################################################

/**
 *
 */

function defense_builder($caveID, &$details) {
  global $template;

  // open template
  $template->setFile('defenseBuilder.tmpl');

  //messages
  $messageText = array (
    0 => array('type' => 'error', 'message' => _('Es konnte kein Arbeitsauftrag gestoppt werden.')),
    1 => array('type' => 'success', 'message' => _('Der Arbeitsauftrag wurde erfolgreich gestoppt.')),
    2 => array('type' => 'info', 'message' => sprintf(_('Du kannst derzeit kein Gebäude oder Verteidigungen abreissen, weil erst vor Kurzem etwas in dieser Höhle abgerissen wurde. Generell muss zwischen zwei Abrissen eine Zeitspanne von %d Minuten liegen.'), TORE_DOWN_TIMEOUT)),
    3 => array('type' => 'error', 'message' => _('Du hast von der Sorte gar keine Gebäude')),
    4 => array('type' => 'error', 'message' => _('Das Gebäude konnte nicht abgerissen werden.')),
    5 => array('type' => 'success', 'message' => _('Das Gebäude wurde erfolgreich abgerissen.')),
    6 => array('type' => 'error', 'message' => _('Der Auftrag konnte nicht erteilt werden. Es fehlen die notwendigen Voraussetzungen.')),
    7 => array('type' => 'success', 'message' => _('Der Arbeitsauftrag wurde erteilt.')),
    8 => array('type' => 'error', 'message' => _('Der Arbeitsauftrag konnte nicht erteilt werden. Ein Arbeitsauftrag ist schon im Gange.')),
  );

  // get this cave's queue
  $queue = defense_getQueue($_SESSION['player']->playerID, $caveID);

  $action = Request::getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Verteidigungsanlage bauen
*
****************************************************************************************************/
    case 'build':
      $defenseID = Request::getVar('defenseID', -1);
      if ($defenseID == -1 || !isset($GLOBALS['defenseSystemTypeList'][$defenseID])) {
        $messageID = 6;
        break;
      }

      if (!rules_checkDependencies($GLOBALS['defenseSystemTypeList'][$defenseID], $details))  {
        $messageID = 6;
        break;
      }

      // check queue exist
      if (sizeof($queue)) {
        $messageID = 8;
        break;
      }

      $messageID = defense_processOrder($defenseID, $caveID, $details);
      $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
      $queue = defense_getQueue($_SESSION['player']->playerID, $caveID);
    break;

/****************************************************************************************************
*
* Ausbau der Verteidigungsanlage abbrechen
*
****************************************************************************************************/
    case 'cancelOrder':
      $eventID = Request::getVar('id', 0);
      if ($eventID == 0) {
        $messageID = 0;
        break;
      }

      // check queue exist
      if (!sizeof($queue) || $queue['event_defenseSystemID'] != $eventID) {
        $messageID = 0;
        break;
      }

      if (Request::isPost('postConfirm')) {
        $messageID = defense_cancelOrder($eventID, $caveID);

        if ($messageID == 1) {
          $queue = null;
        }
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'cancelOrder',
          'confirm_id'      => $eventID,
          'confirm_mode'    => DEFENSE_BUILDER,
          'confirm_msg'     => sprintf(_('Möchtest du den Arbeitsauftrag von <span class="bold">%s</span> abbrechen?'), $GLOBALS['defenseSystemTypeList'][$queue['defenseSystemID']]->name),
        ));
      }
    break;

/****************************************************************************************************
*
* Verteidigungsanlage abreißen
*
****************************************************************************************************/
    case 'demolishing':
      $defenseID = Request::getVar('id', -1);
      if ($defenseID == -1) {
        $messageID = 4;
        break;
      }

      if (!isset($GLOBALS['defenseSystemTypeList'][$defenseID])) {
        $messageID = 4;
        break;
      }

      if (Request::isPost('postConfirm')) {
        $messageID = defense_Demolishing($defenseID, $caveID, $details);
        $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'demolishing',
          'confirm_id'      => $defenseID,
          'confirm_mode'    => DEFENSE_BUILDER,
          'confirm_msg'     => sprintf(_('Möchtest du <span class="bold">%s</span> einmal abreißen?'), $GLOBALS['defenseSystemTypeList'][$defenseID]->name),
        ));
      }
    break;
  }

  $defenseSystem = $defenseSystemRelict = $defenseSystemUnqualified = array();
  foreach ($GLOBALS['defenseSystemTypeList'] as $id => $defense) {
    $maxLevel = round(eval('return '.formula_parseToPHP("{$defense->maxLevel};", '$details')));

    $result = rules_checkDependencies($defense, $details);

    // if all requirements are met, but the maxLevel is 0, treat it like a non-buildable
    if ($maxLevel <= 0 && $result === TRUE) {
      $result = (!$details[$defense->dbFieldName]) ? _('Max. Stufe: 0') : false;
    }

/****************************************************************************************************
*
* Verteidigungsanlage die gebaut werden können.
*
****************************************************************************************************/
    if ($result === TRUE) {
      $defenseSystem[$defense->defenseCategory]['items'][$defense->defenseSystemID] = array(
        'name'             => $defense->name,
        'dbFieldName'      => $defense->dbFieldName,
        'defense_id'       => $defense->defenseSystemID,
        'cave_id'          => $caveID,
        'time'             => time_formatDuration(eval('return ' . formula_parseToPHP($defense->productionTimeFunction . ";", '$details')) * DEFENSESYSTEM_TIME_BASE_FACTOR),
        'maxlevel'         => $maxLevel,
        'currentlevel'     => "0" + $details[$defense->dbFieldName],
        'antiSpyChance'    => $defense->antiSpyChance,
        'breakdown_link'   => ($details[$defense->dbFieldName] > 0) ? true : false
      );
      $defenseSystem[$defense->defenseCategory]['items'][$defense->defenseSystemID] = array_merge($defenseSystem[$defense->defenseCategory]['items'][$defense->defenseSystemID], parseCost($defense, $details));

      // show the building link ?!
      if (sizeof($queue)) {
        $defenseSystem[$defense->defenseCategory]['items'][$defense->defenseSystemID]['no_build_msg'] = _('Ausbau im Gange');
      } else if ($defenseSystem[$defense->defenseCategory]['items'][$defense->defenseSystemID]['notenough'] && $maxLevel > $details[$defense->dbFieldName]) {
        $defenseSystem[$defense->defenseCategory]['items'][$defense->defenseSystemID]['no_build_msg'] = _('Zu wenig Rohstoffe');
      } else if ($maxLevel > $details[$defense->dbFieldName]) {
        $defenseSystem[$defense->defenseCategory]['items'][$defense->defenseSystemID]['build_link'] = true;
      } else {
        $defenseSystem[$defense->defenseCategory]['items'][$defense->defenseSystemID]['no_build_msg'] = _('Max. Stufe');
      }

/****************************************************************************************************
*
* Verteidigungsanlage die zwar nicht gebaut werden können aber schon in der Höhle sind (Relikt)
*
****************************************************************************************************/
    } else if ($details[$defense->dbFieldName]){
      $defenseSystemRelict[$defense->defenseCategory]['items'][$defense->defenseSystemID] = array(
        'name'             => $defense->name,
        'dbFieldName'      => $defense->dbFieldName,
        'defense_id'       => $defense->defenseSystemID,
        'cave_id'          => $caveID,
        'currentlevel'     => "0" + $details[$defense->dbFieldName],
        'dependencies'     => ($result !== false) ? $result : false
      );

/****************************************************************************************************
*
* Verteidigungsanlage die nicht gebaut werden können.
*
****************************************************************************************************/
    } else if ($result !== false && !$defense->nodocumentation){
      $defenseSystemUnqualified[$defense->defenseCategory]['items'][$defense->defenseSystemID] = array(
        'name'             => $defense->name,
        'dbFieldName'      => $defense->dbFieldName,
        'defense_id'       => $defense->defenseSystemID,
        'cave_id'          => $caveID,
        'dependencies'     => $result,
        'antiSpyChance'    => $defense->antiSpyChance,
      );
    }
  }

/****************************************************************************************************
*
* Namen zu den Kategorien hinzufügen & sortieren
*
****************************************************************************************************/
  $tmpDefenseSystem = $tmpDefenseSystemRelict = $tmpDefenseSystemUnqualified = array();
  foreach ($GLOBALS['defenseCategoryTypeList'] as $defenseCategory) {
    if (isset($defenseSystem[$defenseCategory->id])) {
      $tmpDefenseSystem[$defenseCategory->sortID] = array(
        'id'    => $defenseCategory->id,
        'name'  => $defenseCategory->name,
        'items' => $defenseSystem[$defenseCategory->id]['items']
      );
      unset($defenseSystem[$defenseCategory->id]);
    }

    if (isset($defenseSystemRelict[$defenseCategory->id])) {
      $tmpDefenseSystemRelict[$defenseCategory->sortID] = array(
        'id'    => $defenseCategory->id,
        'name'  => $defenseCategory->name,
        'items' => $defenseSystemRelict[$defenseCategory->id]['items']
      );
      unset($defenseSystemRelict[$defenseCategory->id]);
    }

    if (isset($defenseSystemUnqualified[$defenseCategory->id])) {
      $tmpDefenseSystemUnqualified[$defenseCategory->sortID] = array(
        'id'    => $defenseCategory->id,
        'name'  => $defenseCategory->name,
        'items' => $defenseSystemUnqualified[$defenseCategory->id]['items']
      );
      unset($defenseSystemUnqualified[$defenseCategory->id]);
    }
  }

  $defenseSystem            = $tmpDefenseSystem;
  $defenseSystemRelict      = $tmpDefenseSystemRelict;
  $defenseSystemUnqualified = $tmpDefenseSystemUnqualified;
  unset($tmpDefenseSystem, $tmpDefenseSystemRelict, $tmpDefenseSystemUnqualified);

  ksort($defenseSystem);
  ksort($defenseSystemRelict);
  ksort($defenseSystemUnqualified);

/****************************************************************************************************
*
* Irgendwas im Ausbau?
*
****************************************************************************************************/
  if (sizeof($queue)) {
    $template->addVars(array(
      'quene_show'      => true,
      'quene_name'      => $GLOBALS['defenseSystemTypeList'][$queue['defenseSystemID']]->name,
      'quene_nextlevel' => $details[$GLOBALS['defenseSystemTypeList'][$queue['defenseSystemID']]->dbFieldName] + 1,
      'quene_finish'    => time_formatDatetime($queue['end']),
      'quene_modus'     => DEFENSE_BUILDER,
      'quene_event_id'  => $queue['event_defenseSystemID']
    ));
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'cave_id'                    => $caveID,
    'status_msg'                 => (isset($messageID)) ? $messageText[$messageID] : '',
    'defense_system'             => $defenseSystem,
    'defense_system_unqualified' => $defenseSystemUnqualified,
    'defense_system_relict'      => $defenseSystemRelict,
  ));
}

?>