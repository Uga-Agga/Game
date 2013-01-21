<?php
/*
 * Contacts.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 * Copyright (c) 2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Model.php');

DEFINE('CONTACTS_NOERROR',               0x00);

DEFINE('CONTACTS_ERROR_NOSUCHPLAYER',    0x01);
DEFINE('CONTACTS_ERROR_MAXREACHED',      0x02);
DEFINE('CONTACTS_ERROR_INSERTFAILED',    0x03);
DEFINE('CONTACTS_ERROR_DELETEFAILED',    0x04);
DEFINE('CONTACTS_ERROR_DUPLICATE_ENTRY', 0x05);

class Contacts_Model extends Model {

  function Contacts_Model() {
  }

  function getContacts() {
    global $db;

    // init return value
    $result = array();

    // prepare query
    $sql = $db->prepare("SELECT c.*, p.name AS contactname, t.tag AS contacttribe
                         FROM ". CONTACTS_TABLE ." c 
                           LEFT JOIN ". PLAYER_TABLE ." p ON c.contactplayerID = p.playerID 
                           LEFT JOIN ". TRIBE_TABLE ." t ON t.tribeID = p.tribeID 
                         WHERE c.playerID = :playerID
                         ORDER BY contactname");
    $sql->bindParam('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    if (!$sql->execute()) {
      return array();
    }

    $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    $sql->closeCursor();
    return $result;
  }

  function getContact($contactID) {
    global $db;

    $sql = $db->prepare("SELECT c.*, p.name AS contactname
                         FROM ". CONTACTS_TABLE ." c
                           LEFT JOIN ". PLAYER_TABLE ." p ON c.contactplayerID = p.playerID
                         WHERE c.playerID = :playerID AND c.contactID = :contactID LIMIT 1");
    $sql->bindParam('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindParam('contactID', $contactID, PDO::PARAM_INT);
    if (!$sql->execute()) {
      return array();
    }

    $result = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();
    return $result;
  }

  function addContact($name) {
    global $db;

    if (empty($name)) return CONTACTS_ERROR_NOSUCHPLAYER;

    // check username
    $player = getPlayerByName($name);

    // no such player or diplicate entry
    if (!$player) {
      return CONTACTS_ERROR_NOSUCHPLAYER;
    } else {
      $sql = $db->prepare("SELECT *
                           FROM ". CONTACTS_TABLE ."
                           WHERE contactplayerID = :contactplayerID
                             AND playerID = :playerID");
      $sql->bindValue('contactplayerID', $player['playerID'], PDO::PARAM_INT);
      $sql->bindParam('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

      if (!$sql->execute()) {
        return CONTACTS_ERROR_INSERTFAILED;
      }

      if ($sql->fetch(PDO::FETCH_ASSOC)) {
        return CONTACTS_ERROR_DUPLICATE_ENTRY;
      }
    }

    // no more than CONTACTS_MAX should be inserted
    if (sizeof($this->getContacts()) >= CONTACTS_MAX)
      return CONTACTS_ERROR_MAXREACHED;
      
    // insert player
    $sql = $db->prepare("INSERT INTO ". CONTACTS_TABLE ." (playerID, contactplayerID)
                         VALUES (:playerID, :contactID)");
    $sql->bindParam('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindParam('contactID', $player['playerID'], PDO::PARAM_INT);
    
    if (!$sql->execute()) {
      return CONTACTS_ERROR_INSERTFAILED;
    }

    return CONTACTS_NOERROR;
  }

  function deleteContact($contactID) {
    global $db;

    // prepare query
    $sql = $db->prepare("DELETE FROM ". CONTACTS_TABLE ." WHERE playerID = :playerID
                         AND contactID = :contactID");
    $sql->bindParam('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindParam('contactID', $contactID, PDO::PARAM_INT);

    return $sql->execute() == 1 ? CONTACTS_NOERROR : CONTACTS_ERROR_DELETEFAILED;
  }
}

?>