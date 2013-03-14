<?php
/*
 * map.html.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011  Sascha Lange <salange@uos.de>
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

@include_once('modules/CaveBookmarks/model/CaveBookmarks.php');

/** creates the map-page with header and the specified map region */
function getCaveMapContent($caveID, $caves) {
  global $template;

  $caveData = $caves[$caveID];
  $mapSize = getMapSize();  // Größe der Karte wird benötigt
  $message  = '';

  // template öffnen
  $template->setFile('map.tmpl');

  $resolvedCoords = determineCoordsFromParameters($caveData, $mapSize);
  $template->addVars($resolvedCoords);

  // corrected x-y-coords of querried cave
  $xCoord = $resolvedCoords['xCoord'];
  $yCoord = $resolvedCoords['yCoord'];

  $minX = $mapSize['minX'];
  $minY = $mapSize['minY'];
  $maxX = $mapSize['maxX'];
  $maxY = $mapSize['maxY'];

  // get CaveBookmarks
  $cb_model = new CaveBookmarks_Model();

  // get bookmarks
  $bookmarks = $cb_model->getCaveBookmarks(true);

  // set bookmarks
  if (sizeof($bookmarks)) {
    $template->addVars(array('caveBookmarks' => $bookmarks));
  }

  if (Request::getVar('type', '') == 'minimap') {
    $mapData = calcCaveMiniMapRegionData();
  } else {
    $mapData = calcCaveMapRegionData($caveID, $caves, $xCoord, $yCoord);
  }

  $template->addVars($mapData);
}

/** calculates the displayed data for a specific map region. */
function calcCaveMapRegionData($caveID, $caves, $xCoord, $yCoord) {
  $caveData = $caves[$caveID];
  $mapSize = getMapSize();  // Größe der Karte wird benötigt
  $message  = '';

  // calculate the dimensions of the visible map region
  $section = calcVisibleMapRegion($mapSize, $xCoord, $yCoord);
  $minX = $section['minX']; // minimum x-coordinate of caves in visible part of the map
  $minY = $section['minY']; // minimum y-coordinate of caves in visible part of the map
  $maxX = $section['maxX']; // maximum x-coordinate of caves in visible part of the map
  $maxY = $section['maxY']; // maximum y-coordinate of caves in visible part of the map

  $centerX = $section['centerX'];
  $centerY = $section['centerY'];

  // get the map details
  $caveDetails = getCaveDetailsByCoords($minX, $minY, $maxX, $maxY);
  $relations = TribeRelation::getRelations($_SESSION['player']->tribeID);

  $map = array();
  foreach ($caveDetails AS $cave) {
    $cell = array(
      'caveID'        => $cave['caveID'],
      'terrain'       => 'terrain'.$cave['terrain'],
      'terrain_tribe' => $GLOBALS['terrainList'][$cave['terrain']]['tribeRegion'],
      'imgMap'        => $GLOBALS['terrainList'][$cave['terrain']]['imgMap'],
      'barren'        => $GLOBALS['terrainList'][$cave['terrain']]['barren'],
      'title'         => 'Dies ist der Landstrich "' . $cave['cavename'] . '" (' . $cave['xCoord'] . '|' . $cave['yCoord'] . ') - ' . $GLOBALS['terrainList'][$cave['terrain']]['name'],
    );

    // unbewohnte Höhle
    if ($cave['playerID'] == 0) {
      // als Frei! zeigen
      if ($cave['takeoverable'] == 1) {
        $text = _('Frei!');
        $file = "icon_cave_empty";
      // als Einöde zeigen
      } else {
        $text = _('Einöde');
        $file = "icon_waste";
      }
    // bewohnte Höhle
    } else {
      // eigene Höhle
      if ($cave['playerID'] == $_SESSION['player']->playerID) {
        $file = "icon_cave_own";
      } else {
        $file = "icon_cave_other"; // fremde Höhle
      }

      // mit Artefakt
      if ($cave['hasArtefact'] && ($cave['tribe'] != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY)) {
        $file .= "_artefact";
      }

      // Stamm abkürzen
      $decodedTribe = unhtmlentities($cave['tribe']);
      if (strlen($decodedTribe) > 10) {
        $cell['tribe'] = htmlentities(substr($decodedTribe, 0, 8)) . "..";
      } else {
        $cell['tribe'] = $cave['tribe'];
      }
      $cell['tribeID'] = $cave['tribeID'];

      // Besitzer
      $decodedOwner = unhtmlentities($cave['name']);
      if (strlen($decodedOwner) > 10) {
        $text = htmlentities(substr($decodedOwner, 0, 8)) . "..";
      } else {
        $text = $cave['name'];
      }

      // übernehmbare Höhlen können gekennzeichnet werden
      if ($cave['secureCave'] != 1) {
        $cell['unsecure'] = array('dummy' => '');
      }

      if ($_SESSION['player']->playerID == $cave['playerID']) {
        $cell['css_self'] = 't_self';
      }
      if (isset($relations['own'][$cave['tribeID']])) {
        $cell['css_own'] = 't_own_relation_' . $relations['own'][$cave['tribeID']]['relationType'];
      }
      if (isset($relations['other'][$cave['tribeID']])) {
        $cell['css_other'] = 't_other_relation_' . $relations['other'][$cave['tribeID']]['relationType'];
      }
    }

    $cell['file'] = $file;
    $cell['text'] = $text;

    $map[$cave['xCoord']][$cave['yCoord']] = $cell;
  }

  // create a region data array with an empty row as starting point.
  $regionData = array('rows' => array());

  // über alle Zeilen
  for ($j = $minY - 1; $j <= $maxY + 1; ++$j) {
    $cells = array();
    // über alle Spalten
    for ($i = $minX - 1; $i <= $maxX + 1; ++$i ) {

      // leere Zellen
      if (($j == $minY - 1 || $j == $maxY + 1) &&
          ($i == $minX - 1 || $i == $maxX + 1)) {
        array_push($cells, getCornerCell());

      // x-Beschriftung
      } else if ($j == $minY - 1 || $j == $maxY + 1) {
        array_push($cells, getLegendCell('x', $i));

      // y-Beschriftung
      } else if ($i == $minX - 1 || $i == $maxX + 1) {
        array_push($cells, getLegendCell('y', $j));

      // Kartenzelle
      } else {
        array_push($cells, getMapCell($map, $i, $j));
      }
    }
    array_push($regionData['rows'], $cells);
  }

  $mapData = array(
    'centerXCoord' => $centerX,
    'centerYCoord' => $centerY,
    'queryXCoord'  => $xCoord,
    'queryYCoord'  => $yCoord,
    'mapregion'    => $regionData
  );

  return $mapData;
}

