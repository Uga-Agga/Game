<?php
/*
 * tribes.inc.php -
 * Copyright (c) 2004  OGP-Team
 * Copyright (c) 2012-2013 Georg Pitterle
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

class Tribe {
  const MESSAGE_WAR      = 1;
  const MESSAGE_LEADER   = 2;
  const MESSAGE_MEMBER   = 3;
  const MESSAGE_RELATION = 4;
  const MESSAGE_INFO     = 10;

  public static function calculateElo($tribeID, $winnerpoints, $loserTribeID, $loserpoints) {
    global $db;

    $faktor = 2;

    //k faktor bestimmen
    $k = 10;
    if($winnerpoints < 2400){
      $sql = $db->prepare("SELECT calculateTime 
                           FROM " . RANKING_TRIBE_TABLE . "
                           WHERE tribeID = :tribeID");
      $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
      if (!$sql->execute()) return false;

      $row = $sql->fetch(PDO::FETCH_ASSOC);
      $sql->closeCursor();

      if($res['calculateTime'] > 30) {
        $k = 15;
      } else {
        $k = 25;
      }
    }

    $eloneu = $winnerpoints + max(2,$k * $faktor * (1 - (1/(1+pow(10, ($loserpoints - $winnerpoints)/400)))));
    $sql = $db->prepare("UPDATE " . RANKING_TRIBE_TABLE . "
                         SET points_rank = :points_rank, 
                           calculateTime = calculateTime+1 
                         WHERE tribeID = :tribeID");
    $sql->bindValue('points_rank', $eloneu, PDO::PARAM_INT);
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    $k = 10;
    if($loserpoints < 2400){
      $sql = $db->prepare("SELECT calculateTime 
                           FROM " . RANKING_TRIBE_TABLE . "
                           WHERE tribeID = :tribeID");
      $sql->bindValue('tribeID', $loserTribeID, PDO::PARAM_INT);
      if (!$sql->execute()) return false;

      $row = $sql->fetch(PDO::FETCH_ASSOC);
      $sql->closeCursor();

      if($res['calculateTime'] > 30) {
        $k = 15;
      } else {
        $k = 25;
      }
    }
    $eloneu = $loserpoints + min(-2,$k * $faktor * (0 - (1/(1+pow(10, ($winnerpoints - $loserpoints)/400)))));

    $sql = $db->prepare("UPDATE " . RANKING_TRIBE_TABLE . "
                         SET points_rank = :points_rank,
                           calculateTime = calculateTime + 1
                         WHERE tribeID = :tribeID");
    $sql->bindValue('points_rank', $eloneu, PDO::PARAM_INT);
    $sql->bindValue('tribeID', $loserTribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;
  }

  public static function changeTribeAllowed($playerID) {
    global $db;

    if (empty($playerID)) return false;

    $sql = $db->prepare("SELECT (tribeBlockEnd > NOW()+0) AS blocked
                         FROM " . PLAYER_TABLE . "
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $row['blocked'] != 1;
  }

  public static function deleteTribe($tribeData, $force=false) {
    global $db;

    if (empty($tribeData)) return false;

    // GOD ALLY is not deletable
    if (!strcmp($tribeData['tag'], GOD_ALLY)) {
      return false;
    }

    $tribeID = $tribeData['tribeID'];

    // get relations
    $tribeRelations = TribeRelation::getRelations($tribeID);
    if (!empty($tribeRelations)) {
      // end others relations
      foreach ($tribeRelations['other'] as $otherTribeID => $relation){
        $relationType = $GLOBALS['relationList'][$relation['relationType']];
        $oDST = $relationType['onDeletionSwitchTo'];
        if ($oDST >= 0){
          // die relation umschalten und zielrelation temporaer eintragen; sie wird
          // am ende dieser funktion ohne weiteres umschalten geloescht. Das
          // temporaere umschalten ist aber noetig, um zum beispiel die
          // ruhmberechnung im siegfall oder aehnliche effekte, die an
          // relation_setRelation haengen abzuarbeiten.
          if (!TribeRelation::setRelation($otherTribeID, $tribeID, $oDST, 0, 0, $relation['tribe_rankingPoints'], $relation['target_rankingPoints'])) {
            return false;
          }

          // insert history
          if (isset($GLOBALS['relationList'][$oDST]['historyMessage'])){
            self::setHistory($otherTribeID, self::prepareHistoryMessage($tribeData['tag'], $relation['tag'], $GLOBALS['relationList'][$oDST]['historyMessage']));
          }

          TribeMessage::sendIntern($otherTribeID, self::MESSAGE_RELATION, sprintf(_("Haltung gegenüber %s geändert"), $tribeData['tag']), sprintf(_("Die Haltung Ihres Stammes gegenüber dem Stamm %s  wurde automatisch auf %s geändert."), $tribeData['tag'], $GLOBALS['relationList'][$oDST]['name']));
        }
      }
    }

    $members = self::getPlayerList($tribeID);
    foreach ($members AS $playerID => $playerData) {
      if (!self::tribe_leaveTribe($playerID)) {
        return false;
      }
  
      if (!self::setBlockedTime($playerID)) {
        return 0;
      }
  
      $messagesClass = new Messages;
      $messagesClass->sendSystemMessage($playerID, 8, "Auflösung des Stammes", sprintf(_("Ihr Stamm %s wurde soeben aufgelöst. Sollten Sie Probleme mit dem Stammesmenü haben, loggen Sie sich bitte neu ein."), $tribeData['tag']));

      Player::addHistoryEntry($playerID, sprintf(_("verlässt den Stamm '%s'"), $tribeData['tag']));
    }

    $sql = $db->prepare("DELETE FROM " . TRIBE_TABLE . " WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    $sql = $db->prepare("DELETE FROM " . RELATION_TABLE . " WHERE tribeID = :tribeID OR tribeID_target = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->execute();

    $sql = $db->prepare("DELETE FROM " . TRIBE_MESSAGE_TABLE . " WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->execute();

    $sql = $db->prepare("DELETE FROM " . TRIBE_HISTORY_TABLE . " WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->execute();

    $sql = $db->prepare("DELETE FROM " . ELECTION_TABLE . " WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->execute();

    $sql = $db->prepare("SELECT rank 
                         FROM ". RANKING_TRIBE_TABLE . "
                         WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if ($sql->execute()) {
      $rank = $sql->fetch(PDO::FETCH_ASSOC);
      $sql->closeCursor();
    }
  
    if (isset($rank) && !empty($rank['rank'])) {
      $sql = $db->prepare("DELETE FROM " . RANKING_TRIBE_TABLE . " WHERE tribeID = :tribeID");
      $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
      $sql->execute();
      
      $sql = $db->prepare("UPDATE " . RANKING_TRIBE_TABLE . "
                           SET rank = rank - 1
                           WHERE rank > :rank");
      $sql->bindValue('rank', $rank['rank'], PDO::PARAM_INT);
      $sql->execute();
    }

    if ($tribeData['leaderID'] != 0) {
      Player::addHistoryEntry($tribe['leaderID'], sprintf(_("löst den Stamm '%s' auf"), $tribeData['tag']));
    }

    return 1;
  }

  public static function getByID($tribeID) {
    global $db;

    if (empty($tribeID)) return null;

    $sql = $db->prepare("SELECT t.*, p.name AS leaderName
                         FROM " . TRIBE_TABLE . " t
                           LEFT JOIN " . PLAYER_TABLE . " p ON t.leaderID = p.playerID
                         WHERE t.tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return null;

    $result = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (empty($result)) {
      return null;
    }

    $result['description'] = str_replace('<br />', '\n', $result['description']);
    $result['avatar'] = @unserialize($result['avatar']);
    $result['wonderLocked'] = (empty($result['wonderLocked'])) ? array() : @unserialize($result['wonderLocked']);
    if (!is_array($result['wonderLocked'])) {
      $result['wonderLocked'] = array();
    }

    if (!empty($result['awards'])){
      $tmp = explode('|', $result['awards']);
      $awards = array();

      foreach ($tmp AS $tag1) {
        $awards[] = array(
          'award_tag' => $tag1,
          'award_modus' => AWARD_DETAIL
        );
      }

      $result['award'] = $awards;
    }

    if ($result['avatar']) {
      $result['avatar'] = @unserialize($row['avatar']);
    }

    return $result;
  }

  public static function getHistory($tribeID) {
    global $db;

    $sql = $db->prepare("SELECT * 
                         FROM " . TRIBE_HISTORY_TABLE . "
                         WHERE tribeID = :tribeID
                         ORDER BY timestamp ASC");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    $history = $sql->fetchAll(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $history;
  }

  public static function getID($tag, $getData=false) {
    global $db;

    if (empty($tag)) return 0;

    $sql = $db->prepare("SELECT tribeID
                         FROM " . TRIBE_TABLE . "
                         WHERE tag LIKE :tag");
    $sql->bindValue('tag', $tag, PDO::PARAM_INT);
    if (!$sql->execute()) return 0;

    $tribe = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if ($getData && $tribe['tribeID'] != 0) {
      return self::getByID($tribe['tribeID']);
    }

    return $tribe['tribeID'];
  }

  public static function getMemberCount($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $sql = $db->prepare("SELECT COUNT(playerID) AS members 
                         FROM ". PLAYER_TABLE ."
                         WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return (isset($row['members'])) ? $row['members'] : null;
  }

  public static function getPlayerList($tribeID, $getGod=false, $getCaves=false) {
    global $db;

    if (empty($tribeID)) return array();

    $auth = new auth();

    $select = '';
    if ($getGod) {
      foreach (Config::$gods as $god) {
        $select .= ', p.' . $god . ' as ' . $god;
      }
      foreach (Config::$halfGods as $halfgod) {
        $select .= ', p.' . $halfgod . ' as ' . $halfgod;
      }
    }

    $return = array();
    $sql = $db->prepare("SELECT p.playerID, p.name, p.awards, p.auth, r.rank, r.average AS points, r.caves, r.religion, r.fame, r.fame as kp, s.lastAction  {$select}
                         FROM " . PLAYER_TABLE . " p
                           LEFT JOIN " . RANKING_TABLE . " r ON r.playerID = p.playerID
                           LEFT JOIN " . SESSION_TABLE . " s ON s.playerID = p.playerID
                         WHERE p.tribeID = :tribeID
                         ORDER BY r.rank ASC");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    while ($member = $sql->fetch(PDO::FETCH_ASSOC)) {
      $return[$member['playerID']] = $member;

      if (!empty($member['awards'])) {
        $member['awards'] = explode('|', $member['awards']);

        $awards = array();
        foreach ($member['awards'] AS $award) {
          $awards[] = array('tag' => $award, 'award_modus' => AWARD_DETAIL);
        }

        $return[$member['playerID']]['award'] = $awards;
      }

      $return[$member['playerID']]['lastAction'] = date("d.m.Y H:i:s", time_timestampToTime($member['lastAction']));

      $userAuth = unserialize($member['auth']);
      $return[$member['playerID']]['tribeAuth'] = $auth->getAllTypePermission('tribe', $userAuth['tribe']);

      if ($getCaves) { $return[$member['playerID']]['caves'] = array(); }
    }
    $sql->closeCursor();

    if ($getGod) {
      foreach ($GLOBALS['scienceTypeList'] AS $value) {
        $ScienceFieldsName[$value->dbFieldName] = $value->name;
      }

      foreach ($return as $id => $data) {
        $return[$id]['god'] = 'keinen';
        $return[$id]['halfgod'] = 'keinen';
  
        foreach (Config::$gods as $god) {
          if ($return[$id][$god] > 0) {
            $return[$id]['god'] = $ScienceFieldsName[$god];
          }
        }
        foreach (Config::$halfGods as $halfgod) {
          if ($return[$id][$halfgod] > 0) {
            $return[$id]['halfgod'] = $ScienceFieldsName[$halfgod];
          }
        }
      }
    }

    if ($getCaves) {
      $sql = $db->prepare("SELECT caveID, xCoord, yCoord, name, playerID
                           FROM "  . CAVE_TABLE . "
                           WHERE playerID IN ('" . implode("', '", array_keys($return)) . "')");
      if (!$sql->execute()) return array();
      while ($caves = $sql->fetch(PDO::FETCH_ASSOC)) {
        $return[$caves['playerID']]['caves'][$caves['caveID']] = $caves;
      }
      $sql->closeCursor();
    }

    return $return;
  }

  public static function getRanking($tribeID) {
    global $db;

    if (empty($tribeID)) return 0;

    $sql = $db->prepare("SELECT rank
                         FROM " . RANKING_TRIBE_TABLE . "
                         WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return 0;

    $ret = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $ret['rank'];
  }

  public static function insertTribe($tag, $name, $password, $leaderID) {
    global $db;

    if (empty($tag) || empty($name) || empty($password) || empty($leaderID)) return false;

    $sql = $db->prepare("INSERT INTO ". TRIBE_TABLE . "
                           (tag, name, leaderID, created, password, governmentID, validatetime, valid)
                         values 
                           (:tag, :name, :leaderID, NOW() + 0, :password, 1, ((NOW() + INTERVAL " . TRIBE_MINIMUM_LIVESPAN . " SECOND ) + 0),0)");
    $sql->bindValue('tag', $tag, PDO::PARAM_STR);
    $sql->bindValue('name', $name, PDO::PARAM_STR);
    $sql->bindValue('leaderID', $leaderID, PDO::PARAM_INT);
    $sql->bindValue('password', $password, PDO::PARAM_STR);
    if (!$sql->execute()) return false;

    $tribeID = $db->lastInsertId();

    if(!self::setRanking($tribeID)) {
      self::leaveTribe($leaderID);
      return false;
    }

    if (!self::setTribe($leaderID, $tribeID)) {
      self::leaveTribe($leaderID);
      return false;
    }

    return $tribeID;
  }

  public static function isTopTribe($tribeID) {
    global $db;

    $sql = $db->prepare("SELECT rank 
                         FROM " . RANKING_TRIBE_TABLE . " 
                         WHERE tribeID = :tribeID
                         LIMIT 0 , 30");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    $data = $sql->fetch(PDO::FETCH_ASSOC);

    return (isset($data['rank']) && $data['rank'] <= 10) ? true : false;
  }

  public static function leaveTribe($playerID) {
    global $db;

    $sql = $db->prepare("UPDATE " . PLAYER_TABLE . "
                         SET tribeID = 0
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    $sql = $db->prepare("DELETE FROM " . ELECTION_TABLE . "
                         WHERE voterID = :playerID
                           OR playerID LIKE :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    return true;
  }

  public static function prepareHistoryMessage($tribe, $target, $message) {
    return str_replace("[TARGET]", $target, str_replace("[TRIBE]", $tribe, $message));
  }

  public static function processCreate($leaderID, $tag, $password, $restore_rank=false) {
    global $db;

    if (empty($leaderID) || empty($tag) || empty($password)) return -1;

    $tribeData = self::getID($tag);
    if (!empty($tribeData)) {
      return -4;
    }

    if (!self::changeTribeAllowed($leaderID)) {
      return -10;
    }

    $tribeID = self::insertTribe($tag, $tag, $password, $leaderID);

    if ($tribeID === false) {
      return -5;
    }

    Player::addHistoryEntry($leaderID, sprintf(_("gründet den Stamm '%s'"), $tag));

    if ($restore_rank) {
      if (!self::setOldRanking($tag, $password, $tribeID)) {
        return 3;
      }
    }

    return 2;
  }

  public static function processKickMember($playerID, $tribeData) {
    if (empty($playerID) || empty($tribeData)) return -30;

    // do not kick in wartime

    if (!TribeRelation::changeTribeAllowed($tribeData['tribeID'])) {
      return -40;
    }

    // blocked
    if (!self::changeTribeAllowed($playerID)) {
      return -3;
    }

    // get player
    $playerData = Player::getPlayer($playerID);
    if (empty($playerData)) {
      return -41;
    }

    // remove player
    if (!self::leaveTribe($playerID)) {
      return -41;
    }

    Chat::tribeLeave($tribeData['tribeID'], $player['jabberName']);

    Player::addHistoryEntry($playerID, sprintf(_("wird aus dem Stamm '%s' geworfen"), $tribeData['tag']));

    // block player
    self::setBlockedTime($playerID);

    TribeMessage::sendIntern($tribeData['tribeID'], self::MESSAGE_MEMBER, 'Spieler rausgeschmissen', sprintf(_("Der Spieler %s wurde soeben aus dem Stamm ausgeschlossen."), $playerData['name']));

    $messagesClass = new Messages;
    $messagesClass->sendSystemMessage($playerID, 8, 'Stammausschluss.', sprintf(_("Sie wurden aus dem Stamm %s ausgeschlossen."), $tribeData['tag']));

    return 13;
  }

  public static function processLeave($playerID, $tribeData, $force=false) {
    if (empty($playerID) || empty($tribeData)) return -30;

    if (!$force && !TribeRelation::changeTribeAllowed($tribeData['tribeID'])) {
      return -2;
    }

    if (!$force && !self::changeTribeAllowed($playerID)) {
      return -3;
    }

    if ($tribeData['leaderID'] == $playerID) {
      if (!$force && !TribeLeader::removeLeader($tribeData['tribeID'])) {
        return -4;
      }
    }

    // get player
    $playerData = Player::getPlayer($playerID);
    if (empty($playerData)) {
      return -5;
    }

    if (!self::leaveTribe($playerID)) {
      return -5;
    }

    Chat::tribeLeave($tribeData['tribeID'], $player['jabberName']);

    Player::addHistoryEntry($playerID, sprintf(_("verläßt den Stamm '%s'"), $tribeData['tag']));

    self::setBlockedTime($playerID);

    TribeMessage::sendIntern($tribeData['tribeID'], self::MESSAGE_MEMBER, 'Spieleraustritt', sprintf(_("Der Spieler %s ist soeben aus dem Stamm ausgetreten."), $playerData['name']));

    $memberCount = self::getMemberCount($tribeData['tribeID']);
    if ($memberCount !== false && $memberCount == 0) { // Prüfung auf false mit === nötig da die Rückgabe von false nen fehler ist!
      self::deleteTribe($tribeID, $force);
      return 2;
    }

    return 1;
  }

  public static function processJoin($playerID, $tag, $password) {
    global $db;

    if (empty($tag) || empty($password)) return -1;

    $tribeData = self::getID($tag, true);
    if (empty($tribeData)) {
      return -1;
    }

    if (strcmp($password, $tribeData['password']) != 0) {
      return -1;
    }

    if (!self::changeTribeAllowed($playerID)) {
      return -10;
    }
    if (!TribeRelation::changeTribeAllowed($tribeID) ) {
      return -6;
    }

    $playerData = Player::getPlayer($playerID);
    if (empty($playerData)) {
      return -3;
    }

    if ((int) TRIBE_MAXIMUM_SIZE > 0) {
      $sql = $db->prepare("SELECT count(*) < " . (int) TRIBE_MAXIMUM_SIZE . " as IsOk 
                           FROM " . PLAYER_TABLE . "
                           WHERE tribeID = :tribeID");
      $sql->bindValue('tribeID', $tribeData['tribeID'], PDO::PARAM_INT);
      if (!$sql->execute()) return -7;

      $row = $sql->fetch(PDO::FETCH_ASSOC);
      $sql->closeCursor();

      if (!$row['IsOk']) {
        return -7;
      }
    }

    if (!self::setTribe($playerID, $tribeData['tribeID'])) {
      return -3;
    }

    self::setBlockedTime($playerID);

    Chat::tribeJoin($tribeData['tribeID'], $_SESSION['player']->jabberName);

    Player::addHistoryEntry($playerID, sprintf(_("tritt dem Stamm '%s' bei"), $tribeData['tag']));

    TribeMessage::sendIntern($tribeData['tribeID'], self::MESSAGE_MEMBER, 'Spielerbeitritt', sprintf(_("Der Spieler %s ist soeben dem Stamm beigetreten"), $playerData['name']));

    return 1;
  }

  public static function setBlockedTime($playerID) {
    global $db;

    if (empty($playerID)) return false;

    $sql = $db->prepare("UPDATE " . PLAYER_TABLE . "
                         SET tribeBlockEnd = (NOW() + INTERVAL " . TRIBE_BLOCKING_PERIOD_PLAYER . " SECOND)+0
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
  }

  public static function setHistory($tribeID, $message) {
    global $db;

    if (empty($playerID) || empty($message)) return false;

    $time = getUgaAggaTime(time());
    $month = getMonthName($time['month']);

    $sql = $db->prepare("INSERT INTO " . TRIBE_HISTORY_TABLE . " 
                         (tribeID, ingameTime, message) 
                         VALUES (:tribeID, :ingameTime, :message)");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->bindValue('ingameTime', "{$time['day']}. $month im Jahr {$time['year']}", PDO::PARAM_STR);
    $sql->bindValue('message', $message, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
  }

  public static function setLeader($playerID, $tribeID) {
    global $db;

    if (empty($playerID) || empty($tribeID)) return false;

    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                         SET leaderID = :playerID
                         WHERE tag LIKE :tag ");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    $sql->bindValue('tag', $tag, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
  }

  public static function setOldRanking($tag, $password, $tribeID) {
    global $db;

    if (empty($ag) || empty($password) || empty($tribeID)) return false;

    $sql = $db->prepare("SELECT * FROM " . OLD_TRIBES_TABLE . "
                         WHERE tag LIKE :tag
                           AND password = :password
                           AND used = 0
                         LIMIT 1");
    $sql->bindValue('tag', $tag, PDO::PARAM_STR);
    $sql->bindValue('password', $password, PDO::PARAM_STR);
    if (!$sql->execute()) return false;

    $oldTribe = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (empty($oldTribe)) {
      return true; // bail out if no tribe is found, but with positive return value
    }

    $sql = $db->prepare("UPDATE " . RANKING_TRIBE_TABLE . "
                         SET points_rank = :points_rank
                         WHERE tribeID = :tribeID");
    $sql->bindValue('points_rank', $oldTribe['points_rank'], PDO::PARAM_INT);
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    $sql = $db->prepare("UPDATE " . OLD_TRIBES_TABLE . "
                         SET used = 1
                         WHERE tag = :tag");
    $sql->bindValue('tag', $tag, PDO::PARAM_STR);
    if (!$sql->execute()) return false;

    return true;
  }

  public static function setRanking($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $sql = $db->prepare("SELECT MAX(rank) +1 AS newrank FROM " . RANKING_TRIBE_TABLE);
    if (!$sql->execute()) return -1;
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (!isset($row['newrank']) || empty($row['newrank'])) {
      return -2;
    }

    $sql = $db->prepare("INSERT INTO " . RANKING_TRIBE_TABLE . "
                           (tribeID, rank, points_rank)
                         VALUES
                           (:tribeID, :newrank, 1500)");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->bindValue('newrank', $row['newrank'], PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    return true;
  }

  public static function setTribe($playerID, $tribeID) {
    global $db;

    if (empty($playerID) || empty($tribeID)) return false;

    $sql = $db->prepare("UPDATE " . PLAYER_TABLE . "
                        SET tribeID = :tribeID
                        WHERE playerID = :playerID
                          AND tribeID = 0");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
  }

  public static function updateWonLost($tribeID, $targetTribeID, $targetwon) {
    global $db;

    if ($targetwon) {
      $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                           SET war_lost = war_lost + 1
                           WHERE tribeID = :tribeID");
      $sql->bindValue('tribeID', $tribeID, PDO::PARAM_STR);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }

      $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                           SET war_won = war_won + 1
                           WHERE tribeID = :tribeID");
      $sql->bindValue('tribeID', $targetTribeID, PDO::PARAM_STR);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }

      return true;
    }
    else if (!$targetwon) {
      $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                           SET war_won = war_won + 1
                           WHERE tribeID = :tribeID");
      $sql->bindValue('tribeID', $tribeID, PDO::PARAM_STR);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }

      $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                           SET war_lost = war_lost + 1
                           WHERE tribeID = :tribeID");
      $sql->bindValue('tribeID', $targetTribeID, PDO::PARAM_STR);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }

      return true;
    }
  }

  public static function updateTribeData($tribeID, $data) {
    global $db;

    if (empty($tribeID) || empty($data)) return -30;

    if (!self::validatePassword($data['password'])){
      return -12;
    }

    // check if avatar is a image
    if (!empty($data['avatar'])) {
      $avatarInfo = checkAvatar($data['avatar']);
      if (!$avatarInfo) {
        return -13;
      } else {
        $data['avatar'] = $avatarInfo;
      }
    }

    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                          SET name = :name,
                            password = :password,
                            description = :description,
                            avatar = :avatar
                          WHERE tribeID = :tribeID");
    $sql->bindValue('name', $data['name'], PDO::PARAM_STR);
    $sql->bindValue('password', $data['password'], PDO::PARAM_STR);
    $sql->bindParam('description', $data['description']);
    $sql->bindValue('avatar', $data['avatar'], PDO::PARAM_STR);
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return 6;
    }

    return 5;
  }

  public static function validatePassword($password) {
    return preg_match('/^\w{6,}$/', $password);
  }

  public static function validateTag($tag) {
    return preg_match('/^[a-zA-Z][a-zA-Z0-9\-]{0,7}$/', $tag);
  }
}

class TribeDonation {
  public static function getDonations($tribeID) {
    global $db;

    if (empty($tribeID)) return array();

    // Resourcenstring zusammenbasteln
    $fields = array();
    foreach($GLOBALS['resourceTypeList'] as $resource) {
      if ($resource->maxTribeDonation == 0) {
        continue;
      }

      $fields[] = "SUM(t." . $resource->dbFieldName . ") as " . $resource->dbFieldName;
    }

    $sql = $db->prepare("SELECT p.name, ". implode(", ", $fields) ." FROM (" . TRIBE_STORAGE_DONATIONS_TABLE . " t
                  LEFT JOIN " . PLAYER_TABLE . " p
                    ON t.playerID = p.playerID)
                  WHERE t.tribeID = :tribeID
                    GROUP BY t.playerID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    $storage = $sql->fetchAll(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $storage;
  }
  
  public static function setDonations($value_array, $caveID, &$caveData) {
    global $db;

    $playerID = $_SESSION['player']->playerID;

    if (!sizeof($value_array)) {
      return -8;
    }

    $fields_cave = $fields_storage = $fields_donations = $fields_resources = $where = array();
    foreach ($value_array as $resourceID => $value) {
      $value = floor(abs($value)); // nur positive und ganze zahlen zulässig!

      if ($value) {
        if (isset($GLOBALS['resourceTypeList'][$resourceID])) {
          $resource = $GLOBALS['resourceTypeList'][$resourceID];

          // wartezeit einer Ressource nicht abgewartet? Abbruch!
          if (isset($_SESSION['player']->donateLocked['tribe'][$resource->dbFieldName]) && $_SESSION['player']->donateLocked['tribe'][$resource->dbFieldName] > time()) {
            return -9;
          }

          // check if resource is over maxDonation value
          if ($resource->maxTribeDonation < $value) {
            return -9;
          }

          // check for enough resources in cave
          if ($caveData[$resource->dbFieldName] < $value) {
            return -10;
          }

          $fields_cave[] = $resource->dbFieldName . " = " . $resource->dbFieldName . " - " . $value;
          $fields_storage[] = $resource->dbFieldName . " = " . $resource->dbFieldName . " + " . $value;
          $fields_resources[] = $resource->dbFieldName;
          $fields_donations[] = $value;
          $where[] = " AND " . $resource->dbFieldName . " >= " . $value;
        }
      }
    }

    $sql = $db->prepare("INSERT INTO " . TRIBE_STORAGE_DONATIONS_TABLE . 
                          "(playerID, tribeID, timestamp, ".implode (", ", $fields_resources) . ")
                          VALUES (:playerID, :tribeID, :timestamp, " . implode(", ", $fields_donations). ")");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('tribe', $_SESSION['player']->tribeID, PDO::PARAM_STR);
    $sql->bindValue('timestamp', time(), PDO::PARAM_INT);
    if (!$sql->execute()) return -11;

    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . " SET
                          " . implode(", ", $fields_storage) . "
                         WHERE tag LIKE :tribe");
    $sql->bindValue('tribe', $_SESSION['player']->tribe, PDO::PARAM_STR);
    if (!$sql->execute()) return -11;

    $sql = $db->prepare("UPDATE " . CAVE_TABLE . " SET 
                          " . implode (", ", $fields_cave) . "
                          WHERE caveID = :caveID 
                          " . implode(" ", $where));
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
    if (!$sql->execute()) return -20;

    // update caves
    $caveData = getCaveByID($caveID);

    // update Timeout
    foreach ($value_array as $resourceID => $value) {
      if ($value) {
        if (isset($GLOBALS['resourceTypeList'][$resourceID])) {
          $newTime = time() + (TRIBE_STORAGE_DONATION_INTERVAL*60*60);
          Player::setDonateLocked($_SESSION['player']->playerID, 'tribe', $GLOBALS['resourceTypeList'][$resourceID]->dbFieldName, $newTime);
        }
      }
    }

    return 4;
  }
}

class TribeGovernment {
  public static function getGovernment($tribeID) {
    global $db;

    if (empty($tribeID)) return array();

    $sql = $db->prepare("SELECT governmentID, DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, duration, duration < NOW()+0 AS isChangeable 
                         FROM " . TRIBE_TABLE ." 
                         WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    $ret = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $ret;
  }

  public static function setGovernment($tribeID, $governmentID) {
    global $db;

    if (empty($tribeID) || empty($governmentID)) return -27;

    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                         SET governmentID = :governmentID,
                           duration = (NOW() + INTERVAL " . GOVERNMENT_CHANGE_TIME_HOURS . " HOUR)+0
                         WHERE tribeID = :tribeID");
    $sql->bindValue('governmentID', $governmentID, PDO::PARAM_INT);
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return -27;
    }

    TribeMessage::sendIntern($tribeID, self::MESSAGE_LEADER, 'Die Regierung wurde geändert', 'Die Regierung des Stammes wurde auf ' . $GLOBALS['governmentList'][$governmentID]['name'] . ' geändert.');

    return 8;
  }

  public static function isChangeable($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $ret = self::getGovernment($tribeID);
    return (isset($ret['isChangeable'])) ? $ret['isChangeable'] : false;
  }
}

class TribeLeader {
  public static function getElection($tribeID) {
    global $db;

    if (empty($tribeID)) return array();

    $sql = $db->prepare("SELECT p.name, COUNT(e.voterID) AS votes 
                         FROM ". ELECTION_TABLE ." e 
                           LEFT JOIN Player p ON p.playerID = e.playerID 
                         WHERE e.tribeID = :tribeID
                         GROUP BY e.playerID, p.name");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    $votes = array();
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      array_push($votes, $row);
    }
    $sql->closeCursor();

    return $votes;
  }

  public static function getVotes($playerID) {
    global $db;

    if (empty($playerID)) return 0;

    $sql = $db->prepare("SELECT playerID FROM " . ELECTION_TABLE . " WHERE voterID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) return 0;

    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return (isset($row['playerID'])) ? $row['playerID'] : 0;
  }

  public static function removeChoice($playerID) {
    global $db;

    if (empty($voterID)) return -29;

    $sql = $db->prepare("DELETE FROM ". ELECTION_TABLE ."
                         WHERE voterID = :playerID ");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return -29;
    }

    return 9;
  }

  public static function removeLeader($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                         SET leaderID = 0
                         WHERE tribeID = :tribeID ");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
  }

  public static function setChoice($chooseLeaderID, $votePlayerID, $tribeID) {
    global $db;

    if (empty($chooseLeaderID) || empty($votePlayerID) || empty($tribeID)) return -29;

    $sql = $db->prepare("REPLACE ". ELECTION_TABLE." 
                         SET voterID = :voterID, 
                           playerID = :playerID,
                           tribeID = :tribeID");
    $sql->bindValue('voterID', $votePlayerID, PDO::PARAM_INT);
    $sql->bindValue('playerID', $chooseLeaderID, PDO::PARAM_INT);
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return -29;
    }

    return 9;
  }
}

class TribeMessage {
  public static function getMessages($tribeID) {
    global $db;

    if (empty($tribeID)) return false;

    $sql = $db->prepare("SELECT *, DATE_FORMAT(messageTime, '%d.%m.%Y %H:%i') AS date 
                         FROM " . TRIBE_MESSAGE_TABLE . "
                         WHERE tribeID = :tribeID
                         ORDER BY messageTime DESC
                         LIMIT 0, 30");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    $messages = array();
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $messages[$row['tribeMessageID']] = $row;
    }
    $sql->closeCursor();

    return $messages;
  }

  public static function processSendPlayer($tribeID, $sender, $message) {
    if (empty($tribeID) || empty($sender) || empty($message)) return -7;

    $members = Tribe::getPlayerList($tribeID);
    if (empty($members)) return -7;

    // init messages class
    $messagesClass = new Messages;

    foreach ($members AS $playerID => $playerData) {
      if(!$messagesClass->insertMessageIntoDB($playerID, sprintf(_("Stammesnachricht von %s"), $sender), $message, true, true)) {
        return -7;
      }
    }

    return 3;
  }

  public static function processSendIntern($tribeID, $sender, $message) {
    if (empty($tribeID) || empty($sender) || empty($message)) return -7;

    if (!self::sendIntern($tribeID, Tribe::MESSAGE_LEADER, sprintf(_("Nachricht von %s"), $sender), $message)) {
      return -7;
    }

    return 3;
  }

  public static function sendIntern($tribeID, $type, $subject, $message) {
    global $db;

    if (empty($tribeID) || empty($type) || empty($subject) || empty($message)) return false;

    $sql = $db->prepare("INSERT INTO " . TRIBE_MESSAGE_TABLE . " 
                           (tribeID, messageClass, messageSubject, messageText, messageTime) 
                         VALUES
                           (:tribeID, :messageClass, :messageSubject, :messageText, NOW()+0)");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->bindValue('messageClass', $type, PDO::PARAM_INT);
    $sql->bindValue('messageSubject', $subject, PDO::PARAM_STR);
    $sql->bindValue('messageText', $message, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }

    return true;
  }
}

class TribeRelation {
  /**
   * calculate the fame according to the following formula:
   * basis * (V/S) * (V/S) * (S'/V')
   * this is bigger: if, winner had more points,
   * winner gained more points during the battle compared to looser
   */
  public static function calcFame($winner, $winnerOld, $looser, $looserOld) {
    $winner = $winner ? $winner : 1;
    $winner_old = $winner ? $winner : 1;
    $looser = $looser ? $looser : 1;
    $looser_old = $looser_old ? $looser_old : 1;

    return
      (100 + ($winnerOld + $looserOld) / 200) *         // basis points
      max(.125, min(8, ($looser / $winner) * ($looser / $winner) * ($winner_old / $looser_old)));
  }

  public static function changeTribeAllowed($tribeID) {
    $tribeRelations = self::getRelations($tribeID);

    if (!sizeof($tribeRelations)) {
      return true;
    }

    foreach ($GLOBALS['relationList'] as $relationTypeID => $relationType) {
      if ($relationType['dontLeaveTribe']) {
        foreach ($tribeRelations['own'] as $target => $relation) {
          if ($relation['relationType'] == $relationTypeID) {
            return false;
          }
        }
      }
    }

    return true;
  }

  public static function forceSurrender($tribeData, $targetData, $relationID) {
    // check conditions
    if(empty($tribeData) || empty($targetData) || empty($relationID)) return -30;

    if ($tribeData['tribeID'] == $targetData['tribeID']) {
      return -14;
    }

    $tribeWarTargets = self::getWarRelations($tribeData['tribeID']);

    if(!($relation = $tribeWarTargets[$targetData['tribeID']])) {
      return -17; 
    }

    if(!$relation['isForcedSurrenderPracticallyPossible']) {
      return -26;
    }

    // find surrender
    $surrenderId = 0;
    while(!($GLOBALS['relationList'][$surrenderId]['isWarLost']) ){
      $surrenderId++;
    }

    // refresh relations                              
    $messageID = self::processRelationUpdate($targetData, $tribeData, $surrenderId);
    if ($messageID < 0) {
      return $messageID;
    }
  
    // tribe messages for forced surrender
    TribeMessage::sendIntern($tribeData['tribeID'], self::MESSAGE_RELATION, sprintf(_("Zwangskapitulation über %s"), $targetData['tag']), sprintf(_("Ihr Stammesanführer hat den Stamm %s zur Aufgabe gezwungen."), $targetData['tag']));
    TribeMessage::sendIntern($targetData['tribeID'], self::MESSAGE_RELATION, sprintf(_("Zwangskapitulation gegen %s"), $tribeData['tag']), sprintf(_("Der Stammesanführer des Stammes %s hat ihren Stamm zur Aufgabe gezwungen."), $tribeData['tag']));
  
    return $messageID;
  }

  /*
   * this function returns the might (points_rank) for the given tribe.
   * the might are the tribe points WITHOUT fame.
   */
  public static function getMight($tribeID) {
    global $db;


    $sql = $db->prepare("SELECT points_rank 
                         FROM " . RANKING_TRIBE_TABLE . "
                         WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    $ret = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return (isset($ret['points_rank'])) ? $ret['points_rank'] : false;
  }

  public static function getRelations($tribeID) {
    global $db;

    if (empty($tribeID)) return array();

    // get relations from $tag to other tribes
    $sql = $db->prepare("SELECT r.*,  DATE_FORMAT(r.duration, '%d.%m.%Y %H:%i:%s') AS time, (NOW()+0) > r.duration AS changeable, t.tag as tribe
                         FROM ". RELATION_TABLE . " r
                         LEFT JOIN ". TRIBE_TABLE ." t ON t.tribeID = r.tribeID
                         WHERE r.tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    // copy result into an array
    $own = array();
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $own[strtoupper($row['tribeID_target'])] = $row;
    }
    $sql->closeCursor();

    // get relations from other tribes to $tag
    $sql = $db->prepare("SELECT r.*,  DATE_FORMAT(r.duration, '%d.%m.%Y %H:%i:%s') AS time, t.tag as tribe
                         FROM ". RELATION_TABLE . " r
                         LEFT JOIN ". TRIBE_TABLE ." t ON t.tribeID = r.tribeID_target
                         WHERE r.tribeID_target = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return NULL;

    $other=array();
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $other[strtoupper($row['tribeID'])] = $row;
    }
    $sql->closeCursor();

    return array("own" => $own, "other" => $other);
  }

  public static function getRelationBetween($tribeID, $targetTribeID) {
    global $db;

    $sql = $db->prepare("SELECT *, DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, (NOW()+0) > duration AS changeable
                         FROM ". RELATION_TABLE. "
                         WHERE tribeID = :tribeID
                           AND tribeID_target LIKE :tribeID_target");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    $sql->bindValue('tribeID_target', $targetTribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    if (!($own = $sql->fetch(PDO::FETCH_ASSOC))) {
      $own = array(
        'tribeID'        => $tribeID,
        'tribeID_target' => $targetTribeID,
        'changeable'     => 1,
        'relationType'   => 0,
        'tribe_rankingPoints'  => 0,
        'target_rankingPoints' => 0
      );
    }
    $sql->closeCursor();

    $sql = $db->prepare("SELECT *, DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, (NOW()+0) > duration AS changeable
                         FROM ". RELATION_TABLE . "
                         WHERE tribeID = :tribeID_target
                           AND tribe_target LIKE :tribeID");
    $sql->bindValue('tribeID_target', $targetTribeID, PDO::PARAM_INT);
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    if (!($other = $sql->fetch(PDO::FETCH_ASSOC))) {
      $other = array(
        'tribeID'        => $targetTribeID,
        'tribeID_target' => $tribeID,
        'changeable'     => 1,
        'relationType'   => 0,
        'tribe_rankingPoints'  => 0,
        'target_rankingPoints' => 0
      );
    }
    $sql->closeCursor();

    return array("own" => $own, "other" => $other);
  }

  public static function getWarRelations($tribeID) {
    global $db;

    if (empty($tribeID)) return array();

    // first get the id of war
    $warId = 0;
    while( !($GLOBALS['relationList'][$warId]['isWar']) ){
      $warId++;
    }

    $prepareForWarId = 0;
    while( !($GLOBALS['relationList'][$prepareForWarId]['isWar']) ){
      $prepareForWarId++;
    }

    $minTimeForForceSurrenderHours = $GLOBALS['relationList'][$warId]['minTimeForForceSurrenderHours'];
    $maxTimeForForceSurrenderHours = $GLOBALS['relationList'][$warId]['maxTimeForForceSurrenderHours'];

    // generate query for MySQL, get wars
    $sql = $db->prepare("SELECT r_target.tribeID as target,
                           r_own.fame as fame_own,
                           r_target.fame as fame_target,
                           ROUND((
                             (GREATEST(0, r_own.fame) / (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame) + ((GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)) <= 0 )))
                             + (r_own.fame > r_target.fame AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp)) / 3600 >= :maxTimeForForceSurrenderHours AND r_own.fame <= 0 AND r_target.fame <= 0)) * 100, 2)
                             as percent_actual,
                           ROUND(GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp)) / 3600 - :minTimeForForceSurrenderHours) /
                             (2 * (:maxTimeForForceSurrenderHours - :minTimeForForceSurrenderHours) )) * 100, 50) , 2)
                             as percent_estimated,
                           ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :minTimeForForceSurrenderHours) as isForcedSurrenderTheoreticallyPossible,
                           ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :minTimeForForceSurrenderHours) 
                             AND ((GREATEST(0, r_own.fame) / (GREATEST(0, r_own.fame) + 
                             GREATEST(0, r_target.fame) + ( (GREATEST(0, r_own.fame) + 
                             GREATEST(0, r_target.fame)) <= 0 )) ) + (r_own.fame > r_target.fame 
                             AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :maxTimeForForceSurrenderHours
                             AND r_own.fame <= 0 AND r_target.fame <= 0) ) >  GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp)) /
                             3600 - :minTimeForForceSurrenderHours) / (2 * (:maxTimeForForceSurrenderHours - :minTimeForForceSurrenderHours) )), 0.5) 
                             as isForcedSurrenderPracticallyPossible,
                           ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :minTimeForForceSurrenderHours) 
                             AND ((GREATEST(0, r_target.fame) / (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)
                             + ((GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)) <= 0 )))
                             + (r_target.fame > r_own.fame AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :maxTimeForForceSurrenderHours
                             AND r_own.fame <= 0 AND r_target.fame <= 0) ) >  GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/
                             3600 - :minTimeForForceSurrenderHours) / (2 * (:maxTimeForForceSurrenderHours - :minTimeForForceSurrenderHours) )), 0.5) 
                             as isForcedSurrenderPracticallyPossibleForTarget
                         FROM " . RELATION_TABLE . " r_own, " . RELATION_TABLE . " r_target
                         WHERE r_own.tribeID = r_target.tribeID_target
                           AND r_target.tribeID = r_own.tribeID_target
                           AND r_target.relationType = r_own.relationType
                           AND r_own.relationType = :warId
                           AND r_own.tribeID = :tribeID
                         ORDER BY r_own.timestamp ASC");
    $sql->bindValue(':maxTimeForForceSurrenderHours', $maxTimeForForceSurrenderHours, PDO::PARAM_INT);
    $sql->bindValue(':minTimeForForceSurrenderHours', $minTimeForForceSurrenderHours, PDO::PARAM_INT);
    $sql->bindValue(':tribeID', $tribeID, PDO::PARAM_INT);
    $sql->bindValue(':warId', $warId, PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    // copy result into an array
    $warTargets = array();
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      $warTargets[strtoupper($row['targetID'])] = $row;
    }
    $sql->closeCursor();

    return $warTargets;
  }

  public static function hasRelation($relationID, $relations) {
    foreach ($relations as $checkRelation) {
      if ($checkRelation['relationType'] == $relationID) {
        return true;
      }
    }

    return false;
  }

  public static function hasSameEnemy($tribeID, $targetTribeID, $PrepareForWar, $War) {

    // now we need the relations auf the two tribes
    $ownRelations = self::getRelations($tribeID);
    $targetRelations = self::getRelations($targetTribeID);

    foreach ($ownRelations['own'] as $actRelation) {
      foreach ($targetRelations['own'] as $actTargetRelation) {
        if (strcasecmp($actRelation['tribe_target'], $actTargetRelation['tribe_target']) == 0) {
          $ownType = $actRelation['relationType'];
          $targetType = $actTargetRelation['relationType'];

          $weHaveWar   = ($PrepareForWar && $GLOBALS['relationList'][$ownType]['isPrepareForWar']) || ($War && $GLOBALS['relationList'][$ownType]['isWar']);
          $theyHaveWar = ($PrepareForWar && $GLOBALS['relationList'][$targetType]['isPrepareForWar']) || ($War && $GLOBALS['relationList'][$targetType]['isWar']);

          if ($weHaveWar && $theyHaveWar) {
            return true;
          }
        }
      }
    }

    return false;
  }

  public static function isPossible($to, $from) {
    return array_key_exists($to, $GLOBALS['relationList'][$from]['transitions']);
  }

  public static function processRelationUpdate($tribeData, $targetData, $relationID, $force=false) {
    if(empty($tribeData) || empty($targetData) || empty($relationID)) return -30;

    $tribeMight = self::getMight($tribeData['tribeID']);
    $targetMight = self::getMight($targetData['tribeID']);

    if (!$force) {
      if ($tribeData['tribeID'] == $targetData['tribeID']) {
        return -14;
      }

      if (!$tribeData['valid']) {
        return -16;
      }

      $relationInfo = $GLOBALS['relationList'][$relationID];

      $relation = self::getRelationBetween($tribeData['tribeID'], $targetData['tribeID']);
      if ($relation === false) {
        return -17;
      }

      if ($relation['own']['relationType'] == $relationID) { // change to actual relation?
        return -18;
      }

      if (!$relation['own']['changeable']) {
        return -19;
      }

      // check if switching to same relation as target or relation is possible
      if ($relation['other']['relationType'] != $relationType && !self::isPossible($relationType, $relation['own']['relationType'])) {
        return -20;
      }

      if (!$force && ($GLOBALS['relationList'][$relationID]['isWarAlly'])) {
        //generally allowes?
        if (!$GLOBALS['relationList'][$relationFrom]['isAlly']) {
          return -21;
        }
        if (!$GLOBALS['relationList'][$relation['other']['relationType']]['isAlly']) {
          return -22;
        }
        if (!self::hasSameEnemy($tribeData['tribeID'], $targetData['tribeID'], true, true)) {
          return -23;
        }
      }

      $relationTypeOtherActual = $relation['other']['relationType'];
      // check minimum size of target tribe if it's not an ultimatum
      if ((($relationInfo['targetSizeDiffDown'] > 0) || ($relationInfo['targetSizeDiffUp'] > 0)) && (!$GLOBALS['relationList'][$relationTypeOtherActual]['isUltimatum'])) {
        $from_points   = max(0, $tribeMight);
        $target_points = max(0, $targetMight);

        $targetTopTribe = Tribe::isTopTribe();
        if ($targetTopTribe == false) {
          if (($relationInfo['targetSizeDiffDown'] > 0) &&
              ($from_points - $relationInfo['targetSizeDiffDown'] > $target_points )) {
            return -24;
          }

          if (($relationInfo['targetSizeDiffUp'] > 0) &&
              ($from_points + $relationInfo['targetSizeDiffUp'] < $target_points )) {
            return -25;
          }
        }
      }
    }

    // if switching to the same relation of other clan towards us,
    // use their treaty's end_time!
    if ($relationID == $relation['other']['relationType'] && $relationID != 0) {
      $duration = 0;
      $end_time = $relation['other']['duration'];
    } else {
      $duration = $GLOBALS['relationList'][$relation['own']['relationType']]['transitions'][$relationType]['time'];
      $end_time = 0;
    }

    if ($GLOBALS['relationList'][$relationFrom]['isPrepareForWar'] && $GLOBALS['relationList'][$relationID]['isWar']) {
      $OurFame = $relation['own']['fame'];
      $OtherFame = $relation['other']['fame'];
    } else {
      $OurFame = 0;
      $OtherFame = 0;
    }

    if (!self::setRelation($tribeData['tribeID'], $targetData['tribeID'], $relationType, $duration, $end_time, $relation['own']['tribe_rankingPoints'], $relation['own']['target_rankingPoints'], $OurFame)) {
      return -3;
    }

    // calculate elo if war ended  
    if ($GLOBALS['relationList'][$relationType]['isWarWon']) {
      Tribe::calculateElo($tribeData['tribeID'], $tribeMight, $targetData['tribeID'], $targetMight);
      Tribe::updateWonLost($tribeData['tribeID'], $targetData['tribeID'], false);
    } else if ($GLOBALS['relationList'][$relationType]['isWarLost']) {
      Tribe::calculateElo($targetData['tribeID'], $targetMight, $tribeData['tribeID'], $tribeMight);
      Tribe::updateWonLost($tribeData['tribeID'], $targetData['tribeID'], true);
    }

    // insert history message
    if (isset($GLOBALS['relationList'][$relationType]['historyMessage'])) {
      Tribe::setHistory($tribetData['tribeID'], Tribe::prepareHistoryMessage($tribeData['tag'], $targetData['tag'], $GLOBALS['relationList'][$relationType]['historyMessage']));
    }

    TribeMessage::sendIntern($tribetData['tribeID'], self::MESSAGE_RELATION, sprintf(_("Haltung gegenüber %s geändert"), $targetData['tag']), sprintf(_("Ihr Stammesanführer hat die Haltung Ihres Stammes gegenüber dem Stamm %s auf %s geändert."), $targetData['tag'], $GLOBALS['relationList'][$relationType]['name']));
    TribeMessage::sendIntern($targetData['tribeID'], self::MESSAGE_RELATION, sprintf(_("Der Stamm %s ändert seine Haltung"), $tribeData['tag']), sprintf(_("Der Stammesanführer des Stammes %s hat die Haltung seines Stammes ihnen gegenüber auf %s geändert."), $tribeData['tag'], $GLOBALS['relationList'][$relationType]['name']));

    // switch other side if necessary (and not at this type already)
    if (!$end_time && ($oST = $relationInfo['otherSideTo']) >= 0) {
      if (!self::setRelation($targetData['tribeID'], $tribeData['tribeID'], $oST, $duration, 0, $relation['other']['tribe_rankingPoints'], $relation['other']['target_rankingPoints'], $OtherFame)) {
        return -17;
      }

      // insert history
      if (isset($GLOBALS['relationList'][$oST]['historyMessage'])) {
        Tribe::setHistory($targetData['tribeID'], Tribe::prepareHistoryMessage($tribeData['tag'], $targetData['tag'], $GLOBALS['relationList'][$oST]['historyMessage']));
      }

      TribeMessage::sendIntern($tribetData['tribeID'], self::MESSAGE_RELATION, sprintf(_("Der Stamm %s ändert seine Haltung"), $targetData['tag']), sprintf(_("Der Stamm %s hat die Haltung ihnen gegenüber automatisch auf %s geändert."), $targetData['tag'], $GLOBALS['relationList'][$oST]['name']));
      TribeMessage::sendIntern($targetData['tribeID'], self::MESSAGE_RELATION, sprintf(_("Haltung gegenüber %s geändert"), $tribeData['tag']), sprintf(_("Die Haltung Ihres Stammes gegenüber dem Stamm %s wurde automatisch auf %s geändert."), $tribeData['tag'], $GLOBALS['relationList'][$relationType]['name']));
    }

    return 7;
  }

  public static function setRelation($fromTribeID, $targetTribeID, $relation, $duration, $end_time, $from_points_old, $target_points_old, $fame=0) {
    global $db;

    $from_points = self::getMight($fromTribeID);
    if ($from_points === false || $from_points < 0) {
      $from_points = 0;
    }
    $target_points = self::getMight($targetTribeID);
    if ($target_points === false || $target_points < 0) {
      $target_points = 0;
    }

    // have to remember the number of members of the other side?
    $target_members = 0;
    if ($GLOBALS['relationList'][$relation]['storeTargetMembers']) {
      $target_members = Tribe::getMemberCount($targetTribeID);
    }

    if ($relation == 0) {
      $sql = $db->prepare("DELETE FROM " . RELATION_TABLE . "
                           WHERE tribeID = :tribeID
                             AND tribeID_target = :tribeID_target");
      $sql->bindValue('tribeID', $fromTribeID, PDO::PARAM_STR);
      $sql->bindValue('tribeID_target', $targetTribeID, PDO::PARAM_STR);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }
    } else {
      $sql = $db->prepare("REPLACE " . RELATION_TABLE . "
                           SET tribeID = :tribeID,
                             tribeID_target = :tribeID_target,
                             target_members = :target_members,
                             timestamp = = NOW() +0,
                             relationType = :relationType,
                             tribe_rankingPoints = :tribe_rankingPoints,
                             target_rankingPoints = :target_rankingPoints,
                             attackerReceivesFame = :attackerReceivesFame,
                             defenderReceivesFame = :defenderReceivesFame,
                             attackerMultiplicator = :attackerMultiplicator,
                             defenderMultiplicator = :defenderMultiplicator,
                             " . ($end_time) ? "duration = " . (int)$end_time : "duration = (NOW() + INTERVAL " . (int)$duration . " HOUR) + 0
                             fame = :fame");
      $sql->bindValue('tribeID', $fromTribeID, PDO::PARAM_INT);
      $sql->bindValue('tribeID_target', $targetTribeID, PDO::PARAM_INT);
      $sql->bindValue('target_members', $target_members, PDO::PARAM_INT);
      $sql->bindValue('relationType', $relation, PDO::PARAM_INT);
      $sql->bindValue('tribe_rankingPoints', $from_points, PDO::PARAM_INT);
      $sql->bindValue('target_rankingPoints', $target_points, PDO::PARAM_INT);
      $sql->bindValue('attackerReceivesFame', $GLOBALS['relationList'][$relation]['attackerReceivesFame'], PDO::PARAM_INT);
      $sql->bindValue('defenderReceivesFame', $GLOBALS['relationList'][$relation]['defenderReceivesFame'], PDO::PARAM_INT);
      $sql->bindValue('attackerMultiplicator', $GLOBALS['relationList'][$relation]['attackerMultiplicator'], PDO::PARAM_INT);
      $sql->bindValue('defenderMultiplicator', $GLOBALS['relationList'][$relation]['defenderMultiplicator'], PDO::PARAM_INT);
      $sql->bindValue('fame', $fame, PDO::PARAM_INT);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }
    }

    // calculate the fame update if necessary
    if ($GLOBALS['relationList'][$relation]['fameUpdate'] != 0) {
      if ($GLOBALS['relationList'][$relation]['fameUpdate'] > 0) {
        $fame = self::calcFame($from_points, $from_points_old, $target_points, $target_points_old);
      } else if ($GLOBALS['relationList'][$relation]['fameUpdate'] < 0) {
        // calculate fame: first argument is winner!
        $fame = -1 * self::calcFame($target_points, $target_points_old, $from_points, $from_points_old);
      }

      $sql = $db->prepare("UPDATE ". TRIBE_TABLE . "
                           SET fame = fame + :fame
                           WHERE tribeID = :tribeID");
      $sql->bindValue('fame', $fame, PDO::PARAM_INT);
      $sql->bindValue('tribeID', $fromTribeID, PDO::PARAM_STR);
      if (!$sql->execute() || $sql->rowCount() == 0) {
        return false;
      }
    }

    return true;
  }
}

