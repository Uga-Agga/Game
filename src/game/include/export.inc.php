<?php
/*
 * export.inc.php -
 * Copyright (c) 2004  Georg Pitterle
 * Copyright (c) 2011  Sascha Lange <salange@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');


/* 
 * to implement in html as
 *  <a href="export.php?modus=movement&amp;movementID={eventID}&amp;format=xml" onclick="return popitup(this.href)"><img src="http://www.taxitransfers.co.uk/images/XML_Icon.jpg" class="noborder" width="20" height="20" alt="Export als XML" /></a><br />
 *  <a href="export.php?modus=movement&amp;movementID={eventID}&amp;format=bb" onclick="return popitup(this.href)"><img src="http://www.mil.ufl.edu/~nechyba/__eel6825.f2003/icons/text_icon.jpg" class="noborder" width="20" height="20" alt="Export als Text" /></a><br />
 *  <a href="export.php?modus=movement&amp;movementID={eventID}&amp;format=irc" onclick="return popitup(this.href)"><img src="http://www.clipart.clipartist.net/openclipart/clipart/computer/icons/flat-theme/applications/irc_protocol_l.png" class="noborder" width="20" height="20" alt="Export als IRC" /></a>
 *
 */

/*
 *  export_switch() - returns formatted xml/irc/bb code
 */
function export_switch() {
  $modus = Request::getVar('modus', '');
  $format = Request::getVar('format', 'text');
  
  switch ($modus) {
    case 'allCaves':
      switch ($format) {
        case 'xml':
          return export_allCaves_xml();
          break;
          
          
        default:
          echo "Unbekanntes Format für Export! (" . $format .")" ;
          break;
      }
      break;
      
    case 'movement':
      switch ($format) {
        case 'xml':
          return export_movement_xml(Request::getVar('movementID', 0));
          break;
          
        case 'bb':
          return export_movement_bb(Request::getVar('movementID', 0));
          break;
         
        case 'irc':
          return export_movement_irc(Request::getVar('movementID', 0));
          break;
          
        default:
          return "Unbekanntes Format für Export! (" . $format .")" ;
          break;
      }
      break;

    case 'thisCave':
      switch ($format) {
        case 'xml':
          return export_thisCave_xml(Request::getVar('caveID', 0));
          break;
          
          
        default:
          return "Unbekanntes Format für Export! (" . $format .")" ;
          break;
      }
      break;
      
    case 'sciences':
      switch ($format) {
        case 'xml':
          return export_sciences_xml();
          break;
          
        case 'bb':
          return export_sciences_bb();
          break;
          
        default:
          return "Unbekanntes Format für Export! (" . $format .")";
      }
      break;
    
    case 'buildings':
      switch ($format) {
        case 'xml':
          return export_buildings_xml(Request::getVar('caveID', 0));
          break;

        case 'bb':
          return export_buildings_bb(Request::getVar('caveID', 0));
          break;
          
        default:
          return "Unbekanntes Format für Export! (" . $format .")";
          break;
      }
      break;
      
    case 'messages':
      switch ($format) {
        case 'xml':
          return export_messages_xml(Request::getVar('messageID', 0));
          break;
          
        default: 
          return "Unbekanntes Format für Export (" . $format .")";
      }
      break;

      
    default:
      return "Unbekannter Modus für Export!";
      break;
  }
}


/*
 *  export_allCaves_xml() - returns formatted xml-code for all caves 
 */