/** creates the map-page with header and the specified map region */
function calcCaveMiniMapRegionData() {
  $mapData = getCaveDetailsForMiniMap();

  foreach ($mapData AS $cave) {
    $cell = array('title' => 'Dies ist der Landstrich ' . $cave['cavename'] . ' (' . $cave['xCoord'] . '|' . $cave['yCoord'] . ') - ' . $GLOBALS['terrainList'][$cave['terrain']]['name']);

    // unbewohnte Höhle
    if ($cave['playerID'] == 0) {
      // als Frei! zeigen
      if ($cave['takeoverable'] == 1) {
        $cell['title'] .= '<br>' . _('Frei!');
      // als Einöde zeigen
      } else {
        $cell['title'] .= '<br>' . _('Einöde!');
      }
    // bewohnte Höhle
    } else {
      $cell['title'] .= '<br>Besitzer: ' . $cave['name'];
      $cell['title'] .= '<br>Stamm: ' . $cave['tribe'];

      // mit Artefakt
      if ($cave['hasArtefact'] && ($cave['tribe'] != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY)) {
        $cell['title'] .= '<br>' . _('In dieser Höhle befindet sich ein Artefakt!');
      }

      // übernehmbare Höhlen können gekennzeichnet werden
      if ($cave['secureCave'] != 1) {
        $cell['title'] .= '<br>' . _('Diese Höhle kann übernommen werden!');
        $cell['title'] .= '<br>Stamm:' . $cave['tribe'];
      }
    }

    $map[$cave['xCoord']][$cave['yCoord']] = $cell;
  }

  $mapData = array(
    'miniMap'      => true,
    'mapregion'    => json_encode($map)
  );

  return $mapData;
}

/** fills the map-region data into the thin, header-less template.
 This is used as response to Ajax calls. */
