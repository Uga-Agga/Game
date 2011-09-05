<?php
/*
 * artefact.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function artefact_getDetail($caveID, &$myCaves) {
  global $config, $resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList;

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'artefactdetail.ihtml');

  $show_artefact = TRUE;

  $artefactID = request_var('artefactID', 0);
  $artefact = artefact_getArtefactByID($artefactID);

  $description_initiated = $artefact['description_initiated'];
  unset($artefact['description_initiated']);

  // Gott oder nicht?
  if ($_SESSION['player']->tribe != GOD_ALLY) {
    // gibts nicht oder nicht in einer H�hle
    if (!$artefact['caveID']) {
      $show_artefact = FALSE;

    } else {

      $cave = getCaveByID($artefact['caveID']);

      // leere Höhle
      if (!$cave['playerID']) {
        $show_artefact = FALSE;

      } else {

        $owner = getPlayerByID($cave['playerID']);
        // Besitzer ist ein Gott
        if ($owner['tribe'] == GOD_ALLY){
          $show_artefact = FALSE;
        }
      }
    }
  }

  if ($show_artefact) {

    // eigene H�hle ...
    if (array_key_exists($artefact['caveID'], $myCaves)) {

      // Ritual ausf�hren?
      if (isset($_POST['initiate'])) {
        $message = artefact_beginInitiation($artefact);
        tmpl_set($template, 'message', $message);

        // reload
        $myCaves = getCaves($_SESSION['player']->playerID);
      }

      // wenn noch uneingeweiht und in der "richtigen" Hoehle, ritual zeigen
      else if ($artefact['caveID'] == $caveID && $artefact['initiated'] == ARTEFACT_UNINITIATED) {

        // Check, ob bereits eingeweiht wird.
        if (sizeof(artefact_getArtefactInitiationsForCave($caveID)) == 0) {

          // Hol das Einweihungsritual
          $ritual = artefact_getRitualByID($artefact['initiationID']);

          // Hol die Kosten und beurteile ob genug da ist
          $merged_game_rules = array_merge($resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList);

          $cost = array();
          foreach($merged_game_rules as $val) {
            if (array_key_exists($val->dbFieldName, $ritual)) {
              if ($ritual[$val->dbFieldName]) {
                $object_context = (ceil($ritual[$val->dbFieldName]) > floor($myCaves[$artefact['caveID']][$val->dbFieldName])) ?
                                  'LESS' : 'ENOUGH';
                array_push($cost, array('object' => $val->name, $object_context.'/amount' => $ritual[$val->dbFieldName]));
              }
            }
          }

          $artefact['INITIATION'] = array('COST'        => $cost,
                                          'name'        => $ritual['name'],
                                          'description' => $ritual['description'],
                                          'duration'    => time_formatDuration($ritual['duration']),
                                          'HIDDEN'      => array(array('name' => "artefactID", 'value' => $artefact['artefactID']),
                                                                 array('name' => "modus",      'value' => ARTEFACT_DETAIL),
                                                                 array('name' => "initiate",   'value' => 1)));
        }

        // es wird bereits in dieser H�hle eingeweiht...
        else {
          tmpl_iterate($template, 'ARTEFACT/NO_INITIATION');
        }
      }
      // "geheime" Beschreibung nur zeigen, wenn eingeweiht
      if ($artefact['initiated'] == ARTEFACT_INITIATED)
        $artefact['description_initiated'] = $description_initiated;
    }

    tmpl_set($template, 'ARTEFACT', $artefact);
  } else {
    tmpl_set($template, 'message', _('&Uuml;ber dieses Artefakt wei&szlig; man nichts.'));
  }

  return tmpl_parse($template);
}

function artefact_getList($caveID, $myCaves) {

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'artefactlist.ihtml');

  $artefacts = getArtefactList();

  // get moving artefacts
  $movements = artefact_getArtefactMovements();

  $alternate_own = 0;
  $alternate_other = 0;
  $alternate_hidden = 0; 
  $alternate_moving = 0;
  $alternate_limbus = 0;
  foreach ($artefacts AS $value) {

    // eigenes Artefakt
    if (array_key_exists($value['caveID'], $myCaves)) {
      $context = 'ARTEFACT_OWN';
      $value['alternate'] = ++$alternate_own % 2 ? "alternate" : "";

      switch ($value['initiated']) {

        case ARTEFACT_UNINITIATED: if ($value['caveID'] == $caveID)
                                     $value['INITIATION_POSSIBLE'] = array(
                                       'modus_artefact_detail' => ARTEFACT_DETAIL,
                                       'artefactID' => $value['artefactID']);
                                   else
                                     $value['INITIATION_NOT_POSSIBLE'] = array('status' => _('uneingeweiht'));
                                   break;
        case ARTEFACT_INITIATING:  $value['INITIATION_NOT_POSSIBLE'] = array('status' => _('wird gerade eingeweiht'));break;
        case ARTEFACT_INITIATED:   $value['INITIATION_NOT_POSSIBLE'] = array('status' => _('eingeweiht'));break;
        default:                   $value['INITIATION_NOT_POSSIBLE'] = array('status' => _('Fehler'));
      }

    // fremdes Artefakt
    } else {

      // Berechtigung pr�fen

      // ***** kein Gott! *****************************************************
      if ($_SESSION['player']->tribe != GOD_ALLY){

        // Artefakt liegt in einer H�hle
        if ($value['caveID'] != 0) {

          // A. in Ein�den und von G�ttern sind Tabu
          if ($value['playerID'] == 0 || $value['tribe'] == GOD_ALLY) continue;

          $context = 'ARTEFACT_OTHER';
          $value['alternate'] = ++$alternate_other % 2 ? "alternate" : "";
        }

        // Artefakt liegt nicht in einer H�hle
        else {

          // A. wird bewegt?
          $move = $movements[$value['artefactID']];

          // nein. Limbusartefakt!
          if (!$move)
            continue;

          // A. wird bewegt!
          $context = 'ARTEFACT_MOVING_ETA';
          $value += $move;
          $value['alternate'] = ++$alternate_moving % 2 ? "alternate" : "";
        }
      }

      // ***** Gott! *****************************************************+++++
      else {

        // Artefakt liegt in einer H�hle
        if ($value['caveID'] != 0){


          // A. liegt in Ein�de.
          if ($value['playerID'] == 0){
            $context = 'ARTEFACT_HIDDEN';
            $value['alternate'] = ++$alternate_hidden % 2 ? "alternate" : "";
          }

          // A. liegt bei einem Spieler
          else {
            $context = 'ARTEFACT_OTHER';
            $value['alternate'] = ++$alternate_other % 2 ? "alternate" : "";
          }
        }

        // Artefakt liegt nicht in einer H�hle
        else {

          // A. wird bewegt?
          $move = $movements[$value['artefactID']];

          // nein. Limbusartefakt!
          if (!$move){
            $context = 'ARTEFACT_LIMBUS';
            $value['alternate'] = ++$alternate_limbus % 2 ? "alternate" : "";
          }

          // A. wird bewegt!
          else {
            $context = 'ARTEFACT_MOVING_ETA';
            $value += $move;
            $value['alternate'] = ++$alternate_moving % 2 ? "alternate" : "";
          }
        }
      } // Gott
    } // fremdes Artefakt

    $value['modus_artefact_detail'] = ARTEFACT_DETAIL;
    $value['modus_map_detail']      = MAP_DETAIL;
    $value['modus_player_detail']   = PLAYER_DETAIL;
    $value['modus_tribe_detail']    = TRIBE_DETAIL;

    tmpl_iterate($template, $context . '/ARTEFACT');
    tmpl_set($template, $context . '/ARTEFACT', $value);
  }

  return tmpl_parse($template);
}

?>