<?php
/*
 * defense.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');


################################################################################

/**
 *
 */

function defense_builder($caveID, &$details) {
  global $defenseSystemTypeList, $template;

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
    8 => array('type' => 'error', 'message' => _('Der Arbeitsauftrag konnte nicht erteilt werden. Ein Arbeitsauftrag ist schon im gange.')),
  );

  // get this cave's queue
  $queue = defense_getQueue($_SESSION['player']->playerID, $caveID);

  $action = request_var('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Verteidigungsanlage bauen
*
****************************************************************************************************/
    case 'build':
      $defenseID = request_var('defenseID', -1);
      if ($defenseID == -1) {
        $messageID = 6;
        break;
      }

      // check queue exist
      if ($queue) {
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
      $eventID = request_var('id', 0);
      if ($eventID == 0) {
        $messageID = 0;
        break;
      }

      // check queue exist
      if (!$queue || $queue['event_defenseSystemID'] != $eventID) {
        $messageID = 0;
        break;
      }

      if (isset($_POST['cancelOrderConfirm'])) {
        $messageID = defense_cancelOrder($eventID, $caveID);

        if ($messageID == 1) {
          $queue = '';
        }
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'cancelOrder',
          'confirm_id'      => $eventID,
          'confirm_mode'    => DEFENSE_BUILDER,
          'confirm_msg'     => sprintf(_('Möchtest du den Arbeitsauftrag von <span class="bold">%s</span> abbrechen?'), $defenseSystemTypeList[$queue['defenseSystemID']]->name),
        ));
      }
    break;

