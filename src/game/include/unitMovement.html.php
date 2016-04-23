<?php
/*
 * unitMovement.html.php -
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011-2013 David Unger <unger.dave@gmail.com>
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
  global $db, $template;

  $safeForm = true;

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
    $moveArtefactID = Request::getVar('myartefacts', 0);

    // was an artefact chosen?
    if ($moveArtefactID > 0) {
      // now check, whether this artefactID belongs to this cave
      foreach ($myartefacts as $key => $value) {

        // if found, set it
        if ($moveArtefactID == $value['artefactID']) {
          $moveArtefact = $moveArtefactID;
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

  if ($details['hero'] != 0) {
    $hero = getHeroByPlayer($_SESSION['player']->playerID);
    $hero['maxUnitsSize'] = max(HERO_MAX_UNIT_SIZE, $hero['exp']);
    if ($hero['isAlive'] != 1) {
      $details['hero'] = 0;
    }
  }

  if ($details['hero'] != 0 && Request::getVar('moveHero', 0) == 1) {
    $moveHero = $details['hero'];
  }

  /**
   * END HERO MOVEMENTS
   */

  // put user, its session and nogfx flag into session
  $_SESSION['player'] = Player::getPlayer($_SESSION['player']->playerID);

  // get Map Size
  $size = getMapSize();
  $dim_x = ($size['maxX'] - $size['minX'] + 1)/2;
  $dim_y = ($size['maxY'] - $size['minY'] + 1)/2;

  $foodPerCave    = eval('return '. formula_parseToPHP(GameConstants::MOVEMENT_COST . ';', '$details'));
  $minutesPerCave = eval('return '. formula_parseToPHP(GameConstants::MOVEMENT_SPEED . ';', '$details'));
  $minutesPerCave *= MOVEMENT_TIME_BASE_FACTOR/60;

  if (Request::getVar('moveit', false) && sizeof(Request::getVar('unit', array('' => '')))) {
    $targetXCoord   = Request::getVar('targetXCoord', 0);
    $targetYCoord   = Request::getVar('targetYCoord', 0);
    $targetCaveName = Request::getVar('targetCaveName', '');
    $targetCaveID   = Request::getVar('targetCaveID', 0);
    $movementID     = Request::getVar('movementID', 0);

    // check for scripters
    check_timestamp(Request::getVar('tstamp', 0));

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
      $targetPlayer = Player::getPlayer($result['playerID']);
    }

    // Array von Nullwerten befreien
    $unit     = array_filter(Request::getVar('unit', array('' => '')), "filterZeros");
    $unit     = array_map("checkFormValues", $unit);
    $resource = array_map("checkFormValues", Request::getVar('resource', array('' => '')));

    // check non valid units
    foreach ($unit as $unitID => $value) {
      if (!isset($GLOBALS['unitTypeList'][$unitID]) || $value < 0) {
        report_player();
      }
    }

    // check non valid resource
    foreach ($resource as $resourceID => $value) {
      if (!isset($GLOBALS['resourceTypeList'][$resourceID]) || $value < 0) {
        report_player();
      }
    }

    // Test, ob Einheitentragekapazität ausgelastet
    $overloaded = 0;
    foreach ($resource as $resKey => $aRes) {
      $capacity = 0;
      foreach ($unit as $unitKey => $aUnit) {
        if (isset($GLOBALS['unitTypeList'][$unitKey]->encumbranceList[$resKey])) {
          $capacity += $aUnit * $GLOBALS['unitTypeList'][$unitKey]->encumbranceList[$resKey];
        }
      }

      if ($capacity < $aRes) {
        $overloaded = 1;
        break;
      }
    }

    $denymovement_nonenemy = false;
    $denymovement_targetwar = false;
    if ($movementID == 2) {  // move units/resources
      if ($targetPlayer != null) {
        if ($targetPlayer->tribeID != $_SESSION['player']->tribeID) {  //may tade in own tribe
          $ownTribeAtWar = TribeRelation::hasWar($_SESSION['player']->tribeID, true);
          $targetTribeAtWar = TribeRelation::hasWar($targetPlayer->tribeID, true);
          $TribesMayTrade = (TribeRelation::isAlly($_SESSION['player']->tribeID, $targetPlayer->tribeID) && TribeRelation::hasSameEnemy($_SESSION['player']->tribeID, $targetPlayer->tribeID, true, true)) || TribeRelation::isEnemy($_SESSION['player']->tribeID, $targetPlayer->tribeID);

          $denymovement_nonenemy = $ownTribeAtWar && !$TribesMayTrade;
          $denymovement_targetwar =  $targetTribeAtWar && !$TribesMayTrade;
        }
      }
    }

    // check if army is small enough for hero
    $denymovement_hero = false;
    if ($moveHero && ($movementID == 3 || $movementID == 6)) {
      //calculate size of army
      $armySize = 0;
      foreach ($unit as $unitID => $value) {
        $armySize += $GLOBALS['unitTypeList'][$unitID]->hitPoints*$value;
      }

      if ($armySize > $hero['exp'] && $armySize > HERO_MAX_UNIT_SIZE) {
        $denymovement_hero = true;
      }
    }

    if (!isset($ua_movements[$movementID])) {
      if ($movementID != 0) {
        report_player();
      }

      $msg = array('type' => 'error', 'message' => _('Bitte Bewegungsart auswählen!'));
      $moveHero = 0;
    }

    else if (!sizeof($unit)) {
      $msg = array('type' => 'error', 'message' => _('Es sind keine Einheiten ausgewählt!'));
      $moveHero = 0;
    }

    else if ((($targetXCoord == 0) || ($targetYCoord == 0)) && ($targetCaveName == "")) {
      $msg = array('type' => 'error', 'message' => _('Es fehlt eine Zielkoordinate oder ein Zielhöhlenname!'));
      $moveHero = 0;
    }

    else if ((($targetXCoord == 0) || ($targetYCoord == 0)) && !($targetCaveName == "") && $validCaveName === FALSE) {
      $msg = array('type' => 'error', 'message' => sprintf(_('Es gibt keine Höhle mit dem Namen "%s"!'), $targetCaveName));
      $moveHero = 0;
    }

    else if ($overloaded) {
      $msg = array('type' => 'error', 'message' => _('Deine Krieger können die Menge an Ressourcen nicht tragen!!'));
      $moveHero = 0;
    }

    else if (beginner_isCaveProtectedByCoord($targetXCoord, $targetYCoord)) {
      $msg = array('type' => 'error', 'message' => _('Die Zielhöhle steht unter Anfängerschutz.'));
      $moveHero = 0;
    }

    else if (beginner_isCaveProtectedByID($caveID)) {
      $msg = array('type' => 'error', 'message' => _('Ihre Höhle steht unter Anfängerschutz. Sie können den Schutz sofort unter dem Punkt <a href="?modus=cave_detail">Bericht über diese Höhle</a> beenden'));
      $moveHero = 0;
    }

    else if (Request::getVar('movementID', 0) == 6 && cave_isCaveSecureByCoord($targetXCoord, $targetYCoord)) {
      $msg = array('type' => 'error', 'message' => _('Sie können diese Höhle nicht übernehmen. Sie ist gegen übernahmen geschützt.'));
      $moveHero = 0;
    }

    else if ($denymovement_nonenemy)
      $msg = array('type' => 'error', 'message' => _('Sie können im Krieg keine Einheiten zu unbeteiligten Parteien verschieben!'));

    else if ($denymovement_targetwar) {
      $msg = array('type' => 'error', 'message' => _('Sie können keine Einheiten zu kriegführenden Stämmen verschieben, wenn Sie unbeteiligt sind.'));
      $moveHero = 0;
    }

    else if ($denymovement_hero) {
      $msg = array('type' => 'error', 'message' => _('Die Armee ist zu groß um vom Helden unterstützt zu werden!'));
      $moveHero = 0;
    }

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
        if(floor($distance/5) < 11) {
          $tmpdist += ($distance % 5) * (1-0.1*floor($distance/5));
        }

        for ($i = 1; $i <= floor( $distance / 5) && $i < 11; $i++) {
          $tmpdist += 5*(1-0.1*($i-1));
        }
      } else {
        $tmpdist = $distance;
      }

      // Dauer x Rationen x Größe einer Ration x Bewegungsfaktor
      $reqFood = ceil($tmpdist * $minutesPerCave * getMaxSpeedFactor($unit) * $ua_movements[$movementID]->speedfactor * calcRequiredFood($unit) * $foodPerCave * $ua_movements[$movementID]->foodfactor);

      if ($details[$GLOBALS['resourceTypeList'][GameConstants::FUEL_RESOURCE_ID]->dbFieldName] < $reqFood) {
        $msg = array('type' => 'error', 'message' => _('Nicht genug Nahrung zum Ernähren der Krieger auf ihrem langen Marsch vorhanden!'));
      } else {
        $msgID = setMovementEvent($caveID, $details, $targetXCoord, $targetYCoord, $unit, $resource, $movementID, $reqFood, $duration, $moveArtefact, $moveHero, $minutesPerCave * $ua_movements[$movementID]->speedfactor);

        switch ($msgID) {
          case 0:
            $msg = array('type' => 'success', 'message' => sprintf(_('Die Krieger wurden losgeschickt und haben %d Nahrung mitgenommen!'), $reqFood));
            $safeForm = false;
          break;

          case 1:
            $msg = array('type' => 'error', 'message' => _('In diesen Koordinaten liegt keine Höhle!'));
            $moveHero = 0;
          break;

          case 2:
            $msg = array('type' => 'error', 'message' => _('Für diese Bewegung sind nicht genügend Einheiten/Rohstoffe verfügbar!'));
            $moveHero = 0;
          break;

          case 3:
            $msg = array('type' => 'error', 'message' => _('Schwerer Fehler: Bitte Admin kontaktieren!'));
            $moveHero = 0;
          break;
        }
      }
    }
  } else if (Request::isPost('action') && Request::getVar('action', '') == 'cancel' && Request::getVar('eventID', 0)) {
    $msgID = reverseMovementEvent($caveID, Request::getVar('eventID', 0));
    switch ($msgID) {
      case 0: $msg = array('type' => 'success', 'message' => _('Die Einheiten kehren zurück!')); break;
      case 1: $msg = array('type' => 'error', 'message' => _('Fehler bei der Rückkehr!')); break;
    }
  } else if (Request::getVar('moveit', false)  && !sizeof(Request::getVar('unit', array('' => '')))) {
    $msg = array('type' => 'error', 'message' => _('Einheiten mitnehmen?'));
  }

  // refresh this cave
  $temp = getCaveSecure($caveID, $_SESSION['player']->playerID);
  $ownCave[$caveID] = $details = $temp;
  // make sure that bagged artefacts are not shown again
  if ($moveArtefact != 0) {
    $myartefacts = artefact_getArtefactsReadyForMovement($caveID);
  }

  // make sure that moved hero is not shown again
  if ($moveHero != 0) {
    $details['hero'] = 0;
  }

