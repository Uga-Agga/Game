<?php
/*
 * message.inc.php -
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

require_once('messageParser.inc.php');

class MessageClass {

  static function getMessageClasses(){
    static $result = NULL;

    if ($result === NULL){
      $result = array(//1 => _('Quest'),
                      2 => _('Sieg!'),
                      3 => _('Information'),
                      //4 => _('Einheit ausgebildet'),
                      6 => _('Handelsbericht'),
                      7 => _('Rückkehr'),
                      8 => _('Stammesnachricht'),
                      9 => _('Wunder'),
                      10 => _('Benutzernachricht'),
                      11 => _('Spionage'),
                      12 => _('Artefakt'),
                      20 => _('Niederlage!'),
                      //25 => _('Wetter'),
                      //26 => _('Stammeswunder'),
                      //27 => _('Überraschendes Ereignis'),
                      28 => _('Held'),
                      29 => _('Missionierung'),
                      99 => _('Uga-Agga Team'),
                      // special message class: can't be deleted, everybody can see
                      1001 => _('<b>ANKÜNDIGUNG</b>'));
    }

    return $result;
  }
}

class Messages extends Parser {
  var $MessageClass;

  function __construct() {

    $this->MessageClass = MessageClass::getMessageClasses();
    
    parent::__construct();
  }

  /**
   *
   */
  public function getIncomingMessagesCount($messageClass = -2) {
    global $db;

    if ($messageClass >= 0) {
      $sql = $db->prepare("SELECT COUNT(*) as num
                           FROM ". MESSAGE_TABLE . "
                           WHERE recipientID = :playerID
                             AND recipientDeleted != 1
                             AND messageClass = :messageClass");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
    } else {
      $sql = $db->prepare("SELECT COUNT(*) as num
                           FROM ". MESSAGE_TABLE . "
                           WHERE recipientID = :playerID
                             AND recipientDeleted != 1");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return 0;
    }
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $row['num'];
  }

  /**
   *
   */
  public function getOutgoingMessagesCount($messageClass = -2) {
    global $db;

    if ($messageClass >= 0) {
      $sql = $db->prepare("SELECT COUNT(*) as num
                           FROM ". MESSAGE_TABLE ."
                           WHERE senderID = :playerID
                             AND senderDeleted != 1
                             AND messageClass = :messageClass");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
      
    } else {
      $sql = $db->prepare("SELECT COUNT(*) as num
                           FROM ". MESSAGE_TABLE ."
                           WHERE senderID = :playerID
                             AND senderDeleted != 1");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return 0;
    }
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $row['num'];
  }

  /**
   *
   */
  public function getTrashMessagesCount($messageClass = -2) {
    global $db;

    if ($messageClass >= 0) {
      $sql = $db->prepare("SELECT COUNT(*) as num
                           FROM ". MESSAGE_TABLE ."
                           WHERE recipientID = :playerID
                             AND recipientDeleted = 1
                             AND messageClass = :messageClass");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
      
    } else {
      $sql = $db->prepare("SELECT COUNT(*) as num
                           FROM ". MESSAGE_TABLE ."
                           WHERE recipientID = :playerID
                             AND recipientDeleted = 1");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return 0;
    }
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $row['num'];
  }

  /**
   *
   */
  public function getMessagesCount() {
    global $db;

    $sql = $db->prepare("SELECT COUNT(*) as num
                         FROM " . MESSAGE_TABLE . "
                         WHERE senderID = :playerID");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

    if (!$sql->execute()) {
      return 0;
    }
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $row['num'];
  }

  /**
   * returns list of incoming massage IDs
   */
  public function getIncomingIdList($messageClass = -2) {
    global $db;

    if ($messageClass >= 0) {
      $sql = $db->prepare("SELECT messageID
                           FROM ". MESSAGE_TABLE . "
                           WHERE recipientID = :playerID
                             AND recipientDeleted != 1
                             AND messageClass = :messageClass
                           ORDER BY messageID DESC");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
      
    } else {
      $sql = $db->prepare("SELECT messageID
                           FROM ". MESSAGE_TABLE . "
                           WHERE recipientID = :playerID
                             AND recipientDeleted != 1
                           ORDER BY messageID DESC");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return array();
    }

    $i=0;
    $messageIDList = array();
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $messageIDList[$i] = $row['messageID'];
      $i++;
    }
    $sql->closeCursor();

    return $messageIDList;
  }

  /**
   * returns list of outgoing message IDs
   */
  public function getOutgoingIdList($messageClass = -2) {
    global $db;

    if ($messageClass >= 0) {
      $sql = $db->prepare("SELECT messageID
                           FROM ". MESSAGE_TABLE . "
                           WHERE senderID = :playerID
                             AND senderDeleted != 1
                             AND messageClass = :messageClass
                           ORDER BY messageID DESC");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
    } else {
      $sql = $db->prepare("SELECT messageID
                           FROM ". MESSAGE_TABLE . "
                           WHERE senderID = :playerID
                             AND senderDeleted != 1
                           ORDER BY messageID DESC");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return array();
    }

    $i=0;
    $messageIDList = array();
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $messageIDList[$i] = $row['messageID'];
      $i++;
    }
    $sql->closeCursor();

    return $messageIDList;
  }

  /**
   * returns list of incoming massage IDs
   */
  public function getTrashIdList($messageClass = -2) {
    global $db;

    if ($messageClass >= 0) {
      $sql = $db->prepare("SELECT messageID
                           FROM ". MESSAGE_TABLE . "
                           WHERE recipientID = :playerID
                             AND recipientDeleted = 1
                             AND messageClass = :messageClass
                           ORDER BY messageID DESC");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
      
    } else {
      $sql = $db->prepare("SELECT messageID
                           FROM ". MESSAGE_TABLE . "
                           WHERE recipientID = :playerID
                             AND recipientDeleted = 1
                           ORDER BY messageID DESC");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return array();
    }

    $i=0;
    $messageIDList = array();
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $messageIDList[$i] = $row['messageID'];
      $i++;
    }
    $sql->closeCursor();

    return $messageIDList;
  }

  /**
   *
   */
  public function getIncomingMessages($offset, $row_count, $messageClass = -2) {
    global $db;

    $nachrichten = array();

    // get announcements
    $sql = $db->prepare("SELECT m.messageID, p.name, m.messageClass, m.messageSubject AS subject, m.messageTime
                         FROM ". MESSAGE_TABLE ." m
                           LEFT JOIN ". PLAYER_TABLE ." p
                             ON p.playerID = m.senderID 
                         WHERE messageClass = 1001 
                         ORDER BY m.messageTime DESC, m.messageID DESC");
    if (!$sql->execute()) {
      return array();
    }

    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $row['absender_empfaenger'] = empty($row['name']) ? _('System') : $row['name'];
      $t = $row['messageTime'];
      $row['datum'] = $t{6}.$t{7}  .".".
                      $t{4}.$t{5}  .".".
                      $t{2}.$t{3}  ." ".
                      $t{8}.$t{9}  .":".
                      $t{10}.$t{11}.":".
                      $t{12}.$t{13};
      $row['nachrichtenart'] = $this->MessageClass[$row['messageClass']];
      $row['linkparams'] = '?modus=' . MESSAGE_READ . '&amp;messageID=' . $row['messageID'] . '&amp;box=' . BOX_INCOMING . '&amp;filter=' .$messageClass;
      $nachrichten[] = $row;
    }
    $sql->closeCursor();

    // get user messages
    if ($messageClass>= 0) {
      $sql = $db->prepare("SELECT m.messageID, p.name, m.messageClass, m.messageSubject AS subject,  m.messageTime, SIGN(m.read) as `read`
                           FROM ". MESSAGE_TABLE . " m 
                             LEFT JOIN ". PLAYER_TABLE ." p 
                               ON p.playerID = m.senderID 
                           WHERE recipientID = :playerID
                             AND recipientDeleted != 1 
                             AND messageClass = :messageClass
                           ORDER BY m.messageTime DESC, m.messageID DESC 
                           LIMIT :offset, :rowCount");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
      $sql->bindValue('offset', intval($offset), pDO::PARAM_INT);
      $sql->bindValue('rowCount', intval($row_count), PDO::PARAM_INT);
    } else {
      $sql = $db->prepare("SELECT m.messageID, p.name, m.messageClass, m.messageSubject AS subject, m.messageTime, SIGN(m.read) as `read`
                           FROM ". MESSAGE_TABLE . " m 
                             LEFT JOIN ". PLAYER_TABLE ." p 
                               ON p.playerID = m.senderID 
                           WHERE recipientID = :playerID
                             AND recipientDeleted != 1 
                           ORDER BY m.messageTime DESC, m.messageID DESC 
                           LIMIT :offset, :rowCount");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('offset', intval($offset), pDO::PARAM_INT);
      $sql->bindValue('rowCount', intval($row_count), PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return array();
    }

    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $row['absender_empfaenger'] = empty($row['name']) ? _('System') : $row['name'];
      $t = $row['messageTime'];
      $row['datum'] = $t{6}.$t{7}  .".".
                      $t{4}.$t{5}  .".".
                      $t{2}.$t{3}  ." ".
                      $t{8}.$t{9}  .":".
                      $t{10}.$t{11}.":".
                      $t{12}.$t{13};
      $row['nachrichtenart'] = isset($this->MessageClass[$row['messageClass']]) ? $this->MessageClass[$row['messageClass']] : 'Nachricht';
      $row['linkparams'] = '?modus=' . MESSAGE_READ . '&amp;messageID=' . $row['messageID'] . '&amp;box=' . BOX_INCOMING . '&amp;filter='. $messageClass;
      $nachrichten[] = $row;
    }
    $sql->closeCursor();

    return $nachrichten;
  }

  /**
   *
   */
  public function getOutgoingMessages($offset, $row_count, $messageClass = -2) {
    global $db;

    $nachrichtenart = "";
    foreach($this->MessageClass AS $key => $value)
      $nachrichtenart .= 'WHEN ' . $key . ' THEN "' . $value . '" ';

    if ($messageClass >= 0) {
      $sql = $db->prepare("SELECT 
                          m.messageID, 
                          IFNULL(p.name, \"" . _('System') . "\") AS absender_empfaenger, 

                          CASE m.messageClass " . $nachrichtenart . "
                          ELSE \""._('unbekannte Nachrichtenart')."\"
                          END AS nachrichtenart, 

                          m.messageSubject AS subject, 
                          DATE_FORMAT(m.messageTime, '%d.%m.%y %H:%i:%s') AS datum, 
                          SIGN(m.read) as `read` 

                          FROM ". MESSAGE_TABLE . " m 
                          LEFT JOIN ".PLAYER_TABLE ." p 
                          ON p.playerID = m.recipientID 

                          WHERE senderID = :playerID
                          AND senderDeleted != 1 
                          AND messageClass = :messageClass
                          ORDER BY m.messageTime DESC 
                          LIMIT :offset, :rowCount");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
      $sql->bindValue('offset', intval($offset), pDO::PARAM_INT);
      $sql->bindValue('rowCount', intval($row_count), PDO::PARAM_INT);
    } else {
      $sql = $db->prepare("SELECT 
                          m.messageID, 
                          IFNULL(p.name, \"" . _('System') . "\") AS absender_empfaenger, 

                          CASE m.messageClass " . $nachrichtenart . "
                          ELSE \""._('unbekannte Nachrichtenart')."\"
                          END AS nachrichtenart, 

                          m.messageSubject AS subject, 
                          DATE_FORMAT(m.messageTime, '%d.%m.%y %H:%i:%s') AS datum, 
                          SIGN(m.read) as `read` 

                          FROM ". MESSAGE_TABLE . " m 
                          LEFT JOIN ".PLAYER_TABLE ." p 
                          ON p.playerID = m.recipientID 

                          WHERE senderID = :playerID
                          AND senderDeleted != 1 
                          ORDER BY m.messageTime DESC 
                          LIMIT :offset, :rowCount");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('offset', intval($offset), pDO::PARAM_INT);
      $sql->bindValue('rowCount', intval($row_count), PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return array();
    }

    $nachrichten = array();
    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $row['linkparams'] = '?modus=' . MESSAGE_READ . '&amp;messageID=' . $row['messageID'] . '&amp;box=' . BOX_OUTGOING . '&amp;filter=' .$messageClass;

      // FIXME
      unset($row['read']);

      array_push($nachrichten, $row);
    }
    $sql->closeCursor();

    return $nachrichten;
  }

  /**
   *
   */
  public function getTrashMessages($offset, $row_count, $messageClass = -2) {
    global $db;

    $nachrichten = array();

    // get user messages
    if ($messageClass >= 0) {
      $sql = $db->prepare("SELECT m.messageID, p.name, m.messageClass, m.messageSubject AS subject,  m.messageTime, SIGN(m.read) as `read`
                           FROM ". MESSAGE_TABLE . " m 
                             LEFT JOIN ". PLAYER_TABLE ." p 
                               ON p.playerID = m.senderID 
                           WHERE recipientID = :playerID
                             AND recipientDeleted = 1 
                             AND messageClass = :messageClass
                           ORDER BY m.messageTime DESC, m.messageID DESC 
                           LIMIT :offset, :rowCount");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
      $sql->bindValue('offset', intval($offset), pDO::PARAM_INT);
      $sql->bindValue('rowCount', intval($row_count), PDO::PARAM_INT);
    } else {
      $sql = $db->prepare("SELECT m.messageID, p.name, m.messageClass, m.messageSubject AS subject, m.messageTime, SIGN(m.read) as `read`
                           FROM ". MESSAGE_TABLE . " m 
                             LEFT JOIN ". PLAYER_TABLE ." p 
                               ON p.playerID = m.senderID 
                           WHERE recipientID = :playerID
                             AND recipientDeleted = 1 
                           ORDER BY m.messageTime DESC, m.messageID DESC 
                           LIMIT :offset, :rowCount");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('offset', intval($offset), pDO::PARAM_INT);
      $sql->bindValue('rowCount', intval($row_count), PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return array();
    }

    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $row['absender_empfaenger'] = empty($row['name']) ? _('System') : $row['name'];
      $t = $row['messageTime'];
      $row['datum'] = $t{6}.$t{7}  .".".
                      $t{4}.$t{5}  .".".
                      $t{2}.$t{3}  ." ".
                      $t{8}.$t{9}  .":".
                      $t{10}.$t{11}.":".
                      $t{12}.$t{13};
      $row['nachrichtenart'] = isset($this->MessageClass[$row['messageClass']]) ? $this->MessageClass[$row['messageClass']] : 'Nachricht';
      $row['linkparams'] = '?modus=' . MESSAGE_READ . '&amp;messageID=' . $row['messageID'] . '&amp;box=' . BOX_TRASH . '&amp;filter='. $messageClass;
      $row['read'] = 1;
      $nachrichten[] = $row;
    }
    $sql->closeCursor();

    return $nachrichten;
  }

  /**
   *
   */
  public function deleteMessages($messageIDs) {
    global $db;

    // delete all those IDs
    $sql = $db->prepare("UPDATE ". MESSAGE_TABLE."
                         SET senderDeleted = senderDeleted OR (senderID = :playerID),
                           recipientDeleted = recipientDeleted OR (recipientID = :playerID)
                         WHERE messageID = :ID
                           AND messageClass != 1001");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $deleted = 0;

    if(is_array($messageIDs)) {
      foreach ($messageIDs as $ID) {
        $sql->bindValue('ID', $ID, PDO::PARAM_INT);
        $sql->execute();
        $deleted++;
      } 
    } else {
        $sql->bindValue('ID', $messageIDs, PDO::PARAM_INT);
        $sql->execute();
        $deleted++;
    }

    return $deleted;
  }

  /**
   *
   */
  public function recoverMessages($messageIDs) {
    global $db;

    // delete all those IDs
    $sql = $db->prepare("UPDATE ". MESSAGE_TABLE."
                         SET senderDeleted = IF(senderID = :playerID, 0, senderDeleted),
                           recipientDeleted = IF(recipientID = :playerID, 0, recipientDeleted),
                           `read` = 0
                         WHERE messageID = :ID
                           AND messageClass != 1001");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $recover = 0;

    if(is_array($messageIDs)) {
      foreach ($messageIDs as $ID) {
        $sql->bindValue('ID', $ID, PDO::PARAM_INT);
        $sql->execute();
        $recover++;
      }
    } else {
        $sql->bindValue('ID', $messageIDs, PDO::PARAM_INT);
        $sql->execute();
        $recover++;
    }

    return $recover;
  }

  /**
   * Delete all messages of a given box.
   */
  function deleteAllMessages($boxID, $messageClass = FALSE) {
    global $db;

    if ($messageClass == -2) // no class selected
      return 0;

    switch ($boxID) {
      case BOX_INCOMING:
        $deletor = 'recipient';
        break;

      case BOX_OUTGOING:
        $deletor = 'sender';
        break;

      default:
        return 0;
    }

    // messageClass set
    if ($messageClass > 0) {
      $sql = $db->prepare("UPDATE ". MESSAGE_TABLE ." SET 
                       senderDeleted    = senderDeleted    OR (senderID    = :playerID), 
                       recipientDeleted = recipientDeleted OR (recipientID = :playerID) 
                       WHERE messageClass != 1001 
                       AND messageClass = :messageClass 
                       AND {$deletor}ID = :playerID");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageClass', $messageClass, PDO::PARAM_INT);
      
    } else {
      $sql = $db->prepare("UPDATE ". MESSAGE_TABLE ." SET 
                       senderDeleted    = senderDeleted    OR (senderID    = :playerID), 
                       recipientDeleted = recipientDeleted OR (recipientID = :playerID) 
                       WHERE messageClass != 1001
                       AND {$deletor}ID = :playerID");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    }

    if (!$sql->execute()) {
      return 0;
    }

    return $sql->rowCount();
  }

  /**
   *
   */
  public function mailAndDeleteMessages($messageIDs) {
    global $db;

    // get valid messages
    $IDs = implode($messageIDs, ", ");

    $sql  = $db->prepare("SELECT m.recipientID, m.senderID, p.name, m.messageSubject, m.messageText, DATE_FORMAT(m.messageTime, '%d.%m.%Y %H:%i:%s') AS messageTime
                          FROM ". MESSAGE_TABLE ." m
                            LEFT JOIN ". PLAYER_TABLE ." p ON
                             IF (m.recipientID = :playerID, m.senderID = p.playerID, m.recipientID = p.playerID)
                          WHERE messageID IN (" . $IDs . ") AND
                            IF (m.recipientID = :playerID, m.recipientDeleted = 0 AND m.recipientID = :playerID, m.senderDeleted = 0 AND m.senderID = :playerID)");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

    $sql->execute();
    if ($sql->rowCount() == 0) {
      return 0;
    }

    $exporter = new MessageExporter;
    while ($record = $sql->fetch(PDO::FETCH_ASSOC)) {
      $exporter->add(new Message($record));
    }
    $sql->closeCursor();
    $exporter->send($_SESSION['player']->email2);

    return $this->deleteMessages($messageIDs);
  }

  public function markAsRead($messageIDs) {
    global $db;

    //$IDs = (string) implode($messageIDs, ", ");
    $sql = $db->prepare("UPDATE ". MESSAGE_TABLE ."
                         SET `read` = `read` + 1 
                         WHERE messageID = :messageID 
                           AND messageClass != 1001 
                           AND recipientID = :playerID");
    foreach ($messageIDs as $messageID) {
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('messageID', (int) $messageID, PDO::PARAM_INT);
      if (!$sql->execute()) {
        return 0;
      }
    }
    
    return count($messageIDs);
  }

  ///////////////////////////////////////////////////////////////////////////////
  public function getMessageDetail($messageID) {
    global $db;

    $sql = $db->prepare("SELECT 
                           m.messageSubject AS subject, 
                           m.senderID AS senderID, 
                           m.recipientID AS empfaengerID, 
                           IFNULL(p.name, \"System\") AS dummy, 
                           DATE_FORMAT(m.messageTime, '%d.%m.%Y %H:%i:%s') AS datum, 
                           m.messageText AS nachricht, 
                           m.messageXML, 
                           m.messageClass AS nachrichtenart 
                         FROM ". MESSAGE_TABLE ." m 
                           LEFT JOIN ". PLAYER_TABLE . " p 
                            ON IF(:playerID = m.senderID, p.playerID = m.recipientID, p.playerID = m.senderID) 
                         WHERE messageID = :messageID 
                           AND (recipientID  = :playerID
                             OR senderID = :playerID
                             OR messageClass = 1001)");
    $sql->bindValue('playerID', $_SESSION['player']->playerID);
    $sql->bindValue('messageID', $messageID);
    
    if ($sql->rowCountSelect() == 0) return array();
    if (!$sql->execute()) {
      return array();
    }

    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if ($row['senderID'] == $_SESSION['player']->playerID) {
      $row['sender']     = $_SESSION['player']->name;
      $row['empfaenger'] = $row['dummy'];
    } else {
      $row['empfaenger'] = $_SESSION['player']->name;
      $row['sender']     = $row['dummy'];
    }
    unset($row['dummy']);

    if ($row['senderID'] != 0) {
      $row['nachricht'] = $this->p($row['nachricht']);
    }

    // mark as read
    $sql = $db->prepare("UPDATE ". MESSAGE_TABLE ."
                         SET `read` = `read` + 1
                         WHERE messageID = :messageID
                           AND recipientID = :playerID");
    $sql->bindValue('messageID', $messageID, PDO::PARAM_INT);
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->execute();

    return $row;
  }

  public function insertMessageIntoDB($recipient, $subject, $nachricht, $sender_delete=false, $isTribeMessage = false) {
    global $db;

    // wurde nur der Spielername angegeben playerID auslesen
    if (strval(intval($recipient)) !== $recipient) {
      // get Empfaenger ID
      $sql = $db->prepare("SELECT playerID FROM ". PLAYER_TABLE ." WHERE name = :recipient");
      $sql->bindValue('recipient', $recipient, PDO::PARAM_STR);
      if (!$sql->execute()) {
        return 0;
      }

      $row = $sql->fetch(PDO::FETCH_ASSOC);
      if (empty($row)) {
        return 0;
      }
      $sql->closeCursor();

      $recipient = $row['playerID'];
    }

    $sql = $db->prepare("INSERT INTO ". MESSAGE_TABLE ."
                           (recipientID,
                           senderID,
                           messageClass,
                           messageSubject,
                           messageText,
                           messageTime,
                           senderDeleted)
                         VALUES (
                           :recipientID,
                           :senderID,
                           :messageClass,
                           :messageSubject,
                           :messageText,
                           NOW()+0,
                           :senderDelete)");
    $sql->bindValue('recipientID', $recipient, PDO::PARAM_INT);
    $sql->bindValue('senderID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('messageClass', ($isTribeMessage) ? 8 : 10, PDO::PARAM_INT);
    $sql->bindValue('messageSubject', $subject, PDO::PARAM_STR);
    $sql->bindValue('messageText', $nachricht, PDO::PARAM_STR);
    $sql->bindValue('senderDelete', $sender_delete, PDO::PARAM_BOOL);
    if (!$sql->execute()) {
      return false;
    }

    return $sql->rowCount();
  }

  public function sendSystemMessage($receiverID, $type, $subject, $nachricht, $xml = "") {
    global $db;

    $sql = $db->prepare("INSERT INTO ". MESSAGE_TABLE ." (recipientID, messageClass, senderID, messageSubject, messageText, messageXML, messageTime) " .
             "VALUES (:receiverID, :type, :senderID, :subject, :message, :xml, NOW()+0)");
    $sql->bindValue('receiverID', $receiverID, PDO::PARAM_INT);
    $sql->bindValue('type', $type, PDO::PARAM_INT);
    $sql->bindValue('senderID', (int) 0, PDO::PARAM_INT);
    $sql->bindValue('subject', $subject, PDO::PARAM_STR);
    $sql->bindValue('message', $nachricht, PDO::PARAM_STR);
    $sql->bindValue('xml', $xml, PDO::PARAM_STR);
    if (!$sql->execute()) {
      return false;
    }

    return $sql->rowCount();
  }

  public function createSubject($subject) {

    $result = preg_match('/^Re(\((\d*)\))?:(.*)$/i', $subject, $sub);

    // no 'Re:'
    if ($result == 0) {
      return 'Re: ' . $subject;
    } else if (strlen($sub[1])) { // 'Re(x):'
      return sprintf('Re(%d): %s', 1 + (int)$sub[2], trim($sub[3]));
    } else { // 'Re:'
      return sprintf('Re(2): %s', trim($sub[3]));
    }
  }
}

/**
 * This class stores all properties of an ingame message.
 *
 */

class Message {

  var $sender;
  var $recipient;
  var $time;
  var $subject;
  var $text;

  function Message($record){

    $playerID = $_SESSION['player']->playerID;

    $this->sender    = ($record['senderID'] == $playerID ?
                        $_SESSION['player']->name :
                        $record['name']);
    $this->recipient = ($record['recipientID'] == $playerID ?
                        $_SESSION['player']->name :
                        $record['name']);
    $this->time      = $record['messageTime'];
    $this->subject   = $record['messageSubject'];
    $this->text      = $record['messageText'];
  }
}

/**
 * This class exports all added messages
 * TODO: has to be refactored
 *
 */

class MessageExporter {

  var $messages = array();

  function MessageExporter(){
  }

  function add($message){
    $this->messages[] = $message;
  }

  function send($recipient){

    // if there are no messages, dont do anything
    if (!sizeof($this->messages))
      return;

    // concatenate those messages
    $mail = "";
    foreach ($this->messages as $message)
      $mail .= sprintf('%s: %s<br />'.
                       '%s: %s<br />'.
                       '%s: %s<br />'.
                       '%s: %s<br />'.
                       '%s<br /><hr />',
                       _('Absender'), $message->sender,
                       _('Empfänger'), $message->recipient,
                       _('Datum'), $message->time,
                       _('subject'), $message->subject,
                       $message->text);

    // add headers
    $mail = '<html><head><meta http-equiv="Content-type" content="text/html; charset=UTF-8" /></head><body>' . $mail . '</body></html>';

    // zip it
    require_once("zip.lib.php");
    $time_now = date("YmdHis", time());
    $zipfile = new zipfile();
    $zipfile->addFile($mail, "mail.".$time_now.".html");
    $mail = $zipfile->file();

    // put mail together
    $mail_from    = "noreply@uga-agga.de";

    $filename = "mail.".$time_now.".zip";
    $filedata = chunk_split(base64_encode($mail));

    $mail_boundary = '=_' . md5(uniqid(rand()) . microtime());

    // create header
    $mime_type    = "application/zip-compressed";
    $mail_headers = "From: $mail_from\n".
                    "MIME-version: 1.0\n".
                    "Content-type: multipart/mixed; ".
                    "boundary=\"$mail_boundary\"\n".
                    "Content-transfer-encoding: 8BIT\n".
                    "X-attachments: $filename;\n\n";

    // hier fängt der normale mail-text an
    $mail_headers .= "--$mail_boundary\n".
                     "Content-Type: text/plain; charset=\"UTF-8\"\n\n".
                     _("Hallo,\n\ndu hast im Nachrichten Fenster auf den Knopf 'Mailen&löschen' gedrückt. Die dabei markierten Nachrichten werden dir nun mit dieser Email zugesandt. Um den Datenverkehr gering zu halten, wurden dabei deine Nachrichten komprimiert. Mit einschlägigen Programmen wie WinZip lässt sich diese Datei entpacken.\n\nGruß, dein UA-Team") . "\n";

    // hier fängt der datei-anhang an
    $mail_headers .= "--$mail_boundary\n".
                     "Content-type: $mime_type; name=\"$filename\";\n".
                     "Content-Transfer-Encoding: base64\n".
                     "Content-disposition: attachment; filename=\"$filename\"\n\n".
                     $filedata;

    // gibt das ende der email aus
    $mail_headers .= "\n--$mail_boundary--\n";

    // und abschicken
    mail($recipient, _('Deine Uga-Agga InGame Nachrichten'), "", $mail_headers);
  }
}

?>