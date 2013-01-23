<?php
/*
 * formula_parser.inc.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once("rules/game.rules.php");
require_once("rules/effects.list.php");
require_once("rules/wonder.rules.php");
require_once("rules/tutorial.php");

init_Buildings();
init_Units();
init_Resources();
init_Sciences();
init_DefenseSystems();
init_Effects();

init_Symbols();

function init_Symbols(){
  global $FORMULA_SYMBOLS, $FORMULA_READABLE;

  $FORMULA_SYMBOLS = array(
    "R" => &$GLOBALS['resourceTypeList'],
    "B" => &$GLOBALS['buildingTypeList'],
    "U" => &$GLOBALS['unitTypeList'],
    "S" => &$GLOBALS['scienceTypeList'],
    "D" => &$GLOBALS['defenseSystemTypeList'],
    "E" => &$GLOBALS['effectTypeList']
  );

  $FORMULA_READABLE = array(
    "LEAST"    => "Min",
    "GREATEST" => "Max",
    "POW"      => "Potenz"
  );
}

function sign($value) {
  if ($value > 0) return 1;
  if ($value < 0) return -1;
  return 0;
}

function formula_parseToSQL($formula) {
  global $FORMULA_SYMBOLS;

  $sql = "";
  $farmmalus = max($_SESSION['player']->fame - FREE_FARM_POINTS , 0);
  $formula = str_replace("[E25.ACT]", $farmmalus, $formula);
  // abstract functions are sql functions -> no translation needed

  // parse symbols
  for ($i = 0; $i < strlen($formula); $i++) {
    // opening brace
    if ($formula{$i} == '[') {

      $symbol = $formula{++$i};
      $index = 0;

      while($formula{++$i} != '.')
        $index = $index * 10 + ($formula{$i} + 0);

      $field  = substr($formula, ++$i, 3);

      // 'ACT]' or 'MAX]'
      $i += 3;

      if (strncasecmp($field, "ACT", 3) == 0) {
        $sql .= $FORMULA_SYMBOLS[$symbol][$index]->dbFieldName;
      } else if (strncasecmp($field, "MAX", 3) == 0) {
        $sql .= formula_parseToSQL($FORMULA_SYMBOLS[$symbol][$index]->maxLevel);
      }
    } else {
      $sql .= $formula{$i};
    }
  }

  $sql = str_replace(array('min(',   'max(',      'sgn('),
                     array('LEAST(', 'GREATEST(', 'SIGN('),
                     $sql);

  return $sql;
}

function formula_parseToSelectSQL($formula) {
  global $FORMULA_SYMBOLS;

  $sql = "";
  $farmmalus = max($_SESSION['player']->fame - FREE_FARM_POINTS , 0);
  $formula = str_replace("[E25.ACT]", $farmmalus, $formula);
  // abstract functions are sql functions -> no translation needed

  // parse symbols
  for ($i = 0; $i < strlen($formula); $i++) {
    // opening brace
    if ($formula{$i} == '[') {

      $symbol = $formula{++$i};
      $index = 0;

      while($formula{++$i} != '.')
        $index = $index * 10 + ($formula{$i} + 0);

      $field  = substr($formula, ++$i, 3);

      // 'ACT]' or 'MAX]'
      $i += 3;

      if (strncasecmp($field, "ACT", 3) == 0) {
        $sqlFields[$FORMULA_SYMBOLS[$symbol][$index]->dbFieldName] = true;
      } else if (strncasecmp($field, "MAX", 3) == 0) {
        formula_parseToSQL($FORMULA_SYMBOLS[$symbol][$index]->maxLevel);
      }
    }
  }

  return array_keys($sqlFields);
}

function formula_parseToPHP($formula, $detail) {
  global $FORMULA_SYMBOLS;

  $farmmalus = max($_SESSION['player']->fame - FREE_FARM_POINTS , 0);
  $formula = str_replace("[E25.ACT]", $farmmalus, $formula);

  if (Config::RUN_TIMER) {
    $timer = page_startTimer();
  }

  // translate abstract functions to php functions
  $formula = str_replace(array('sgn'), array('SIGN'), $formula);

  // translate variables
  $php = '';
  for ($i = 0; $i < strlen($formula); $i++) {
    if ($formula{$i} == '[') {

      $symbol = $formula{++$i};
      $index = 0;

      while($formula{++$i} != '.') {
        $index = $index * 10 + ($formula{$i} + 0);
      }

      $field = substr($formula, ++$i, 3);
      // 'ACT]' or 'MAX]'
      $i += 3;

      if (strncasecmp($field, "ACT", 3) == 0) {
        $php .= $detail . '["' . $FORMULA_SYMBOLS[$symbol][$index]->dbFieldName . '"]';
      } else if (strncasecmp($field, "MAX", 3) == 0) {
        $php .= formula_parseToPHP($FORMULA_SYMBOLS[$symbol][$index]->maxLevel, $detail);
      }
    } else {
      $php .= $formula{$i};
    }
  }

  if (Config::RUN_TIMER) {
    echo "<p>rules_parseToPHP: " . page_stopTimer($timer) . "s</p>";
  }

  return $php;
}

function formula_parseToReadable($formula){
  global $FORMULA_SYMBOLS, $FORMULA_READABLE;

  $formula = str_replace(array_keys($FORMULA_READABLE), $FORMULA_READABLE, $formula);

  // parse symbols
  $return = '';
  for ($i = 0; $i < strlen($formula); $i++) {
    // opening brace
    if ($formula{$i} == '[') {

      $symbol = $formula{++$i};
      $index = 0;

      while($formula{++$i} != '.') {
        $index = $index * 10 + ($formula{$i} + 0);
      }

      $field  = substr($formula, ++$i, 3);

      // 'ACT]' or 'MAX]'
      $i += 3;

      if (strncasecmp($field, "ACT", 3) == 0) {
        $return .= $FORMULA_SYMBOLS[$symbol][$index]->name;
      }
      else if (strncasecmp($field, "MAX", 3) == 0) {
        $return .= formula_parseToReadable($FORMULA_SYMBOLS[$symbol][$index]->maxLevel);
      }
    } else {
      $return .= $formula{$i};
    }
  }

  return $return;
}

/** This function checks if an object can be build by examining its dependencies.
 *
 *  @param $object    the object to be checked
 *  @param $caveData  the data to be checked against
 *
 *  @return  returns TRUE if the object can be build,
 *           FALSE if the object cannot be build at all because of mutual exclusion
 *           or a string describing the circumstances needed to build that object
 */
