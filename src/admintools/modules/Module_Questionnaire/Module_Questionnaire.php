<?php
/*
 * Module_Questionnaire.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("Module_Base.lib.php");
require_once("Menu.lib.php");
require_once("Menu_Item.lib.php");
require_once("Module_Questionnaire.lib.php");

class Module_Questionnaire extends Module_Base {

  function Module_Questionnaire(){
    $this->modi[] = 'questionnaire_create';
    $this->modi[] = 'questionnaire_report';
    $this->modi[] = 'questionnaire_cross';
    $this->modi[] = 'questionnaire_snails';
  }

  function getContent($modus){

    global $db_game, $params;

    $content = "";
    switch ($modus){
      case 'questionnaire_create':

        $template = tmpl_open("modules/Module_Questionnaire/templates/create.ihtml");

        // Form Submitted
        if (isset($params->creator)){

          $laufzeit = max(0, intval($params->laufzeit));
          $credits  = max(0, intval($params->credits));

          $choices = array();
          for ($i = 1; $i <= sizeof($params->choiceID); ++$i){
            if ($params->choiceID[$i] == "") break;
            $choices[$i] = strval($params->choiceID[$i]);
          }

          if (!sizeof($choices)){
            tmpl_set($template, "MESSAGE/message", "Keine Antworten!");
          } else {
            $query = "INSERT INTO Questionnaire_questions ".
                     "(questionText, expiresOn, credits) ".
                     "VALUES ('{$params->questionText}', ".
                     "(NOW() + INTERVAL $laufzeit DAY) + 0, $credits)";

            if (!$db_game->query($query)) die("Datenbankfehler beim Eintragen der Frage!");

            $questionID = $db_game->insertID();

            foreach ($choices AS $key => $value){
              $query = "INSERT INTO Questionnaire_choices ".
                       "(questionID, choiceID, choiceText) ".
                       "VALUES ($questionID, $key, '$value')";
              if (!$db_game->query($query)) die("Datenbankfehler beim Eintragen der Antwort [$key]!");
            }
            tmpl_set($template, "MESSAGE/message", "Frage eingetragen!");
          }
        }
        // just show it
        else {

          $numChoices = intval(3 + $params->moreChoices);

          tmpl_set($template, "FORM/moreChoices", $numChoices);

          for ($i = 1; $i <= $numChoices; ++$i){
            tmpl_iterate($template, 'FORM/CHOICE');
            tmpl_set($template, 'FORM/CHOICE/choiceID', $i);
          }
        }

        $content = tmpl_parse($template);
        break;

      case 'questionnaire_report':
        $template = tmpl_open("modules/Module_Questionnaire/templates/report.ihtml");

        if (sizeof($questions = getQuestions($db_game)))
          tmpl_set($template, 'QUESTIONS/QUESTION', $questions);
        if (isset($params->questionID))
          if (sizeof($report = getReport($db_game, $questions[$params->questionID]))){
            tmpl_set($template, 'RESULT', $report);
          }
        $content = tmpl_parse($template);
        break;

      case 'questionnaire_snails':
        $template = tmpl_open("modules/Module_Questionnaire/templates/snails.ihtml");

        // Form Submitted
        if (isset($params->creator)){

          // TODO snailNumber checken
          $credits = (int)$params->snailNumber;

          // TODO Namen checken.. zB auf %
          $player  = ($params->snailPlayer);

          $query = "UPDATE Player ".
                   "SET questionCredits = questionCredits + $credits ".
                   "WHERE name = '$player' LIMIT 1";
          if (!$db_game->query($query))
            die("Datenbankfehler beim Eintragen der Schnecken!: $query");

          if ($db_game->affected_rows() == 1)
            tmpl_set($template, 'MESSAGE/message', "'$player' got $credits more snails.");
          else
            tmpl_set($template, 'MESSAGE/message', "No such player: '$player' ?");
        }

        $content = tmpl_parse($template);
        break;

      case 'questionnaire_cross':
        $template = tmpl_open("modules/Module_Questionnaire/templates/cross.ihtml");

        // Form Submitted
        if (isset($params->crossing)){
          $sql = "SELECT a1.choiceID, a2.choiceID, COUNT(*) ".
                 "FROM Questionnaire_answers a1 ".
                 "LEFT JOIN Questionnaire_answers a2 ".
                 "ON a1.playerID = a2.playerID AND ".
                 "a2.questionID = 10 ".
                 "WHERE a1.questionID = 20 GROUP BY a1.choiceID, a2.choiceID";
        }

        if (sizeof($questions = getQuestions($db_game))){
          tmpl_set($template, 'QUESTIONONE', $questions);
          tmpl_set($template, 'QUESTIONTWO', $questions);
        }

        $content = tmpl_parse($template);
        break;
    }
    return $content;
  }

  function getMenu(){
    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=questionnaire_create", "create"));
    $menu->addItem(new Menu_Item("?modus=questionnaire_report", "report"));
    $menu->addItem(new Menu_Item("?modus=questionnaire_snails", "snails"));
    return $menu->getMenu();
  }

  function getName(){
    return "Questionnaire";
  }
}
?>