/****************************************************************************************************
*
* Verteidigungsanlage abreißen
*
****************************************************************************************************/
    case 'demolishing':
      $defenseID = request_var('id', -1);
      if ($defenseID == -1) {
        $messageID = 4;
        break;
      }

      if (!isset($defenseSystemTypeList[$defenseID])) {
        $messageID = 4;
        break;
      }

      if (isset($_POST['cancelOrderConfirm'])) {
        $messageID = defense_Demolishing($defenseID, $caveID, $details);
        $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'demolishing',
          'confirm_id'      => $defenseID,
          'confirm_mode'    => DEFENSE_BUILDER,
          'confirm_msg'     => sprintf(_('Möchtest du <span class="bold">%s</span> einmal abreißen?'), $defenseSystemTypeList[$defenseID]->name),
        ));
      }
    break;
  }

  $defenseSystem = $defenseSystemRelict = $defenseSystemUnqualified = array();
  foreach ($defenseSystemTypeList as $id => $defense) {
    $maxLevel = round(eval('return '.formula_parseToPHP("{$defense->maxLevel};", '$details')));

    $result = rules_checkDependencies($defense, $details);

    // if all requirements are met, but the maxLevel is 0, treat it like a non-buildable
    if ($maxLevel <= 0 && $result === TRUE) {
      $result = ($details[$defense->dbFieldName]) ? _('Max. Stufe: 0') : false;
    }

/****************************************************************************************************
*
* Verteidigungsanlage die gebaut werden können.
*
****************************************************************************************************/
    if ($result === TRUE) {
      $defenseSystem[$defense->defenseSystemID] = array(
        'name'             => $defense->name,
        'dbFieldName'      => $defense->dbFieldName,
        'defense_id'       => $defense->defenseSystemID,
        'cave_id'          => $caveID,
        'time'             => time_formatDuration(eval('return ' . formula_parseToPHP($defense->productionTimeFunction . ";", '$details')) * DEFENSESYSTEM_TIME_BASE_FACTOR),
        'maxlevel'         => $maxLevel,
        'currentlevel'     => "0" + $details[$defense->dbFieldName],
        'description'      => $defense->description,
        'duration_formula' => formula_parseToReadable($defense->productionTimeFunction),
        'rangeAttack'      => $defense->attackRange,
        'attackRate'       => $defense->attackRate,
        'defenseRate'      => $defense->defenseRate,
        'size'             => $defense->hitPoints,
        'antiSpyChance'    => $defense->antiSpyChance,
        'breakdown_link'   => ($details[$defense->dbFieldName] > 0) ? true : false
      );
      $defenseSystem[$defense->defenseSystemID] = array_merge($defenseSystem[$defense->defenseSystemID], parseCost($defense, $details));

      // show the building link ?!
      if ($queue) {
        $defenseSystem[$defense->defenseSystemID]['no_build_msg'] = _('Ausbau im Gange');
      } else if ($defenseSystem[$defense->defenseSystemID]['notenough'] && $maxLevel > $details[$defense->dbFieldName]) {
        $defenseSystem[$defense->defenseSystemID]['no_build_msg'] = _('Zu wenig Rohstoffe');
      } else if ($maxLevel > $details[$defense->dbFieldName]) {
        $defenseSystem[$defense->defenseSystemID]['build_link'] = true;
      } else {
        $defenseSystem[$defense->defenseSystemID]['no_build_msg'] = _('Max. Stufe');
      }

/****************************************************************************************************
*
* Verteidigungsanlage die zwar nicht gebaut werden können aber schon in der Höhle sind (Relikt)
*
****************************************************************************************************/
    } else if ($details[$defense->dbFieldName]){
      $defenseSystemRelict[$defense->defenseSystemID] = array(
        'name'             => $defense->name,
        'dbFieldName'      => $defense->dbFieldName,
        'defense_id'       => $defense->defenseSystemID,
        'cave_id'          => $caveID,
        'currentlevel'     => "0" + $details[$defense->dbFieldName],
        'description'      => $defense->description,
        'duration_formula' => formula_parseToReadable($defense->productionTimeFunction),
        'rangeAttack'      => $defense->attackRange,
        'attackRate'       => $defense->attackRate,
        'defenseRate'      => $defense->defenseRate,
        'size'             => $defense->hitPoints,
        'antiSpyChance'    => $defense->antiSpyChance,
        'dependencies'     => ($result !== FALSE) ? $result : false
      );

/****************************************************************************************************
*
* Verteidigungsanlage die nicht gebaut werden können.
*
****************************************************************************************************/
    } else if ($result !== FALSE && !$defense->nodocumentation){
      $defenseSystemUnqualified[$defense->defenseSystemID] = array(
        'name'             => $defense->name,
        'dbFieldName'      => $defense->dbFieldName,
        'defense_id'       => $defense->defenseSystemID,
        'cave_id'          => $caveID,
        'dependencies'     => $result,
        'description'      => $defense->description,
        'duration_formula' => formula_parseToReadable($defense->productionTimeFunction),
        'rangeAttack'      => $defense->attackRange,
        'attackRate'       => $defense->attackRate,
        'defenseRate'      => $defense->defenseRate,
        'size'             => $defense->hitPoints,
        'antiSpyChance'    => $defense->antiSpyChance,
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
      'quene_name'      => $defenseSystemTypeList[$queue['defenseSystemID']]->name,
      'quene_nextlevel' => $details[$defenseSystemTypeList[$queue['defenseSystemID']]->dbFieldName] + 1,
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


################################################################################

/**
 *
 */

function defense_showProperties($defenseID, $cave) {
  global $buildingTypeList, $defenseSystemTypeList, $resourceTypeList, $scienceTypeList, $unitTypeList;

  // first check whether that defense should be displayed...
  $defense = $defenseSystemTypeList[$defenseID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$defense->maxLevel};", '$cave')));
  $maxLevel = ($maxLevel < 0) ? 0 : $maxLevel;

  if (!$defense || ($defense->nodocumentation &&
                 !$cave[$defense->dbFieldName] &&
                 rules_checkDependencies($defense, $cave) !== TRUE)) {
    $defense = current($defenseSystemTypeList);
  }

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'externalProperties.ihtml');

  $currentlevel = $cave[$defense->dbFieldName];
  $levels = array();
  for ($level = $cave[$defense->dbFieldName], $count = 0;
       $level < $maxLevel && $count < 6;
       ++$count, ++$level, ++$cave[$defense->dbFieldName]){

    $duration = time_formatDuration(
                  eval('return ' .
                       formula_parseToPHP($defense->productionTimeFunction.";",'$cave'))
                  * DEFENSESYSTEM_TIME_BASE_FACTOR);

    // iterate ressourcecosts
    $resourcecost = array();
    foreach ($defense->resourceProductionCost as $resourceID => $function){

      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$cave')));
      if ($cost)
        array_push($resourcecost,
                   array(
                   'name'        => $resourceTypeList[$resourceID]->name,
                   'dbFieldName' => $resourceTypeList[$resourceID]->dbFieldName,
                   'value'       => $cost));
    }
    // iterate unitcosts
    $unitcost = array();
    foreach ($defense->unitProductionCost as $unitID => $function){
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$cave')));
      if ($cost)
        array_push($unitcost,
                   array(
                   'name'        => $unitTypeList[$unitID]->name,
                   'dbFieldName' => $unitTypeList[$unitID]->dbFieldName,
                   'value'       => $cost));
    }

    $buildingCost = array();
    foreach ($defense->buildingProductionCost as $key => $value)
      if ($value != "" && $value != 0)
        array_push($buildingCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                        'name'        => $buildingTypeList[$key]->name,
                                        'value'       => ceil(eval('return '.formula_parseToPHP($defense->buildingProductionCost[$key] . ';', '$details')))));

    $externalCost = array();
    foreach ($defense->externalProductionCost as $key => $value)
      if ($value != "" && $value != 0)
        array_push($externalCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                        'name'        => $defenseSystemTypeList[$key]->name,
                                        'value'       => ceil(eval('return '.formula_parseToPHP($defense->externalProductionCost[$key] . ';', '$details')))));

    $levels[$count] = array('level' => $level + 1,
                            'time'  => $duration,
                            'BUILDINGCOST' => $buildingCost,
                            'EXTERNALCOST' => $externalCost,
                            'RESOURCECOST' => $resourcecost,
                            'UNITCOST'     => $unitcost);
  }
  if (sizeof($levels))
    $levels = array('population' => $cave['population'], 'LEVEL' => $levels);


  $dependencies     = array();
  $buildingdep      = array();
  $defensesystemdep = array();
  $resourcedep      = array();
  $sciencedep       = array();
  $unitdep          = array();

  foreach ($defense->buildingDepList as $key => $level)
    if ($level)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($defense->defenseSystemDepList as $key => $level)
    if ($level)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&gt;= " . $level));

  foreach ($defense->resourceDepList as $key => $level)
    if ($level)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($defense->scienceDepList as $key => $level)
    if ($level)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&gt;= " . $level));

  foreach ($defense->unitDepList as $key => $level)
    if ($level)
      array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                 'level' => "&gt;= " . $level));


  foreach ($defense->maxBuildingDepList as $key => $level)
    if ($level != -1)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($defense->maxDefenseSystemDepList as $key => $level)
    if ($level != -1)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&lt;= " . $level));

  foreach ($defense->maxResourceDepList as $key => $level)
    if ($level != -1)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($defense->maxScienceDepList as $key => $level)
    if ($level != -1)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&lt;= " . $level));

  foreach ($defense->maxUnitDepList as $key => $level)
    if ($level != -1)
      array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                 'level' => "&lt;= " . $level));


  if (sizeof($buildingdep))
    array_push($dependencies, array('name' => _('Erweiterungen'),
                                    'DEP'  => $buildingdep));

  if (sizeof($defensesystemdep))
    array_push($dependencies, array('name' => _('Verteidigungsanlagen'),
                                    'DEP'  => $defensesystemdep));

  if (sizeof($resourcedep))
    array_push($dependencies, array('name' => _('Rohstoffe'),
                                    'DEP'  => $resourcedep));

  if (sizeof($sciencedep))
    array_push($dependencies, array('name' => _('Forschungen'),
                                    'DEP'  => $sciencedep));

  if (sizeof($unitdep))
    array_push($dependencies, array('name' => _('Einheiten'),
                                    'DEP'  => $unitdep));

  tmpl_set($template, '/', array('name'          => $defense->name,
                                 'dbFieldName'   => $defense->dbFieldName,
                                 'description'   => $defense->description,
                                 'maxlevel'      => $maxLevel,
                                 'currentlevel'  => $currentlevel,
                                 'rangeAttack'   => $defense->attackRange,
                                 'attackRate'    => $defense->attackRate,
                                 'defenseRate'   => $defense->defenseRate,
                                 'size'          => $defense->hitPoints,
                                 'antiSpyChance' => $defense->antiSpyChance,
                                 'LEVELS'        => $levels,
                                 'DEPGROUP'      => $dependencies,
                                 'rules_path'    => RULES_PATH));


  return tmpl_parse($template);
}


