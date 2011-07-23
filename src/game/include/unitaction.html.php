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

function unitAction($caveID, &$ownCave) {

  global $config, $db,
         $MAX_RESOURCE,
         $MOVEMENTCOSTCONSTANT,
         $MOVEMENTSPEEDCONSTANT,
         $resourceTypeList,
         $unitTypeList,
         $FUELRESOURCEID;

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

    $validCaveName = FALSE;

    // targetCaveID >>> coords
    if ($targetCaveID > 0) {
      $result = getCaveByID(intval($targetCaveID));
      if (sizeof($result) != 0) {
        $targetXCoord = $result['xCoord'];
        $targetYCoord = $result['yCoord'];
        $validCaveName = TRUE;
      }

    // name >>> coords
    } else if ($targetCaveName != "") {
      $result = getCaveByName($targetCaveName);
      if (sizeof($result) != 0) {
        $targetXCoord = $result['xCoord'];
        $targetYCoord = $result['yCoord'];
        $validCaveName = TRUE;
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
      $msg = _('Bitte Bewegungsart ausw&auml;hlen!');

    else if (!sizeof($unit))
      $msg = _('Es sind keine Einheiten ausgew&auml;hlt!');

    else if ((($targetXCoord == 0) || ($targetYCoord == 0)) && ($targetCaveName == ""))
      $msg = _('Es fehlt eine Zielkoordinate oder ein Zielh&ouml;hlenname!');

    else if ((($targetXCoord == 0) || ($targetYCoord == 0)) && !($targetCaveName == "") && $validCaveName === FALSE)
      $msg = sprintf(_('Es gibt keine H&ouml;hle mit dem Namen "%s"!'), $targetCaveName);

    else if ($overloaded)
      $msg = _('Deine Krieger k&ouml;nnen die Menge an Ressourcen nicht tragen!!');

    else if (beginner_isCaveProtectedByCoord($targetXCoord, $targetYCoord))
      $msg = _('Die Zielh&ouml;hle steht unter Anf&auml;ngerschutz.');

    else if (beginner_isCaveProtectedByID($caveID))
      $msg = _('Ihre H&ouml;hle steht unter Anf&auml;ngerschutz. Sie k&ouml;nnen den Schutz sofort unter dem Punkt <a href="?modus=cave_detail">Bericht &uuml;ber diese H&ouml;hle</a> beenden');

    else if (request_var('movementID', 0) == 6 && cave_isCaveSecureByCoord($targetXCoord, $targetYCoord))
      $msg = _('Sie k&ouml;nnen diese H&ouml;hle nicht &uuml;bernehmen. Sie ist gegen &Uuml;bernahmen gesch&uuml;tzt.');

    else if ($denymovement_nonenemy)
      $msg = _('Sie k&ouml;nnen im Krieg keine Einheiten zu unbeteiligten Parteien verschieben!');

    else if ($denymovement_targetwar)
      $msg = _('Sie k&ouml;nnen keine Einheiten zu kriegf&uuml;hrenden St&auml;mmen verschieben, wenn Sie unbeteiligt sind.');

    //  Einheiten bewegen!
    else {

  // Entfernung x Dauer pro Höhle x größter Geschwindigkeitsfaktor x Bewegungsfaktor
  $duration = ceil(
        getDistanceByCoords($details['xCoord'], $details['yCoord'],
                            $targetXCoord, $targetYCoord) *
        $minutesPerCave *
        getMaxSpeedFactor($unit) *
        $ua_movements[$movementID]->speedfactor);
        
  $distance = ceil(getDistanceByCoords($details['xCoord'], $details['yCoord'],
                            $targetXCoord, $targetYCoord));
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
        $msg = _('Nicht genug Nahrung zum Ern&auml;hren der Krieger auf ihrem langen Marsch vorhanden!');

      } else {
        $msgID = setMovementEvent(
          $caveID, $details,
          $targetXCoord, $targetYCoord,
          $unit, $resource,
          $movementID, $reqFood, $duration,
          $moveArtefact,
          $minutesPerCave * $ua_movements[$movementID]->speedfactor);

        switch ($msgID) {
          case 0: $msg = sprintf(_('Die Krieger wurden losgeschickt und haben %d Nahrung mitgenommen!'), $reqFood);
                  break;
          case 1: $msg = _('In diesen Koordinaten liegt keine H&ouml;hle!');
                  break;
          case 2: $msg = _('F&uuml;r diese Bewegung sind nicht gen&uuml;gend Einheiten/Rohstoffe verf&uuml;gbar!');
                  break;
          case 3: $msg = _('Schwerer Fehler: Bitte Admin kontaktieren!');
        }
      }
    }
  } else if ($eventID = request_var('eventID', 0)) {

    $msgID = reverseMovementEvent($caveID, $eventID);
    switch ($msgID) {
      case 0: $msg = _('Die Einheiten kehren zur&uuml;ck!'); break;
      case 1: $msg = _('Fehler bei der R&uuml;ckkehr!'); break;
    }
  }

  // refresh this cave
  $temp = getCaveSecure($caveID, $_SESSION['player']->playerID);
  $ownCave[$caveID] = $details = $temp;
  // make sure that bagged artefacts are not shown again
  if ($moveArtefact != 0)
    $myartefacts = artefact_getArtefactsReadyForMovement($caveID);

// //////////////////////////////////////////////////////////////
// Create the page
// //////////////////////////////////////////////////////////////

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'unitaction.ihtml');

  // messages
  if (isset($msg)) tmpl_set($template, '/MESSAGE/msg', $msg);

  // javascript support
  tmpl_set($template, 'currentX',             $details['xCoord']);
  tmpl_set($template, 'currentY',             $details['yCoord']);
  tmpl_set($template, 'dim_x',                $dim_x);
  tmpl_set($template, 'dim_y',                $dim_y);
  tmpl_set($template, 'speed',                $minutesPerCave);
  tmpl_set($template, 'fuel_id',              $FUELRESOURCEID);
  tmpl_set($template, 'fuel_name',            $resourceTypeList[$FUELRESOURCEID]->name);
  tmpl_set($template, 'movementcostconstant', $foodPerCave);
  tmpl_set($template, "resourceTypes",        $MAX_RESOURCE);
  tmpl_set($template, "rules_path",           RULES_PATH);

  // movements
  $selectable_movements = array();
  foreach ($ua_movements AS $value)
    if ($value->playerMayChoose)
      $selectable_movements[] = get_object_vars($value);
  tmpl_set($template, 'SELECTACTION', $selectable_movements);


  // resources
  $resources = array();
  for($res = 0; $res < sizeof($resourceTypeList); $res++)
    if (!$resourceTypeList[$res]->nodocumentation)
    $resources[] = array(
      'resourceID'    => $resourceTypeList[$res]->resourceID,
      'name'          => $resourceTypeList[$res]->name,
      'currentAmount' => "0" + $details[$resourceTypeList[$res]->dbFieldName],
      'dbFieldName'   => $resourceTypeList[$res]->dbFieldName);

  tmpl_set($template, 'RESOURCE',         $resources);
  tmpl_set($template, 'TOTAL',            $resources);
  tmpl_set($template, 'RESOURCE_LUGGAGE', $resources);

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
          'load' => "0" + (isset($unitTypeList[$i]->encumbranceList[$j]) ? $unitTypeList[$i]->encumbranceList[$j] : 0));
        $temp[] = "0" + (isset($unitTypeList[$i]->encumbranceList[$j]) ? $unitTypeList[$i]->encumbranceList[$j] : 0);
      }
    }

    $unitprops[] = array(
      'unitID'           => $unitTypeList[$i]->unitID,
      'foodCost'         => $unitTypeList[$i]->foodCost,
      'speedFactor'      => $unitTypeList[$i]->wayCost,
      'resourceLoad'     => implode(",", $temp),
      'maxWarriorAnzahl' => $details[$unitTypeList[$i]->dbFieldName]);

    $units[] = array(
      'name'             => $unitTypeList[$i]->name,
      'modus'            => UNIT_PROPERTIES,
      'unitID'           => $unitTypeList[$i]->unitID,
      'foodCost'         => $unitTypeList[$i]->foodCost,
      'speedFactor'      => $unitTypeList[$i]->wayCost,
      'maxWarriorAnzahl' => $details[$unitTypeList[$i]->dbFieldName],
      // ?? warum -> ?? $i gegen namen ersetzen!!! TODO
      'warriorID'        => $i,
      'ENCUMBRANCE'      => $encumbrance);
  }
  tmpl_set($template, 'UNITPROPS',     $unitprops);
  tmpl_set($template, 'SELECTWARRIOR', $units);

  // weitergereichte Koordinaten
  if (!request_var('movementID', 0)) {
    tmpl_set($template, 'targetXCoord', request_var('targetXCoord', 0));
    tmpl_set($template, 'targetYCoord', request_var('targetYCoord', 0));
    tmpl_set($template, 'targetCaveName', request_var('targetCaveName', ""));
  }

  // weitere Paramter
  $hidden = array(
    array('name'=>'modus',  'value'=>UNIT_MOVEMENT),
    array('name'=>'moveit', 'value'=>'true'),
    array('name'=>'trigger','value'=>'self'),
    array('name'=>'tstamp', 'value'=>"".time()));
  tmpl_set($template, 'PARAMS', $hidden);


  $movements = digest_getMovements(array($caveID => $details), array(), true);

  foreach($movements AS $move) {
    if ($move['isOwnMovement']) {
      tmpl_iterate($template, 'MOVEMENT/MOVE');
      tmpl_set($template, 'MOVEMENT/MOVE', $move);
    } else {
      tmpl_iterate($template, 'OPPMOVEMENT/MOVE');
      tmpl_set($template, 'OPPMOVEMENT/MOVE', $move);
    }
  }

  // artefakte
  if (sizeof($myartefacts) != 0)
    tmpl_set($template, '/ARTEFACTS/ARTEFACT', $myartefacts); 
    
  // Module "CaveBookmarks" Integration
  // FIXME should know whether the module is installed
  if (TRUE) {

    // show CAVEBOOKMARKS context
    tmpl_set($template, '/CAVEBOOKMARKS/iterate', '');

    // get model
    $cb_model = new CaveBookmarks_Model();

    // get bookmarks
    $bookmarks = $cb_model->getCaveBookmarks(true);

    // set bookmarks
    if (sizeof($bookmarks)){
      tmpl_set($template, '/CAVEBOOKMARKS/CAVEBOOKMARK',   $bookmarks);
      tmpl_set($template, '/CAVEBOOKMARKS/CAVEBOOKMARKJS', $bookmarks);
    }
  }

  return tmpl_parse($template);
}

?>