function getCaveMapRegionContent($caveID, $caves) {
  global $template;

  $caveData = $caves[$caveID];
  $mapSize = getMapSize();  // Größe der Karte wird benötigt
  $message  = '';

  // template öffnen
  $template->setFile('mapRegion.tmpl');

  // Grundparameter setzen
  $template->addVars(array(
    'modus'         => MAP,
    'mapRegionLink' => MAP_REGION,
    'caveID'        => $caveID
    ));

  $resolvedCoords = determineCoordsFromParameters($caveData,  $mapSize);
  $template->addVars($resolvedCoords);

  $xCoord = $resolvedCoords['xCoord'];
  $yCoord = $resolvedCoords['yCoord'];

  $mapData = calcCaveMapRegionData($caveID, $caves, $xCoord, $yCoord);
  $template->addVars($mapData);
}

function getCaveReport($caveID, $ownCaves, $targetCaveID, $method) {
  global $template;

  if (!$targetCaveID) {
    $template->throwError('Es wurde keine Höhle ausgewählt.');
    return;
  }

  if ($method == 'ajax') {
    $shortVersion = true;
    $template->setFile('mapDetailAjax.tmpl');
  }
  else {
    $shortVersion = false;
    $template->setFile('mapDetail.tmpl');
  }

  $cave  = getCaveByID($targetCaveID);

  $caveDetails   = array();
  $playerDetails = null;
  $showArtePossible = false;

  if ($cave['playerID'] != 0) {
    $caveDetails   = getCaves($cave['playerID']);
    $playerDetails = Player::getPlayer($cave['playerID']);

    $showArtePossible = ($playerDetails->tribe != GOD_ALLY) ? true : false;
  }

  $cave['terrain_name'] = $GLOBALS['terrainList'][$cave['terrain']]['name'];
  $cave['terrain_img'] = $GLOBALS['terrainList'][$cave['terrain']]['img'];

  if ($GLOBALS['terrainList'][$cave['terrain']]['tribeRegion']) {
    $cave['terrain_description'] = $GLOBALS['terrainList'][$cave['terrain']]['description'];
    $cave['terrain_tribe_cave'] = $GLOBALS['terrainList'][$cave['terrain']]['tribeRegion'];

    $attackerTribe = Tribe::getByID($cave['lastAttackingTribeID']);
    $cave['tribe_cave_tag'] = $attackerTribe['tag'];
  }

  $region = getRegionByID($cave['regionID']);

  // Wenn die Höhle ein Artefakt enthält und man berechtigt ist -> anzeigen
  if ($cave['hasArtefact'] && ($showArtePossible || $_SESSION['player']->tribe == GOD_ALLY)) {
    $cave['hasArtefact'] = true;
  } else {
    $cave['hasArtefact'] = false;
  }
  if ($cave['hasPet'] && ($showArtePossible || $_SESSION['player']->tribe == GOD_ALLY)) {
    $cave['hasPet'] = true;
  } else {
    $cave['hasArtefact'] = false;
  }

  $template->addVar('cave_details', $cave);

  if ($cave['playerID'] != 0) {
    $template->addVar('player_details', $playerDetails);

/****************************************************************************************************
*
* Alle Höhlen des Spielers ausgeben
*
****************************************************************************************************/
    $caves = array();
    foreach ($caveDetails AS $key => $value) {
      $temp = array(
        'caveName'   => $value['name'],
        'xCoord'     => $value['xCoord'],
        'yCoord'     => $value['yCoord'],
        'terrain'    => $GLOBALS['terrainList'][$value['terrain']]['name'],
        'caveSize'   => floor($value[CAVE_SIZE_DB_FIELD] / 50) + 1,
        'secureCave' => $value['secureCave'],
      );

      if ($value['hasArtefact'] && ($playerDetails->tribe != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY)) {
        $temp['artefact'] = true;
      }
      if ($value['hasPet'] && ($playerDetails->tribe != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY)) {
        $temp['pet'] = true;
      }

      $caves[] = $temp;
    }

    $template->addVar('player_caves', $caves);
  } else {
    if (sizeof($ownCaves) < $_SESSION['player']->takeover_max_caves) {
      if ($cave['starting_position'] == 0 && $cave['takeoverable'] == 0 && $cave['takeover_level'] > 0) {
        $template->addVar('maybe_takeoverable', true);
      } else if ($cave['takeoverable'] == 1) {
        $template->addVar('takeoverable', true);
      }
    }
  }
}

?>