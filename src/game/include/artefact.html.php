<?php
/*
 * artefact.html.php -
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

function artefact_getDetail($caveID, &$myCaves) {
  global $template;

  $messageText = array(
    -5 => array('type' => 'error', 'message' => _('Dieses Artefakt kann nicht noch einmal eingeweiht werden.')),
    -4 => array('type' => 'error', 'message' => _('Fehler: Artefakt konnte nicht auf ARTEFACT_INITIATING gestellt werden.')),
    -3 => array('type' => 'error', 'message' => _('Sie weihen bereits ein anderes Artefakt ein.')),
    -2 => array('type' => 'error', 'message' => _('Es fehlen die notwendigen Voraussetzungen.')),
    -1 => array('type' => 'error', 'message' => _('Fehler: Ritual nicht gefunden.')),
     0 => array('type' => 'notice', 'message' => _('Über dieses Artefakt weiß man nichts!')),
     1 => array('type' => 'success', 'message' => _('Die Einweihung des Artefakts wurde gestartet!'))
  );

  // open template
  $template->setFile('artefactDetail.tmpl');

  $show_artefact = TRUE;

  $artefactID = Request::getVar('artefactID', 0);
  $artefact = artefact_getArtefactByID($artefactID);

  if (empty($artefact)) {
    $messageID = 0;
  } else {
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
          if ($owner['tribe'] == GOD_ALLY) {
            $show_artefact = FALSE;
          }
        }
      }
    }

    $showRitual = 0;
    $showStatus = 0;
    $template->addVars(array('show_artefact' => $show_artefact));
    if ($show_artefact) {
      $artefact['img'] = $artefact['uninitiationImg']; // Bild vom uninitalisierten Artefakt per default anzeigen!

      // eigene Höhle ...
      if (isset($myCaves[$artefact['caveID']])) {
        $showStatus = 1;

        // Ritual ausführen?
        if (Request::isPost('initiate')) {
          $messageID = artefact_beginInitiation($artefact);

          // reload
          $myCaves = getCaves($_SESSION['player']->playerID);

        // wenn noch uneingeweiht und in der "richtigen" Höhle, ritual zeigen
        } else if ($artefact['caveID'] == $caveID && $artefact['initiated'] == ARTEFACT_UNINITIATED) {
          // Check, ob bereits eingeweiht wird.
          if (sizeof(artefact_getArtefactInitiationsForCave($caveID)) == 0) {
            $showRitual = 1;

            // Hol das Einweihungsritual
            $ritual = artefact_getRitualByID($artefact['initiationID']);

            // Hol die Kosten und beurteile ob genug da ist
            $merged_game_rules = array_merge($GLOBALS['resourceTypeList'], $GLOBALS['buildingTypeList'], $GLOBALS['unitTypeList'], $GLOBALS['scienceTypeList'], $GLOBALS['defenseSystemTypeList']);

            $cost = array();
            foreach($merged_game_rules as $val) {
              if (isset($ritual[$val->dbFieldName])) {
                if ($ritual[$val->dbFieldName]) {
                  $object_context = (ceil($ritual[$val->dbFieldName]) > floor($myCaves[$artefact['caveID']][$val->dbFieldName])) ?
                                    'less-' : 'enough ';
                  array_push($cost, array('object' => $val->name, 'amount' => $ritual[$val->dbFieldName], 'class' => $object_context));
                }
              }
            }

            $artefact['initiation'] = array(
              'cost'        => $cost,
              'name'        => $ritual['name'],
              'description' => $ritual['description'],
              'duration'    => time_formatDuration($ritual['duration']),
              'initiate'    => 1
            );
          } else {
            $showRitual = -1;
          }
        } elseif ($artefact['caveID'] == $caveID && $artefact['initiated'] == ARTEFACT_INITIATING) {
            // Arte wird gerade eingeweiht
            $showRitual = -1;
        }

        // "geheime" Beschreibung nur zeigen, wenn eingeweiht
        if ($artefact['initiated'] == ARTEFACT_INITIATED) {
          $artefact['description_initiated'] = $description_initiated;

          // Besitzer des Artefaktes und initalisiert? Richtiges Artefakt Bild anzeigen
          if (isset($myCaves[$artefact['caveID']])) {
            $artefact['img'] = $artefact['initiationImg'];
          }
        }
      }

      $template->addVars(array('artefact'   => $artefact));
      $template->addVars(array('showRitual' => $showRitual));
      $template->addVars(array('showStatus' => $showStatus));
    } else {
      // über dieses Artefakt weiß man nichts!
      $messageID = 0;
    }
  }

  $template->addVar('status_msg', (isset($messageID)) ? $messageText[$messageID] : '');
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
    if (isset($ownCaves[$artefact['caveID']])) {
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
          if ($artefact['playerID'] == 0 || $artefact['tribe'] == GOD_ALLY) {
            continue;
          }

          $artefact['isOwnArtefact'] = false;
          $otherArtefactsList[] = $artefact;
        }

        // Artefakt liegt nicht in einer Höhle
        else {
          // A. wird bewegt?
          $move = (isset($movements[$artefact['artefactID']])) ? $movements[$artefact['artefactID']] : false;

          // nein. Limbusartefakt!
          if (!$move) {
            continue;
          }

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
          $move = (isset($movements[$artefact['artefactID']])) ? $movements[$artefact['artefactID']] : false;

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
  
  $template->addVars(array(
    'ownArtefactsList' => $ownArtefactsList, 
    'otherArtefactsList' => $otherArtefactsList,
    'movedArtefactsList' => $movedArtefactsList
  ));
}

?>