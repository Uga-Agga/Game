<?php
/*
 * questionnaire.html.php -
 * Copyright (c) 2003  OGP Team, 2011 Sascha Lange <salange@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

define("SILVERPERGOLD",   26);
define("COPPERPERSILVER", 13);

function questionnaire_getQuestionnaire($caveID, &$ownCaves) {
  global $template;
  $msg = "";

  if (sizeof(Request::getVar('question', array('' => '')))) {
    $msg = questionnaire_giveAnswers();
  }

  $template->setFile('questionnaire.tmpl');
  $template->setShowRresource(false);

  // show message
  if (!empty($msg)) {
    $template->addVar('status_msg', $msg);
  }
  
  // show my credits
  if ($account = questionnaire_getCredits($_SESSION['player']->questionCredits)) {
    $template->addVar('account', $account);
  }

  // show the questions
  $questions = questionnaire_getQuestions();

  if (sizeof($questions)>0) {
    $template->addVar('questions', array(
      'question' => $questions,
      'params'   => array(
        array('name' => "modus", 'value' => QUESTIONNAIRE)
      )
    ));
  } else {
    $template->addVar('status_msg', array('type' => 'notice', 'message' => _('Derzeit liegen keine weiteren Fragen vor.')));
  }
}

function questionnaire_getCredits($credits) {
  $copper = $credits % COPPERPERSILVER;
  $silver = intval($credits / COPPERPERSILVER) % SILVERPERGOLD;
  $gold   = intval($credits / SILVERPERGOLD / COPPERPERSILVER);

  $result = array('credits' => $credits);
  if (!$credits) $result['copper'] = 0;
  else {
    if ($copper) $result['copper'] = $copper;
    if ($silver) $result['silver'] = $silver;
    if ($gold)   $result['gold']   = $gold;
  }
  return $result;
}

function questionnaire_getQuestions() {
  global $db;

  // get possible questions
  $sql = $db->prepare("SELECT * FROM ". QUESTIONNAIRE_QUESTIONS_TABLE ." WHERE expiresOn > NOW() + 0 ORDER BY questionID ASC");
  if (!$sql->execute()) {
    return array();
  }

  $questions = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $questions[$row['questionID']] = $row;
  }
  
  if (sizeof($questions) == 0) {
    return array();
  }

  // get answers
  $sql = $db->prepare("SELECT * FROM ". QUESTIONNAIRE_ANSWERS_TABLE ." ".
           "WHERE playerID = :playerID ".
           "AND questionID IN (".implode(", ", array_map(array($db, 'quote'), array_keys($questions))).") ".
           "ORDER BY questionID ASC");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return array();
  }

  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    unset($questions[$row['questionID']]);
  }

  $questionIDs = implode(", ", array_map(array($db, 'quote'), array_keys($questions)));
  
  if (empty($questionIDs)) return array();
  
  $sql = $db->prepare("SELECT * FROM ". QUESTIONNAIRE_CHOISES_TABLE ."
                       WHERE questionID IN (". $questionIDs . ")
                       ORDER BY choiceID ASC");

  if ($sql->rowCountSelect() == 0) return array();
  if (!$sql->execute()) return array();

  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (isset($question[$row['questionID']]['CHOICE'])) {
      $questions[$row['questionID']]['CHOICE'] = array();
    }

    $questions[$row['questionID']]['CHOICE'][$row['choiceID']] = $row;
  }
  return $questions;
}

function questionnaire_giveAnswers() {
  global $db;

  // filter given answers
  $answers = Request::getVar('question', array('' => ''));
  foreach ($answers AS $questionID => $choiceID) {
    if ($choiceID < 0) unset($answers[$questionID]);
  }

  // get valid answers
  $sql = $db->prepare("SELECT * 
                       FROM ". QUESTIONNAIRE_CHOISES_TABLE ."
                       WHERE questionID IN (".implode(", ", array_map(array($db, 'quote'), array_keys($answers))).")");
  if ($sql->rowCountSelect() == 0) {
    return array('type' => 'error', 'message' => _('Keine derartigen Fragen!'));
  }
  if (!$sql->execute()) {
    return array('type' => 'error', 'message' => _('Fehler beim auslesen der Fragen'));
  }

  $valid = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($valid[$row['questionID']])) {
      $valid[$row['questionID']] = array();
    }
    $valid[$row['questionID']][$row['choiceID']] = $row;
  }

  // validate given answers
  foreach ($answers AS $questionID => $choiceID) {
    if (!isset($valid[$questionID][$choiceID])) {
      unset($answers[$questionID]);
    }
  }

  $valid = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($valid[$row['questionID']])) {
      $valid[$row['questionID']] = array();
    }
    $valid[$row['questionID']][$row['choiceID']] = $row;
  }

  // answers now contains valid answers

  // get questions
  $questions = array();
  $sql = $db->prepare("SELECT * 
                       FROM ". QUESTIONNAIRE_QUESTIONS_TABLE ."
                       WHERE questionID IN (".implode(",", array_keys($answers)).")");
  if ($sql->rowCountSelect() == 0) {
    return array('type' => 'error', 'message' => _('Keine derartigen Fragen!'));
  }
  if (!$sql->execute()) {
    return array('type' => 'error', 'message' => _('Fehler beim auslesen der Fragen'));
  }
  
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $questions[$row['questionID']] = $row;
  }

  // insert into db and reward afterwards
  $rewards = 0;
  foreach ($answers AS $questionID => $choiceID) {
    $sql = $db->prepare("INSERT INTO ". QUESTIONNAIRE_ANSWERS_TABLE ."
                           (playerID, questionID, choiceID)
                         VALUES
                          (:playerID, :questionID, :choiceID)");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('questionID', $questionID, PDO::PARAM_INT);
    $sql->bindValue('choiceID', $choiceID, PDO::PARAM_INT);
    if (!$sql->execute()) {
      return array('type' => 'error', 'message' => _('Fehler beim eintragen der Antworten.'));
    }
    if ($sql->rowCount() != 1) {
      continue;
    }
    $sql->closeCursor();
    $rewards += $questions[$questionID]['credits'];
  }
  
  // now update playerstats
  if (!questionnaire_addCredits($rewards)) {
    return array('type' => 'error', 'message' => _('Probleme beim Eintragen der Bonuspunkte.'));
  }

  return;
}

function questionnaire_addCredits($credits) {
  global $db;

  $sql = $db->prepare("UPDATE ". PLAYER_TABLE ."
                       SET questionCredits = questionCredits + :credits 
                       WHERE playerID = :playerID
                         AND questionCredits + :credits >= 0");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('credits', $credits, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  $_SESSION['player']->questionCredits += $credits;
  return true;
}

function questionnaire_presents($caveID, &$ownCaves) {
  global $db, $template;

  $template->setFile('questionnairePresents.tmpl');
  $template->setShowRresource(false);

  $msg = "";
  if (Request::getVar('presentID', 0) > 0) {
    $msg = questionnaire_getPresent($caveID, $ownCaves, Request::getVar('presentID', 0));
  }

  // show message
  if ($msg != "") {
    $template->setVar('message', $msg);
  }

  // show my credits
  if ($account = questionnaire_getCredits($_SESSION['player']->questionCredits)) {
    $template->addVar('account', $account);
  }

  $sql = $db->prepare("SELECT * 
                       FROM ". QUESTIONNAIRE_PRESENTS_TABLE ."
                       ORDER BY presentID ASC");
  if (!$sql->execute()) {
    return array('type' => 'error', 'message' => _('Fehler beim eintragen der Antworten.'));
  }

  $presents = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (!questionnaire_timeIsRight($row)) {
      continue;
    }

    $row += questionnaire_getCredits($row['credits']);

    $externals = array();
    foreach ($v AS $external) {
      if ($row[$external->dbFieldName] > 0) {
        $externals[] = array(
          'amount' => $row[$external->dbFieldName],
          'name'   => $external->name);
      }
    }
    if (sizeof($externals)) $row['EXTERNAL'] = $externals;

    $resources = array();
    foreach ($GLOBALS['resourceTypeList'] AS $resource) {
      if ($row[$resource->dbFieldName] > 0) {
        $resources[] = array(
          'amount' => $row[$resource->dbFieldName],
          'name'   => $resource->name);
      }
    }
    if (sizeof($resources)) $row['RESOURCE'] = $resources;

    $units = array();
    foreach ($GLOBALS['unitTypeList'] AS $unit) {
      if ($row[$unit->dbFieldName] > 0) {
        $units[] = array(
          'amount' => $row[$unit->dbFieldName],
          'name'   => $unit->name);
      }
    }
    if (sizeof($units)) $row['UNIT'] = $units;

    $presents[] = $row;
  }
  if (sizeof($presents)){
    $template->addVar('presents', array(
      'present' => $presents,
      'params'  => array(
        array('name' => "modus", 'value' => QUESTIONNAIRE_PRESENTS)
      )
    ));
  }
  else {
    $template->addVar('NO_PRESENT', "&nbsp;");
  }
}

function questionnaire_timeIsRight($row) {
  global $db;

  static $now = null;

  // get current uga agga time
  if ($now === null) {
    $now = getUgaAggaTime(time());
  }

  $parsed_row = array();
  questionnaire_parseNumericElement($row['hour'], $parsed_row['hour'], HOURS_PER_DAY);
  questionnaire_parseNumericElement($row['day_of_month'], $parsed_row['day_of_month'], DAYS_PER_MONTH);
  questionnaire_parseNumericElement($row['month'], $parsed_row['month'], MONTHS_PER_YEAR);

  return $parsed_row['hour'][$now['hour']] &&
         $parsed_row['day_of_month'][$now['day']] &&
         $parsed_row['month'][$now['month']];
}

function questionnaire_parseNumericElement($element, &$targetArray, $numberOfElements) {
  $subelements = explode(", ", $element);
  for ($i = 0; $i < $numberOfElements; $i++) {
    $targetArray[$i] = ($subelements[0] == "*");
  }

  for ($i = 0; $i < count($subelements); $i++) {
    if (preg_match("~^(\\*|([0-9]{1,2})(-([0-9]{1,2}))?)(/([0-9]{1,2}))?$~",
        $subelements[$i],  $matches)) {

      if ($matches[1] == "*") {
        $matches[2] = 0; // from
        $matches[4] = $numberOfElements; //to
      } else if ($matches[4] == "") {
        $matches[4] = $matches[2];
      }
      if ($matches[5][0] != "/")
        $matches[6] = 1; // step
      for ($j = questionnaire_lTrimZeros($matches[2]);
           $j <= questionnaire_lTrimZeros($matches[4]);
           $j += questionnaire_lTrimZeros($matches[6]))
        $targetArray[$j] = TRUE;
    }
  }
}

function questionnaire_parseCharElement($element, &$targetArray, $allowedElements) {

  $subelements = explode(",", $element);
  foreach ($allowedElements AS $character)
    $targetArray[$character] = ($subelements[0] == "*");

  // list
  foreach ($subelements AS $character)
    if (in_array($character, $allowedElements))
      $targetArray[$character] = true;
}

function questionnaire_lTrimZeros($number) {
  while ($number[0]=='0') $number = substr($number,1);
  return $number;
}

function questionnaire_getPresent($caveID, &$ownCaves, $presentID) {
  global $db;

  $sql = $db->prepare("SELECT * FROM ". QUESTIONNAIRE_PRESENTS_TABLE ." WHERE presentID = :presentID");
  $sql->bindValue('presentID', $presentID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return _('Probleme beim auslesen des Geschenks.');
  }

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (!questionnaire_timeIsRight($row)) {
    return _('"Dieses Geschenk kann ich euch nicht anbieten, Häuptling!"');
  }

  // genügend Schnecken?
  $myaccount = questionnaire_getCredits($_SESSION['player']->questionCredits);
  $price     = questionnaire_getCredits($row['credits']);

  if ($myaccount['credits'] < $price['credits'])
    return _('"Ihr habt nicht die passenden Schnecken, Häuptling!"');

  // Preis abziehen
  if (!questionnaire_addCredits(-$row['credits']))
    return _('"Ich bin mit dem Schnecken abzählen durcheinander gekommen, Häuptling! Versucht es noch einmal!"');

  // Geschenk überreichen

  $presents = array();
  $caveData = $ownCaves[$caveID];
  foreach ($GLOBALS['defenseSystemTypeList'] AS $external) {
    if ($row[$external->dbFieldName] > 0) {
      $dbField    = $external->dbFieldName;
      $maxLevel   = round(eval('return '.formula_parseToPHP("{$external->maxLevel};", '$caveData')));
      $presents[] = "$dbField = LEAST(GREATEST($maxLevel, $dbField), $dbField + ".$row[$external->dbFieldName].")";
    }
  }

  foreach ($GLOBALS['resourceTypeList'] AS $resource) {
    if ($row[$resource->dbFieldName] > 0) {
      $dbField    = $resource->dbFieldName;
      $maxLevel   = round(eval('return '.formula_parseToPHP("{$resource->maxLevel};", '$caveData')));
      $presents[] = "$dbField = LEAST($maxLevel, $dbField + ".$row[$resource->dbFieldName].")";
    }
  }

  foreach ($GLOBALS['unitTypeList'] AS $unit) {
    if ($row[$unit->dbFieldName] > 0) {
      $dbField    = $unit->dbFieldName;
      $presents[] = "$dbField = $dbField + " . $row[$unit->dbFieldName];
    }
  }

  if (sizeof($presents)) {
    // UPDATE Cave
    $sql = $db->prepare("UPDATE ". CAVE_TABLE ."
                         SET " . implode(", ", $presents) . "
                         WHERE caveID = :caveID
                           AND playerID = :playerID");
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    if (!$sql->execute()) {
      return _('Probleme beim UPDATE des Geschenks.');
    }

    // UPDATE Questionnaire_presents
    $sql = $db->prepare("UPDATE ". QUESTIONNAIRE_PRESENTS_TABLE ."
                         SET use_count = use_count + 1
                         WHERE presentID = :presentID");
    $sql->bindValue('presentID', $presentID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return _('Probleme beim UPDATE des Geschenks.');
    }

    // Höhle auffrischen
    $ownCaves[$caveID] = getCaveSecure($caveID, $_SESSION['player']->playerID);

    return _('Eure Geschenke sind nun in eurer Höhle!');
  }

  return _('Danke für die Schnecken!');
}

?>