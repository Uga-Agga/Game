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
    if (sizeof($coords) == 0) {
      $message = sprintf(_('Die H&ouml;hle mit dem Namen: "%s" konnte nicht gefunden werden!'), $caveName);
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = sprintf(_('Die H&ouml;hle mit dem Namen: "%s" befindet sich in (%d|%d).'), $caveName, $xCoord, $yCoord);
    }
  }

  // caveID eingegeben ?
  else if ($targetCaveID = request_var('targetCaveID', 0)) {
    $coords = getCaveByID($targetCaveID);
    if ($coords === null) {
      $message = sprintf(_('Die H&ouml;hle mit der ID: "%d" konnte nicht gefunden werden!'), $targetCaveID);       
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = sprintf(_('Die H&ouml;hle mit der ID: "%d" befindet sich in (%d|%d).'), $targetCaveID, $xCoord, $yCoord);
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

  // Karte mit Beschriftungen ausgeben
/*
  // über alle Zeilen
  for ($j = $minY - 1; $j <= $maxY + 1; ++$j) {
    tmpl_iterate($template, '/ROWS');
    // über alle Spalten
    for ($i = $minX - 1; $i <= $maxX + 1; ++$i ) {
      tmpl_iterate($template, '/ROWS/CELLS');

      // leere Zellen
      if (($j == $minY - 1 || $j == $maxY + 1) && 
          ($i == $minX - 1 || $i == $maxX + 1)) {
        tmpl_set($template, "/ROWS/CELLS", getCornerCell());
      
      // x-Beschriftung
      } else if ($j == $minY - 1 || $j == $maxY + 1) {
        tmpl_set($template, "/ROWS/CELLS", getLegendCell('x', $i));
      
      // y-Beschriftung
      } else if ($i == $minX - 1 || $i == $maxX + 1) {
        tmpl_set($template, "/ROWS/CELLS", getLegendCell('y', $j));
      
      // Kartenzelle
      } else {
        tmpl_set($template, "/ROWS/CELLS", getMapCell($map, $i, $j));
      }
    }
  } */
  
  // Minimap
  $width  = $mapSize['maxX'] - $mapSize['minX'] + 1;
  $height = $mapSize['maxY'] - $mapSize['minY'] + 1;
  
  // compute mapcenter coords
  $mcX = $minX + intval($MAP_WIDTH/2);
  $mcY = $minY + intval($MAP_HEIGHT/2);

/* $template->addVar("/MINIMAP", array('file'    => "images/minimap.png.php?x=" . $xCoord . "&amp;y=" . $yCoord,
                                        'modus'   => MAP,
                                        'width'   => intval($width * MINIMAP_SCALING / 100),
                                        'height'  => intval($height * MINIMAP_SCALING / 100),
                                        'scaling' => MINIMAP_SCALING));
*/
  $MAP_WIDTH_NEG = intval($MAP_WIDTH/2) * -1;
  $MAP_HEIGHT_NEG = intval($MAP_HEIGHT/2) * -1;

/*
  tmpl_set($template, '/O',  array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['minX']),
                                                                         'y' =>  $mcY));

  tmpl_set($template, '/SO', array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['minX']),
                                                                         'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_WIDTH/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['minY'])));

  tmpl_set($template, '/S',  array('modus' => MAP, 'caveID' => $caveID, 'x' =>  $mcX, 
                                                                         'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_WIDTH/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['minY'])));

  tmpl_set($template, '/SW', array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['maxX']),
                                                                         'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_WIDTH/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['minY'])));

  tmpl_set($template, '/W',  array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['maxX']),
                                                                         'y' =>  $mcY));

  tmpl_set($template, '/NW', array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['maxX']), 
                                                                         'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['maxY'])));

  tmpl_set($template, '/N',  array('modus' => MAP, 'caveID' => $caveID, 'x' =>  $mcX, 
                                                                         'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['maxY'])));

  tmpl_set($template, '/NO', array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['minX']),
       
                                                                         'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['maxY'])));
*/
/*
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
    if (sizeof($bookmarks))
      tmpl_set($template, '/CAVEBOOKMARKS/CAVEBOOKMARK', $bookmarks);
  }*/

}


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
  
  $template->addVars(array(
    'centerXCoord' => $centerX,
    'centerYCoord' => $centerY
  ));

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
  
  $template->addVars(array('mapregion' => $regionData));
  
  // Minimap
  $width  = $mapSize['maxX'] - $mapSize['minX'] + 1;
  $height = $mapSize['maxY'] - $mapSize['minY'] + 1;
  
  // compute mapcenter coords
  $mcX = $minX + intval($MAP_WIDTH/2);
  $mcY = $minY + intval($MAP_HEIGHT/2);

/* $template->addVar("/MINIMAP", array('file'    => "images/minimap.png.php?x=" . $xCoord . "&amp;y=" . $yCoord,
                                        'modus'   => MAP,
                                        'width'   => intval($width * MINIMAP_SCALING / 100),
                                        'height'  => intval($height * MINIMAP_SCALING / 100),
                                        'scaling' => MINIMAP_SCALING));
*/
  $MAP_WIDTH_NEG = intval($MAP_WIDTH/2) * -1;
  $MAP_HEIGHT_NEG = intval($MAP_HEIGHT/2) * -1;

/*
  tmpl_set($template, '/O',  array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['minX']),
                                                                         'y' =>  $mcY));

  tmpl_set($template, '/SO', array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['minX']),
                                                                         'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_WIDTH/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['minY'])));

  tmpl_set($template, '/S',  array('modus' => MAP, 'caveID' => $caveID, 'x' =>  $mcX, 
                                                                         'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_WIDTH/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['minY'])));

  tmpl_set($template, '/SW', array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['maxX']),
                                                                         'y' => ((($mcY + $MAP_HEIGHT) < ($mapSize['maxY'] + intval($MAP_WIDTH/2))) ? ($mcY + $MAP_HEIGHT) : $mapSize['minY'])));

  tmpl_set($template, '/W',  array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['maxX']),
                                                                         'y' =>  $mcY));

  tmpl_set($template, '/NW', array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX - $MAP_WIDTH) > $MAP_WIDTH_NEG) ? ($mcX - $MAP_WIDTH) : $mapSize['maxX']), 
                                                                         'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['maxY'])));

  tmpl_set($template, '/N',  array('modus' => MAP, 'caveID' => $caveID, 'x' =>  $mcX, 
                                                                         'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['maxY'])));

  tmpl_set($template, '/NO', array('modus' => MAP, 'caveID' => $caveID, 'x' => ((($mcX + $MAP_WIDTH) < ($mapSize['maxX'] + intval($MAP_WIDTH/2))) ? ($mcX + $MAP_WIDTH) : $mapSize['minX']),
       
                                                                         'y' => ((($mcY - $MAP_HEIGHT) > $MAP_HEIGHT_NEG) ? ($mcY - $MAP_HEIGHT) : $mapSize['maxY'])));
*/
/*
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
    if (sizeof($bookmarks))
      tmpl_set($template, '/CAVEBOOKMARKS/CAVEBOOKMARK', $bookmarks);
  }*/

}

