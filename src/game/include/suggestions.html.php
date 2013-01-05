<?php
/*
 * suggestions.html.php -
 * Copyright (c) 2005  Marcus Lunzenauer/Johannes Roessel
 * Copyright (c) 2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function suggestions_getContent() {
  global $template;

  // open template
  $template->setFile('suggestions.tmpl');

  $messageText = array(
    -3 => array('type' => 'error', 'message' => _('Fehler beim eintragen des Vorschlags.')),
    -2 => array('type' => 'error', 'message' => _('Es wurde kein Nachrichtentext angegeben.')),
    -1 => array('type' => 'error', 'message' => _('Du hast schon die Maximalzahl an möglichen Vorschlägen erreicht.')),
     1 => array('type' => 'success', 'message' => _('Der Vorschlag wurde erfolgreich verschickt.'))
  );

  $suggestionsCount = suggestions_countSuggestion($_SESSION['player']->playerID);

  $action = Request::getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Nachricht eintragen
*
****************************************************************************************************/
    case 'add':
      if ($suggestionsCount >= SUGGESTIONS_MAX) {
        $messageID = -1;
        break;
      }

      $message = Request::getVar('inputMessage', '');
      if (empty($message)) {
        $messageID = -2;
        break;
      }

      $messageID = suggestions_addSuggestion($_SESSION['player']->playerID, $message);
      if ($messageID > 0) {
        $suggestionsCount++;
      }
    break;
  }

  $template->addVars(array(
    'max_suggestions' => ($suggestionsCount >= SUGGESTIONS_MAX) ? true : false,
    'status_msg'      => (isset($messageID)) ? $messageText[$messageID] : '',
  ));
}

function suggestions_countSuggestion($playerID) {
  global $db;

  if (empty($playerID)) return 0;

  $sql = $db->prepare("SELECT suggestion_credits
                       FROM " . PLAYER_TABLE . "
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute()) return 0;

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret['suggestion_credits'];
}

function suggestions_addSuggestion($playerID, $message) {
  global $db;

  // insert suggestion
  $sql = $db->prepare("INSERT INTO ". SUGGESTIONS_TABLE ." 
                       (playerID, Suggestion) 
                       VALUES (:playerID, :message)");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('message', $message, PDO::PARAM_STR);
  if (!$sql->execute()) return -3;
  
  // refresh number of used suggestion credits
  $sql = $db->prepare("UPDATE ". PLAYER_TABLE ."
                       SET suggestion_credits = suggestion_credits + 1
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->execute();
  
  return 1;
}

?>