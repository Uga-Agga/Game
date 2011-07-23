<?php
/*
 * Suggestions.php -
 * Copyright (c) 2004  Marcus Lunzenauer/Johannes Roessel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Model.php');

DEFINE('SUGGESTIONS_NOERROR',             0x00);

DEFINE('SUGGESTIONS_ERROR_INSERTFAILED',  0x01);

class Suggestions_Model extends Model {

  function Suggestions_Model() {
  }

  function addSuggestion($suggestion) {
    global $db;

    // insert suggestion
    $sql = $db->prepare("INSERT INTO ". SUGGESTIONS_TABLE ." 
                         (playerID, Suggestion) 
                         VALUES (:playerID, :suggestion)");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('suggestion', addslashes($suggestion), PDO::PARAM_STR);
    
    if (!$sql->execute())
      return SUGGESTIONS_ERROR_INSERTFAILED;
    // refresh number of used suggestion credits
    $sql = $db->prepare("UPDATE ". PLAYER_TABLE ."
                         SET suggestion_credits = suggestion_credits + 1
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    
    if (!$sql->execute())
      return SUGGESTIONS_ERROR_INSERTFAILED;

    return SUGGESTIONS_NOERROR;
  }

  function getCount() {
    global $db;

    $retval = NULL;

    $sql = $db->prepare("SELECT suggestion_credits 
                         FROM ". PLAYER_TABLE ."
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

    if ($sql->execute())
      $retval = $sql->fetch(PDO::FETCH_ASSOC);

    return $retval['suggestion_credits'];
  }
}

?>