function getCaveReport($caveID, $ownCaves, $targetCaveID) {
  global $terrainList;

  $cave  = getCaveByID($targetCaveID);

  $caveDetails   = array();
  $playerDetails = array();
  if ($cave['playerID'] != 0) {
    $caveDetails   = getCaves($cave['playerID']);
    $playerDetails = getPlayerByID($cave['playerID']);
  }

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'mapdetail.ihtml');

  if ($cave['protected']) tmpl_set($template, 'PROPERTY', _('Anf&auml;ngerschutz aktiv'));

  if (!$cave['secureCave'] && $cave['playerID']){
    tmpl_iterate($template, 'PROPERTY');
    tmpl_set($template, 'PROPERTY', _('&uuml;bernehmbar'));
  }

  $region = getRegionByID($cave['regionID']);

  $template->addVar(array('cavename'     => $cave['name'],
                            'xcoord'       => $cave['xCoord'],
                            'ycoord'       => $cave['yCoord'],
                            'terrain'      => $terrainList[$cave['terrain']]['name'],
                            'region'       => $region['name'],
                            'movementlink' => sprintf("?modus=unit_movement&amp;targetXCoord=%d&amp;targetYCoord=%d&amp;targetCaveName=%s",
                                                      $cave['xCoord'], $cave['yCoord'], unhtmlentities($cave['name'])),
                            'backlink'     => sprintf("?modus=map&amp;xCoord=%d&amp;yCoord=%d",
                                                      $cave['xCoord'], $cave['yCoord'])));
  if ($cave['playerID'] != 0){

    $template->addVar('/OCCUPIED', array('playerLink'  => "?modus=player_detail&amp;detailID=" . $playerDetails['playerID'],
                                           'caveOwner'   => $playerDetails['name']));

    if ($playerDetails['tribe']){
      tmpl_set($template, '/OCCUPIED/TRIBE', array(
        'tribeLink'   => "?modus=tribe_detail&amp;tribe=".urlencode(unhtmlentities($playerDetails['tribe'])),
        'ownersTribe' => $playerDetails['tribe']));
    }
    if ($cave['artefacts'] != 0 &&
        ($playerDetails['tribe'] != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY)) {
      tmpl_set($template, '/OCCUPIED/ARTEFACT/artefactLink', "?modus=artefact_list&amp;caveID={$caveID}");
    }

    $caves = array();
    foreach ($caveDetails AS $key => $value) {
      $temp = array('caveName'     => $value['name'],
                    'xCoord'       => $value['xCoord'],
                    'ycoord'       => $value['yCoord'],
                    'terrain'      => $terrainList[$value['terrain']]['name'],
                    'caveSize'     => floor($value[CAVE_SIZE_DB_FIELD] / 50) + 1,
                    'movementLink' => "?modus=unit_movement&amp;targetXCoord=" . $value['xCoord'] .
                                      "&amp;targetYCoord=" . $value['yCoord'] .
                                      "&amp;targetCaveName=" . unhtmlentities($value['name']));

      if ($value['artefacts'] != 0 && ($playerDetails['tribe'] != GOD_ALLY || $_SESSION['player']->tribe == GOD_ALLY))
        $temp['ARTEFACT'] = array('artefactLink' => "?modus=artefact_list&amp;caveID={$caveID}");

      if ($value['protected'] && $value['playerID'])
        $temp['PROPERTY'] = array('text' => _('Anf&auml;ngerschutz aktiv'));
      else if (!$value['secureCave'])
        $temp['PROPERTY'] = array('text' => _('&uuml;bernehmbar'));

      $caves[] = $temp;
    }
    $template->addVar('/OCCUPIED/CAVES', $caves);

  } else if (sizeof($ownCaves) < $_SESSION['player']->takeover_max_caves && $cave['takeoverable'] == 1){

    $template->addVar(
             array('modus'        => TAKEOVER,
                   'caveID'       => $caveID,
                   'targetXCoord' => $cave['xCoord'],
                   'targetYCoord' => $cave['yCoord']));
  }

}

?>