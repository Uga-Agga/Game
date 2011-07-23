<?php
/*
 * digest.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/**
 * Diese Funktion stellt alle Daten f�r den Terminkalender zusammen und parsed
 * sie danach in das Template.
 *
 * @param  meineHoehlen  Enth�lt die Records aller eigenen H�hlen.
 */

function digest_getDigest($ownCaves) {
  global $config, $template;

  // open template
  $template->setFile('easyDigest.tmpl');
  
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
  foreach ($ownCaves as $value){
    $units[$value['caveID']] = array(
      'caveID'    => $value['caveID'],
      'cave_name' => $value['name'],
      'modus'     => UNIT_BUILDER);
    $buildings[$value['caveID']] = array(
      'caveID' => $value['caveID'],
      'cave_name' => $value['name'],
      'modus'  => IMPROVEMENT_DETAIL);
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
    switch ($value['modus']){
      case UNIT_BUILDER:
        unset($units[$value['caveID']]);
        break;

      case IMPROVEMENT_DETAIL:
        unset($buildings[$value['caveID']]);
        break;

      case EXTERNAL_BUILDER:
        unset($defenses[$value['caveID']]);
        break;

      case SCIENCE:
        unset($sciences[$value['caveID']]);
        break;
    }
  }

  // send to template
  $template->addVars(array(
    'own_movements'     => $ownMovement,
    'opponent_movement'  => $opponentMovement,
    'initiations'        => $initiations,
    'appointments'       => $appointments,
    'no_building'        => $buildings,
    'no_unit'            => $units,
    'no_defenses'        => $defenses,
    'no_sciences'        => $sciences,
    'microtime'          => time() . '000'
  ));
}

?>