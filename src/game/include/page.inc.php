<?php
/*
 * page.inc.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once("include/params.inc.php");
require_once("include/config.inc.php");
require_once("include/db.inc.php");
require_once("include/Player.php");

function page_error403($message) {
  global $config;

  @session_destroy();
  Header("Location: ".$config->ERROR403_URL."?message=".urlencode($message));
  exit;
}

function page_dberror() {
  global $config;

  header("Location: finish.php?id=db");
  exit;
}

function stopwatch($start=false) {
  static $starttime;

  list($usec, $sec) = explode(" ", microtime());
  $mt = ((float)$usec + (float)$sec);

  if (!empty($start))
    return ($starttime = $mt);
  else
    return $mt - $starttime;
}

function page_start() {
  global $db, $config;

  // start stopwatch
  stopwatch('start');

  // get configuration
  $config = new Config();

  // check for cookie
  // FIXME german string..
  if (!sizeof($_COOKIE)) {
    page_error403('Sie m&uuml;ssen 3rd party cookies erlauben.');
  }

  // start session
  session_start();

  // get request params
  $params = new Params();

  // check for valid session
  // FIXME german string..
  if (!isset($_SESSION['player']) || !$_SESSION['player']->playerID) {
    page_error403(sprintf('Sie waren für %d Minuten oder mehr inaktiv.', date("i", ini_get("session.gc_maxlifetime"))));
  }

  // connect to database
  if (!($db = DbConnect())) {
    page_dberror();
  }

  // init I18n
  $_SESSION['player']->init_i18n();
}

function page_refreshUserData() {
  global $db, $config;

  $player = Player::getPlayer($_SESSION['player']->playerID);
  if (!$player)
    return FALSE;

  $_SESSION['player'] = $player;

  return TRUE;
}

function page_end($watch = true) {
  global $db, $config;
  if ($config->RUN_TIMER ||$watch) {
    $proctime  = stopwatch();
    $dbpercent = round($db->getQueryTime()/$proctime * 100, 2);

    echo "\n<!-- Seite aufgebaut in ".$proctime." Sekunden (".
         (100 - $dbpercent)."% PHP - ".
         $dbpercent."% MySQL) mit ".
         $db->getQueryCount()." Abfragen in ".$db->getQueryTime()."Sek. -->";
    echo "\n<!-- Memory Usage: " . page_memoryConvert(memory_get_usage(true)) . " -->";
  }

  $db = NULL;
}

function page_memoryConvert($size) {
  $unit=array('b','kb','mb','gb','tb','pb');
  return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function page_startTimer() {
  list($usec, $sec) = explode(" ", microtime());
  return ((float)$usec + (float)$sec);
}

function page_stopTimer($time) {
  $newTime = page_startTimer();
  return $newTime - $time;
}

function page_sessionExpired() {
  return isset($_SESSION['lastAction']) && time() > $_SESSION['lastAction'] + SESSION_MAX_LIFETIME;
}

function page_sessionValidate() {
  global $config, $db;

  // calculate seconds with 1000s frac
  list($usec, $sec) = explode(" ", microtime());
  $microtime = $sec + $usec;

  $sql = $db->prepare("UPDATE " . SESSION_TABLE . "
                       SET microtime = :setMicrotime
                       WHERE playerID = :playerID
                         AND sessionID = :sessionID
                         AND ((lastAction < (NOW() - INTERVAL 2 SECOND) + 0)
                           OR microtime <= :whereMicrotime - :requestTimeout)");
  $sql->bindValue('setMicrotime', $microtime, PDO::PARAM_INT);
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('sessionID', $_SESSION['session']['sessionID'], PDO::PARAM_INT);
  $sql->bindValue('whereMicrotime', $microtime, PDO::PARAM_INT);
  $sql->bindValue('requestTimeout', $config->WWW_REQUEST_TIMEOUT, PDO::PARAM_INT);
  
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return FALSE;
  }
  $sql->closeCursor();

  return true; //md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['HTTP_ACCEPT_LANGUAGE']) == $_SESSION['session']['loginchecksum'];
}

function page_getModus() {
  global $config;

  $modus = request_var('modus', NEWS);
  if (empty($modus)) {
    $modus = NEWS;
  }

  if (in_array($modus, $config->rememberModusInclude)) {
    $_SESSION['current_modus'] = $modus;
  } else {
    $_SESSION['current_modus'] = NEWS;
  }

  return $modus;
}

function page_logRequest($modus, $caveID) {
  global $config, $db;

  if ($config->LOG_ALL && in_array($modus, $config->logModusInclude)){
    $sql = $db->prepare("INSERT INTO " . LOG_X_TABLE . date("w") . "
                          (playerID, caveID, ip, request, sessionID)
                        VALUES (:playerID, :caveID, :ip, :request, :sessionID)");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
    $sql->bindValue('ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
    $sql->bindValue('request', var_export($_REQUEST, TRUE), PDO::PARAM_STR);
    $sql->bindValue('sessionID', session_id(), PDO::PARAM_STR);
    $sql->execute();
  }
}

function page_ore() {
  global $db;
  //TODO
  return; 
  $now = time();

  // increment time diff count
  if (isset($_SESSION['ore_time'])) {
    $diff = $now - $_SESSION['ore_time'];
    if (!isset($_SESSION['ore_time_diff'][$diff])) {
      $_SESSION['ore_time_diff'][$diff] = 1;
    } else {
      $_SESSION['ore_time_diff'][$diff]++;
    }

    // increment counter and log if required
    if (++$_SESSION['ore_counter'] == 50) {
      $sql = $db->prepare("INSERT INTO ore_log
                            (playerID, time_diff, stamp, sid)
                          VALUES (:playerID, :time_diff, :stamp, :sid)");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      $sql->bindValue('time_diff', addslashes(var_export($_SESSION['ore_time_diff'], TRUE)), PDO::PARAM_INT);
      $sql->bindValue('stamp', addslashes(time_toDatetime($now)), PDO::PARAM_STR);
      $sql->bindValue('sid', session_id(), PDO::PARAM_STR);
      $sql->execute();
  
      $_SESSION['ore_counter'] = 0;
    }
  }

  // set new timestamp
  $_SESSION['ore_time'] = $now;
}

?>