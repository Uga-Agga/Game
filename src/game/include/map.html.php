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



/** given the querried coordinates, calculates the minimal, maximal and center
  coordinates of the caves in the visible map region. Uses the constants MAP_WIDTH
  and MAP_HEIGHT from the configuration to determine size of the region. Also handles
  the case where the actual map in the database has fewer caves than specified in
  MAP_WIDTH and / or MAP_HEIGHT */
function calcVisibleMapRegion($mapSize, $xCoord, $yCoord) {
   // correct width und height for the case where the actual map is smaller than what could be displayed
   // in a map section.
  $MAP_WIDTH  = min(MAP_WIDTH,  $mapSize['maxX']-$mapSize['minX']+1); // attention: reads the constant MAP_WIDTH and writes the corrected value to a local variable of same name...
  $MAP_HEIGHT = min(MAP_HEIGHT, $mapSize['maxY']-$mapSize['minY']+1);

  // Nun befinden sich in $xCoord und $yCoord die gesuchten Koordinaten.
  // ermittele nun die linke obere Ecke des Bildausschnittes
  $minX = min(max($xCoord - intval($MAP_WIDTH/2),  $mapSize['minX']), $mapSize['maxX']-$MAP_WIDTH+1);
  $minY = min(max($yCoord - intval($MAP_HEIGHT/2), $mapSize['minY']), $mapSize['maxY']-$MAP_HEIGHT+1);

  // ermittele nun die rechte untere Ecke des Bildausschnittes
  $maxX = $minX + $MAP_WIDTH  - 1;
  $maxY = $minY + $MAP_HEIGHT - 1;

  $centerX = $minX+($maxX-$minX)/2;
  $centerY = $minY+($maxY-$minY)/2;

  return array(
    'minX'  => $minX, 'maxX' => $maxX,
    'minY'  => $minY, 'maxY' => $maxY,
    'width' => $maxX-$minX+1,
    'height' => $maxY-$minY+1,
    'centerX' => $centerX, 
    'centerY' => $centerY,
  );
}


