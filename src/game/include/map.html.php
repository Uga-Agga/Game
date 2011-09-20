<?php
/*
 * map.html.php - 
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011  Sascha Lange <salange@uos.de>, David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

@include_once('modules/CaveBookmarks/model/CaveBookmarks.php');


function determineCoordsFromParameters($caveData, $mapSize) {
  
  // default Werte: Koordinaten of the given caveData (that is the data of the presently selected own cave)
  $xCoord  = $caveData['xCoord'];
  $yCoord  = $caveData['yCoord'];
  $message = '';

  // wenn in die Minimap geklickt wurde, zoome hinein
  if (($minimap_x = request_var('minimap_x', 0)) && 
      ($minimap_y = request_var('minimap_y', 0)) && 
      ($scaling   = request_var('scaling', 0)) !== 0) {
        
    $xCoord = Floor($minimap_x * 100 / $scaling) + $mapSize['minX'];
    $yCoord = Floor($minimap_y * 100 / $scaling) + $mapSize['minY'];
  }

  // caveName eingegeben ?
  else if ($caveName = request_var('caveName', "")) {
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
  else if ($targetCaveID = request_var('targetCaveID', 0)) {
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
  else if (request_var('xCoord', 0) && request_var('yCoord', 0)) {
    $xCoord = request_var('xCoord', 0);
    $yCoord = request_var('yCoord', 0);
  }
  
  // Koordinaten begrenzen
  if ($xCoord < $mapSize['minX']) $xCoord = $mapSize['minX'];
  if ($yCoord < $mapSize['minY']) $yCoord = $mapSize['minY'];
  if ($xCoord > $mapSize['maxX']) $xCoord = $mapSize['maxX'];
  if ($yCoord > $mapSize['maxY']) $yCoord = $mapSize['maxY'];
  
  return array (
    'xCoord'  => $xCoord,
    'yCoord'  => $yCoord,
    'message' => $message);
}



/** creates the map-page with header and the specified map region */
function getCaveMapContent($caveID, $caves) {

  global $config, $terrainList, $template;

  $caveData = $caves[$caveID];
  $mapSize = getMapSize();  // Größe der Karte wird benötigt
  $message  = '';

  // template öffnen
  $template->setFile('map.tmpl');

  // Grundparameter setzen
  $template->addVars(array(
    'modus'         => MAP,
    'mapRegionLink' => MAP_REGION,
    'caveID'        => $caveID
    ));

  $resolvedCoords = determineCoordsFromParameters($caveData, $mapSize);
  $template->addVars($resolvedCoords);
  
  $xCoord = $resolvedCoords['xCoord'];
  $yCoord = $resolvedCoords['yCoord'];  
      
  // Minimap
  $width  = $mapSize['maxX'] - $mapSize['minX'] + 1;
  $height = $mapSize['maxY'] - $mapSize['minY'] + 1;
  
  // compute mapcenter coords
  $mcX = $minX + intval($MAP_WIDTH/2);
  $mcY = $minY + intval($MAP_HEIGHT/2);

  $template->addVars(array(
    'minimap' => array('file'    => "images/minimap.png.php?x=" . $xCoord . "&amp;y=" . $yCoord,
                       'modus'   => MAP,
                       'width'   => intval($width * MINIMAP_SCALING / 100),
                       'height'  => intval($height * MINIMAP_SCALING / 100),
                       'scaling' => MINIMAP_SCALING)));

  $MAP_WIDTH_NEG = intval($MAP_WIDTH/2) * -1;
  $MAP_HEIGHT_NEG = intval($MAP_HEIGHT/2) * -1;


  $template->addVars(array('E' => array('x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['maxX']),
                                        'y' =>  $mcY)));

  $template->addVars(array('SE' => array('x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['maxX']),
                                         'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_WIDTH/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['maxY']))));

  $template->addVars(array('S' => array('x' =>  $mcX, 
                                        'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_HEIGHT/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['maxY']))));

  $template->addVars(array('SW' => array('x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['minX']),
                                         'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_WIDTH/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['maxY']))));

  $template->addVars(array('W' => array('x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['minX']),
                                        'y' =>  $mcY)));

  $template->addVars(array('NW' => array('x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['minX']), 
                                         'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['minY']))));

  $template->addVars(array('N' => array('x' =>  $mcX, 
                                        'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['minY']))));

  $template->addVars(array('NE' => array('x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['maxX']),
                                         'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['minY']))));



  // Module "CaveBookmarks" Integration
  // FIXME should know whether the module is installed
  if (TRUE) {

    // get model
    $cb_model = new CaveBookmarks_Model();
    
    // get bookmarks
    $bookmarks = $cb_model->getCaveBookmarks(true);
    
    // set bookmarks
    if (sizeof($bookmarks))
      $template->addVars(array( caveBookmarks => $bookmarks));
  }
  
  $mapData = calcCaveMapRegionData($caveID, $caves, $xCoord, $yCoord);
  $template->addVars($mapData);
}


/** calculates the displayed data for a specific map region. */
function calcCaveMapRegionData($caveID, $caves, $xCoord, $yCoord) {
  
  global $config, $terrainList;

  $caveData = $caves[$caveID];
  $mapSize = getMapSize();  // Größe der Karte wird benötigt
  $message  = '';
      
  // width und height anpassen
  $MAP_WIDTH  = min(MAP_WIDTH,  $mapSize['maxX']-$mapSize['minX']+1);
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
  
  // get the map details
  $caveDetails = getCaveDetailsByCoords($minX, $minY, $maxX, $maxY);

  $map = array();
  foreach ($caveDetails AS $cave) {

    $cell = array('terrain'   => 'terrain'.$cave['terrain'],
                  'alt'       => "{$cave['cavename']} - ({$cave['xCoord']}|{$cave['yCoord']}) - {$cave['region']}",
                  'link'      => "modus=map_detail&amp;targetCaveID={$cave['caveID']}");

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
      if ($cave['playerID'] == $_SESSION['player']->playerID)
        $file = "icon_cave_own";
      // fremde Höhle
      else
        $file = "icon_cave_other";

      // mit Artefakt
      if ($cave['artefacts'] != 0 && ($cave['tribe'] != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY))
        $file .= "_artefact";


      // link zum Tribe einfügen
      $cell['link_tribe'] = "modus=tribe_detail&amp;tribe=".urlencode(unhtmlentities($cave['tribe']));

      // Stamm abkürzen
      $decodedTribe = unhtmlentities($cave['tribe']);
      if (strlen($decodedTribe) > 10)
        $cell['text_tribe'] = htmlentities(substr($decodedTribe, 0, 8)) . "..";
      else
        $cell['text_tribe'] = $cave['tribe'];

      // Besitzer
      $decodedOwner = unhtmlentities($cave['name']);
      if (strlen($decodedOwner) > 10)
        $text = htmlentities(substr($decodedOwner, 0, 8)) . "..";
      else
        $text = $cave['name'];

      // übernehmbare Höhlen können gekennzeichnet werden
      if ($cave['secureCave'] != 1)
        $cell['unsecure'] = array('dummy' => '');
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
  $regionData = array( 'rows' => array());  

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
    'mapregion' => $regionData);

  return $mapData;
  
    // TODO: this functionality has to be added again! may be done in JS.
/*
  
  // Minimap
  $width  = $mapSize['maxX'] - $mapSize['minX'] + 1;
  $height = $mapSize['maxY'] - $mapSize['minY'] + 1;
  
  // compute mapcenter coords
  $mcX = $minX + intval($MAP_WIDTH/2);
  $mcY = $minY + intval($MAP_HEIGHT/2);

  $template->addVar("/MINIMAP", array('file'    => "images/minimap.png.php?x=" . $xCoord . "&amp;y=" . $yCoord,
                                        'modus'   => MAP,
                                        'width'   => intval($width * MINIMAP_SCALING / 100),
                                        'height'  => intval($height * MINIMAP_SCALING / 100),
                                        'scaling' => MINIMAP_SCALING));
*/
  
  
}


/** fills the map-region data into the thin, header-less template. 
 This is used as response to Ajax calls. */
function getCaveMapRegionContent($caveID, $caves) {

  global $config, $terrainList, $template;

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



function getCaveReport($caveID, $ownCaves, $targetCaveID) {
  global $terrainList, $template;

  if (!$targetCaveID) {
    $template->throwError('Es wurde keine Höhle ausgewählt.');
    return;
  }

  // open template
  $template->setFile('mapDetail.tmpl');

  $cave  = getCaveByID($targetCaveID);

  $caveDetails   = array();
  $playerDetails = array();
  if ($cave['playerID'] != 0) {
    $caveDetails   = getCaves($cave['playerID']);
    $playerDetails = getPlayerByID($cave['playerID']);
  }

/*
  if ($cave['protected']) tmpl_set($template, 'PROPERTY', _('Anf&auml;ngerschutz aktiv'));

  if (!$cave['secureCave'] && $cave['playerID']){
    tmpl_iterate($template, 'PROPERTY');
    tmpl_set($template, 'PROPERTY', _('&uuml;bernehmbar'));
  }
*/
  $cave['terrain'] = $terrainList[$cave['terrain']]['name'];
  $region = getRegionByID($cave['regionID']);

  $template->addVar('cave_details', $cave);
  /*
                            'backlink'     => sprintf("?modus=map&amp;xCoord=%d&amp;yCoord=%d",
                                                      $cave['xCoord'], $cave['yCoord'])));
*/

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
        'terrain'      => $terrainList[$value['terrain']]['name'],
        'caveSize'     => floor($value[CAVE_SIZE_DB_FIELD] / 50) + 1,
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