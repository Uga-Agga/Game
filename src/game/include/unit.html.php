<?php
/*
 * unitbuild.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function unit_getUnitDetail($caveID, &$details) {
  global $resourceTypeList, $unitTypeList, $FUELRESOURCEID, $template;

  // open template
  $template->setFile('unitBuilder.tmpl');

  // messages
  $messageText = array (
    0 => array('type' => 'success', 'message' => _('Der Arbeitsauftrag wurde erfolgreich gestoppt.')),
    1 => array('type' => 'error', 'message' => _('Es konnte kein Arbeitsauftrag gestoppt werden.')),
    2 => array('type' => 'error', 'message' => _('Der Auftrag konnte nicht erteilt werden. Es fehlen die notwendigen Voraussetzungen.')),
    3 => array('type' => 'success', 'message' => _('Der Auftrag wurde erteilt')),
    4 => array('type' => 'info', 'message' => sprintf(_('Bitte korrekte Anzahl der Einheiten Angeben (1 ... %d)'), MAX_SIMULTAN_BUILDED_UNITS)),
    5 => array('type' => 'error', 'message' =>_('Der Arbeitsauftrag konnte nicht erteilt werden. Ein Arbeitsauftrag ist schon im Gange.')),
  );

  // get this cave's queue
  $queue = unit_getQueue($_SESSION['player']->playerID, $caveID);

  $action = request_var('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Einheiten bauen
*
****************************************************************************************************/
    case 'build':
      $unitID = request_var('unitID', -1);
      $quantity = request_var('quantity', 0);
      if ($unitID == -1) {
        $messageID = 2;
        break;
      }

      // check queue exist
      if ($queue) {
        $messageID = 5;
        break;
      }

      $messageID = unit_processOrder($unitID, $quantity, $caveID, $details);
      $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
      $queue = unit_getQueue($_SESSION['player']->playerID, $caveID);
    break;

/****************************************************************************************************
*
* Ausbau der Einheiten abbrechen
*
****************************************************************************************************/
    case 'cancelOrder':
      $eventID = request_var('id', 0);
      if ($eventID == 0) {
        $messageID = 1;
        break;
      }

      // check queue exist
      if (!$queue || $queue['event_unitID'] != $eventID) {
        $messageID = 1;
        break;
      }

      if (isset($_POST['cancelOrderConfirm'])) {
        $messageID = unit_cancelOrder($eventID, $caveID);

        if ($messageID == 0) {
          $queue = '';
        }
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'cancelOrder',
          'confirm_id'      => $eventID,
          'confirm_mode'    => UNIT_BUILDER,
          'confirm_msg'     => sprintf(_('Möchtest du den Arbeitsauftrag von <span class="bold">%s</span> abbrechen?'), $unitTypeList[$queue['unitID']]->name),
        ));
      }
    break;
  }

  $units = $unitsUnqualified = array();
  foreach ($unitTypeList as $id => $unit) {
    $result = rules_checkDependencies($unit, $details);

/****************************************************************************************************
*
* Einheiten die gebaut werden können.
*
****************************************************************************************************/

    if ($result === TRUE) {
      $units[$unit->unitID] = array(
        'name'             => $unit->name,
        'dbFieldName'      => $unit->dbFieldName,
        'unit_id'          => $unit->unitID,
        'cave_id'          => $caveID,
        'time'             => time_formatDuration(eval('return ' . formula_parseToPHP($unit->productionTimeFunction . ";", '$details')) * BUILDING_TIME_BASE_FACTOR),
        'stock'            => "0" + $details[$unit->dbFieldName],
        'description'      => $unit->description,
        'duration_formula' => formula_parseToReadable($unit->productionTimeFunction),
        'visible'          => $unit->visible,
        'range_attack'     => $unit->attackRange,
        'areal_attack'     => $unit->attackAreal,
        'attack_rate'      => $unit->attackRate,
        'rd_Resist'        => $unit->rangedDamageResistance,
        'defense_rate'     => $unit->defenseRate,
        'size'             => $unit->hitPoints,
        'spy_value'        => $unit->spyValue,
        'spy_chance'       => $unit->spyChance,
        'spy_quality'      => $unit->spyQuality,
        'anti_spy_chance'  => $unit->antiSpyChance,
        'fuel_name'        => $resourceTypeList[$FUELRESOURCEID]->dbFieldName,
        'fuel_factor'      => $unit->foodCost,
        'way_cost'         => $unit->wayCost,
        'normal_damage_probabilit'    => 100 * (1-($unit->heavyDamageProbability + $unit->criticalDamageProbability)),
        'heavy_damage_probability'    => 100 * ($unit->heavyDamageProbability),
        'critical_damage_probability' => 100 * ($unit->criticalDamageProbability),

      );
      $units[$unit->unitID] = array_merge($units[$unit->unitID], parseCost($unit, $details));

      // show the building link ?!
      if ($queue)
        $units[$unit->unitID]['no_build_msg'] = _('Ausbau im Gange');
      else if ($units[$unit->unitID]['notenough'])
        $units[$unit->unitID]['no_build_msg'] = _('Zu wenig Rohstoffe');
      else {
        $units[$unit->unitID]['build_link'] = true;
      }

/****************************************************************************************************
*
* Einheiten die nicht gebaut werden können.
*
****************************************************************************************************/
    } else if ($result !== FALSE && !$unit->nodocumentation) {
      $unitsUnqualified[$unit->unitID] = array(
        'name'             => $unit->name,
        'dbFieldName'      => $unit->dbFieldName,
        'unit_id'          => $unit->unitID,
        'cave_id'          => $caveID,
        'dependencies'     => $result,
        'description'      => $unit->description,
        'duration_formula' => formula_parseToReadable($unit->productionTimeFunction),
        'visible'          => $unit->visible,
        'range_attack'     => $unit->attackRange,
        'areal_attack'     => $unit->attackAreal,
        'attack_rate'      => $unit->attackRate,
        'rd_Resist'        => $unit->rangedDamageResistance,
        'defense_rate'     => $unit->defenseRate,
        'size'             => $unit->hitPoints,
        'spy_value'        => $unit->spyValue,
        'spy_chance'       => $unit->spyChance,
        'spy_quality'      => $unit->spyQuality,
        'anti_spy_chance'  => $unit->antiSpyChance,
        'fuel_name'        => $resourceTypeList[$FUELRESOURCEID]->dbFieldName,
        'fuel_factor'      => $unit->foodCost,
        'way_cost'         => $unit->wayCost,
        'normal_damage_probabilit'    => 100 * (1-($unit->heavyDamageProbability + $unit->criticalDamageProbability)),
        'heavy_damage_probability'    => 100 * ($unit->heavyDamageProbability),
        'critical_damage_probability' => 100 * ($unit->criticalDamageProbability),
      );
      $unitsUnqualified[$unit->unitID] = array_merge($unitsUnqualified[$unit->unitID], parseCost($unit, $details));
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
      'quene_name'      => $unitTypeList[$queue['unitID']]->name,
      'quene_quantity'  => $queue['quantity'],
      'quene_finish'    => time_formatDatetime($queue['end']),
      'quene_modus'     => UNIT_BUILDER,
      'quene_event_id'  => $queue['event_unitID']
    ));
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'cave_id'           => $caveID,
    'status_msg'        => (isset($messageID)) ? $messageText[$messageID] : '',
    'units'             => $units,
    'units_unqualified' => $unitsUnqualified,
  ));

}

?>