function determineCoordsFromParameters($caveData, $mapSize) {
  // default Werte: Koordinaten of the given caveData (that is the data of the presently selected own cave)
  $xCoord  = $caveData['xCoord'];
  $yCoord  = $caveData['yCoord'];
  $message = '';

  // wenn in die Minimap geklickt wurde, zoome hinein
  if (($minimap_x = Request::getVar('minimap_x', 0)) && 
      ($minimap_y = Request::getVar('minimap_y', 0)) && 
      ($scaling   = Request::getVar('scaling', 0)) !== 0) {

    $xCoord = Floor($minimap_x * 100 / $scaling) + $mapSize['minX'];
    $yCoord = Floor($minimap_y * 100 / $scaling) + $mapSize['minY'];
  }

  // caveName eingegeben ?
  else if ($caveName = Request::getVar('caveName', '')) {
    $coords = getCaveByName($caveName);
    if (!$coords['xCoord']) {
      $message = sprintf(_('Die Höhle mit dem Namen: "%s" konnte nicht gefunden werden!'), $caveName);
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = sprintf(_('Die Höhle mit dem Namen: "%s" befindet sich in (%d|%d).'), $caveName, $xCoord, $yCoord);
    }
  }

  // caveID eingegeben ?
  else if (($targetCaveID = Request::getVar('targetCaveID', 0)) > 0) {
    $coords = getCaveByID($targetCaveID);
    if ($coords === null) {
      $message = sprintf(_('Die Höhle mit der ID: "%d" konnte nicht gefunden werden!'), $targetCaveID);       
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = sprintf(_('Die Höhle mit der ID: "%d" befindet sich in (%d|%d).'), $targetCaveID, $xCoord, $yCoord);
    }
  }

  // Koordinaten eingegeben ?
  else if (Request::getVar('xCoord', 0) && Request::getVar('yCoord', 0)) {
    $xCoord = Request::getVar('xCoord', 0);
    $yCoord = Request::getVar('yCoord', 0);
  }

  // Koordinaten begrenzen
  if ($xCoord < $mapSize['minX']) $xCoord = $mapSize['minX'];
  if ($yCoord < $mapSize['minY']) $yCoord = $mapSize['minY'];
  if ($xCoord > $mapSize['maxX']) $xCoord = $mapSize['maxX'];
  if ($yCoord > $mapSize['maxY']) $yCoord = $mapSize['maxY'];

  return array (
    'xCoord'  => $xCoord,
    'yCoord'  => $yCoord,
    'message' => $message
  );
}

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

  $section = calcVisibleMapRegion($mapSize, $xCoord, $yCoord);

  // Minimap
  $width  = $mapSize['maxX'] - $mapSize['minX'] + 1;
  $height = $mapSize['maxY'] - $mapSize['minY'] + 1;

  $template->addVars(array(
    'minimap' => array('file'    => "images/minimap.png.php?x=" . $xCoord . "&amp;y=" . $yCoord,
                       'modus'   => MAP,
                       'width'   => intval($width * MINIMAP_SCALING / 100),
                       'height'  => intval($height * MINIMAP_SCALING / 100),
                       'scaling' => MINIMAP_SCALING)));

  $template->addVars(array('E' => array('x' =>  (($section['centerX'] + $section['width']) < $maxX ? ($section['centerX'] + $section['width']) : $maxX),
                                         'y' =>    $section['centerY'])));

  $template->addVars(array('SE' => array('x' => (($section['centerX'] + $section['width']  < $maxX) ? ($section['centerX'] + $section['width']) : $maxX),
                                          'y' => (($section['centerY'] + $section['height'] < $maxY) ? ($section['centerY'] + $section['height']) : $maxY))));

  $template->addVars(array('S' => array('x' =>    $section['centerX'], 
                                         'y' =>  (($section['centerY'] + $section['height'] < $maxY) ? ($section['centerY'] + $section['height']) : $maxY))));

  $template->addVars(array('SW' => array('x' => (($section['centerX'] - $section['width']  > $minX) ? ($section['centerX'] - $section['width']) : $minX),
                                          'y' => (($section['centerY'] + $section['height'] < $maxY) ? ($section['centerY'] + $section['height']) : $maxY))));

  $template->addVars(array('W' => array('x' =>  (($section['centerX'] - $section['width']  > $minX) ? ($section['centerX'] - $section['width']) : $minX),
                                         'y' =>    $section['centerY'])));

  $template->addVars(array('NW' => array('x' => (($section['centerX'] - $section['width']  > $minX) ? ($section['centerX'] - $section['width']) : $minX), 
                                          'y' => (($section['centerY'] - $section['height'] > $minY) ? ($section['centerY'] - $section['height']) : $minY))));

  $template->addVars(array('N' => array('x' =>    $section['centerX'], 
                                         'y' =>  (($section['centerY'] - $section['height'] > $minY) ? ($section['centerY'] - $section['height']) : $minY))));

  $template->addVars(array('NE' => array('x' => (($section['centerX'] + $section['width']  < $maxX) ? ($section['centerX'] + $section['width']) : $maxX),
                                          'y' => (($section['centerY'] - $section['height'] > $minY) ? ($section['centerY'] - $section['height']) : $minY))));

  // Module "CaveBookmarks" Integration
  // FIXME should know whether the module is installed
  if (TRUE) {
    // get model
    $cb_model = new CaveBookmarks_Model();

    // get bookmarks
    $bookmarks = $cb_model->getCaveBookmarks(true);

    // set bookmarks
    if (sizeof($bookmarks)) {
      $template->addVars(array('caveBookmarks' => $bookmarks));
    }
  }

  $mapData = calcCaveMapRegionData($caveID, $caves, $xCoord, $yCoord);
  $template->addVars($mapData);

  $template->addVars(array( 'minimap' => array(
    'file_base' => "images/minimap.png.php",
    'file'      => "images/minimap.png.php?x=" . $xCoord . "&amp;y=" . $yCoord,
    'modus'     => MAP,
    'width'     => intval(($maxX-$minX+1) * MINIMAP_SCALING / 100),
    'height'    => intval(($maxY-$minY+1) * MINIMAP_SCALING / 100),
    'scaling'   => MINIMAP_SCALING
  )));
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
  $relations = relation_getRelationsForTribe($_SESSION['player']->tribe);

  $map = array();
  foreach ($caveDetails AS $cave) {

    $cell = array(
      'terrain'   => 'terrain'.$cave['terrain'],
      'imgMap'    => $GLOBALS['terrainList'][$cave['terrain']]['imgMap'],
      'barren'    => $GLOBALS['terrainList'][$cave['terrain']]['barren'],
      'title'     => 'Dies ist der Landstrich "' . $cave['cavename'] . '" (' . $cave['xCoord'] . '|' . $cave['yCoord'] . ') - ' . $cave['region'],
      'link'      => "modus=" . MAP_DETAIL . "&amp;targetCaveID={$cave['caveID']}",
    );

    // unbewohnte Höhle
    if ($cave['playerID'] == 0) {
      // als Frei! zeigen
      if ($cave['takeoverable'] == 1) {
        $text = _('Frei!');
        $file = "icon_cave_empty";
      // als Einöde zeigen
      } else {
        $text = _('Ein&ouml;de');
        $file = "icon_waste";
      }

    // bewohnte Höhle
    } else {

      // eigene Höhle
      if ($cave['playerID'] == $_SESSION['player']->playerID) {
        $file = "icon_cave_own";
      // fremde Höhle
      } else {
        $file = "icon_cave_other";
      }

      // mit Artefakt
      if ($cave['artefacts'] != 0 && ($cave['tribe'] != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY)) {
        $file .= "_artefact";
      }

      // link zum Tribe einfügen
      $cell['link_tribe'] = "modus=" . TRIBE_DETAIL . "&amp;tribe=".urlencode(unhtmlentities($cave['tribe']));

      // Stamm abkürzen
      $decodedTribe = unhtmlentities($cave['tribe']);
      if (strlen($decodedTribe) > 10) {
        $cell['text_tribe'] = htmlentities(substr($decodedTribe, 0, 8)) . "..";
      } else {
        $cell['text_tribe'] = $cave['tribe'];
      }

      // Besitzer
      $decodedOwner = unhtmlentities($cave['name']);
      if (strlen($decodedOwner) > 10)
        $text = htmlentities(substr($decodedOwner, 0, 8)) . "..";
      else
        $text = $cave['name'];

      // übernehmbare Höhlen können gekennzeichnet werden
      if ($cave['secureCave'] != 1) {
        $cell['unsecure'] = array('dummy' => '');
      }

      if ($_SESSION['player']->tribeID == $cave['tribeID']) {
        $cell['css_self'] = 't_self';
      }
      if (isset($relations['own'][strtoupper($cave['tribe'])])) {
        $cell['css_own'] = 't_own_relation_' . $relations['own'][strtoupper($cave['tribe'])]['relationType'];
      }
      if (isset($relations['other'][strtoupper($cave['tribe'])])) {
        $cell['css_other'] = 't_other_relation_' . $relations['other'][strtoupper($cave['tribe'])]['relationType'];
      }
    }

    $cell['file'] = $file;
    $cell['text'] = $text;

    // Wenn die Höhle ein Artefakt enthält und man berechtigt ist -> anzeigen
    if ($cave['artefacts'] != 0 && ($cave['tribe'] != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY)) {
      $cell['artefacts'] = $cave['artefacts'];
      $cell['artefacts_text'] = sprintf(_('Artefakte: %d'), $cave['artefacts']);
    }
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
  $playerDetails = array();
  $showArtePossible = false;

  if ($cave['playerID'] != 0) {
    $caveDetails   = getCaves($cave['playerID']);
    $playerDetails = getPlayerByID($cave['playerID']);

    $showArtePossible = ($playerDetails['tribe'] != GOD_ALLY) ? true : false;
  }

  $cave['terrain_name'] = $GLOBALS['terrainList'][$cave['terrain']]['name'];
  $cave['terrain_img'] = $GLOBALS['terrainList'][$cave['terrain']]['img'];
  $region = getRegionByID($cave['regionID']);

  if ($cave['artefacts'] != 0 && ($showArtePossible || $_SESSION['player']->tribe == GOD_ALLY)) {
    $cave['artefact'] = true;
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
        'caveName'     => $value['name'],
        'xCoord'       => $value['xCoord'],
        'yCoord'       => $value['yCoord'],
        'terrain'      => $GLOBALS['terrainList'][$value['terrain']]['name'],
        'caveSize'     => floor($value[CAVE_SIZE_DB_FIELD] / 50) + 1,
        'secureCave'   => $value['secureCave'],
      );

      if ($value['artefacts'] != 0 && ($playerDetails['tribe'] != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY)) {
        $temp['artefact'] = true;
      }

      $caves[] = $temp;
    }

    $template->addVar('player_caves', $caves);
  } else if (sizeof($ownCaves) < $_SESSION['player']->takeover_max_caves && $cave['takeoverable'] == 1) {
    $template->addVar('takeoverable', true);
  }
}

?>