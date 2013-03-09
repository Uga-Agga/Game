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
      $sql = $db->prepare("REPLACE " . CHAT_USER_TABLE . "
                           (roomID, name, deleted)
                           VALUES (:roomID, :name, 0)");
      foreach ($playerNames as $name) {
        $sql->bindValue('roomID', $roomID, PDO::PARAM_INT);
        $sql->bindValue('name', strlower($name), PDO::PARAM_STR);
        $sql->execute();
      }
      if (!$db->commit()) return false;
    } else {
      $sql = $db->prepare("REPLACE " . CHAT_USER_TABLE . "
                           (roomID, name, deleted)
                           VALUES (:roomID, :name, 0)");
      $sql->bindValue('roomID', $roomID, PDO::PARAM_INT);
      $sql->bindValue('name', strlower($playerNames), PDO::PARAM_STR);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }
    }

    return true;
  }

  public static function authAddTribe($roomID, $tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $playerNames = array();
    $sql = $db->prepare("SELECT playerID, jabberName
                         FROM " . PLAYER_TABLE . "
                         WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $playerNames[$row['playerID']] = $row['jabberName'];
    }
    $sql->closeCursor();

    if (!empty($playerNames)) {
      return self::authAdd($roomID, $playerNames);
    }

    return false;
  }

  public static function authDel($roomID, $IDs) {
    global $db;

    if (empty($IDs)) return false;

    if (is_array($IDs)) {
      $db->beginTransaction();
      $sql = $db->prepare("UPDATE " . CHAT_USER_TABLE . "
                           SET deleted = 1, success = 0
                           WHERE roomID = :roomID
                             AND id = :id");
      foreach ($IDs as $id) {
        $sql->bindValue('roomID', $roomID, PDO::PARAM_INT);
        $sql->bindValue('id', $id, PDO::PARAM_STR);
        $sql->execute();
      }
      if (!$db->commit()) return false;

    } else {
      $sql = $db->prepare("UPDATE " . CHAT_USER_TABLE . "
                           SET deleted = 1, success = 0
                           WHERE roomID = :roomID
                             AND id = :id");
      $sql->bindValue('roomID', $roomID, PDO::PARAM_INT);
      $sql->bindValue('id', $IDs, PDO::PARAM_INT);
      if (!$sql->execute()) return false;
    }

    return true;
  }

  public static function checkUserExists($user){
    global $db;

    if (empty($user)) return false;

    $sql = $db->prepare("SELECT jabberName
                         FROM " . PLAYER_TABLE . "
                         WHERE jabberName = :jabberName");
    $sql->bindValue('jabberName', $user, PDO::PARAM_STR);
    if (!$sql->execute()) return false;

    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (empty($row)) {
      return false;
    }

    return true;
  }

  public static function userAdd($user) {
    global $db;

    if (empty($user)) return false;

    $sql = $db->prepare("INSERT INTO " . CHAT_QUEUE_TABLE . "
                         (type, user)
                         VALUES('userAdd', :name)");
    $sql->bindValue('name', $user, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
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

    if (empty($tribeID) || empty($tag) || empty($name)) return false;

    $sql = $db->prepare("INSERT INTO " . CHAT_ROOM_TABLE . "
                         (tribeID, tag, name, autojoin)
                         VALUES(:tribeID, :tag, :name, :autojoin)");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->bindValue('tag', strtolower ($tag), PDO::PARAM_STR);
    $sql->bindValue('name', $name, PDO::PARAM_STR);
    $sql->bindValue('autojoin', $autojoin, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return $db->lastInsertId();
  }

  public static function tribeDel($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $rooms = self::getRoomsByTribeID($tribeID);

    $db->beginTransaction();
    $sql = $db->prepare("DELETE FROM " . CHAT_USER_TABLE . " WHERE roomID = :roomID");
    foreach ($rooms as $room) {
      $sql->bindValue('roomID', $room['id'], PDO::PARAM_INT);
      $sql->execute();
    }
    if (!$db->commit()) return false;

    $sql = $db->prepare("UPDATE " . CHAT_ROOM_TABLE . " SET deleted = 1, success = 0 WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
  }

  public static function tribeInit($tribeID, $tag, $playerName) {
    global $db;

    if (empty($tribeID) || empty($tag)) return false;

    $rommID = self::tribeAdd($tribeID, "{$tag}", "[{$tag}] Haupthöhle", true);
    self::authAdd($rommID, $playerName);

    $rommID = self::tribeAdd($tribeID, "{$tag}1", "[{$tag}] Nebenhöhle 1");
    self::authAdd($rommID, $playerName);

    $rommID = self::tribeAdd($tribeID, "{$tag}2", "[{$tag}] Nebenhöhle 2");
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

    if (empty($tribeID) || empty($playerName)) return false;

    $rooms = self::getRoomsByTribeID($tribeID);

    $db->beginTransaction();
    $sql = $db->prepare("UPDATE " . CHAT_USER_TABLE . "
                         SET deleted = 1, success = 0
                         WHERE roomID = :roomID
                           AND name = :name");
    foreach ($rooms as $room) {
      $sql->bindValue('roomID', $room['id'], PDO::PARAM_INT);
      $sql->bindValue('name', $playerName, PDO::PARAM_STR);
      $sql->execute();
    }
    if (!$db->commit()) return false;
  }

  public static function getRoomsByPlayerID($playerID) {
    global $db;

    if (empty($playerID)) return array();

    $rooms = array();
    $sql = $db->prepare("SELECT cr.tag, cr.autojoin
                         FROM " . CHAT_ROOM_TABLE . " cr
                           LEFT JOIN " . CHAT_USER_TABLE . " cu ON cu.roomID = cr.id
                           LEFT JOIN " . PLAYER_TABLE . " p ON p.jabberName LIKE cu.name
                         WHERE cr.success = 1
                           AND cu.success = 1
                           AND p.playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $rooms[] = $row;
    }
    $sql->closeCursor();

    return $rooms;
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

  public static function getUsersByTribeID($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $user = array();
    $sql = $db->prepare("SELECT c.*
                         FROM " . CHAT_USER_TABLE . " c
                           LEFT JOIN " . CHAT_ROOM_TABLE . " r ON r.id = c.roomID
                         WHERE r.tribeID = :tribeID
                           AND c.deleted = 0");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $user[$row['roomID']][$row['id']] = $row;
    }
    $sql->closeCursor();

    return $user;
  }

  public static function isTagFree($tag){
    global $db;

    if (empty($tag)) return false;

    $sql = $db->prepare("SELECT count(*) as count
                         FROM " . CHAT_ROOM_TABLE . "
                         WHERE tag = :tag");
    $sql->bindValue('tag', strtolower ($tag), PDO::PARAM_STR);
    if (!$sql->execute()) return false;

    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if ($row['count'] == 0) {
      return true;
    }

    return false;
  }
}

?>