function export_allCaves_xml() {
  global $db;
  
  $caves = array();
  
  $sql = $db->prepare("SELECT * 
                       FROM ". CAVE_TABLE ."
                       WHERE playerID = :playerID 
                       ORDER BY name ASC");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  if ($sql->execute()) {
    while($row = $sql->fetch(PDO::FETCH_ASSOC))
      $caves_data[$row['caveID']] = $row;
  } else return 'Datenbankfehler!';
  

  $xml = new mySimpleXML("<?xml version='1.0' encoding='utf-8'?><allCaves></allCaves>");
  $allCaves = $xml;
  $header = $allCaves->addChild('header');
  $header->addChild('playerName', $_SESSION['player']->name);
  
  $caves = $allCaves->addChild('caves');
  foreach ($caves_data AS $caveID => $caveDetails) {
    $cave = $caves->addChild('cave');
    $cave->addChild('id', $caveDetails['caveID']);
    $cave->addChild('caveName', $caveDetails['name']);
    $cave->addChild('xCoord', $caveDetails['xCoord']);
    $cave->addChild('yCoord', $caveDetails['yCoord']);

    // Ressources
    $resources = $cave->addChild('resources');
    foreach ($GLOBALS['resourceTypeList'] AS $resourceID => $resourceDetail) {
      if ($caveDetails[$resourceDetail->dbFieldName] > 0) {
        $resource = $resources->addChild('resource');
        $resource->addChild('id', $resourceDetail->resourceID);
        $resource->addChild('resourceName', $resourceDetail->name);
        $resource->addChild('value', $caveDetails[$resourceDetail->dbFieldName]);
        $resource->addChild('delta', $caveDetails[$resourceDetail->dbFieldName ."_delta"]);
      }
    }
    
    // Units
    $units = $cave->addChild('units');
    foreach ($GLOBALS['unitTypeList'] AS $unitsID => $unitDetail) {
      if ($caveDetails[$unitDetail->dbFieldName] > 0) {
        $unit = $units->addChild('unit');
        $unit->addChild('id', $unitDetail->unitID);
        $unit->addChild('unitName', $unitDetail->name);
        $unit->addChild('value', $caveDetails[$unitDetail->dbFieldName]);
      }
    }
    
    // Buildings
    $buildings = $cave->addChild('buildings');
    foreach ($GLOBALS['buildingTypeList'] AS $buildingID => $buildingDetail) {
      if ($caveDetails[$buildingDetail->dbFieldName] > 0) {
        $building = $buildings->addChild('building');
        $building->addChild('id', $buildingDetail->buildingID);
        $building->addChild('buildingName', $buildingDetail->name);
        $building->addChild('value', $caveDetails[$buildingDetail->dbFieldName]);
      }
    }
    
    // Defense Systems
    $defenseSystems = $cave->addChild('defenseSystems');
    foreach ($GLOBALS['defenseSystemTypeList'] AS $defenseSystemID => $defenseSystemDetail) {
      if ($caveDetails[$defenseSystemDetail->dbFieldName] > 0) {
        $defenseSystem = $defenseSystems->addChild('defenseSystem');
        $defenseSystem->addChild('id', $defenseSystemDetail->defenseSystemID);
        $defenseSystem->addChild('defenseSystemName', $defenseSystemDetail->name);
        $defenseSystem->addChild('value', $caveDetails[$defenseSystemDetail->dbFieldName]);
      }
    }   
  }
  
  return $xml->asPrettyXML();
}


/*
 *  export_thisCave_xml() - returns formatted xml-code for a single caves 
 */
function export_thisCave_xml($caveID) {
  global $db;
  
  $caves=array();
  
  $sql = $db->prepare("SELECT * FROM ". CAVE_TABLE ." ".
                     "WHERE playerID = :playerID " .
                     "AND caveID = :caveID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if ($sql->execute()) {
    $row = $sql->fetch(PDO::FETCH_ASSOC);
  } else return 'Datenbankfehler!';

  $xml = new mySimpleXML("<?xml version='1.0' encoding='utf-8'?><thisCave></thisCave>");
  $cave = $xml;
  $header = $cave->addChild('header');
  $header->addChild('playerName', $_SESSION['player']->name);

  $cave->addChild('caveID', $row['caveID']);
  $cave->addChild('caveName', $row['name']);
  $cave->addChild('xCoord', $row['xCoord']);
  $cave->addChild('yCoord', $row['yCoord']);

  // Ressources
  $resources = $cave->addChild('resources');
  foreach ($GLOBALS['resourceTypeList'] AS $resourceID => $resourceDetail) {
    if ($row[$resourceDetail->dbFieldName] > 0) {
      $resource = $resources->addChild('resource');
      $resource->addChild('id', $resourceDetail->resourceID);
      $resource->addChild('resourceName', $resourceDetail->name);
      $resource->addChild('value', $row[$resourceDetail->dbFieldName]);
      $resource->addChild('delta', $row[$resourceDetail->dbFieldName ."_delta"]);
    }
  }

  // Units
  $units = $cave->addChild('units');
  foreach ($GLOBALS['unitTypeList'] AS $unitsID => $unitDetail) {
    if ($row[$unitDetail->dbFieldName] > 0) {
      $unit = $units->addChild('unit');
      $unit->addChild('id', $unitDetail->unitID);
      $unit->addChild('unitName', $unitDetail->name);
      $unit->addChild('value', $row[$unitDetail->dbFieldName]);
    }
  }

  // Buildings
  $buildings = $cave->addChild('buildings');
  foreach ($GLOBALS['buildingTypeList'] AS $buildingID => $buildingDetail) {
    if ($row[$buildingDetail->dbFieldName] > 0) {
      $building = $buildings->addChild('building');
      $building->addChild('id', $buildingDetail->buildingID);
      $building->addChild('buildingName', $buildingDetail->name);
      $building->addChild('value', $row[$buildingDetail->dbFieldName]);
    }
  }

  // Defense Systems
  $defenseSystems = $cave->addChild('defenseSystems');
  foreach ($GLOBALS['defenseSystemTypeList'] AS $defenseSystemID => $defenseSystemDetail) {
    if ($row[$defenseSystemDetail->dbFieldName] > 0) {
      $defenseSystem = $defenseSystems->addChild('defenseSystem');
      $defenseSystem->addChild('id', $defenseSystemDetail->defenseSystemID);
      $defenseSystem->addChild('defenseSystemName', $defenseSystemDetail->name);
      $defenseSystem->addChild('value', $row[$defenseSystemDetail->dbFieldName]);
    }
  }

  return $xml->asPrettyXML();
}

