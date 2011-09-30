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
  global $config, $template;
  global $resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList;
  
  $template->throwError('Diese Seite wird noch überarbeitet.');
  return;

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'artefactdetail.ihtml');

  $show_artefact = TRUE;

  $artefactID = request_var('artefactID', 0);
  $artefact = artefact_getArtefactByID($artefactID);

  $description_initiated = $artefact['description_initiated'];
  unset($artefact['description_initiated']);

  // Gott oder nicht?
  if ($_SESSION['player']->tribe != GOD_ALLY) {
    // gibts nicht oder nicht in einer Höhle
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

    // eigene Höhle ...
    if (array_key_exists($artefact['caveID'], $myCaves)) {

      // Ritual ausführen?
      if (isset($_POST['initiate'])) {
        $message = artefact_beginInitiation($artefact);
        tmpl_set($template, 'message', $message);

        // reload
        $myCaves = getCaves($_SESSION['player']->playerID);
      }

      // wenn noch uneingeweiht und in der "richtigen" Höhle, ritual zeigen
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

        // es wird bereits in dieser Höhle eingeweiht...
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
    tmpl_set($template, 'message', _('Über dieses Artefakt weiß man nichts.'));
  }

  return tmpl_parse($template);
}

function artefact_getList($caveID, $ownCaves) {
  global $template;

  $template->setFile('artefactlist.tmpl');

  //get artefacts
  $artefacts = getArtefactList();

  // get moving artefacts
  $movements = artefact_getArtefactMovements();

  $ownArtefactsList = array();
  $otherArtefactsList = array();
  $movedArtefactsList = array();
  
  foreach ($artefacts AS $artefact) { 
    
    // eigenes Artefakt
    if (array_key_exists($artefact['caveID'], $ownCaves)) {

      switch ($artefact['initiated']) {

        case ARTEFACT_UNINITIATED: 
          if ($artefact['caveID'] == $caveID) {
            $artefact['initiation_possible'] = array('artefactID' => $artefact['artefactID']);
          }
          else {
            $artefact['initiation_not_possible'] = array('status' => _('uneingeweiht'));
          }
          break;
          
        case ARTEFACT_INITIATING:
          $artefact['initiation_not_possible'] = array('status' => _('wird gerade eingeweiht'));
          break;
          
        case ARTEFACT_INITIATED:
          $artefact['initiation_not_possible'] = array('status' => _('eingeweiht'));
          break;
          
        default:
          $artefact['initiation_not_possible'] = array('status' => _('Fehler'));
          break;
      }
      
      $ownArtefactsList[] = $artefact;
    // fremdes Artefakt
    } else {

      // Berechtigung prüfen

      // ***** kein Gott! *****************************************************
      if ($_SESSION['player']->tribe != GOD_ALLY) {

        // Artefakt liegt in einer Höhle
        if ($artefact['caveID'] != 0) {

          // A. in Einöden und von Göttern sind Tabu
          if ($artefact['playerID'] == 0 || $artefact['tribe'] == GOD_ALLY) continue;

          $artefact['isOwnArtefact'] = false;
          $otherArtefactsList[] = $artefact;
        }

        // Artefakt liegt nicht in einer Höhle
        else {

          // A. wird bewegt?
          $move = $movements[$artefact['artefactID']];

          // nein. Limbusartefakt!
          if (!$move)
            continue;
          
          // A. wird bewegt!
          $artefact['showEndTime'] = true;
          $artefact += $move;
          $movedArtefactsList[] = $artefact;
        }
      }

      // ***** Gott! *****************************************************+++++
      else {

        // Artefakt liegt in einer Höhle
        if ($artefact['caveID'] != 0) {


          // A. liegt in Einöde.
          if ($artefact['playerID'] == 0) {
            $artefact['hideArtefact'] = true;
            $otherArtefactsList[] = $artefact;
          }

          // A. liegt bei einem Spieler
          else {
            $artefact['isOwnArtefact'] = false;
            $otherArtefactsList[] = $artefact;
          }
        }

        // Artefakt liegt nicht in einer Höhle
        else {

          // A. wird bewegt?
          $move = $movements[$artefact['artefactID']];

          // nein. Limbusartefakt!
          if (!$move) {
            $artefact['isLimbusArtefact'] = true;
          }

          // A. wird bewegt!
          else {
            $artefact['showEndTime'] = true;
            $artefact += $move;
            $movedArtefactsList[] = $artefact;
          }
        }
      } // Gott
    } // fremdes Artefakt
  } // foreach
  
  $template->addVars(array('ownArtefactsList' => $ownArtefactsList, 
                           'otherArtefactsList' => $otherArtefactsList,
                           'movedArtefactsList' => $movedArtefactsList));
  //print_r($template);
}

?>