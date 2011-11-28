<?php
/*
 * sessionstart.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("config.inc.php");

require_once("include/config.inc.php");
require_once("include/db.inc.php");
require_once("include/page.inc.php");
require_once("include/params.inc.php");
require_once("include/Player.php");

// set session id
if (function_exists('posix_getpid')) {
  session_id(md5(microtime().posix_getpid()));
} else {
  session_id(md5(microtime().rand()));
}

// start session
session_start();

// keine Variablen angegeben
$sessionID = Request::getVar('id', '');
$playerID = Request::getVar('userID', 0);
$noGfx = Request::getVar('nogfx', 0);

if (!$sessionID || !$playerID) {
  page_error403("Fehlende Loginvariablen.");
}

// connect to database
if (!($db = DbConnect())) {
  header("Location: Config::GAME_END_URL?id=db");
  exit;
}

//check user from Session-table with id
$sql = $db->prepare("SELECT *
                     FROM " . SESSION_TABLE . "
                     WHERE s_id = :s_id
                       AND playerID = :playerID");
$sql->bindValue('s_id', $sessionID, PDO::PARAM_STR);
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
if (!$sql->execute()) {
  header("Location: Config::GAME_END_URL?id=wrongSessionID");
  exit;
}

$sessionRow = $sql->fetch(PDO::FETCH_ASSOC);
$sql->closeCursor();

// sessionstart sollte nur einmal augerufen werden können
$sql = $db->prepare("UPDATE " . SESSION_TABLE . "
                     SET s_id_used = 1
                     WHERE s_id = :s_id
                       AND playerID = :playerID
                       AND s_id_used = 0");
$sql->bindValue('s_id', $sessionID, PDO::PARAM_STR);
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
if (!$sql->execute() || $sql->rowCount() == 0) {
  header("Location: Config::GAME_END_URL?id=wrongSessionID");
  exit;
}

// get player by playerID for session
$player = Player::getPlayer($playerID);
if (!$player) {
  header("Location: Config::GAME_END_URL?id=wrongSessionID");
  exit;
}

// put user, its session and nogfx flag into session
$_SESSION['player']    = $player;
$_SESSION['nogfx']     = ($noGfx == 1);
$_SESSION['session']   = $sessionRow;
$_SESSION['logintime'] = date("YmdHis");

// initiate Session messages
$_SESSION['messages'] = array();

// go to start url
header("Location: Config::GAME_START_URL");
exit;

?>