/*
 * export_movement_xml($movementID) - returns formatted xml-code of movement
 */

function export_movement_xml($movementID) {
  global $db;

  require_once('lib/Movement.php');
  require_once('include/digest.inc.php');

  // gather data
  $ua_movements = Movement::getMovements();

  $move = export_getSingleMovement($movementID);
  if (!sizeof($move)) {
    return 'Es wurde keine Bewegung gefunden!';
  }

  // get Cave data
  $sourceCaveData = getCaveNameAndOwnerByCaveID($move['source_caveID']);
  $targetCaveData = getCaveNameAndOwnerByCaveID($move['target_caveID']);
  
  // check if it's a player related movement
  if (($sourceCaveData['player_name'] !== $_SESSION['player']->name) && ($targetCaveData['player_name'] !== $_SESSION['player']->name)) {
    return 'Nur eigene Bewegungen erlaubt!';
  }

  // artefact data
  $artefactData = array();
  if ($move['artefactID'] != 0) {
    $artefactData = artefact_getArtefactByID($move['artefactID']);
  }

  // form xml-object
  $xml = new mySimpleXML("<?xml version='1.0' encoding='utf-8'?><movement></movement>");

  $movement = $xml;

  $source = $movement->addChild('source');
  $source->addChild('sourcePlayerName', $sourceCaveData['player_name']);
  $source->addChild('sourcePlayerTribe', $sourceCaveData['player_tribe']);
  $source->addChild('sourceCaveName', $sourceCaveData['cave_name']);
  $source->addChild('source_xCoord', $sourceCaveData['xCoord']);
  $source->addChild('source_yCoord', $sourceCaveData['yCoord']);

  $target = $movement->addChild('target');
  $target->addChild('targetPlayerName', $targetCaveData['player_name']);
  $target->addChild('targetPlayerTribe', $targetCaveData['player_tribe']);
  $target->addChild('targetCaveName', $targetCaveData['cave_name']);
  $target->addChild('target_xCoord', $targetCaveData['xCoord']);
  $target->addChild('target_yCoord', $targetCaveData['yCoord']);
  
  $movement->addChild('movementType', $ua_movements[$move['movementID']]->description);
  $movement->addChild('movementStart', time_formatDatetime($move['start']));
  $movement->addChild('movementEnd', time_formatDatetime($move['end']));

  // Units
  $units = $movement->addChild('units');
  foreach ($GLOBALS['unitTypeList'] AS $unitsID => $unitDetail) {
     if ($move[$unitDetail->dbFieldName] > 0) {
       if ($move['isOwnMovement'] || $unitDetail->visible) {
         $unit = $units->addChild('unit');
         $unit->addAttribute('id', $unitDetail->unitID);
         $unit->addAttribute('unitName', $unitDetail->name);
         $unit->addChild('value',  (($ua_movements[$move['movementID']]->fogUnit && !$move['isOwnMovement']) ? calcFogUnit($move[$unitDetail->dbFieldName]) : $move[$unitDetail->dbFieldName]));
       }
     }
   }

  // Resources
  $resources = $movement->addChild('resources');
  foreach ($GLOBALS['resourceTypeList'] AS $resourceID => $resourceDetail) {
    if ($move[$resourceDetail->dbFieldName] > 0) {
      $resource = $resources->addChild('resource');
      $resource->addAttribute('id', $resourceDetail->resourceID);
      $resource->addAttribute('resourceName', $resourceDetail->name);
      $resource->addChild('value', (($ua_movements[$move['movementID']]->fogResource && !$move['isOwnMovement']) ? calcFogResource($move[$resourceDetail->dbFieldName]) : $move[$resourceDetail->dbFieldName]));
    }
  }

  // Artefact
  if ($move['artefactID'] != 0) {
    $artefact = $movement->addChild('artefact');
    $artefact->addChild('name', $artefactData['name']);
  }
  
  // Hero
  if ($move['heroID'] != 0) {
    $movement->addChild('hero', 'true');
  }

  return $xml->asPrettyXML();
}

