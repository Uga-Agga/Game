<?php
/*
 * basic.lib.php - basic routines
 * Copyright (c) 2003  OGP Team
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
  if (!$sql->execute()) return 0;
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
 * use an additional list of allowed field names to prevent
 * users from cheating the formulas
 */
function db_makeSetStatementSecure($data, $fields) {
  if (!$data) {
    return 0;
  }

  $count = 0;
  $statement = "";
  
  foreach($fields as $field) {
    if (array_key_exists($field, $data)) {
      $count++;
      $statement .= $field ." = '". $data[$field] ."', ";
    }
  }

  if (!$count) return 0;
  return substr($statement, 0, strlen($statement) - 2);  // remove ", "
}

/**
 * connect to login database
 */
function db_connectToLoginDB($host=0, $user=0, $pwd=0, $name=0) {
  global $config;

  if(!$host) $host  = $config->DB_LOGIN_HOST;
  if(!$user) $user  = $config->DB_LOGIN_USER;
  if(!$pwd)  $pwd   = $config->DB_LOGIN_PWD;
  if(!$name) $name  = $config->DB_LOGIN_NAME;

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

function processProductionCost ($item, $caveID, $cave, $quantity = 1) {
  global $defenseSystemTypeList, $unitTypeList, $buildingTypeList,
         $scienceTypeList, $resourceTypeList, $db;

  if (isset($item->maxLevel)) {
    $maxLevel = round(eval('return '.formula_parseToPHP("{$item->maxLevel};", '$cave')));
  }

  $set     = array();
  $where   = array("WHERE caveID = {$caveID} ");

  if (isset($item->maxLevel)) {
    array_push($where, "{$item->dbFieldName} < $maxLevel");
  }

  // get all the resource costs
  if (isset($item->resourceProductionCost)) {
    foreach ($item->resourceProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $resourceTypeList[$key]->dbFieldName;
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
        $dbField = $unitTypeList[$key]->dbFieldName;
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
        $dbField = $buildingTypeList[$key]->dbFieldName;
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
        $dbField = $defenseSystemTypeList[$key]->dbFieldName;
        array_push($set, "{$dbField} = {$dbField} - ({$formula})");
        array_push($where, "{$dbField} >= ({$formula})");
      }
    }
  }

  // generate SQL
  if (sizeof($set)) {
    $set     = implode(", ", $set);
    $set     = "UPDATE ". CAVE_TABLE ." SET $set ";
  }

  // generate dependencies
  if (isset($item->buildingDepLIst)) {
    foreach($item->buildingDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$buildingTypeList[$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxBuildingDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$buildingTypeList[$key]->dbFieldName} <= $value");
      }
    }
  }

  if (isset($item->defenseSystemDepList)) {
    foreach($item->defenseSystemDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$defenseSystemTypeList[$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxDefenseSystemDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$defenseSystemTypeList[$key]->dbFieldName} <= $value");
      }
    }
  }

  if (isset($item->resourceDepList)) {
    foreach($item->resourceDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$resourceTypeList[$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxResourceDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$resourceTypeList[$key]->dbFieldName} <= $value");
      }
    }
  }

  if (isset($item->scienceDepList)) {
    foreach($item->scienceDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$scienceTypeList[$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxScienceDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$scienceTypeList[$key]->dbFieldName} <= $value");
      }
    }
  }

  if (isset($item->unitDepList)) {
    foreach($item->unitDepList as $key => $value) {
      if ($value) {
        array_push($where, "{$unitTypeList[$key]->dbFieldName} >= $value");
      }
    }
    foreach($item->maxUnitDepList as $key => $value) {
      if ($value != -1) {
        array_push($where, "{$unitTypeList[$key]->dbFieldName} <= $value");
      }
    }
  }

  $where = implode(" AND ", $where);

  $ret = $db->exec($set.$where);

  if ($ret != 1) {
    return 0;
  }

  return 1;
}

