<?php
/*
 * questionnaire.html.php -
 * Copyright (c) 2003  OGP Team,
 * Copyright (c) 2011 Sascha Lange <salange@uos.de>
 * Copyright (c) 2012-2013 David Unger <unger.dave@gmail.com>
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

  // open template
  $template->setFile('questionnaire.tmpl');
  $template->setShowResource(false);

  //messages
  $messageText = array (
   -5 => array('type' => 'error', 'message' => _('Probleme beim Eintragen der Bonuspunkte. Bitte wende dich ans UgaAgga Team.')),
   -4 => array('type' => 'error', 'message' => _('Beim eintragen der Antworten gab es leider Probleme. Bitte probiere es später nochmals.')),
   -3 => array('type' => 'error', 'message' => _('Fehler beim auslesen der Fragen. Bitte probiere es später nochmals.')),
   -2 => array('type' => 'error', 'message' => _('Ich bin mir sicher das ich die solche Fragen nicht gestellt habe!')),
   -1 => array('type' => 'error', 'message' => _('Du hast keine Frage beantwortet.')),
    1 => array('type' => 'success', 'message' => _('Deine Fragen wurden erfolgreich eingetragen und du hast deine Schnecken bekommen.')),
  );

  $action = Request::getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* eintragen der Antworten
*
****************************************************************************************************/
    case 'answers':
      $answers = Request::getVar('question', array('' => ''));
      if (empty($answers)) {
        $messageID = -1;
      }

      $messageID = questionnaire_giveAnswers($answers);
    break;
  }

  // fragen auslesen
  $questions = questionnaire_getQuestions();

  // show my credits
  $credits = questionnaire_getCredits($_SESSION['player']->questionCredits);

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'credits'    => $credits,
    'questions'  => $questions,
    'status_msg' => (isset($messageID)) ? $messageText[$messageID] : '',
  ));
}

function questionnaire_getQuestionnairePresents($caveID, &$ownCaves) {
  global $template;

  // open template
  $template->setFile('questionnairePresents.tmpl');
  $template->setShowResource(false);

  //messages
  $messageText = array(
    -5 => array('type' => 'error', 'message' => _('Ich bin mit dem Schnecken abzählen durcheinander gekommen, Häuptling! Versucht es noch einmal!')),
    -4 => array('type' => 'error', 'message' => _('Ihr habt nicht die passenden Schnecken, Häuptling!"')),
    -3 => array('type' => 'error', 'message' => _('Dieses Geschenk kann ich euch nicht anbieten, Häuptling!')),
    -2 => array('type' => 'error', 'message' => _('Datenbankfehler. Bitte versuche es später nochmals.')),
    -1 => array('type' => 'error', 'message' => _('Du hast keine Belognung ausgewählt.')),
     1 => array('type' => 'success', 'message' => _('Eure Geschenke sind nun in eurer Höhle!')),
     2 => array('type' => 'info', 'message' => _('Danke für die Schnecken!'))
  );

  $action = Request::getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Gescenke abholen
*
****************************************************************************************************/
    case 'present':
      $presentID = Request::getVar('presentID', 0);
      $messageID = questionnaire_getPresent($caveID, $ownCaves, $presentID);
    break;
  }

  // geschenke auslesen
  $presents = questionnaire_getPresents();

  // show my credits
  $credits = questionnaire_getCredits($_SESSION['player']->questionCredits);

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'credits'    => $credits,
    'presents'   => $presents,
    'status_msg' => (isset($messageID)) ? $messageText[$messageID] : '',
  ));
}

function questionnaire_getQuestions() {
  global $db;

  // get possible questions
  $questions = array();
  $sql = $db->prepare("SELECT q.*
                       FROM " . QUESTIONNAIRE_QUESTIONS_TABLE . " q
                         LEFT JOIN ". QUESTIONNAIRE_ANSWERS_TABLE ." qa
                            on qa.questionID = q.questionID
                       WHERE expiresOn > NOW() + 0
                        AND qa.choiceID IS NULL
                       ORDER BY questionID ASC");
  if (!$sql->execute()) return array();

  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $questions[$row['questionID']] = $row;
  }
  $sql->closeCursor();

  if (empty($questions)) {
    return array();
  }

  $sql = $db->prepare("SELECT * FROM ". QUESTIONNAIRE_CHOISES_TABLE ."
                       WHERE questionID IN (". implode(", ", array_map(array($db, 'quote'), array_keys($questions))) . ")
                       ORDER BY choiceID ASC");

  if ($sql->rowCountSelect() == 0) return array();
  if (!$sql->execute()) return array();

  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (isset($question[$row['questionID']]['choice'])) {
      $questions[$row['questionID']]['choice'] = array();
    }

    $questions[$row['questionID']]['choice'][$row['choiceID']] = $row;
  }
  $sql->closeCursor();

  return $questions;
}