/*
 * export_movement_bb - returns movements bb-code formatted
 */
function export_movement_bb ($movementID) {
  global $db;
  
  require_once('lib/Movement.php');
  require_once('include/digest.inc.php');
  
  // gather data
  $ua_movements = Movement::getMovements();
  
  $move = export_getSingleMovement($movementID);
  if (!sizeof($move)) {
    return 'Es wurde keine Bewegung gefunden!';
  }

  // get Cave data
  $sourceCaveData = getCaveNameAndOwnerByCaveID($move['source_caveID']);
  $targetCaveData = getCaveNameAndOwnerByCaveID($move['target_caveID']);

  // check if it's a player related movement
  if (($sourceCaveData['player_name'] !== $_SESSION['player']->name) && ($targetCaveData['player_name'] !== $_SESSION['player']->name)) {
    return 'Nur eigene Bewegungen erlaubt!';
  }

  // artefact data
  $artefactData = array();
  if ($move['artefactID'] != 0) {
    $artefactData = artefact_getArtefactByID($move['artefactID']);
  }

  // header
  $header = "Bewegungsart: " . $ua_movements[$move['movementID']]->description ."\n";
  $header .= "Startzeitpunkt: " . time_formatDatetime($move['start']) ."\n";
  $header .= "Endzeitpunkt: " . time_formatDatetime($move['end'])."\n";

  // movement source
  $source = "Starthöhle: " .  $sourceCaveData['cave_name'];
  $source .=" (". $sourceCaveData['xCoord'] ."|". $sourceCaveData['yCoord'] .") ";
  if ($sourceCaveData['player_name']) {
    $source .= "des Spielers " . $sourceCaveData['player_name']." ";
    $source .= "aus dem Stamme " .$sourceCaveData['player_tribe']." ";
  }
  $source .= "\n";

  // movement target
  $target = "Zielhöhle: " .  $targetCaveData['cave_name'];
  $target .=" (". $targetCaveData['xCoord'] ."|". $targetCaveData['yCoord'] .") ";
  if ($targetCaveData['player_name']) {
    $target .= "des Spielers " . $targetCaveData['player_name']." ";
    $target .= "aus dem Stamme " .$targetCaveData['player_tribe']." ";
  }
  $target .= "\n";

  // units
  $units = "Einheiten: \n";
  foreach ($GLOBALS['unitTypeList'] AS $unitsID => $unitDetail) {
    if ($move[$unitDetail->dbFieldName] > 0) {
      if ($move['isOwnMovement'] || $unitDetail->visible)
        $units .= $unitDetail->name .": " . (($ua_movements[$move['movementID']]->fogUnit && !$move['isOwnMovement']) ? calcFogUnit($move[$unitDetail->dbFieldName]) : $move[$unitDetail->dbFieldName]) . "\n";
      
    }
  }

  // resources
  $resources = "";
  foreach ($GLOBALS['resourceTypeList'] AS $resourceID => $resourceDetail) {
    if ($move[$resourceDetail->dbFieldName] > 0) 
      $resources .= $resourceDetail->name .": " . (($ua_movements[$move['movementID']]->fogResource && !$move['isOwnMovement']) ? calcFogResource($move[$resourceDetail->dbFieldName]) : $move[$resourceDetail->dbFieldName]) . "\n";
    }
  if ($resources !== "") 
    $resources = "transportierte Rohstoffe: \n" . $resources; 

  // Artefact
  $artefact = "";
  if ($move['artefactID'] != 0) {
    $artefact = "transportierte Artefakte: \n" . $artefactData['name'] . "\n";
  }
  
  // Hero
  $hero = "";
  if ($move['heroID'] != 0) {
    $hero = "Der Held läuft mit!";
  }

  $bb = "";
  $bb .= $header . "\n";
  $bb .= $source;
  $bb .= $target . "\n";
  $bb .= $units . "\n";
  $bb .= $resources ."\n";
  $bb .= $artefact . "\n";
  $bb .= $hero ."\n";

  return $bb;
}

