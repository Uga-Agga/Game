<?php
/*
 * digest.html.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/**
 * Diese Funktion stellt alle Daten für den Terminkalender zusammen und parsed
 * sie danach in das Template.
 *
 * @param  meineHoehlen  Enthält die Records aller eigenen Höhlen.
 */

function digest_getDigest($ownCaves) {
  global $template;

  // open template
  $template->setFile('digest.tmpl');

  // get movements
  // don't show returning movements
  // don't show details for each movement
  $movements = digest_getMovements($ownCaves, array(), true);

  $ownMovement = $opponentMovement = array();
  // set each movement into the template
  foreach($movements AS $move) {
    // own movements
    if ($move['isOwnMovement']){
      $ownMovement[] = $move;
    // adverse movements
    } else {
      $opponentMovement[] = $move;
    }
  }

  // get artefact initiations and parse them into the template
  $initiations = digest_getInitiationDates($ownCaves);
  $appointments = digest_getAppointments($ownCaves);

  // fill arrays with potential shortcuts
  $units = $buildings = $defenses = $sciences = array();
  foreach ($ownCaves as $value) {
    $units[$value['caveID']] = array(
      'caveID'    => $value['caveID'],
      'cave_name' => $value['name'],
      'modus'     => UNIT_BUILDER);
    $buildings[$value['caveID']] = array(
      'caveID' => $value['caveID'],
      'cave_name' => $value['name'],
      'modus'  => IMPROVEMENT_BUILDER);
    $defenses[$value['caveID']] = array(
      'caveID' => $value['caveID'],
      'cave_name' => $value['name'],
      'modus'  => DEFENSE_BUILDER);
    $sciences[$value['caveID']] = array(
      'caveID' => $value['caveID'],
      'cave_name' => $value['name'],
      'modus' => SCIENCE_BUILDER);
  }

  // remove elements in these arrays, if there is such an appointment
  foreach ($appointments as $value) {
    switch ($value['modus']) {
      case UNIT_BUILDER:
        unset($units[$value['cave_id']]);
        break;

      case IMPROVEMENT_BUILDER:
        unset($buildings[$value['cave_id']]);
        break;

      case DEFENSE_BUILDER:
        unset($defenses[$value['cave_id']]);
        break;

      case SCIENCE_BUILDER:
        unset($sciences[$value['cave_id']]);
        break;
    }
  }

  // remove elements in these arrays, if there is such an appointment
  $caveAction = array();
  foreach ($appointments as $value) {
    switch ($value['modus']) {
      case UNIT_BUILDER:
        $caveAction['units'][$value['cave_id']] = true;
        break;

      case IMPROVEMENT_BUILDER:
        $caveAction['buildings'][$value['cave_id']] = true;
        break;

      case DEFENSE_BUILDER:
        $caveAction['defenses'][$value['cave_id']] = true;
        break;

      case SCIENCE_BUILDER:
        $caveAction['sciences'][$value['cave_id']] = true;
        break;
    }
  }

  $u = $b = $d = $s = 0;
  $caveNoAction = array();
  foreach ($ownCaves as $value) {
    if (!isset($caveAction['units'][$value['caveID']])) {
      $caveNoAction[$u++]['units'] = array(
        'caveID'    => $value['caveID'],
        'cave_name' => $value['name'],
        'modus'     => UNIT_BUILDER);
    }

    if (!isset($caveAction['buildings'][$value['caveID']])) {
      $caveNoAction[$b++]['buildings'] = array(
        'caveID' => $value['caveID'],
        'cave_name' => $value['name'],
        'modus'  => IMPROVEMENT_BUILDER);
    }

    if (!isset($caveAction['defenses'][$value['caveID']])) {
      $caveNoAction[$d++]['defenses'] = array(
        'caveID' => $value['caveID'],
        'cave_name' => $value['name'],
        'modus'  => DEFENSE_BUILDER);
    }

    if (!isset($caveAction['sciences'][$value['caveID']])) {
      $caveNoAction[$s++]['sciences'] = array(
        'caveID' => $value['caveID'],
        'cave_name' => $value['name'],
        'modus' => SCIENCE_BUILDER);
    }
  }

  // send to template
  $template->addVars(array(
    'own_movements'      => $ownMovement,
    'opponent_movement'  => $opponentMovement,
    'initiations'        => $initiations,
    'appointments'       => $appointments,
    'cave_no_action'     => $caveNoAction,
    'microtime'          => time() . '000'
  ));
}

?>