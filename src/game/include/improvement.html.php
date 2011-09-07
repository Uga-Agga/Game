<?php
/*
 * improvement.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function improvement_getImprovementDetail($caveID, &$details) {
  global $buildingTypeList, $template;

  // open template
  $template->setFile('improvement.tmpl');

  // messages
  $messageText = array (
    0 => array('type' => 'success', 'message' => _('Der Arbeitsauftrag wurde erfolgreich gestoppt.')),
    1 => array('type' => 'error', 'message' => _('Es konnte kein Arbeitsauftrag gestoppt werden.')),
    2 => array('type' => 'error', 'message' => _('Der Auftrag konnte nicht erteilt werden. Es fehlen die notwendigen Voraussetzungen.')),
    3 => array('type' => 'success', 'message' => _('Der Arbeitsauftrag wurde erfolgreich erteilt.')),
    5 => array('type' => 'success', 'message' => _('Das Gebäude wurde erfolgreich abgerissen.')),
    6 => array('type' => 'error', 'message' => _('Das Gebäude konnte nicht abgerissen werden.')),
    7 => array('type' => 'error', 'message' => _('Sie haben von der Sorte gar keine Gebäude.')),
    8 => array('type' => 'error', 'message' => sprintf(_('Sie können derzeit kein Gebäude oder Verteidigungen abreißen, weil erst vor Kurzem etwas in dieser Höhle abgerissen wurde. Generell muss zwischen zwei Abrissen eine Zeitspanne von %d Minuten liegen.'), TORE_DOWN_TIMEOUT)),
    9 => array('type' => 'error', 'message' => _('Der Arbeitsauftrag konnte nicht erteilt werden. Ein Arbeitsauftrag ist schon im gange.'))
  );

  // get this cave's queue
  $queue = improvement_getQueue($_SESSION['player']->playerID, $caveID);

  $action = request_var('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Erweiterung bauen
*
****************************************************************************************************/
    case 'build':
      $buildingID = request_var('buildingID', -1);
      if ($buildingID == -1) {
        $messageID = 2;
        break;
      }

      // check queue exist
      if ($queue) {
        $messageID = 9;
        break;
      }

      $messageID = improvement_processOrder($buildingID, $caveID, $details);
      $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
      $queue = improvement_getQueue($_SESSION['player']->playerID, $caveID);
    break;

/****************************************************************************************************
*
* Ausbau der Erweiterung abbrechen
*
****************************************************************************************************/
    case 'cancelOrder':
      $eventID = request_var('id', 0);
      if ($eventID == 0) {
        $messageID = 1;
        break;
      }

      // check queue exist
      if (!$queue || $queue['event_expansionID'] != $eventID) {
        $messageID = 1;
        break;
      }

      if (isset($_POST['cancelOrderConfirm'])) {
        $messageID = improvement_cancelOrder($eventID, $caveID);
        if ($messageID == 0) {
          $queue = '';
        }
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'cancelOrder',
          'confirm_id'      => $eventID,
          'confirm_mode'    => IMPROVEMENT_BUILDER,
          'confirm_msg'     => sprintf(_('Möchtest du den Arbeitsauftrag von <span class="bold">%s</span> abbrechen?'), $buildingTypeList[$queue['expansionID']]->name),
        ));
      }
    break;