/*
 * export_movement_irc - returns movements irc-code formatted
 */
function export_movement_irc ($movementID) {
  global $db;
  
  require_once('lib/Movement.php');
  require_once('include/digest.inc.php');

  // gather data
  $ua_movements = Movement::getMovements();

  $move = export_getSingleMovement($movementID);
  if (!sizeof($move)) {
    return 'Es wurde keine Bewegung gefunden!';
  }

  // get Cave data
  $sourceCaveData = getCaveNameAndOwnerByCaveID($move['source_caveID']);
  $targetCaveData = getCaveNameAndOwnerByCaveID($move['target_caveID']);
  
  // check if it's a player related movement
  if (($sourceCaveData['player_name'] !== $_SESSION['player']->name) && ($targetCaveData['player_name'] !== $_SESSION['player']->name)) {
    return 'Nur eigene Bewegungen erlaubt!';
  }

  // artefact data
  $artefactData = array();
  if ($move['artefactID'] != 0) {
    $artefactData = artefact_getArtefactByID($move['artefactID']);
  }

  // header
  $header = "Bewegungsart: 4" . $ua_movements[$move['movementID']]->description ."\n";
  $header .= "Startzeitpunkt:4 " . time_formatDatetime($move['start']) ."\n";
  $header .= "Endzeitpunkt:4 " .time_formatDatetime($move['end'])."\n";

  // movement source
  $source = "Starthöhle: 4" .  $sourceCaveData['cave_name'] ." ";
  $source .=" (". $sourceCaveData['xCoord'] ."|". $sourceCaveData['yCoord'] .") ";
  if ($sourceCaveData['player_name']) {
    $source .= "des Spielers  4" . $sourceCaveData['player_name']." ";
    $source .= "aus dem Stamme 4" .$sourceCaveData['player_tribe']." ";
  }
  $source .= "\n";

  // movement target
  $target = "Zielhöhle: 4" .  $targetCaveData['cave_name'];
  $target .=" (". $targetCaveData['xCoord'] ."|". $targetCaveData['yCoord'] .") ";
  if ($targetCaveData['player_name']) {
    $target .= "des Spielers 4 " . $targetCaveData['player_name']." ";
    $target .= "aus dem Stamme 4" .$targetCaveData['player_tribe']." ";
  }
  $target .= "\n";

  // units
  $units = "Einheiten: ";
  foreach ($GLOBALS['unitTypeList'] AS $unitsID => $unitDetail) {
    if ($move[$unitDetail->dbFieldName] > 0) {
      if ($move['isOwnMovement'] || $unitDetail->visible)
        $units .= $unitDetail->name .": " . (($ua_movements[$move['movementID']]->fogUnit && !$move['isOwnMovement']) ? calcFogUnit($move[$unitDetail->dbFieldName]) : $move[$unitDetail->dbFieldName]) . ", ";
    }
  }
  $units = substr($units, 0, -2);

  // resources
  $resources = "";
  foreach ($GLOBALS['resourceTypeList'] AS $resourceID => $resourceDetail) {
    if ($move[$resourceDetail->dbFieldName] > 0) 
      $resources .= $resourceDetail->name .": " . (($ua_movements[$move['movementID']]->fogResource && !$move['isOwnMovement']) ? calcFogResource($move[$resourceDetail->dbFieldName]) : $move[$resourceDetail->dbFieldName]) . ", ";
    }
  if ($resources !== "") {
    $resources = "transportierte Rohstoffe: " . $resources;
    $resources = substr($resources, 0, -2); 
  }

  // Artefacts
  $artefact = '';
  if ($move['artefactID'] != 0) {
    $artefact = "transportierte Artefakte: " . $artefactData['name'];
  }
  
  // Hero
  $hero = '';
  if ($move['heroID'] != 0) {
    $hero = "Held läuft mit!";
  }

  $irc = "";
  $irc .= $header;
  $irc .= $source;
  $irc .= $target . "\n";
  $irc .= $units . "\n";
  $irc .= $resources ."\n";
  $irc .= $artefact . "\n";
  $irc .= $hero . "\n";

  return $irc;
}

