<?php
/*
 * Module_Questionnaire.lib.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function getQuestions($db){
  $questions = array();
  $query = "SELECT * FROM Questionnaire_questions ORDER BY questionID DESC";
  if (!($result = $db->query($query))) die("Datenbankfehler:" . mysql_error());
  if ($result->isEmpty()) return array();
  while ($row = $result->nextRow(MYSQL_ASSOC))
    $questions[$row['questionID']] = $row;
  return $questions;
}

function getReport($db, $question){

  $sql = 'SELECT COUNT(*) AS absolute, c.choiceText '.
         'FROM Questionnaire_answers a '.
         'LEFT JOIN Questionnaire_choices c '.
         'ON a.choiceID = c.choiceID AND a.questionID = c.questionID '.
         'WHERE a.questionID = '.$question['questionID'].' '.
         'GROUP BY a.choiceID '.
         'ORDER BY a.choiceID ASC';

  $answers = array();
  if (!($result = $db->query($sql))) die("Datenbankfehler:" . mysql_error());
  if ($result->isEmpty()) return array();

  $num_answers = 0;
  while ($row = $result->nextRow(MYSQL_ASSOC)){
    $answers[] = $row;
    $num_answers += $row['absolute'];
  }

  foreach ($answers AS $key => $value){
    $answers[$key]['percent']  = round($value['absolute'] / $num_answers * 100, 2);
    $answers[$key]['barwidth'] = 4 * (int)$answers[$key]['percent'];
  }

  return array('questionText' => $question['questionText'],
               'voters'       => $num_answers,
               'CHOICE'       => $answers);
}

?>