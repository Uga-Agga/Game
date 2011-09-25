<?php
/*
 * unitaction.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Movement.php');
@include_once('modules/CaveBookmarks/model/CaveBookmarks.php');

function unit_Movement($caveID, &$ownCave) {

  global $config, $db, $template;
  global $MAX_RESOURCE, $MOVEMENTCOSTCONSTANT, $MOVEMENTSPEEDCONSTANT, $FUELRESOURCEID, $resourceTypeList, $unitTypeList;

  // get movements
  $ua_movements = Movement::getMovements();

  $details = $ownCave[$caveID];

  /***************************************************************************/
  /**                                                                       **/
  /** CHECK ARTEFACTS                                                       **/
  /**                                                                       **/
  /***************************************************************************/

  // artefact moving: get ID if any
  //
  // $params->POST->myartefacts will be
  //   NULL, if it is not set at all
  //   -1 when choosing no artefact to move
  //   0 if there was a real choice

  // default: Move No Artefact (this var holds the artefactID to move)
  $moveArtefact = 0;

  // this array shall contain the artefacts if any
  $myartefacts = array();

  // does the cave contain an artefact at least?
  if ($details['artefacts'] > 0) {
    // get artefacts
    $myartefacts = artefact_getArtefactsReadyForMovement($caveID);

    // was an artefact chosen?
    if (request_var('myartefacts', 0) > 0) {

      $tempID = request_var('myartefacts', 0);

      // now check, whether this artefactID belongs to this cave
      foreach ($myartefacts as $key => $value) {

        // if found, set it
        if ($tempID == $value['artefactID']) {
          $moveArtefact = $tempID;
          break;
        }
      }
    }
  }
  // now $moveArtefact should contain 0 for 'move no artefact'
  // or the artefactID of the artefact to be moved

  /***************************************************************************/
  /***************************************************************************/
  /***************************************************************************/

  /**
   * HERO MOVEMENT
   */
  
  $moveHero = 0;
  
  if ($details['hero'] != 0 && request_var('moveHero', false) == true)
    $moveHero = $details['hero'];
  
  /**
   * END HERO MOVEMENTS
   */
  
  // put user, its session and nogfx flag into session
  $_SESSION['player'] = Player::getPlayer($_SESSION['player']->playerID);

  // get Map Size
  $size = getMapSize();
  $dim_x = ($size['maxX'] - $size['minX'] + 1)/2;
  $dim_y = ($size['maxY'] - $size['minY'] + 1)/2;

  $foodPerCave    = eval('return '. formula_parseToPHP($MOVEMENTCOSTCONSTANT . ';', '$details'));
  $minutesPerCave = eval('return '. formula_parseToPHP($MOVEMENTSPEEDCONSTANT . ';', '$details'));
  $minutesPerCave *= MOVEMENT_TIME_BASE_FACTOR/60;

  if (request_var('moveit', false) && sizeof(request_var('unit', array('' => '')))) {
    $targetXCoord   = intval(request_var('targetXCoord', 0));
    $targetYCoord   = intval(request_var('targetYCoord', 0));
    $targetCaveName = request_var('targetCaveName', "");
    $targetCaveID   = intval(request_var('targetCaveID', 0));
    $movementID     = intval(request_var('movementID', 0));

    // check for scripters
    check_timestamp(request_var('tstamp', 0));

    $validCaveName = false;

    // targetCaveID >>> coords
    if ($targetCaveID > 0) {
      $result = getCaveByID(intval($targetCaveID));
      if (sizeof($result) != 0) {
        $targetXCoord = $result['xCoord'];
        $targetYCoord = $result['yCoord'];
        $validCaveName = true;
      }

    // name >>> coords
    } else if ($targetCaveName != "") {
      $result = getCaveByName($targetCaveName);
      if (sizeof($result) != 0) {
        $targetXCoord = $result['xCoord'];
        $targetYCoord = $result['yCoord'];
        $validCaveName = true;
      }
    }

    // get target player
    $result = getCaveByCoords(intval($targetXCoord), intval($targetYCoord));
    if (sizeof($result) != 0) {
      $targetPlayer = new Player(getPlayerByID($result['playerID']));
    }

    // Array von Nullwerten befreien
    $unit     = array_filter(request_var('unit', array('' => '')), "filterZeros");
    $unit     = array_map("checkFormValues", $unit);
    $resource = array_map("checkFormValues", request_var('rohstoff', array('' => '')));

    // Test, ob Einheitentragekapazität ausgelastet
    $overloaded = 0;
    foreach ($resource as $resKey => $aRes) {
      $capacity = 0;
      foreach ($unit as $unitKey => $aUnit) {
        if (array_key_exists($resKey, $unitTypeList[$unitKey]->encumbranceList))
          $capacity += $aUnit * $unitTypeList[$unitKey]->encumbranceList[$resKey];
      }

      if ($capacity < $aRes) {
        $overloaded = 1;
        break;
      }
    }

    $denymovement_nonenemy = false;
    $denymovement_targetwar = false;
    if ($movementID == 2) {  // move units/resources
      if (strtoupper($targetPlayer->tribe) != strtoupper($_SESSION['player']->tribe)) {  //may tade in own tribe

        $ownTribe = $_SESSION['player']->tribe;
        $targetTribe = $targetPlayer->tribe;
        $targetIsNonPlayer = $targetPlayer->playerID == 0;


        $ownTribeAtWar = tribe_isAtWar($ownTribe, TRUE);
        $targetTribeAtWar = tribe_isAtWar($targetTribe, TRUE);
        $TribesMayTrade = relation_areAllies($ownTribe, $targetTribe) ||
                          relation_areEnemys($ownTribe, $targetTribe) ||
                          $targetIsNonPlayer;

        $denymovement_nonenemy = $ownTribeAtWar && !$TribesMayTrade;
        $denymovement_targetwar =  $targetTribeAtWar && !$TribesMayTrade;
      }
    }

    if (request_var('movementID', 0) == 0)
      $msg = array('type' => 'error', 'message' => _('Bitte Bewegungsart auswählen!'));

    else if (!sizeof($unit))
      $msg = array('type' => 'error', 'message' => _('Es sind keine Einheiten ausgewählt!'));

    else if ((($targetXCoord == 0) || ($targetYCoord == 0)) && ($targetCaveName == ""))
      $msg = array('type' => 'error', 'message' => _('Es fehlt eine Zielkoordinate oder ein Zielhöhlenname!'));

    else if ((($targetXCoord == 0) || ($targetYCoord == 0)) && !($targetCaveName == "") && $validCaveName === FALSE)
      $msg = array('type' => 'error', 'message' => sprintf(_('Es gibt keine Höhle mit dem Namen "%s"!'), $targetCaveName));

    else if ($overloaded)
      $msg = array('type' => 'error', 'message' => _('Deine Krieger können die Menge an Ressourcen nicht tragen!!'));

    else if (beginner_isCaveProtectedByCoord($targetXCoord, $targetYCoord))
      $msg = array('type' => 'error', 'message' => _('Die Zielhöhle steht unter Anfängerschutz.'));

    else if (beginner_isCaveProtectedByID($caveID))
      $msg = array('type' => 'error', 'message' => _('Ihre Höhle steht unter Anfängerschutz. Sie können den Schutz sofort unter dem Punkt <a href="?modus=cave_detail">Bericht über diese Höhle</a> beenden'));

    else if (request_var('movementID', 0) == 6 && cave_isCaveSecureByCoord($targetXCoord, $targetYCoord))
      $msg = array('type' => 'error', 'message' => _('Sie können diese Höhle nicht übernehmen. Sie ist gegen übernahmen geschützt.'));

    else if ($denymovement_nonenemy)
      $msg = array('type' => 'error', 'message' => _('Sie können im Krieg keine Einheiten zu unbeteiligten Parteien verschieben!'));

    else if ($denymovement_targetwar)
      $msg = array('type' => 'error', 'message' => _('Sie können keine Einheiten zu kriegführenden Stämmen verschieben, wenn Sie unbeteiligt sind.'));

    //  Einheiten bewegen!
    else {
      // Entfernung x Dauer pro Höhle x größter Geschwindigkeitsfaktor x Bewegungsfaktor
      $duration = ceil(getDistanceByCoords($details['xCoord'], $details['yCoord'], $targetXCoord, $targetYCoord) * $minutesPerCave * getMaxSpeedFactor($unit) * $ua_movements[$movementID]->speedfactor);
      $distance = ceil(getDistanceByCoords($details['xCoord'], $details['yCoord'], $targetXCoord, $targetYCoord));

      $tmpdist = 0;
      $i = 0;
      if($distance > 15) {
        $distance = $distance - 15;
        $tmpdist = 15;
        if(floor($distance/5)<11)
          $tmpdist += ($distance % 5) * (1-0.1*floor($distance/5));

        for ($i = 1; $i <= floor( $distance / 5) && $i < 11; $i++) {
          $tmpdist += 5*(1-0.1*($i-1));
        }
      } else {
        $tmpdist = $distance;
      }

      // Dauer x Rationen x Größe einer Ration x Bewegungsfaktor
      $reqFood = ceil($tmpdist *
                      $minutesPerCave *
                      getMaxSpeedFactor($unit) *
                      $ua_movements[$movementID]->speedfactor *
                      calcRequiredFood($unit) *
                      $foodPerCave *
                      $ua_movements[$movementID]->foodfactor);

      if ($details[$resourceTypeList[$FUELRESOURCEID]->dbFieldName]< $reqFood) {
        $msg = array('type' => 'error', 'message' => _('Nicht genug Nahrung zum Ernähren der Krieger auf ihrem langen Marsch vorhanden!'));

      } else {
        $msgID = setMovementEvent(
          $caveID, $details,
          $targetXCoord, $targetYCoord,
          $unit, $resource,
          $movementID, $reqFood, $duration,
          $moveArtefact, $moveHero,
          $minutesPerCave * $ua_movements[$movementID]->speedfactor);

        switch ($msgID) {
          case 0: $msg = array('type' => 'success', 'message' => sprintf(_('Die Krieger wurden losgeschickt und haben %d Nahrung mitgenommen!'), $reqFood));
                  break;
          case 1: $msg = array('type' => 'error', 'message' => _('In diesen Koordinaten liegt keine Höhle!'));
                  break;
          case 2: $msg = array('type' => 'error', 'message' => _('Für diese Bewegung sind nicht genügend Einheiten/Rohstoffe verfügbar!'));
                  break;
          case 3: $msg = array('type' => 'error', 'message' => _('Schwerer Fehler: Bitte Admin kontaktieren!'));
        }
      }
    }
  } else if ($eventID = request_var('eventID', 0)) {
    $msgID = reverseMovementEvent($caveID, $eventID);
    switch ($msgID) {
      case 0: $msg = array('type' => 'success', 'message' => _('Die Einheiten kehren zurück!')); break;
      case 1: $msg = array('type' => 'error', 'message' => _('Fehler bei der Rückkehr!')); break;
    }
  }

  // refresh this cave
  $temp = getCaveSecure($caveID, $_SESSION['player']->playerID);
  $ownCave[$caveID] = $details = $temp;
  // make sure that bagged artefacts are not shown again
  if ($moveArtefact != 0)
    $myartefacts = artefact_getArtefactsReadyForMovement($caveID);
    
  // make sure that moved hero is not shown again
  if ($moveHero != 0)
    $details['hero'] = 0;

