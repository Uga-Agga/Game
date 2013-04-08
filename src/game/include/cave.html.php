<?php
/*
 * cave.html.php -
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

function getCaveDetailsContent(&$details, $showGiveUp = TRUE) {
  global $db, $template;

  // open template
  $template->setFile('caveDetail.tmpl');

  $statusMsg = '';
  $action = Request::getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Höhle aufgeben!
*
****************************************************************************************************/
    case 'caveGiveUp':
      if (Request::getVar('giveUpCaveID', 0) == $details['caveID'] && Request::isPost('postConfirm')) {
        if (cave_giveUpCave($details['caveID'], $_SESSION['player']->playerID, $_SESSION['player']->tribe)) {
          $template->throwError(_('Sie haben sich aus dieser Höhle zurückgezogen.'));
          return;
        } else {
          $statusMsg = array('type' => 'error', 'message' => _('Diese Höhle kann nicht aufgegeben werden.'));
        }
      } else {
        $statusMsg = array('type' => 'error', 'message' => _('Fehler beim aufgeben der Höhle.'));
      }
    break;

/****************************************************************************************************
*
* Anfängerschutz deaktivieren
*
****************************************************************************************************/
    case 'endProtection':
      if (Request::getVar('giveUpCaveID', 0) == $details['caveID'] && Request::isPost('postConfirm')) {
        if (beginner_endProtection($details['caveID'], $_SESSION['player']->playerID)) {
          $statusMsg = array('type' => 'success', 'message' => _('Sie haben den Anfängerschutz abgeschaltet.'));
          $details['protected'] = 0;
        } else {
          $statusMsg = array('type' => 'error', 'message' => _('Sie konnten den Anfängerschutz nicht abschalten.'));
        }
      } else {
        $statusMsg = array('type' => 'error', 'message' => _('Fehler beim deaktivieren des Anfängerschutzes.'));
      }
    break;
  }

  if (!empty($statusMsg)) {
    $template->addVar('status_msg', $statusMsg);
  }

  // get region data
  $region = getRegionByID($details['regionID']);
  $details['region_name'] = $region['name'];

  // set properties
  $properties = array();
  if ($details['protected']) {
    $details['properties'][] = array('text' => _('Anfängerschutz aktiv'));
  }

  if (!$details['secureCave']) {
    $details['properties'][] = array('text' => _('Übernehmbar'));
  }

  if ($details['starting_position'] > 0) {
    $details['properties'][] = array('text' => _('Haupthöhle'));
  }

  // fill give-up form
  if ($showGiveUp) {
    $template->addVar('give_up', true);
  }

  // fill end beginner protection form
  if ($details['protected']) {
    $template->addVar('unprotected', true);
  }

  $template->addVar('cave_data', $details);

  // RESOURCES AUSFUELLEN
  $resources = array();
  foreach ($GLOBALS['resourceTypeList'] as $resource) {
    if (!$resource->nodocumentation || ($details[$resource->dbFieldName] > 0)) {
      $resources[] = array(
        'dbFieldName' => $resource->dbFieldName,
        'name'        => $resource->name,
        'value'       => $details[$resource->dbFieldName]
      );
    }
  }
  if (sizeof($resources)) {
    $template->addVar('resource', $resources);
  }

  // UNITS AUSFUELLEN
  $units = array();
  foreach ($GLOBALS['unitTypeList'] as $unit) {
    $value = $details[$unit->dbFieldName];
    if ($value != 0)
      $units[] = array(
        'dbFieldName' => $unit->dbFieldName,
        'name'        => $unit->name,
        'value'       => $value
      );
  }
  if (sizeof($units)) {
    $template->addVar('units', $units);
  }

  // BUILDINGS AUSFUELLEN
  $buildings = array();
  foreach ($GLOBALS['buildingTypeList'] as $building) {
    $value = $details[$building->dbFieldName];
    if ($value != 0)
      $buildings[] = array(
        'dbFieldName' => $building->dbFieldName,
        'name'        => $building->name,
        'value'       => $value
      );
  }
  if (sizeof($buildings)) {
    $template->addVar('buildings', $buildings);
  }

  // VERTEIDIGUNG AUSFUELLEN
  $defenses = array();
  foreach ($GLOBALS['defenseSystemTypeList'] as $defense) {
    $value = $details[$defense->dbFieldName];
    if ($value != 0) {
      $defenses[] = array(
        'dbFieldName'  => $defense->dbFieldName,
        'name'  => $defense->name,
        'value' => $value
      );
    }
  }
  if (sizeof($defenses)) {
    $template->addVar('defenses', $defenses);
  }
}