/****************************************************************************************************
*
* Erweiterung abreißen
*
****************************************************************************************************/
    case 'demolishing':
      $improvementID = request_var('id', -1);
      if ($improvementID == -1) {
        $messageID = 4;
        break;
      }

      if (!isset($buildingTypeList[$improvementID])) {
        $messageID = 4;
        break;
      }

      if (isset($_POST['cancelOrderConfirm'])) {
        $messageID = improvement_Demolishing($improvementID, $caveID, $details);
        $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'demolishing',
          'confirm_id'      => $improvementID,
          'confirm_mode'    => IMPROVEMENT_BUILDER,
          'confirm_msg'     => sprintf(_('Möchtest du <span class="bold">%s</span> einmal abreißen?'), $buildingTypeList[$improvementID]->name),
        ));
      }
    break;
  }

  $improvement = $improvementRelict = $improvementUnqualified = array();
  foreach ($buildingTypeList as $id => $building) {
    $maxLevel = round(eval('return ' . formula_parseToPHP("{$building->maxLevel};", '$details')));
    $notenough = FALSE;

    $result = rules_checkDependencies($building, $details);

/****************************************************************************************************
*
* Erweiterungen die gebaut werden können.
*
****************************************************************************************************/
    if ($result === TRUE) {
      $improvement[$building->buildingID] = array(
        'name'             => $building->name,
        'dbFieldName'      => $building->dbFieldName,
        'building_id'      => $building->buildingID,
        'cave_id'          => $caveID,
        'time'             => time_formatDuration(eval('return ' . formula_parseToPHP($building->productionTimeFunction . ";", '$details')) * BUILDING_TIME_BASE_FACTOR),
        'maxlevel'         => $maxLevel,
        'currentlevel'     => "0" + $details[$building->dbFieldName],
        'description'      => $building->description,
        'duration_formula' => formula_parseToReadable($building->productionTimeFunction),
        'breakdown_link'   => ($details[$building->dbFieldName] > 0) ? true : false,
      );
      $improvement[$building->buildingID] = array_merge($improvement[$building->buildingID], parseCost($building, $details));

      // show the building link ?!
      if ($queue) {
        $improvement[$building->buildingID]['no_build_msg'] = _('Ausbau im Gange');
      } else if ($improvement[$building->buildingID]['notenough'] && $maxLevel > $details[$building->dbFieldName]) {
        $improvement[$building->buildingID]['no_build_msg'] = _('Zu wenig Rohstoffe');
      } else if ($maxLevel > $details[$building->dbFieldName]) {
        $improvement[$building->buildingID]['build_link'] = true;
      } else {
        $improvement[$building->buildingID]['no_build_msg'] = _('Max. Stufe');
      }

/****************************************************************************************************
*
* Erweiterungen die zwar nicht gebaut werden können aber schon in der Höhle sind (Relikt)
*
****************************************************************************************************/
    } else if ($details[$building->dbFieldName]) {
      $improvementRelict[$building->buildingID] = array(
        'name'             => $building->name,
        'dbFieldName'      => $building->dbFieldName,
        'building_id'      => $building->buildingID,
        'cave_id'          => $caveID,
        'currentlevel'     => "0" + $details[$building->dbFieldName],
        'description'      => $building->description,
        'duration_formula' => formula_parseToReadable($building->productionTimeFunction),
        'dependencies'     => ($result !== FALSE) ? $result : false
      );

/****************************************************************************************************
*
* Erweiterungen die nicht gebaut werden können.
*
****************************************************************************************************/
    } else if ($result !== FALSE && !$building->nodocumentation) {
      $improvementUnqualified[$building->buildingID] = array(
        'name'             => $building->name,
        'dbFieldName'      => $building->dbFieldName,
        'building_id'      => $building->buildingID,
        'cave_id'          => $caveID,
        'dependencies'     => $result,
        'description'      => $building->description,
        'duration_formula' => formula_parseToReadable($building->productionTimeFunction)
      );
    }
  }

/****************************************************************************************************
*
* Irgendwas im Ausbau?
*
****************************************************************************************************/
  if ($queue) {
    $template->addVars(array(
      'quene_show'      => true,
      'quene_name'      => $buildingTypeList[$queue['expansionID']]->name,
      'quene_nextlevel' => $details[$buildingTypeList[$queue['expansionID']]->dbFieldName] + 1,
      'quene_finish'    => time_formatDatetime($queue['end']),
      'quene_modus'     => IMPROVEMENT_BUILDER,
      'quene_event_id'  => $queue['event_expansionID']
    ));
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'cave_id'                 => $caveID,
    'status_msg'              => (isset($messageID)) ? $messageText[$messageID] : '',
    'improvement'             => $improvement,
    'improvement_unqualified' => $improvementUnqualified,
    'improvement_relict'      => $improvementRelict,
  ));
}

?>