// //////////////////////////////////////////////////////////////
// Create the page
// //////////////////////////////////////////////////////////////

  // open template
  $template->setFile('unitMovement.tmpl');

  $template->addVars(array(
    'currentX'               => $details['xCoord'],
    'currentY'               => $details['yCoord'],
    'dim_x'                  => $dim_x,
    'dim_y'                  => $dim_y,
    'speed'                  => $minutesPerCave,
    'fuel_id'                => $FUELRESOURCEID,
    'fuel_name'              => $resourceTypeList[$FUELRESOURCEID]->name,
    'movement_cost_constant' => $foodPerCave,
    'resource_types'         => $MAX_RESOURCE,
    'status_msg'             => (isset($msg)) ? $msg : '',
  ));

  // movements
  $selectable_movements = array();
  foreach ($ua_movements AS $value) {
    if ($value->playerMayChoose) {
      $selectable_movements[] = get_object_vars($value);
    }
  }
  $template->addVar('selectable_movements', $selectable_movements);

  // resources
  $resources = array();
  for($res = 0; $res < sizeof($resourceTypeList); $res++) {
    if (!$resourceTypeList[$res]->nodocumentation) {
      $resources[] = array(
        'resource_id'    => $resourceTypeList[$res]->resourceID,
        'name'           => $resourceTypeList[$res]->name,
        'current_amount' => "0" + $details[$resourceTypeList[$res]->dbFieldName],
        'dbFieldName'    => $resourceTypeList[$res]->dbFieldName
      );
    }
  }
  $template->addVar('resource', $resources);

  // units table
  $unitprops = array();
  $units     = array();
  for($i = 0; $i < sizeof($unitTypeList); $i++) {

    // if no units of this type, next type
    if (!$details[$unitTypeList[$i]->dbFieldName]) continue;

    $temp = array();
    $encumbrance = array();
    for( $j = 0; $j < count($resourceTypeList); $j++) {
      if (!$resourceTypeList[$j]->nodocumentation) {
        $encumbrance[$j] = array(
          'resourceID' => $j,
          'load'       => "0" + (isset($unitTypeList[$i]->encumbranceList[$j]) ? $unitTypeList[$i]->encumbranceList[$j] : 0)
        );
        $temp[] = "0" + (isset($unitTypeList[$i]->encumbranceList[$j]) ? $unitTypeList[$i]->encumbranceList[$j] : 0);
      }
    }

    $units[] = array(
      'name'              => $unitTypeList[$i]->name,
      'unit_id'           => $unitTypeList[$i]->unitID,
      'food_cost'         => $unitTypeList[$i]->foodCost,
      'resource_load'     => implode(",", $temp),
      'speed_factor'      => $unitTypeList[$i]->wayCost,
      'max_warrior_count' => $details[$unitTypeList[$i]->dbFieldName],
      // ?? warum -> ?? $i gegen namen ersetzen!!! TODO
      'warrior_id'        => $i,
      'encumbrance'       => $encumbrance
    );
  }
  $template->addVar('unit_list', $units);

  // weitergereichte Koordinaten
  if (!request_var('movementID', 0)) {
    $template->addVars(array(
      'target_x_coord'   => request_var('targetXCoord', 0),
      'target_y_coord'   => request_var('targetYCoord', 0),
      'target_cave_name' => request_var('targetCaveName', ''),
    ));
  }

  // weitere Paramter
   $template->addVar('params', array(
    array('name'=>'modus',  'value'=> UNIT_MOVEMENT),
    array('name'=>'moveit', 'value'=> 'true'),
    array('name'=>'trigger','value'=> 'self'),
    array('name'=>'tstamp', 'value'=> "".time())
  ));

  $movements = digest_getMovements(array($caveID => $details), array(), true);

  $ownMovement = $oppMovement = array();
  foreach($movements AS $move) {
    if ($move['isOwnMovement']) {
      $ownMovement[] = $move;
    } else {
      $oppMovement[] = $move;
    }
  }

    $template->addVars(array(
      'ownMovement' => $ownMovement,
      'oppMovement' => $oppMovement,
    ));
  
  // artefakte
  if (sizeof($myartefacts) != 0) {
    //tmpl_set($template, '/ARTEFACTS/ARTEFACT', $myartefacts); 
    $template->addVar('artefact', $myartefacts);
  }
  
  // hero
  if ($details['hero'] != 0) {
    $template->addVar('hero', true);
  }

  // Module "CaveBookmarks" Integration
  // FIXME should know whether the module is installed
  if (TRUE) {
    // get model
    $cb_model = new CaveBookmarks_Model();

    // get bookmarks
    $bookmarks = $cb_model->getCaveBookmarks(true);

    // set bookmarks
    if (sizeof($bookmarks)){
      $template->addVar('bookmarks_cave', $bookmarks);
    }
  }
}

?>