function getAllCavesDetailsContent($ownCaves) {
  global $db, $template;

  // open template
  $template->setFile('cavesDetail.tmpl');

  $mycaves = array();
  foreach ($ownCaves AS $caveID => $caveDetails) {
    $mycaves[] = array(
      'cave_name_url' => urlencode($caveDetails['name']),
      'cave_name'     => $caveDetails['name'],
      'cave_id'       => $caveID,
      'cave_x'        => $caveDetails['xCoord'],
      'cave_y'        => $caveDetails['yCoord']
    );
  }
  $template->addVar('caves', $mycaves);
  $template->addVar('cave_count', count($mycaves));

  $sum = $alt = 0;
  $myres = array();
  foreach ($GLOBALS['resourceTypeList'] AS $resource) {
    $temp = array(
      'name'        => $resource->name,
      'dbFieldName' => $resource->dbFieldName,
      'cave'        => array()
    );

    $row_sum = $row_sum_delta = $row_sum_max   = 0;
    foreach ($ownCaves AS $caveID => $caveDetails) {
      $amount = $caveDetails[$resource->dbFieldName];
      $delta = $caveDetails[$resource->dbFieldName.'_delta'];
      $max = round(eval('return ' . formula_parseToPHP("{$resource->maxLevel};", '$caveDetails')));
      $row_sum       += $amount;
      $row_sum_delta += $delta;
      $row_sum_max   += $max;
      if ($delta >= 0) $delta = "+" . $delta;
      $temp['cave'][] = array('amount' => $amount, 'delta' => $delta);
    }

    if (!$row_sum) continue;
    $alt++;
    $sum += $row_sum;
    $temp['sum']       = $row_sum;
    if ($row_sum_delta >= 0) {
      $row_sum_delta = "+" . $row_sum_delta;
    }
    $temp['sum_delta'] = $row_sum_delta;
    $temp['sum_max'] = $row_sum_max;
    $myres[] = $temp;
  }

  if ($sum > 0) {
    $template->addVar('resource', $myres);
  }

  $sum = $alt = 0;
  $myunits = array();
  foreach ($GLOBALS['unitTypeList'] AS $unit) {
    $temp = array(
      'name'        => $unit->name,
      'dbFieldName' => $unit->dbFieldName,
      'cave'        => array()
    );

    $row_sum = 0;
    foreach ($ownCaves AS $caveID => $caveDetails){
      $amount = $caveDetails[$unit->dbFieldName];
      $row_sum += $amount;
      $temp['cave'][] = array('amount' => $amount);
    }

    if (!$row_sum) continue;
    $alt++;
    $sum += $row_sum;
    $temp['sum'] = $row_sum;
    $myunits[] = $temp;
  }

  if ($sum > 0) {
    $template->addVar('unit', $myunits);
  }

  $sum = $alt = 0;
  $mybuildings = array();
  foreach ($GLOBALS['buildingTypeList'] AS $building) {
    $temp = array(
      'name'        => $building->name,
      'dbFieldName' => $building->dbFieldName,
      'CAVE'        => array()
    );

    $row_sum = 0;
    foreach ($ownCaves AS $caveID => $caveDetails) {
      $amount = $caveDetails[$building->dbFieldName];
      $row_sum += $amount;
      $temp['cave'][] = array('amount' => $amount);
    }

    if (!$row_sum) continue;
    $alt++;
    $sum += $row_sum;
    $temp['sum'] = $row_sum;
    $mybuildings[] = $temp;
  }

  if ($sum > 0) {
    $template->addVar('building', $mybuildings);
  }

  $sum = $alt = 0;
  $mydefenses = array();
  foreach ($GLOBALS['defenseSystemTypeList'] AS $defense) {
    $temp = array(
      'name'        => $defense->name,
      'dbFieldName' => $defense->dbFieldName,
      'cave'        => array()
    );

    $row_sum = 0;
    foreach ($ownCaves AS $caveID => $caveDetails) {
      $amount = $caveDetails[$defense->dbFieldName];
      $row_sum += $amount;
      $temp['cave'][] = array('amount' => $amount);
    }
    if (!$row_sum) continue;
    $alt++;
    $sum += $row_sum;
    $temp['sum'] = $row_sum;
    $mydefenses[] = $temp;
  }

  if ($sum > 0) {
    $template->addVar('defenses', $mydefenses);
  }
}