function rules_checkDependencies($object, $caveData) {
  foreach ($object->maxBuildingDepList as $key => $value) {
    if ($value != -1 && $value < $caveData[$GLOBALS['buildingTypeList'][$key]->dbFieldName]) {
      return FALSE;
    }
  }
  foreach ($object->maxDefenseSystemDepList as $key => $value) {
    if ($value != -1 && $value < $caveData[$GLOBALS['defenseSystemTypeList'][$key]->dbFieldName]) {
      return false;
    }
  }
  foreach ($object->maxResourceDepList as $key => $value) {
    if ($value != -1 && $value < $caveData[$GLOBALS['resourceTypeList'][$key]->dbFieldName]) {
      return false;
    }
  }
  foreach ($object->maxScienceDepList as $key => $value) {
    if ($value != -1 && $value < $caveData[$GLOBALS['scienceTypeList'][$key]->dbFieldName]) {
      return false;
    }
  }
  foreach ($object->maxUnitDepList as $key => $value) {
    if ($value != -1 && $value < $caveData[$GLOBALS['unitTypeList'][$key]->dbFieldName]) {
      return false;
    }
  }

  $dep = NULL;
  foreach($object->buildingDepList as $key => $value) {
    if ($value != "" && $value > $caveData[$GLOBALS['buildingTypeList'][$key]->dbFieldName]) {
      $dep .= $GLOBALS['buildingTypeList'][$key]->name . ": (" . $caveData[$GLOBALS['buildingTypeList'][$key]->dbFieldName] . "/" . $value . "), ";
    }
  }
  foreach($object->defenseSystemDepList as $key => $value) {
    if ($value != "" && $value > $caveData[$GLOBALS['defenseSystemTypeList'][$key]->dbFieldName]) {
      $dep .= $GLOBALS['defenseSystemTypeList'][$key]->name . ": (" . $caveData[$GLOBALS['defenseSystemTypeList'][$key]->dbFieldName] . "/" . $value . "), ";
    }
  }
  foreach($object->resourceDepList as $key => $value) {
    if ($value != "" && $value > $caveData[$GLOBALS['resourceTypeList'][$key]->dbFieldName]) {
      $dep .= $GLOBALS['resourceTypeList'][$key]->name . ": (" . $caveData[$GLOBALS['resourceTypeList'][$key]->dbFieldName] . "/" . $value . "), ";
    }
  }
  foreach($object->scienceDepList as $key => $value) {
    if ($value != "" && $value > $caveData[$GLOBALS['scienceTypeList'][$key]->dbFieldName]) {
      $dep .= $GLOBALS['scienceTypeList'][$key]->name . ": (" . $caveData[$GLOBALS['scienceTypeList'][$key]->dbFieldName] . "/" . $value . "), ";
    }
  }
  foreach($object->unitDepList as $key => $value) {
    if ($value != "" && $value > $caveData[$GLOBALS['unitTypeList'][$key]->dbFieldName]) {
      $dep .= $GLOBALS['unitTypeList'][$key]->name . ": (" . $caveData[$GLOBALS['unitTypeList'][$key]->dbFieldName] . "/" . $value . "), ";
    }
  }

  return ($dep === NULL ? TRUE : substr($dep, 0, -2));
}

?>