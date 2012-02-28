<?php
/*
 * adjustEffects.php - adjusts all caves' effects
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/***** COMMAND LINE *****/

// include PEAR::Console_Getopt
require_once('Console/Getopt.php');

// check for command line options
$options = Console_Getopt::getOpt(Console_Getopt::readPHPArgv(), 'ch');
if (PEAR::isError($options)) {
  adjust_usage();
  exit(1);
}

// check for options
$checkOnly = FALSE;
foreach ($options[0] as $option) {
  
  // option h
  if ('h' == $option[0]) {
    adjust_usage(); exit(1);
  
  // option c
  } else if ('c' == $option[0]) {
    $checkOnly = TRUE;
  }
}

/***** INIT *****/

// include necessary files
include "util.inc.php";
include INC_DIR . "config.inc.php";
include INC_DIR . "db.inc.php";

include INC_DIR . "effect_list.php";
include INC_DIR . "game_rules.php";
include INC_DIR . "wonder.rules.php";

include INC_DIR . "artefact.inc.php";
include INC_DIR . "basic.lib.php";
include INC_DIR . "wonder.inc.php";

// get globals
$config = new Config();

// show header
adjust_showHeader();

// connect to databases
$db = DbConnect();

// get caveIDs
$caveIDs = adjust_getCaveIDs($db);

// adjust caves
foreach ($caveIDs as $caveID) {
  adjust_adjustCave($db, $caveID);
}

// show footer
adjust_showFooter();



// ***** FUNCTIONS ***** *******************************************************

/**
 * Shows usage
 */
function adjust_usage() {
  echo "Usage: php adjustEffects.php [-c] [-h]\n".
       "  -c  Just check, do not update\n".
       "  -h  This help\n";
}


/**
 * Logging function with printf syntax
 */
function adjust_log($format /* , .. */) {

  // get args
  $args = func_get_args();

  // get format string
  $format = array_shift($args);

  // do something
  echo vsprintf($format, $args) . "\n";
}


/**
 * Shows header
 */
function adjust_showHeader() {
  adjust_log('------------------------------------------------------------');
  adjust_log('- ADJUST EFFECTS -------------------------------------------');
  adjust_log('- from %s', date('r'));
  adjust_log('------------------------------------------------------------');
}

/**
 * Returns all caves' ID
 *
 * @param dbgame
 *          the link to the game DB
 * @return  all caveIDs
 */
function adjust_getCaveIDs($db) {

  // prepare result
  $result = array();

  // prepare query
  $sql = $db->prepare("SELECT caveID FROM ". CAVE_TABLE ." ORDER BY caveID ASC");

  
  // ignore errors
  if ($sql->rowCountSelect() == 0 ) {
    adjust_log('%s: Could not retrieve caveIDs', __FUNCTION__);
    return $result;
  }
  if (!$sql->execute()) {
    adjust_log('%s: Could not retrieve caveIDs', __FUNCTION__);
    return $result;
  }

  // collect caveIDs
  while ($row = $sql->fetch())
    $result[] = $row['caveID'];

  return $result;
}


/**
 * Adjusts deviating cave's effects
 *
 * @param dbgame
 *          the link to the game DB
 * @return  all caveIDs
 */
function adjust_adjustCave($db, $caveID) {

  global $checkOnly;

  // get cave
  $cave = getCaveByID($caveID);

  // get artefact effects
  $artefactEffects = artefact_recalculateEffects($caveID);

  // get wonder effects
  $wonderEffects = wonder_recalc($caveID, $db);

  // check each effect
  $adjustments = array();
  foreach($GLOBALS['effectTypeList'] AS $effectID => $effect) {

    // get actual value
    $actual = $cave[$effect->dbFieldName];

    // get nominal value
    $nominal = $artefactEffects[$effectID] + $wonderEffects[$effectID];
    $nominal += (double) $GLOBALS['terrainList'][$cave['terrain']]['effects'][$effectID];

    // check for deviation
    if ($actual != $nominal) {

      // log difference
      adjust_log('%4d:  %-30s  nominal:%f  actual:%f', $caveID,
                 $effect->dbFieldName, $nominal, $actual);

      // collect adjustments
      $adjustments[] = sprintf('%s = %f', $effect->dbFieldName, $nominal);
    }
  }

  // prepare query
  $set = implode(", ", $adjustments); 
  $sql = $db->prepare("UPDATE ". CAVE_TABLE ." SET {$set} WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, pdo::PARAM_INT);

  // adjust cave
  if (0 != sizeof($adjustments) && !$checkOnly) {
    adjust_log('Adjusting cave %d (%s)', $caveID, $cave['name']);

    // send query
    if (!$sql->execute()) {
      adjust_log('Error! "%s": %s', $query, $sql->errorInfo());
    }
  }
}


/**
 * Shows footer
 */
function adjust_showFooter() {
  adjust_log('------------------------------------------------------------');
  adjust_log('- STOP -----------------------------------------------------');
  adjust_log('- at %s', date('r'));
  adjust_log('------------------------------------------------------------');
}

?>