<?php
/*
 * basic.lib.php - basic routines
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/* ***************************************************************************/
/* **** GET CAVE FUNCTIONS ***** *********************************************/
/* ***************************************************************************/

/** This function returns the cave data for a given caveID
 */
function getCaveByID($caveID) {
  global $db;

  $sql = $db->prepare("SELECT *, (protection_end > NOW()+0) AS protected
                       FROM " . CAVE_TABLE . "
                       WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) return NULL;

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret;
}

/** This function returns the cave data for a given caveID and playerID
 */
function getCaveSecure($caveID, $playerID){
  global $db;

  $sql = $db->prepare("SELECT *, (protection_end > NOW()+0) AS protected
                       FROM " . CAVE_TABLE . "
                       WHERE caveID = :caveID
                         AND playerID = :playerID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute()) return NULL;
  
  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  return $ret;
}

/** This function returns the cave data for a given cave name
 */
function getCaveByName($caveName){
  global $db;

  $sql = $db->prepare("SELECT *
                       FROM " . CAVE_TABLE . "
                       WHERE name = :name");
  $sql->bindValue('name', $caveName, PDO::PARAM_STR);
  if (!$sql->execute()) return array();

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret;
}

/** This function returns the cave data for given cave coordinates
 */
function getCaveByCoords($xCoord, $yCoord){
  global $db;

  $sql = $db->prepare("SELECT *
                       FROM " . CAVE_TABLE . "
                       WHERE xCoord = :xCoord
                         AND yCoord = :yCoord");
  $sql->bindValue('xCoord', $xCoord, PDO::PARAM_INT);
  $sql->bindValue('yCoord', $yCoord, PDO::PARAM_INT);
  if (!$sql->execute()) return array();

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret;
}

/** This function returns the cave data for all caves of a given playerID
 */
function getCaves($playerID){
  global $db;

  $caves = array();
  $sql = $db->prepare("SELECT *, (protection_end > NOW()+0) AS protected
                       FROM " . CAVE_TABLE . "
                       WHERE playerID = :playerID
                       ORDER BY name ASC");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute()) return array();

  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $caves[$row['caveID']] = $row;
  }
  $sql->closeCursor();
  
  return $caves;
}

/** This function returns the cave data for all caves of a given regionID
 */
function getCavesByRegion($regionID){
  global $db;

  $caves = array();
  $sql = $db->prepare("SELECT *, (protection_end > NOW()+0) AS protected
                       FROM " . CAVE_TABLE . "
                       WHERE regionID = :regionID
                       ORDER BY name ASC");
  $sql->bindValue('regionID', $regionID, PDO::PARAM_INT);
  if (!$sql->execute()) return 0;
  while($row = $sql->fetch(PDO::FETCH_ASSOC)){
    $caves[$row['caveID']] = $row;
  }
  $sql->closeCursor();
  return $caves;
}

/** This function returns the cave name and owner for a given cave name
 */
function getCaveNameAndOwnerByCaveID($caveID) {
  global $db;

  $sql = $db->prepare("SELECT c.name AS cave_name, p.name AS player_name, p.tribe AS player_tribe, c.xCoord, c.yCoord
                       FROM " . CAVE_TABLE . " c
                         LEFT JOIN " . PLAYER_TABLE . " p ON c.playerID = p.playerID
                       WHERE c.caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) return array();

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret;
}

/* ***************************************************************************/
/* **** GET REGION FUNCTIONS ***** *******************************************/
/* ***************************************************************************/

/** This function returns an array with all Regions
 */
function getRegions() {
  global $db;

  $regions = array();
  $sql = $db->prepare("SELECT *
                       FROM " . REGIONS_TABLE ."
                       ORDER BY name");
  if (!$sql->execute()) return $regions;
  while($row = $sql->fetch(PDO::FETCH_ASSOC)){
    $regions[$row['regionID']] = $row;
  }
  $sql->closeCursor();

  return $regions;
}

/** This function returns the region data for the given region ID
 */
function getRegionByID($regionID) {
  global $db;

  $sql = $db->prepare("SELECT *
                       FROM " . REGIONS_TABLE . "
                       WHERE regionID = :regionID");
  $sql->bindValue('regionID', $regionID, PDO::PARAM_INT);
  if (!$sql->execute()) return array();

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret;
}

/** This function returns the region data for the given region name
 */
function getRegionByName($name) {
  global $db;

  $sql = $db->prepare("SELECT *
                       FROM " . REGIONS_TABLE . "
                       WHERE name = :name");
  $sql->bindValue('name', $name, PDO::PARAM_STR);
  if (!$sql->execute()) return array();

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret;
}

/* ***************************************************************************/
/* **** GET PLAYER FUNCTIONS ***** *******************************************/
/* ***************************************************************************/

/** This function returns a players data
 */
function getPlayerByID($playerID) {
  global $db;

  $sql = $db->prepare("SELECT *
                       FROM " . PLAYER_TABLE . "
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute()) return array();

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret;
}

/** This function returns a players data
 */
function getPlayerByName($name){
  global $db;

  $sql = $db->prepare("SELECT *
                       FROM " . PLAYER_TABLE . "
                       WHERE name = :name");
  $sql->bindValue('name', $name, PDO::PARAM_INT);
  if (!$sql->execute()) return array();

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret;
}

/* ***************************************************************************/
/* **** MAP FUNCTIONS ***** **************************************************/
/* ***************************************************************************/

function getMapSize() {
  global $db;

  static $size = null;

  if ($size === null){
    $sql = $db->prepare("SELECT min(xCoord) as minX, max(xCoord) as maxX, min(yCoord) as minY, max(yCoord) as maxY
                         FROM " . CAVE_TABLE);
    if ($sql->execute()) {
      $size = $sql->fetch(PDO::FETCH_ASSOC);
    }
    $sql->closeCursor();
  }

  return $size;
}

/* ***************************************************************************/
/* **** SQL QUERY FUNCTIONS ***** ********************************************/
/* ***************************************************************************/

/**
 * connect to login database
 */
function db_connectToLoginDB($host=0, $user=0, $pwd=0, $name=0) {
  if(!$host) $host  = Config::DB_LOGIN_HOST;
  if(!$user) $user  = Config::DB_LOGIN_USER;
  if(!$pwd)  $pwd   = Config::DB_LOGIN_PWD;
  if(!$name) $name  = Config::DB_LOGIN_NAME;

  /* Connect to an ODBC database using driver invocation */
  $dsn = "mysql:dbname={$name};host={$host}";

  try {
    $db_login = new ePDO($dsn, $user, $pwd);
  } catch (PDOException $e) {
    return false;
  }

  return $db_login;
}

/* ***************************************************************************/
/* **** PHP HELP FUNCTIONS ***** *********************************************/
/* ***************************************************************************/

function unhtmlentities($string) {
  static $trans_tbl;

  if (empty($trans_tbl)){
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
  }

  return strtr($string, $trans_tbl);
}

/** This function shortens a html string to a certain number of characters
 *  paying attention to character entities like &amp;
 */
function lib_shorten_html($string, $length){
  $temp = unhtmlentities($string);

  if (strlen($temp) > $length) {
    return htmlentities(substr($temp, 0, $length)) . "...";
  }

  return $string;
}

function processProductionCost ($item, $caveID, $cave, $quantity = 1, $fromTribeStorage = false) {
  global $db;

  if (isset($item->maxLevel)) {
    $maxLevel = round(eval('return '.formula_parseToPHP("{$item->maxLevel};", '$cave')));
  }

  $set     = array();
  if ($fromTribeStorage) {
    $where = array("WHERE tag LIKE '" . $_SESSION['player']->tribe."'");
  } else {
    $where   = array("WHERE caveID = {$caveID} ");
  }

  if (isset($item->maxLevel)) {
    array_push($where, "{$item->dbFieldName} < $maxLevel");
  }

  // get all the resource costs
  if (isset($item->resourceProductionCost)) {
    foreach ($item->resourceProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $GLOBALS['resourceTypeList'][$key]->dbFieldName;
        array_push($set, "{$dbField} = {$dbField} - ({$formula})");
        array_push($where, "{$dbField} >= ({$formula})");
      }
    }
  }

  // get all the unit costs
  if (isset($item->unitProductionCost)) {
    foreach ($item->unitProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $GLOBALS['unitTypeList'][$key]->dbFieldName;
        array_push($set, "{$dbField} = {$dbField} - ({$formula})");
        array_push($where, "{$dbField} >= ({$formula})");
      }
    }
  }

  // get all the building costs
  if (isset($item->buildingProductionCost)) {
    foreach ($item->buildingProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $GLOBALS['buildingTypeList'][$key]->dbFieldName;
        array_push($set, "{$dbField} = {$dbField} - ({$formula})");
        array_push($where, "{$dbField} >= ({$formula})");
      }
    }
  }

  // get all the defense costs
  if (isset($item->defenseProductionCost)) {
    foreach ($item->defenseProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $GLOBALS['defenseSystemTypeList'][$key]->dbFieldName;
        array_push($set, "{$dbField} = {$dbField} - ({$formula})");
        array_push($where, "{$dbField} >= ({$formula})");
      }
    }
  }

  // generate dependencies
  if (isset($item->buildingDepLIst)) {
    foreach($item->buildingDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$GLOBALS['buildingTypeList'][$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxBuildingDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$GLOBALS['buildingTypeList'][$key]->dbFieldName} <= $value");
      }
    }
  }

  if (isset($item->defenseSystemDepList)) {
    foreach($item->defenseSystemDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$GLOBALS['defenseSystemTypeList'][$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxDefenseSystemDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$GLOBALS['defenseSystemTypeList'][$key]->dbFieldName} <= $value");
      }
    }
  }

  if (isset($item->resourceDepList)) {
    foreach($item->resourceDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$GLOBALS['resourceTypeList'][$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxResourceDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$GLOBALS['resourceTypeList'][$key]->dbFieldName} <= $value");
      }
    }
  }

  if (isset($item->scienceDepList)) {
    foreach($item->scienceDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$GLOBALS['scienceTypeList'][$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxScienceDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$GLOBALS['scienceTypeList'][$key]->dbFieldName} <= $value");
      }
    }
  }

  if (isset($item->unitDepList)) {
    foreach($item->unitDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$GLOBALS['unitTypeList'][$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxUnitDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$GLOBALS['unitTypeList'][$key]->dbFieldName} <= $value");
      }
    }
  }

  // generate SQL
  if ($fromTribeStorage) {
    $table = TRIBE_TABLE;
  } else {
    $table = CAVE_TABLE;
  }
  if (sizeof($set)) {
    $set = implode(', ', $set);
    $where = implode(" AND ", $where);
    
    $sql = "UPDATE ". $table ." SET {$set} {$where}";
    if (!$db->exec($sql)) {
      return false;
    }
  }

  return true;
}