function questionnaire_getPresents() {
  global $db;

  $sql = $db->prepare("SELECT *
                       FROM ". QUESTIONNAIRE_PRESENTS_TABLE ."
                       ORDER BY presentID ASC");
  if (!$sql->execute()) return array();

  $presents = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (!questionnaire_timeIsRight($row)) {
      continue;
    }

    $row += questionnaire_getCredits($row['credits']);

    $externals = array();
    foreach ($GLOBALS['defenseSystemTypeList'] AS $external) {
      if ($row[$external->dbFieldName] > 0) {
        $externals[] = array(
          'amount' => $row[$external->dbFieldName],
          'name'   => $external->name);
      }
    }
    if (sizeof($externals)) $row['defenseSystem'] = $externals;

    $resources = array();
    foreach ($GLOBALS['resourceTypeList'] AS $resource) {
      if ($row[$resource->dbFieldName] > 0) {
        $resources[] = array(
          'amount' => $row[$resource->dbFieldName],
          'name'   => $resource->name);
      }
    }
    if (sizeof($resources)) $row['resource'] = $resources;

    $units = array();
    foreach ($GLOBALS['unitTypeList'] AS $unit) {
      if ($row[$unit->dbFieldName] > 0) {
        $units[] = array(
          'amount' => $row[$unit->dbFieldName],
          'name'   => $unit->name);
      }
    }
    if (sizeof($units)) $row['unit'] = $units;

    $presents[] = $row;
  }
  $sql->closeCursor();

  return $presents;
}

function questionnaire_getCredits($credits) {
  $copper = $credits % COPPERPERSILVER;
  $silver = intval($credits / COPPERPERSILVER) % SILVERPERGOLD;
  $gold   = intval($credits / SILVERPERGOLD / COPPERPERSILVER);

  $result = array('credits' => $credits);

  if (!$credits) {
    $result['copper'] = 0;
  } else {
    if ($copper) $result['copper'] = $copper;
    if ($silver) $result['silver'] = $silver;
    if ($gold)   $result['gold']   = $gold;
  }

  return $result;
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

      if (!isset($matches[5]) || $matches[5][0] != "/") {
        $matches[6] = 1; // step
      }

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

  if (empty($presentID)) {
    return -1;
  }

  $sql = $db->prepare("SELECT * FROM ". QUESTIONNAIRE_PRESENTS_TABLE ." WHERE presentID = :presentID");
  $sql->bindValue('presentID', $presentID, PDO::PARAM_INT);
  if (!$sql->execute()) return -2;

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (!questionnaire_timeIsRight($row)) {
    return -3;
  }

  // genügend Schnecken?
  $myaccount = questionnaire_getCredits($_SESSION['player']->questionCredits);
  $price     = questionnaire_getCredits($row['credits']);

  if ($myaccount['credits'] < $price['credits'])
    return -4;

  // Preis abziehen
  if (!questionnaire_addCredits(-$row['credits']))
    return -5;

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
    if (!$sql->execute()) return -2;

    // UPDATE Questionnaire_presents
    $sql = $db->prepare("UPDATE ". QUESTIONNAIRE_PRESENTS_TABLE ."
                         SET use_count = use_count + 1
                         WHERE presentID = :presentID");
    $sql->bindValue('presentID', $presentID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return -2;
    }

    // Höhle auffrischen
    $ownCaves[$caveID] = getCaveSecure($caveID, $_SESSION['player']->playerID);

    return 1;
  }

  return 2;
}

function questionnaire_giveAnswers($answers) {
  global $db;

  // filter given answers
  foreach ($answers AS $questionID => $choiceID) {
    if ($choiceID < 0) unset($answers[$questionID]);
  }

  // get valid answers
  $sql = $db->prepare("SELECT *
                       FROM ". QUESTIONNAIRE_CHOISES_TABLE ."
                       WHERE questionID IN (".implode(", ", array_map(array($db, 'quote'), array_keys($answers))).")");
  if ($sql->rowCountSelect() == 0) return -2;
  if (!$sql->execute()) return -3;

  $choises = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($choises[$row['questionID']])) {
      $choises[$row['questionID']] = array();
    }
    $choises[$row['questionID']][$row['choiceID']] = $row;
  }
  $sql->closeCursor();

  // validate given answers
  foreach ($answers AS $questionID => $choiceID) {
    if (!isset($choises[$questionID][$choiceID])) {
      unset($answers[$questionID]);
    }
  }

  // answers now contains valid answers

  // get questions
  $questions = array();
  $sql = $db->prepare("SELECT *
                       FROM ". QUESTIONNAIRE_QUESTIONS_TABLE ."
                       WHERE questionID IN (".implode(",", array_keys($answers)).")");
  if ($sql->rowCountSelect() == 0) return -2;
  if (!$sql->execute()) return -3;

  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $questions[$row['questionID']] = $row;
  }
  $sql->closeCursor();

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
    if (!$sql->execute()) return -4;
    if ($sql->rowCount() != 1) {
      continue;
    }
    $sql->closeCursor();

    //$rewards += $questions[$questionID]['credits'];
    $rewards += $choises[$questionID][$choiceID]['credits'];
  }

  // now update playerstats
  if (!questionnaire_addCredits($rewards)) {
    return -5;
  }

  return 1;
}

?>