/*
 *  export_sciences_xml() - returns sciences of a player in formatted xml
 */

function export_sciences_xml() {
  global $db, $scienceTypeList;

  $sql = $db->prepare("SELECT * FROM ". PLAYER_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  if ($sql->execute()) {
      $player_data = $sql->fetch(PDO::FETCH_ASSOC);
  } else return 'Datenbankfehler!';

  $xml = new mySimpleXML("<?xml version='1.0' encoding='utf-8'?><sciences></sciences>");

  $header = $xml->addChild('header');
  $header->addChild('playerName', $_SESSION['player']->name);

  foreach ($scienceTypeList AS $scienceID => $scienceDetail){
    if ($player_data[$scienceDetail->dbFieldName]) {
      $science = $xml->addChild('science');
      $science->addChild('scienceName', $scienceDetail->name);
      $science->addChild('scienceID', $scienceDetail->scienceID);
      $science->addChild('value', $player_data[$scienceDetail->dbFieldName]);
    }   
  }

  return $xml->asPrettyXML();
}

/*
 *  export sciences_bb() - returns sciences of a player in formatted text
 */

function export_sciences_bb() {
  global $db, $scienceTypeList;

  $sql = $db->prepare("SELECT * FROM ". PLAYER_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  if ($sql->execute()) {
      $player_data = $sql->fetch(PDO::FETCH_ASSOC);
  } else return 'Datenbankfehler!';

  $header = "Forschungen des Spielers " . $_SESSION['player']->name;

  $science = "";
  foreach ($scienceTypeList AS $scienceID => $scienceDetail) {
    if ($player_data[$scienceDetail->dbFieldName]) {
      $science .= $scienceDetail->name . ": " . $player_data[$scienceDetail->dbFieldName] ."\n";
    }
  }

  $bb = "";
  $bb .= $header ."\n\n";
  $bb .= $science ."\n";

  return $bb;
}

/*
 * export buildings_xml - return buildings of a cave in formatted text
 */

function export_buildings_xml($caveID) {
  global $db;

  $sql = $db->prepare("SELECT * FROM ". CAVE_TABLE ." ".
                       "WHERE playerID = :playerID " .
                       "AND caveID = :caveID ");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if ($sql->execute())
      $row = $sql->fetch(PDO::FETCH_ASSOC);
  else return 'Datenbankfehler!';

  $xml = new mySimpleXML("<?xml version='1.0' encoding='utf-8'?><buildings></buildings>");
  $cave = $xml;

  $header = $xml->addChild('header');
  $header->addChild('playerName', $_SESSION['player']->name);

  $cave->addChild('caveID', $row['caveID']);
  $cave->addChild('caveName', $row['name']);
  $cave->addChild('xCoord', $row['xCoord']);
  $cave->addChild('yCoord', $row['yCoord']);

  // Buildings
  $buildings = $cave->addChild('buildings');
  foreach ($GLOBALS['buildingTypeList'] AS $buildingID => $buildingDetail) {
    if ($row[$buildingDetail->dbFieldName] > 0) {
      $building = $buildings->addChild('building');
      $building->addChild('id', $buildingDetail->buildingID);
      $building->addChild('buildingName', $buildingDetail->name);
      $building->addChild('value', $row[$buildingDetail->dbFieldName]);
    }
  }

  return $xml->asPrettyXML();
}

/*
 * export_buildings_bb() - returns formatted bb-code for the buildings of a cave
 */

function export_buildings_bb($caveID) {
  global $db;

  $sql = $db->prepare("SELECT * FROM ". CAVE_TABLE." ".
                       "WHERE playerID = :playerID ".
                       "AND caveID = :caveID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

  if ($sql->execute())
      $row = $sql->fetch(PDO::FETCH_ASSOC);
  else return 'Datenbankfehler!';

  $header = "Erweiterungen der H&ouml;hle " . $row['name'] . 
    " (". $row['xCoord'] ."|". $row['yCoord'] .") des Spielers " . 
    $_SESSION['player']->name;

  $building = "";
  foreach ($GLOBALS['buildingTypeList'] AS $buildingID => $buildingDetail) {
    if ($row[$buildingDetail->dbFieldName]) {
      $building .= $buildingDetail->name . ": " . $row[$buildingDetail->dbFieldName] ."\n";
    }
  }

  $bb = "";
  $bb .= $header ."\n\n";
  $bb .= $building ."\n";

  return $bb;
}

/*
 * export_messages_xml() - returns xml of messages
 */
function export_messages_xml($messageID) {
  global $db; 

  $sql = $db->prepare("SELECT messageID, senderID, recipientID, messageXML
                       FROM " . MESSAGE_TABLE . "
                       WHERE messageID = :messageID");
  $sql->bindValue('messageID', $messageID, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return "Datenbankfehler!";
  }

  $message_data = $sql->fetch(PDO::FETCH_ASSOC);

  if ($message_data['senderID'] != $_SESSION['player']->playerID && $message_data['recipientID'] != $_SESSION['player']->playerID) {
    return "Sie können nur auf eigene Nachrichten zugreifen!";
  }

  if (empty($message_data['messageXML'])) {
    return "Es konnte keine Nachricht geladen werden.";
  }

  $xml = simplexml_load_string($message_data['messageXML'], 'mySimpleXML');

  return $xml->asPrettyXML();
}

########################################################
###  common functions                   ################
########################################################

function export_getSingleMovement($movementID) {
  global $db;

  $sql = $db->prepare("SELECT * FROM ". EVENT_MOVEMENT_TABLE ." WHERE event_movementID = :movementID");
  $sql->bindValue('movementID', $movementID, PDO::PARAM_INT);

  if ($sql->execute()) {
    $move = $sql->fetch(PDO::FETCH_ASSOC);
  } else {
    return array();
  }
  $sql->closeCursor();

  if (!sizeof($move) || empty($move)) {
    return array();
  }

  // check if it's own movement
  $meineHoehlen = getCaves($_SESSION['player']->playerID);

  $move['isOwnMovement'] = in_array($move['caveID'], array_keys($meineHoehlen));

  return $move;
}

function XML2Array($xml) {
  if ($xml instanceof SimpleXMLElement) {
    $attributes = $xml->attributes();
    $children   = $xml->children();
  }else {
    return false;
  }
  //get attributes as items
  if($attributes) {
    foreach($attributes as $name => $attribute) {
      $thisNode[$name] = (String)$attribute;
    }
  }
  //get children elements to array item
  if($children) {
    $newarray = array();
    foreach($children as $name => $child) {
    //have children. and atributes alway with the element have children;
      if($child->children()) {
        if($newarray[$name]) {
          $newarray[$name]++;
        }else {
         $newarray[$name] = 1;
        }
      }else {
      $thisNode[$name] = (string)$child;
    }
  }
//to fix the version 0.1 always has a 0=>null to end the multi elements
    foreach($newarray as $name => $value) {
      if($value > 1) {
        for($i = 0; $i < $value; $i++) {
          $thisNode[$name][$i] = XML2Array($children->{$name}[$i]);
        }
      }else {
        $thisNode[$name][] = XML2Array($children->$name);
      }
    }
  }

  if (count($thisNode) > 0) {
    return $thisNode;
  } else {
    return false;
  }
}

class mySimpleXML extends SimpleXMLElement {
  
  public function asPrettyXML() {
    $string = ($this->asXML());
      /**
       * put each element on it's own line
       */
      $string =preg_replace("/>\s*</",">\n<",$string);

      /**
       * each element to own array
       */
      $xmlArray = explode("\n",$string);

      /**
       * holds indentation
       */
      $currIndent = 0;

      /**
       * set xml element first by shifting of initial element
       */
      $string = array_shift($xmlArray) . "\n";

      foreach($xmlArray as $element) {
        /** find open only tags... add name to stack, and print to string
         * increment currIndent
         */

        if (preg_match('/^<([\w])+[^>\/]*>$/U',$element)) {
          $string .=  str_repeat(' ', $currIndent) . $element . "\n";
          $currIndent += 2;
        }

        /**
         * find standalone closures, decrement currindent, print to string
         */
        elseif ( preg_match('/^<\/.+>$/',$element)) {
          $currIndent -= 2;
          $string .=  str_repeat(' ', $currIndent) . $element . "\n";
        }
        /**
         * find open/closed tags on the same line print to string
         */
        else {
          $string .=  str_repeat(' ', $currIndent) . $element . "\n";
        }
      }
    return $string;
  }
}

?>