function processProductionCostSetBack($item, $caveID, $cave, $quantity = 1, $fromTribeStorage = false) {
  global $db;

  $setBack = array();

  // get all the resource costs
  if (isset($item->resourceProductionCost)) {
    foreach ($item->resourceProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $GLOBALS['resourceTypeList'][$key]->dbFieldName;
        array_push($setBack, "{$dbField} = {$dbField} + ({$formula})");
      }
    }
  }

  // get all the unit costs
  if (isset($item->unitProductionCost)) {
    foreach ($item->unitProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $GLOBALS['unitTypeList'][$key]->dbFieldName;
        $setBack[] = "{$dbField} = {$dbField} + ({$formula})";
      }
    }
  }

  // get all the building costs
  if (isset($item->buildingProductionCost)) {
    foreach ($item->buildingProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $GLOBALS['buildingTypeList'][$key]->dbFieldName;
        array_push($setBack, "{$dbField} = {$dbField} + ({$formula})");
      }
    }
  }

  // get all the defense costs
  if (isset($item->defenseProductionCost)) {
    foreach ($item->defenseProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $GLOBALS['defenseSystemTypeList'][$key]->dbFieldName;
        array_push($setBack, "{$dbField} = {$dbField} + ({$formula})");
      }
    }
  }

  // generate SQL
  if ($fromTribeStorage) {
    $where = " WHERE tag = '" . $_SESSION['player']->tribe."'"; 
    $table = TRIBE_TABLE;
  } else {
    $where = " WHERE caveID = " . $caveID;
    $table = CAVE_TABLE;
  }
  if (sizeof($setBack)) {
    $setBack = implode(", ", $setBack);
    $sql = "UPDATE ". $table ." SET {$setBack} " . $where;
    if (!$db->exec($sql)) {
      return false;
    }
  }

  return true;
}

