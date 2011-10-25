<?php
/*
 * science.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function science_getScienceDetail($caveID, &$details){
  global $scienceTypeList, $template;

  // open template
  $template->setFile('scienceBuilder.tmpl');

  // messages
  $messageText = array(
    0 => array('type' => 'success', 'message' =>_('Der Forschungsauftrag wurde erfolgreich gestoppt.')),
    1 => array('type' => 'error', 'message' =>_('Es konnte kein Forschungsauftrag gestoppt werden.')),
    2 => array('type' => 'error', 'message' =>_('Der Auftrag konnte nicht erteilt werden. Es fehlen die notwendigen Voraussetzungen.')),
    3 => array('type' => 'success', 'message' =>_('Der Auftrag wurde erteilt')),
    4 => array('type' => 'info', 'message' =>_('Dieses Wissen wird schon in einer anderen Höhle erforscht.')),
    5 => array('type' => 'info', 'message' =>_('Es wird gerade in einer anderen Höhle Wissen erforscht, das dieses Wissen ausschließt.'))
  );

  // get this cave's queue
  $queue = science_getQueue($_SESSION['player']->playerID, $caveID);

  $action = request_var('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Forschung starten
*
****************************************************************************************************/
    case 'build':
      $scienceID = request_var('scienceID', -1);
      if ($scienceID == -1) {
        $messageID = 2;
        break;
      }

      // check queue exist
      if ($queue) {
        $messageID = 2;
        break;
      }

      $messageID = science_processOrder($scienceID, $caveID, $details);
      $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
      $queue = science_getQueue($_SESSION['player']->playerID, $caveID);
    break;

/****************************************************************************************************
*
* Forschung abbrechen
*
****************************************************************************************************/
    case 'cancelOrder':
      $eventID = request_var('id', 0);
      if ($eventID == 0) {
        $messageID = 2;
        break;
      }

      // check queue exist
      if (!$queue || $queue['event_scienceID'] != $eventID) {
        $messageID = 2;
        break;
      }

      if (isset($_POST['cancelOrderConfirm'])) {
        $messageID = science_cancelOrder($eventID, $caveID);

        if ($messageID == 0) {
          $queue = '';
        }
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'cancelOrder',
          'confirm_id'      => $eventID,
          'confirm_mode'    => SCIENCE_BUILDER,
          'confirm_msg'     => sprintf(_('Möchtest die Forschung von <span class="bold">%s</span> abbrechen?'), $scienceTypeList[$queue['scienceID']]->name),
        ));
      }
    break;
  }


  $sciences = $sciencesUnqualified = array();
  foreach ($scienceTypeList as $id => $science) {
    $maxLevel = round(eval('return '.formula_parseToPHP("{$science->maxLevel};", '$details')));
    $notenough=FALSE;

    $result = rules_checkDependencies($science, $details);

/****************************************************************************************************
*
*  Forschungen die man forschen kann.
*
****************************************************************************************************/
    if ($result === TRUE) {
      $sciences[$science->scienceID] = array(
        'name'             => $science->name,
        'dbFieldName'      => $science->dbFieldName,
        'science_id'       => $science->scienceID,
        'modus'            => SCIENCE_BUILDER,
        'cave_id'          => $caveID,
        'time'             => time_formatDuration(eval('return ' . formula_parseToPHP($science->productionTimeFunction . ";", '$details')) * SCIENCE_TIME_BASE_FACTOR),
        'maxlevel'         => $maxLevel,
        'currentlevel'     => "0" + $details[$science->dbFieldName],
        'description'      => $science->description,
        'duration_formula' => formula_parseToReadable($science->productionTimeFunction)
      );
      $sciences[$science->scienceID] = array_merge($sciences[$science->scienceID], parseCost($science, $details));

      // show the building link ?!
      if ($queue) {
        $sciences[$science->scienceID]['no_build_msg'] = _('Erforschung im Gange');
      } else if ($sciences[$science->scienceID]['notenough'] && $maxLevel > $details[$science->dbFieldName]) {
        $sciences[$science->scienceID]['no_build_msg'] = _('Zu wenig Rohstoffe');
      } else if ($maxLevel > $details[$science->dbFieldName]) {
        $sciences[$science->scienceID]['build_link'] = true;
      } else {
        $sciences[$science->scienceID]['no_build_msg'] = _('Max. Stufe');
      }

/****************************************************************************************************
*
* Forschungen die noch nicht geforscht werden können.
*
****************************************************************************************************/
    } else if ($result !== FALSE && !$science->nodocumentation){
      $sciencesUnqualified[$science->scienceID] = array(
        'name'             => $science->name,
        'dbFieldName'      => $science->dbFieldName,
        'science_id'       => $science->scienceID,
        'modus'            => SCIENCE_DETAIL,
        'caveID'           => $caveID,
        'dependencies'     => $result,
        'description'      => $science->description,
        'duration_formula' => formula_parseToReadable($science->productionTimeFunction)
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
      'quene_name'      => $scienceTypeList[$queue['scienceID']]->name,
      'quene_nextlevel' => $details[$scienceTypeList[$queue['scienceID']]->dbFieldName] + 1,
      'quene_finish'    => time_formatDatetime($queue['end']),
      'quene_modus'     => SCIENCE_BUILDER,
      'quene_event_id'  => $queue['event_scienceID']
    ));
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'cave_id'             => $caveID,
    'status_msg'          => (isset($messageID)) ? $messageText[$messageID] : '',
    'science'             => $sciences,
    'science_unqualified' => $sciencesUnqualified,
  ));
}

?>