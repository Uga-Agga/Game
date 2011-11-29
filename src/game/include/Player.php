<?php
/*
 * Player.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('languages/languages.php');

class Player {

  var $playerID;
  var $name;

  var $email;
  var $email2;

  var $created;
  var $avatar;
  var $awards;
  var $description;
  var $fame;
  var $gfxpath;
  var $icq;
  var $language;
  var $lastVote;
  var $origin;
  var $questionCredits;
  var $secureCaveCredits;
  var $sex;
  var $takeover_max_caves;
  var $template;
  var $timeCorrection;
  var $tribe;
  var $tribeBlockEnd;

  function Player($record) {

    $this->playerID           = $record['playerID'];
    $this->name               = $record['name'];
    $this->email              = $record['email'];
    $this->email2             = $record['email2'];
    $this->created            = $record['created'];
    $this->avatar             = $record['avatar'];
    $this->awards             = $record['awards'];
    $this->description        = $record['description'];
    $this->fame               = $record['fame'];
    $this->gfxpath            = $record['gfxpath'];
    $this->icq                = $record['icq'];
    $this->language           = $record['language'];
    $this->lastVote           = $record['lastVote'];
    $this->origin             = $record['origin'];
    $this->questionCredits    = $record['questionCredits'];
    $this->secureCaveCredits  = $record['secureCaveCredits'];
    $this->sex                = $record['sex'];
    $this->takeover_max_caves = $record['takeover_max_caves'];
    $this->template           = $record['template'];
    $this->timeCorrection     = $record['timeCorrection'];
    $this->tribe              = $record['tribe'];
    $this->tribeBlockEnd      = $record['tribeBlockEnd'];
  }

  static function getPlayer($playerID, $complete=false) {
    global $db;

    // get player out of the database
    $sql = $db->prepare("SELECT *
                         FROM " . PLAYER_TABLE . "
                         WHERE playerID = :playerID");
    $sql->bindParam('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) return NULL;

    $playerData = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (!$playerData)  return NULL;

    return ($complete) ? $playerData : new Player($playerData);
  }

  static function getHistory($playerID) {
    global $db;

    // prepare result
    $retval = array();

    // prepare query
    $sql = $db->prepare("SELECT * FROM ". PLAYER_HISTORY_TABLE ." 
                         WHERE playerID = :playerID 
                         ORDER BY timestamp ASC");
    $sql->bindValue('playerID', $playerID);

    // get all entries
    if (!($sql->rowCountSelect() == 0)) {
      if ($sql->execute()) {
        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
          $row['timestamp'] = time_formatDatetime($row['timestamp']);
          $retval[] = $row;
        }
      }
    }

    return $retval;
  }

  public static function addHistoryEntry($playerID, $entry, $timestamp = -1) {
    global $db;

    if ($timestamp == -1)
      $timestamp = time();

    // prepare query
    $sql = $db->prepare("INSERT INTO ". PLAYER_HISTORY_TABLE ." 
                     (playerID, timestamp, entry) 
                     VALUES (:playerID, :timestamp, :entry)");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    $sql->bindValue('timestamp', time_toDatetime($timestamp), PDO::PARAM_STR);
    $sql->bindValue('entry', $entry, PDO::PARAM_STR);
    
    return $sql->execute();
  }

  /** This function returns the difference between UTC and the
   *  player'slocaltime in seconds
   */
  function getTimeCorrection() {
    return intval(date("Z"));
  }

  function getTemplatePath() {
    return sprintf('%s/templates/%s/%s/', UA_GAME_DIR, $this->language, Config::$template_paths[$this->template]);
  }

  /**
   * This function inits everything I18n-tish.
   *
   */
  function init_i18n() {
    setlocale(LC_MESSAGES, $this->language);
    bindtextdomain(LANGUAGE_DOMAIN, UA_GAME_DIR . '/include/languages');
    textdomain(LANGUAGE_DOMAIN);
  }
}

?>