// parse costs
function parseCost($building, &$details) {
  
  $ret = array();
  $notenough = false;
  if (isset($building->resourceProductionCost)) {
    foreach ($building->resourceProductionCost as $resourceID => $function) {
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

      if ($cost) {
        $ret['resource_cost'][] = array(
          'dbFieldName'  => $GLOBALS['resourceTypeList'][$resourceID]->dbFieldName,
          'name'         => $GLOBALS['resourceTypeList'][$resourceID]->name,
          'value'        => $cost,
          'enough'       => ($details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName] >= $cost) ? true : false,
          'missing'      => $cost - $details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName]
        );

        if (($details[$GLOBALS['resourceTypeList'][$resourceID]->dbFieldName] < $cost)) {
          $notenough = true;
        }
      }
    }
  }

  if (isset($building->unitProductionCost)) {
    foreach ($building->unitProductionCost as $unitID => $function) {
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

      if ($cost) {
        $ret['unit_cost'][] = array(
          'dbFieldName'  => $GLOBALS['unitTypeList'][$unitID]->dbFieldName,
          'name'         => $GLOBALS['unitTypeList'][$unitID]->name,
          'value'        => $cost,
          'enough'       => ($details[$GLOBALS['unitTypeList'][$unitID]->dbFieldName] >= $cost) ? true : false,
          'missing'      => $cost - $details[$GLOBALS['unitTypeList'][$unitID]->dbFieldName]
        );

        if (($details[$GLOBALS['unitTypeList'][$unitID]->dbFieldName] < $cost)) {
          $notenough = true;
        }
      }
    }
  }

  if (isset($building->buildingProductionCost)) {
    foreach ($building->buildingProductionCost as $buildingID => $function) {
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

      if ($cost) {
        $ret['building_cost'][] = array(
          'dbFieldName'  => $GLOBALS['buildingTypeList'][$buildingID]->dbFieldName,
          'name'         => $GLOBALS['buildingTypeList'][$buildingID]->name,
          'value'        => $cost,
          'enough'       => ($details[$GLOBALS['buildingTypeList'][$buildingID]->dbFieldName] >= $cost) ? true : false,
          'missing'      => $cost - $details[$GLOBALS['buildingTypeList'][$buildingID]->dbFieldName]
        );

        if (($details[$GLOBALS['buildingTypeList'][$buildingID]->dbFieldName] < $cost)) {
          $notenough = true;
        }
      }
    }
  }

  if (isset($building->defenseProductionCost)) {
    foreach ($building->defenseProductionCost as $defenseID => $function) {
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

      if ($cost) {
        $ret['defense_cost'][] = array(
          'dbFieldName'  => $GLOBALS['defenseSystemTypeList'][$defenseID]->dbFieldName,
          'name'         => $GLOBALS['defenseSystemTypeList'][$defenseID]->name,
          'value'        => $cost,
          'enough'       => ($details[$GLOBALS['defenseSystemTypeList'][$defenseID]->dbFieldName] >= $cost) ? true : false,
          'missing'      => $cost - $details[$GLOBALS['defenseSystemTypeList'][$defenseID]->dbFieldName]
        );

        if (($details[$GLOBALS['defenseSystemTypeList'][$defenseID]->dbFieldName] < $cost)) {
          $notenough = true;
        }
      }
    }
  }

  $ret['notenough'] = $notenough;
  return $ret;
}