function cave_giveUpCave($caveID, $playerID, $tribeID) {
  global $db;

  $sql = $db->prepare("UPDATE ". CAVE_TABLE ."
                      SET playerID = 0,
                        takeoverable = 2,
                        protection_end = NOW()+0,
                        secureCave = 0
                      WHERE playerID = :playerID
                        AND caveID = :caveID
                        AND starting_position = 0");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  $sql = $db->prepare("UPDATE ". CAVE_TABLE ." c
                       SET  name = (SELECT name FROM ". CAVE_ORGINAL_NAME_TABLE ." co WHERE co.caveID = :caveID)
                       WHERE c.caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->execute();

  @unlink("/var/www/speed/images/temp/{$caveID}.png");

  // delete all scheduled Events
  //   Event_movement - will only be deleted, when a new player gets that cave
  //   Event_artefact - can't be deleted, as it would result in serious errors
  //   Event_wonder   - FIX ME: don't know
  $db->query("DELETE FROM ". EVENT_DEFENSE_SYSTEM_TABLE ." WHERE caveID = '$caveID'");
  $db->query("DELETE FROM ". EVENT_EXPANSION_TABLE ." WHERE caveID = '$caveID'");
  $db->query("DELETE FROM ". EVENT_SCIENCE_TABLE ." WHERE caveID = '$caveID'");
  $db->query("DELETE FROM ". EVENT_UNIT_TABLE ." WHERE caveID = '$caveID'");
  $db->query("DELETE FROM ". EVENT_HERO_TABLE ." WHERE caveID = '$caveID'");

  if ($tribeID != 0) {
    $ownRelations = TribeRelation::getRelations($tribeID);

    foreach ($ownRelations['own'] as $actRelation) {
      $ownType = $actRelation['relationType'];

      if ($GLOBALS['relationList'][$ownType]['isPrepareForWar'] || $GLOBALS['relationList'][$ownType]['isWar']) {
        $newfame = $actRelation['fame'] - (NONSECURE_CAVE_VAlUE * NONSECURE_CAVE_GIVEUP_FAKTOR);

        $sql = $db->prepare("UPDATE ". RELATION_TABLE ."
                             SET fame = :newfame
                             WHERE tribeID = :actTribeRelation
                               AND tribeID_target  = :actTargetRelation");
        $sql->bindValue('newfame', $newfame, PDO::PARAM_INT);
        $sql->bindValue('actTribeRelation', $actRelation['tribeID'], PDO::PARAM_INT);
        $sql->bindValue('actTargetRelation', $actRelation['tribeID_target'], PDO::PARAM_INT);

        $sql->execute();
      }
    }
  }

  // delete hero
  $caveData = getCaveByID($caveID);
  if($caveData['hero'] != 0) {
    hero_killHero($playerID);
  }

  return 1;
}

?>