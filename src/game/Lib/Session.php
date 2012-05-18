<?php
/*
 * Session.php -
 * Copyright (c) 2012 David Unger <unger.dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set Namespace **/
namespace Lib;

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################
class Session {
  public static $caves    = NULL;
  public static $player   = NULL;
  public static $playerID = 0;

  public function register() {}

  public static function start() {
    // check for cookie
    if (!sizeof($_COOKIE)) {
      throw new Exception('Sie müssen 3rd party cookies erlauben.');
    }

    // start session
    session_start();

    // check for valid session
    if (!isset($_SESSION['player']) || !$_SESSION['player']->playerID) {
      //header("Location: " . \Lib\Config::GAME_END_URL . "?id=inaktiv");
      //exit;
    }

    self::$playerID = $_SESSION['player']->playerID;
  }

  public static function refreshPlayerData() {
    self::$player = Model\Player::getPlayer(self::$playerID);
    if (self::$player == NULL) {
      throw new Exception('Fehler beim auslesen des Spielers!');
    }
    
    self::$caves  = Model\Cave::getByPlayerID(self::$playerID);
  }

  public static function update() {
    if (isset($_SESSION['lastAction']) && time() > $_SESSION['lastAction'] + SESSION_MAX_LIFETIME) {
      return false;
    }
    $_SESSION['lastAction'] = time();

    // calculate seconds with 1000s frac
    list($usec, $sec) = explode(" ", microtime());
    $microtime = $sec + $usec;
  
    $sql = Database::$db->prepare("UPDATE " . SESSION_TABLE . "
                         SET microtime = :setMicrotime
                         WHERE playerID = :playerID
                           AND sessionID = :sessionID
                           AND ((lastAction < (NOW() - INTERVAL 2 SECOND) + 0)
                             OR microtime <= :whereMicrotime - :requestTimeout)");
    $sql->bindValue('setMicrotime', $microtime, \PDO::PARAM_INT);
    $sql->bindValue('playerID', $_SESSION['player']->playerID, \PDO::PARAM_INT);
    $sql->bindValue('sessionID', $_SESSION['session']['sessionID'], \PDO::PARAM_INT);
    $sql->bindValue('whereMicrotime', $microtime, \PDO::PARAM_INT);
    $sql->bindValue('requestTimeout', Config::WWW_REQUEST_TIMEOUT, \PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }
  
    return true;
  }
}

?>