function createRequestString($requestKeys) {
  // standard typen
  if (!is_array($requestKeys) || empty($requestKeys)) {
    return (isset($_REQUEST['modus'])) ? 'modus='.$_REQUEST['modus'] : '';
  }

  $requestKeys = array_merge(Config::$requestKeysNeed, $requestKeys);

  $requestAry = array();
  foreach ($requestKeys as $key) {
    if (isset($_REQUEST[$key])) {
      $requestAry[] = $key . '=' . $_REQUEST[$key];
    }
  }

  return implode('&amp;', $requestAry);
}

function checkAvatar($url) {
  if (empty($url)) {
    return false;
  }

  $contentTypes = array('image/gif', 'image/png', 'image/jpeg');

  // curl mit Url initialisieren
  $ch = curl_init($url);

  // optionen setzen: nur header zur�ckliefern
  curl_setopt_array($ch, array(
    CURLOPT_HEADER         => true,
    CURLOPT_NOBODY         => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5
  ));

  curl_exec($ch);

  // pr�fe ob der Content-Type einer der geforderten ist
  if (eregi('^('. implode('|', $contentTypes). ')', curl_getinfo($ch, CURLINFO_CONTENT_TYPE))) {
    // bild infos holen
    $imageInfo = @getimagesize($url);

    // Bild zu gro�?
    if ($imageInfo[0] > MAX_AVATAR_WIDTH || $imageInfo[1] > MAX_AVATAR_HEIGHT) {
      return false;
    }

    $return = serialize(array(
      'path'   => $url,
      'width'  => $imageInfo[0],
      'height' => $imageInfo[1],
    ));
  } else {
    $return = false;
  }

  // curl Hadle schliessen
  curl_close($ch);

  return $return;
}

?>