function processProductionCostSetBack($item, $caveID, $cave, $quantity = 1) {
  global $defenseSystemTypeList, $unitTypeList, $buildingTypeList, $scienceTypeList, $resourceTypeList;
  global $db;

  $setBack = array();

  // get all the resource costs
  if (isset($item->resourceProductionCost)) {
    foreach ($item->resourceProductionCost as $key => $value) {
      if ($value) {
        $formula = formula_parseToSQL($value);
        $formula = "{$quantity} * ({$formula})";
        $dbField = $resourceTypeList[$key]->dbFieldName;
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
        $dbField = $unitTypeList[$key]->dbFieldName;
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
        $dbField = $buildingTypeList[$key]->dbFieldName;
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
        $dbField = $defenseSystemTypeList[$key]->dbFieldName;
        array_push($setBack, "{$dbField} = {$dbField} + ({$formula})");
      }
    }
  }

  // generate SQL
  if (sizeof($setBack)) {

    $setBack = implode(", ", $setBack);
    $setBack = "UPDATE ". CAVE_TABLE ." SET {$setBack} WHERE caveID = {$caveID}";
  }

  $ret = $db->exec($setBack);
  
  if ($ret != 1) {
    return 0;
  }

  return 1;
}

// parse costs
function parseCost($building, &$details) {
  global $resourceTypeList, $unitTypeList, $buildingTypeList, $defenseSystemTypeList;

  $ret = array();
  $notenough = false;
  if (isset($building->resourceProductionCost)) {
    foreach ($building->resourceProductionCost as $resourceID => $function) {
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

      if ($cost) {
        $ret['resource_cost'][] = array(
          'dbFieldName' => $resourceTypeList[$resourceID]->dbFieldName,
          'name'         => $resourceTypeList[$resourceID]->name,
          'value'        => $cost,
          'enough'       => ($details[$resourceTypeList[$resourceID]->dbFieldName] >= $cost) ? true : false,
          'missing'      => $cost - $details[$resourceTypeList[$resourceID]->dbFieldName]
        );

        if (($details[$resourceTypeList[$resourceID]->dbFieldName] < $cost)) {
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
          'dbFieldName' => $unitTypeList[$unitID]->dbFieldName,
          'name'         => $unitTypeList[$unitID]->name,
          'value'        => $cost,
          'enough'       => ($details[$unitTypeList[$unitID]->dbFieldName] >= $cost) ? true : false,
          'missing'      => $cost - $details[$unitTypeList[$unitID]->dbFieldName]
        );

        if (($details[$unitTypeList[$unitID]->dbFieldName] < $cost)) {
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
          'dbFieldName' => $buildingTypeList[$buildingID]->dbFieldName,
          'name'         => $buildingTypeList[$buildingID]->name,
          'value'        => $cost,
          'enough'       => ($details[$buildingTypeList[$buildingID]->dbFieldName] >= $cost) ? true : false,
          'missing'      => $cost - $details[$buildingTypeList[$buildingID]->dbFieldName]
        );

        if (($details[$buildingTypeList[$buildingID]->dbFieldName] < $cost)) {
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
          'dbFieldName' => $defenseSystemTypeList[$defenseID]->dbFieldName,
          'name'         => $defenseSystemTypeList[$defenseID]->name,
          'value'        => $cost,
          'enough'       => ($details[$defenseSystemTypeList[$defenseID]->dbFieldName] >= $cost) ? true : false,
          'missing'      => $cost - $details[$defenseSystemTypeList[$defenseID]->dbFieldName]
        );

        if (($details[$defenseSystemTypeList[$defenseID]->dbFieldName] < $cost)) {
          $notenough = true;
        }
      }
    }
  }

  $ret['notenough'] = $notenough;
  return $ret;
}

function parseUrl($queryAry, $allow=array(), $new=array()) {
  if (empty($queryAry) || empty($allow)) {
    return;
  }

  parse_str($queryString, $queryAry);

  foreach ($queryAry as $item => $value) {
    if (!in_array($item, $allow)) {
      unset($queryAry[$item]);
      continue;
    }
  }

  foreach ($new as $item => $value) {
    $queryAry[urlencode($item)] = urlencode($value);
  }

  $return = array();
  foreach ($queryAry as $item => $value) {
    $return[] = $item . '=' . $value;
  }

  $return = implode("&amp;", $return);

  return $return;
}

?>