// //////////////////////////////////////////////////////////////
// Create the page
// //////////////////////////////////////////////////////////////

  // open template
  $template->setFile('unitMovement.tmpl');

  // movements
  $selectableMovements = array();
  foreach ($ua_movements AS $value) {
    if ($value->playerMayChoose) {
      $selectableMovements[$value->id] = get_object_vars($value);
    }
  }
  $template->addVar('selectable_movements', $selectableMovements);

  $movementData = array(
    'currentX'       => $details['xCoord'],
    'currentY'       => $details['yCoord'],
    'dim_x'          => $dim_x,
    'dim_y'          => $dim_y,
    'min_x'          => $size['minX'],
    'max_X'          => $size['maxX'],
    'min_Y'          => $size['minY'],
    'max_Y'          => $size['maxY'],
    'minutesPerCave' => $minutesPerCave,
    'foodID'         => GameConstants::FUEL_RESOURCE_ID,
    'foodfactor'     => $foodPerCave,
    'movements'      => $selectableMovements
  );

  $template->addVars(array(
    'fuel_name'     => $GLOBALS['resourceTypeList'][GameConstants::FUEL_RESOURCE_ID]->name,
    'status_msg'    => (isset($msg)) ? $msg : '',
    'movement_data' => json_encode($movementData)
  ));

  // resources
  $resources = array();
  foreach ($GLOBALS['resourceTypeList'] as $resourceID => $dummy) {
    $amount = (isset($details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName])) ? floor($details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName]) : 0;

    if (!$GLOBALS['resourceTypeList'][$resourceID]->nodocumentation || $amount > 0) {
      $resources[] = array(
        'resource_id'    => $GLOBALS['resourceTypeList'][$resourceID]->resourceID,
        'name'           => $GLOBALS['resourceTypeList'][$resourceID]->name,
        'current_amount' => $details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName],
        'dbFieldName'    => $GLOBALS['resourceTypeList'][$resourceID]->dbFieldName,
        'value'          => ($safeForm && isset($resource[$resourceID]) && $resource[$resourceID] > 0) ? $resource[$resourceID] : '',
      );
      $resourcesJson[$resourceID] = array(
        'resource_id'    => $GLOBALS['resourceTypeList'][$resourceID]->resourceID,
        'name'           => $GLOBALS['resourceTypeList'][$resourceID]->name,
        'current_amount' => $details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName],
        'amount'         => 0,
      );
    }
  }
  $template->addVar('resource', $resources);
  $template->addVar('resource_json', json_encode($resourcesJson));

  // units table
  $unitprops = array();
  $units     = array();
  $unitsJson = array();
  foreach ($GLOBALS['unitTypeList'] as $unitID => $dummy) {
    // if no units of this type, next type
    if (!$details[$GLOBALS['unitTypeList'][$unitID]->dbFieldName]) continue;

    $temp = array();
    $encumbrance = array();
    foreach ($GLOBALS['resourceTypeList'] as $resourceID => $dummy) {
      $amount = (isset($details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName])) ? floor($details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName]) : 0;

      if (!$GLOBALS['resourceTypeList'][$resourceID]->nodocumentation || $amount > 0) {
        $encumbrance[$resourceID] = array(
          'resourceID'  => $resourceID,
          'dbFieldName' => (isset($GLOBALS['unitTypeList'][$unitID]->encumbranceList[$resourceID])) ? $GLOBALS['resourceTypeList'][$resourceID]->dbFieldName : '',
          'name'        => (isset($GLOBALS['unitTypeList'][$unitID]->encumbranceList[$resourceID])) ? $GLOBALS['resourceTypeList'][$resourceID]->name : '',
          'load'        => (isset($GLOBALS['unitTypeList'][$unitID]->encumbranceList[$resourceID]) ? $GLOBALS['unitTypeList'][$unitID]->encumbranceList[$resourceID] : 0)
        );
        $temp[] = (isset($GLOBALS['unitTypeList'][$unitID]->encumbranceList[$resourceID]) ? $GLOBALS['unitTypeList'][$unitID]->encumbranceList[$resourceID] : 0);
      }
    }

    $units[] = array(
      'name'              => $GLOBALS['unitTypeList'][$unitID]->name,
      'unit_id'           => $unitID,
      'food_cost'         => $GLOBALS['unitTypeList'][$unitID]->foodCost,
      'speed_factor'      => $GLOBALS['unitTypeList'][$unitID]->wayCost,
      'max_unit_count'    => $details[$GLOBALS['unitTypeList'][$unitID]->dbFieldName],
      'encumbrance'       => $encumbrance,
      'hitPoints'         => $GLOBALS['unitTypeList'][$unitID]->hitPoints,
      'value'             => ($safeForm && isset($unit[$unitID])) ? $unit[$unitID] : '',
      'size'              => $GLOBALS['unitTypeList'][$unitID]->hitPoints,
      'arealAttack'       => $GLOBALS['unitTypeList'][$unitID]->attackAreal,
      'rangeAttack'       => $GLOBALS['unitTypeList'][$unitID]->attackRange,
      'attackRate'        => $GLOBALS['unitTypeList'][$unitID]->attackRate
    );

    $unitsJson[] = array(
      'unit_id'      => $unitID,
      'foodCost'     => $GLOBALS['unitTypeList'][$unitID]->foodCost,
      'speedFactor'  => $GLOBALS['unitTypeList'][$unitID]->wayCost,
      'maxUnitCount' => $details[$GLOBALS['unitTypeList'][$unitID]->dbFieldName],
      'encumbrance'  => $encumbrance,
      'hitPoints'    => $GLOBALS['unitTypeList'][$unitID]->hitPoints,
      'size'         => $GLOBALS['unitTypeList'][$unitID]->hitPoints,
      'arealAttack'  => $GLOBALS['unitTypeList'][$unitID]->attackAreal,
      'rangeAttack'  => $GLOBALS['unitTypeList'][$unitID]->attackRange,
      'attackRate'   => $GLOBALS['unitTypeList'][$unitID]->attackRate
    );
  }
  $template->addVar('unit_list', $units);
  $template->addVar('unit_list_json', json_encode($unitsJson));

  // weitergereichte Koordinaten
  if (!Request::getVar('movementID', 0) || $safeForm) {
    if (Request::getVar('targetCaveID', 0) > 0) {
      $caveData = getCaveByID(Request::getVar('targetCaveID', 0));

      $template->addVars(array(
        'target_x_coord'   => $caveData['xCoord'],
        'target_y_coord'   => $caveData['yCoord'],
        'target_cave_name' => $caveData['name'],
      ));
    } else {
      $template->addVars(array(
        'target_x_coord'   => Request::getVar('targetXCoord', ''),
        'target_y_coord'   => Request::getVar('targetYCoord', ''),
        'target_cave_name' => Request::getVar('targetCaveName', ''),
      ));
    }
  }

  // weitere Paramter
   $template->addVar('params', array(
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
    $template->addVar('artefact', $myartefacts);
  }

  // hero
  if ($details['hero'] != 0) {
    $template->addVar('hero', $hero);
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