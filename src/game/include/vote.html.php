<?php
/*
 * vote.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');


################################################################################


/**
 * Diese Konstante gibt an, wieviele Sekunden vergehen müssen, damit der User
 * erneut den Vote-Knopf eingeblendet bekommt.
 */
DEFINE('VOTE_INTERVAL', 60 * 60 * 24); // every day

$VOTE_CLASSES = array('gn' => 'GalaxyNewsVoteButton');


################################################################################

/**
 * This class describes a vote button.
 */

class VoteButton {
  var $id;
  var $imgSrc;
  var $link;
  var $arguments;

  function VoteButton(){
  }

  function getButtonParams() {

    return array('imgSrc' => $this->imgSrc,
                   'id'     => $this->id);
  }

  function getURL() {

    // concatenate arguments to url
    $args = array();
    foreach ($this->arguments AS $k => $v)
      $args[] = sprintf('%s=%s', $k, $v);
    $args = implode('&', $args);

    return $this->link . (strlen($args) ? ('?' . $args) : '');
  }
}

class GalaxyNewsVoteButton extends VoteButton {

  function GalaxyNewsVoteButton() {
    $this->id        = 'gn';
    $this->imgSrc    = 'http://www.galaxy-news.de/images/vote.gif';
    $this->link      = 'http://www.galaxy-news.de/';
    $this->arguments = array('page'    => 'charts',
                             'op'      => 'vote',
                             'game_id' => '39');
  }
}

################################################################################


function vote_main() {
  global $request;
  // initialize return value
  $result = '';

  // get current task
  $task = $request->getVar('task', '');

  switch ($task) {

    // show vote button
    case 'show':
    default:
      $result = vote_show();
      break;

    // vote button was activated
    case 'vote':
      $result = vote_vote();
  }

  return $result;
}


################################################################################


function vote_show() {
  global $template, $VOTE_CLASSES;

  // should the buttons be shown?
  if ($_SESSION['player']->lastVote + VOTE_INTERVAL > time()) {
    return '';
  }

  // show each button
  $buttons = array();
  foreach ($VOTE_CLASSES as $class) {
    $button = new $class();
    $buttons[] = $button->getButtonParams();
  }

  $template->addVar('vote_button', $buttons);
}


################################################################################


function vote_vote() {
  global $VOTE_CLASSES, $db;

  // already voted
  if ($_SESSION['player']->lastVote + VOTE_INTERVAL > time()) {
    exit();
  }

  // get button id
  $id = strval($request->getVar('id', ''));

  // get class
  if (!isset($VOTE_CLASSES[$id])) {
    exit();
  }
  $class = $VOTE_CLASSES[$id];

  // create new object
  $button = new $class();

  // update database
  $now = time();
  $sql = $db->prepare("UPDATE ". PLAYER_TABLE ." 
                       SET lastVote = :lastVote
                       WHERE playerID = :playerID");
  $sql->bindValue('lastVote', $now, PDO::PARAM_INT);
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->execute();

  $_SESSION['player']->lastVote = $now;

  // locate to voting site
  Header("Location: " . $button->getURL());
  exit;
}

?>