################################################################################

/**
 *
 */

function defense_getQueue($playerID, $caveID) {
  global $db;

  // prepare query
  $sql = $db->prepare("SELECT e.* FROM ". EVENT_DEFENSE_SYSTEM_TABLE ." e
                         LEFT JOIN ". CAVE_TABLE ." c ON c.caveID = e.caveID
                      WHERE c.caveID IS NOT NULL AND c.playerID = :playerID
                        AND e.caveID = :caveID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return 0;
  }
  
  if (!($result = $sql->fetch())) {
    return 0;
  }

  return $result;
}


################################################################################

/**
 *
 */

function defense_cancelOrder($event_defenseSystemID, $caveID) {
  global $db;

  // prepare query
  $sql = $db->prepare("DELETE FROM ". EVENT_DEFENSE_SYSTEM_TABLE . "
                       WHERE event_defenseSystemID = :dfSID 
                         AND caveID = :caveID");
  $sql->bindValue('dfSID', $event_defenseSystemID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  // execute query
  if($sql->execute()) {
    return 1;
  }

  return 0;
}


################################################################################

/**
 *
 */

function defense_demolishingPossible($caveID) {
  global $db;

  // prepare query
  $sql = $db->prepare("SELECT toreDownTimeout < NOW()+0 AS possible ".
                   "FROM ". CAVE_TABLE ." WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  // execute query
  if (!$sql->execute())
    return 0;

  if (!($row = $sql->fetch()) || !$row["possible"])
    return 0;

  return 1;
}


################################################################################

/**
 *
 */

function defense_Demolishing($defenseID, $caveID, $cave) {

  global $resourceTypeList, $defenseSystemTypeList, $db;

  $dbFieldName = $defenseSystemTypeList[$defenseID]->dbFieldName;

  // can't demolish
  if (!defense_demolishingPossible($caveID)) {
    return 2;
  }

  // no defenseSystem of that type
  if ($cave[$dbFieldName] < 1) {
    return 3;
  }

//  $query = "UPDATE Cave ";
//  $where = "WHERE caveID = '$caveID' ".
//           "AND {$dbFieldName} > 0 ";
//
//  // add resources gain
//  /*
//  if (is_array($defenseSystemTypeList[$defenseID]->resourceProductionCost)){
//    $resources = array();
//    foreach ($defenseSystemTypeList[$defenseID]->resourceProductionCost as $key => $value){
//      if ($value != "" && $value != "0"){
//        $formula     = formula_parseToSQL($value);
//        $dbField     = $resourceTypeList[$key]->dbFieldName;
//        $maxLevel    = round(eval('return '.formula_parseToPHP("{$resourceTypeList[$key]->maxLevel};", '$cave')));
//        $resources[] = "$dbField = LEAST($maxLevel, $dbField + ($formula) / {$config->DEFENSESYSTEM_PAY_BACK_DIVISOR})";
//      }
//    }
//    $set .= implode(", ", $resources);
//  }
//  */
//
//  // ATTENTION: "SET defenseSystem = defenseSystem - 1" has to be placed BEFORE
//  //            the calculation of the resource return. Otherwise
//  //            mysql would calculate the cost of the NEXT step not
//  //            of the LAST defenseSystem step (returns would be too high)...
//  $query .= "SET {$dbFieldName} = {$dbFieldName} - 1, ".
//            "toreDownTimeout = (NOW() + INTERVAL ".
//            TORE_DOWN_TIMEOUT." MINUTE)+0 ";
            
  $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                       SET {$dbFieldName} = {$dbFieldName} - 1,
                       toreDownTimeout = (NOW() + INTERVAL :toreDownTime MINUTE) + 0
                       WHERE caveID = :caveID 
                       AND {$dbFieldName} > 0");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindVAlue('toreDownTime', TORE_DOWN_TIMEOUT, PDO::PARAM_INT);

  if (!$sql->execute() || !$sql->rowCount() == 1) {
    return 4;
  }

  return 5;
}



################################################################################

/**
 *
 */

function defense_processOrder($defenseID, $caveID, $cave) {
  global $defenseSystemTypeList, $unitTypeList, $buildingTypeList, $scienceTypeList, $resourceTypeList;
  global $db;

  $external = $defenseSystemTypeList[$defenseID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$external->maxLevel};", '$cave')));

  // take production costs from cave
  if (!processProductionCost($external, $caveID, $cave))
    return 6;

  // calculate the production time;
  $prodTime = 0;
  if ($time_formula = $external->productionTimeFunction) {
    $time_eval_formula = formula_parseToPHP($time_formula, '$cave');

    $time_eval_formula="\$prodTime = $time_eval_formula;";
    eval($time_eval_formula);
  }

  $prodTime *= DEFENSESYSTEM_TIME_BASE_FACTOR;
  $now = time();
  $sql = $db->prepare("INSERT INTO ". EVENT_DEFENSE_SYSTEM_TABLE ." (caveID, defenseSystemID, ".
                   "start, end) VALUES (:caveID, :defenseID, :start, :end)");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('defenseID', $defenseID, PDO::PARAM_INT);
  $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
  $sql->bindValue('end', time_toDatetime($now + $prodTime), PDO::PARAM_STR);

  if (!$sql->execute()) {
    //give production costs back
    processProductionCostSetBack($external, $caveID, $cave);
    return 6;
  }

  return 7;
}

?>