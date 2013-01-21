<?php
/*
 * chat.inc.php -
 * Copyright (c) 2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

class Chat {
  public static function authAdd($roomID, $playerNames) {
    global $db;

    if (empty($playerNames)) return false;

    if (is_array($playerNames)) {
      $db->beginTransaction();
      $sql = $db->prepare("INSERT INTO " . CHAT_USER_TABLE . " 
                           (roomID, name)
                           VALUES (:roomID, :name)");
      foreach ($users as $user) {
        $sql->bindValue('roomID', $roomID, PDO::PARAM_INT);
        $sql->bindValue('name', $playerNames, PDO::PARAM_STR);
        $sql->execute();
      }

      if (!$db->commit()) {
        return false;
      }
    } else {
      $sql = $db->prepare("INSERT INTO " . CHAT_USER_TABLE . "
                           (roomID, name)
                           VALUES (:roomID, :name)");
      $sql->bindValue('roomID', $roomID, PDO::PARAM_INT);
      $sql->bindValue('name', $playerNames, PDO::PARAM_STR);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }
    }

    return true;
  }

  public static function authDel($roomID, $playerNames) {
    global $db;

    if (empty($playerNames)) return false;

    if (is_array($playerNames)) {
      $db->beginTransaction();
      $sql = $db->prepare("UPDATE " . CHAT_USER_TABLE . "
                           SET deleted = 1
                           WHERE roomID = :roomID
                             AND name = :name");
      foreach ($users as $user) {
      $sql->bindValue('roomID', $roomID, PDO::PARAM_INT);
      $sql->bindValue('user', $playerNames, PDO::PARAM_STR);
        $sql->execute();
      }

      if (!$db->commit()) {
        return false;
      }
    } else {
      $sql = $db->prepare("UPDATE " . CHAT_USER_TABLE . "
                           SET deleted = 1
                           WHERE roomID = :roomID
                             AND name = :name");
      $sql->bindValue('roomID', $roomID, PDO::PARAM_INT);
      $sql->bindValue('user', $playerNames, PDO::PARAM_STR);
      if (!$sql->execute()) {
        return false;
      }
    }

    return true;
  }

  public static function userAdd($playerName) {
    global $db;

    if (empty($playerName)) return false;

    $sql = $db->prepare("INSERT INTO " . CHAT_QUEUE_TABLE . " 
                         (type, user)
                         VALUES('userAdd', :name)");
    $sql->bindValue('name', $playerName, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }
  }

  public static function userDel($user) {
    global $db;

    if (empty($user)) return false;

    $sql = $db->prepare("INSERT INTO " . CHAT_QUEUE_TABLE . " 
                         (type, user)
                         VALUES('userDel', :name)");
    $sql->bindValue('name', $users, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }
  }

  public static function tribeAdd($tribeID, $tag, $name, $autojoin=false) {
    global $db;

    if (empty($tribeID) || empty($tag)) return false;

    $sql = $db->prepare("INSERT INTO " . CHAT_ROOM_TABLE . " 
                         (tribeID, tag, name, autojoin)
                         VALUES('tribeID', :tag, :name, :autojoin)");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->bindValue('name', $tag, PDO::PARAM_STR);
    $sql->bindValue('tribeID', $autojoin, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return $sql->lastInsertId();
  }

  public static function tribeDel($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $rooms = self::getRoomsByTribeID($tribeID);

    $db->beginTransaction();
    $sql = $db->prepare("UPDATE " . CHAT_USER_TABLE . "
                         SET deleted = 1
                         WHERE roomID = :roomID");
    foreach ($rooms as $room) {
      $sql->bindValue('roomID', $room['id'], PDO::PARAM_INT);
      $sql->bindValue('name', $playerName, PDO::PARAM_STR);
      $sql->execute();
    }

    if (!$db->commit()) {
      return false;
    }

    $sql = $db->prepare("UPDATE " . CHAT_ROOM_TABLE . " SET deleted = 1 WHERE AND tribeID = :tribeID");
    $sql->bindValue('tribeID', tribeID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
  }

  public static function tribeInit($tribeID, $tag, $playerName) {
    global $db;

    if (empty($tribeID) || empty($tag)) return false;

    $rommID = self::tribeAdd($tribeID, "{$tag}_1", "[{$tag}] Haupthöhle", true);
    self::authAdd($rommID, $playerName);

    $rommID = self::tribeAdd($tribeID, "{$tag}_2", "[{$tag}] Nebenhöhle 1");
    self::authAdd($rommID, $playerName);

    $rommID = self::tribeAdd($tribeID, "{$tag}_3", "[{$tag}] Nebenhöhle 2");
    self::authAdd($rommID, $playerName);
  }

  public static function tribeJoin($tribeID, $playerName) {
    global $db;

    if (empty($playerName)) return false;

    $sql = $db->prepare("SELECT *
                         FROM " . CHAT_ROOM_TABLE . "
                         WHERE tribeID = :tribeID
                           AND autojoin = 1");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();
  
    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      self::authAdd($row['id'], $playerName);
    }
    $sql->closeCursor();
  }

  public static function tribeLeave($tribeID, $playerName) {
    global $db;

    if (epty($tribeID) || empty($playerName)) return false;

    $rooms = self::getRoomsByTribeID($tribeID);

    $db->beginTransaction();
    $sql = $db->prepare("UPDATE " . CHAT_USER_TABLE . "
                         SET deleted = 1
                         WHERE roomID = :roomID
                           AND name = :name");
    foreach ($rooms as $room) {
      $sql->bindValue('roomID', $room['id'], PDO::PARAM_INT);
      $sql->bindValue('name', $playerName, PDO::PARAM_STR);
      $sql->execute();
    }

    if (!$db->commit()) {
      return false;
    }
  }

  public static function getRoomsByTribeID($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $rooms = array();
    $sql = $db->prepare("SELECT *
                         FROM " . CHAT_ROOM_TABLE . "
                         WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();
  
    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $rooms[$row['id']] = $row;
    }
    $sql->closeCursor();
    
    return $rooms;
  }

  public static function getUsersByTribeID($tribeID) {}
}
?>