class TribeWonder {
  public static function wonder_addStatistic($wonderID, $failSuccess) {
    global $db;

    $sql = $db->prepare("SELECT *
                         FROM " . STATISTIC_TABLE . "
                         WHERE type = :type
                           AND name = :wonderID");
    $sql->bindValue('type', WONDER_STATS_CACHE, PDO::PARAM_INT);
    $sql->bindValue('wonderID', $wonderID, PDO::PARAM_INT);
    $sql->execute();

    $wonderStats = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (empty($wonderStats)) {
      if ($failSuccess == 1) {
        $success = 1; $fail = 0;
      } else {
        $success = 0; $fail = 1;
      }

      $value = array('success' => $success, 'fail' => $fail);

      $sql = $db->prepare("INSERT INTO ". STATISTIC_TABLE ."
                             (type, name, value) 
                           VALUES (:type, :name, :value)");
      $sql->bindValue('type', WONDER_STATS_CACHE, PDO::PARAM_INT);
      $sql->bindValue('name', $wonderID, PDO::PARAM_INT);
      $sql->bindValue('value', json_encode($value), PDO::PARAM_STR);;
      $sql->execute();
    } else {
      $value = json_decode($wonderStats['value'], true);

      if ($failSuccess == 1) {
        $value['success']++;
      } else {
        $value['fail']++;
      }

      $sql = $db->prepare("UPDATE ". STATISTIC_TABLE ."
                            SET value = :value
                           WHERE type = :type
                             AND name = :name");
      $sql->bindValue('type', WONDER_STATS_CACHE, PDO::PARAM_INT);
      $sql->bindValue('name', $wonderID, PDO::PARAM_INT);
      $sql->bindValue('value', json_encode($value), PDO::PARAM_STR);
      $sql->execute();
    }
  }

  public static function checkRelations($relations, $targetTribe, $casterTribe, $casterTribeRelations, $targetTribeRelations) {
    $targetTribe = strtoupper($targetTribe);
    $casterTribe = strtoupper($casterTribe);

    foreach ($relations as $relation) {
      $valid = false;
      switch ($relation['type']) {
        case 'own2other':
          $check = (isset($casterTribeRelations['own'][$targetTribe]) && $casterTribeRelations['own'][$targetTribe]['relationType'] == $relation['relationID']) ? true : false;

          if (!$relation['negate'] && $check) {
            $valid = true;
          } else if ($relation['negate'] && !$check) {
            $valid = true;
          }
        break;

        case 'own2any':
          $check = TribeRelation::hasRelation($relation['relationID'], $casterTribeRelations['own']);

          if (!$relation['negate'] && $check) {
            $valid = true;
          } else if ($relation['negate'] && !$check) {
            $valid = true;
          }
        break;

        case 'other2own':
          $check = (isset($casterTribeRelations['other'][$targetTribe]) && $casterTribeRelations['other'][$targetTribe]['relationType'] == $relation['relationID']) ? true : false;

          if (!$relation['negate'] && $check) {
            $valid = true;
          } else if ($relation['negate'] && !$check) {
            $valid = true;
          }
        break;

        case 'other2any':
          $check = TribeRelation::hasRelation($relation['relationID'], $targetTribeRelations['own']);

          if (!$relation['negate'] && $check) {
            $valid = true;
          } else if ($relation['negate'] && !$check) {
            $valid = true;
          }
        break;

        case 'any2own':
          $check = TribeRelation::hasRelation($relation['relationID'], $casterTribeRelations['other']);
  
          if (!$relation['negate'] && $check) {
            $valid = true;
          } else if ($relation['negate'] && !$check) {
            $valid = true;
          }
        break;

        case 'any2other':
          $check = TribeRelation::hasRelation($relation['relationID'], $targetTribeRelations['other']);

          if (!$relation['negate'] && $check) {
            $valid = true;
          } else if ($relation['negate'] && !$check) {
            $valid = true;
          }
        break;
      }

      if ($valid == false) {
        return false;
      }
    }

    return true;
  }

  public static function processTribeWonder($wonderID, $casterTribeData, $targetTribeName) {
    global $db;

    if (empty($wonderID) || empty($casterTribeData) || empty($targetTribeName)) return -30;

    // check if wonder exists and is TribeWonder
    if (isset($GLOBALS['wonderTypeList'][$wonderID]) || !$wonder->isTribeWonder) {
      $wonder = $GLOBALS['wonderTypeList'][$wonderID];
    } else {
      return -33;
    }

    // check if tribes exist
    $targetTribeData = Tribe::getID($targetTribeName, true);
    if ($targetTribeID == 0) {
      return -15;
    }

    $casterTribeID = $casterTribeData['tribeID'];
    $targetTribeID = $targetTribeData['tribeID'];

    // check if tribe is valid
    if (!$targetTribeData['valid']) {
      return -34;
    }
  
    // check if caster tribe ist valid
    if (!$casterTribeData['valid']) {
      return -35;
    }

    $casterTribeRelations = TribeRelation::getRelations($casterTribeID);
    $targetTribeRelations = TribeRelation::getRelations($targetTribeID);

    $wonderPossible = false;
    foreach ($wonder->targetsPossible as $targetsPossible) {
      // check target
      if ($targetsPossible['target'] == 'own' && strtoupper($casterTribe) != strtoupper($targetTribe)) {
        continue;
      }

      if ($targetsPossible['target'] == 'other' && strtoupper($casterTribe) == strtoupper($targetTribe)) {
        continue;
      }

      // check relation
      $check = self::checkRelations($targetsPossible['relation'], $targetTribeID, $casterTribeID, $casterTribeRelations, $targetTribeRelations);

      if ($check == true) {
        $wonderPossible = true;
        break;
      }
    }

    if ($wonderPossible == false) {
      return -37;
    }

    // take wonder Costs from TribeStorage
    $memberNumber = Tribe::getMemberCount($casterTribe);
    if (!processProductionCost($wonder, 0, NULL, $memberNumber, true)) {
      return -33;
    }

    // does the wonder fail?
    if (((double)rand() / (double)getRandMax()) > $wonder->chance) {
      return 11; // wonder did fail
    }

    // schedule the wonder's impacts

    // create a random factor between -0.3 and +0.3
    $delayRandFactor = (rand(0,getrandmax()) / getrandmax()) * 0.6 - 0.3;
    // now calculate the delayDelta depending on the first impact's delay
    $delayDelta = $wonder->impactList[0]['delay'] * $delayRandFactor;

    // get targets
    $targets = self::getPlayerList($targetTribeID, false, true);

    if (sizeof($targets) == 0) {
      return -33;
    }

    $now = time();
    // loop over targets
    foreach ($targets as $playerID => $playerData) {
      foreach ($playerData['caves'] as $caveID => $caveData) {
        // loop over impacts
        foreach ($wonder->impactList as $impactID => $impact) {
          $delay = (int)(($delayDelta + $impact['delay']) * WONDER_TIME_BASE_FACTOR);

          $sql = $db->prepare("INSERT INTO " . EVENT_WONDER_TABLE . "
                                (casterID, sourceID, targetID, wonderID, impactID, start, end) 
                               VALUES
                                 (:playerID, :caveID, :targetID, :wonderID, :impactID, :start, :end)");
          $sql->bindValue('playerID', 0, PDO::PARAM_INT); // playerID 0, for not receiving lots of wonder-end-messages
          $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
          $sql->bindValue('targetID', $target['caveID'], PDO::PARAM_INT);
          $sql->bindValue('wonderID', $wonderID, PDO::PARAM_INT);
          $sql->bindValue('impactID', $impactID, PDO::PARAM_INT);
          $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
          $sql->bindValue('end', time_toDatetime($now + $delay), PDO::PARAM_STR);

          $sql->execute();
        } // end foreach impactList
      }
    } // end foreach target

    // send caster messages
    $messageClass = new Messages;
    $messageClass->sendSystemMessage($_SESSION['player']->playerID, 9, sprintf(_("Stammeswunder erwirkt auf %s", $targetTribe)), sprintf(_("Sie haben auf den Stamm %s ein Stammeswunder %s erwirkt.'", $targetTribe, $wonder->name)));

    // send target messages
    $targetPlayersArray = array();
    foreach ($targets as $target) {
      if (!isset($targetPlayersArray[$target['playerID']])) {
        $targetPlayersArray[$target['playerID']] = $target;
      }
    }

    foreach($targetPlayersArray as $target) {
      $messageClass->sendSystemMessage($target['playerID'], 9, 'Stammeswunder!', sprintf(_("Der Stamm %s hat ein Stammeswunder auf deine Höhlen gewirkt.'", $casterTribe)));
    }

    return 12;
  }

  public static function setBlockedTime($tribeID, $wonderID, $locked) {
    global $db;

    $locked[$wonderID] = time() + $GLOBALS['wonderTypeList'][$wonderID]->secondsBetween;

    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                          SET wonderLocked = :wonderLocked
                          WHERE tribeID = :tribeID");
    $sql->bindValue('wonderLocked', serialize($locked), PDO::PARAM_STR);
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return 6;
    }
  }
}

?>