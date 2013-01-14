<?php
/*
 * playerDetail.html.php -
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011-2012 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function player_getContent($caveID, $playerID) {
  global $db, $template;

  // open template
  $template->setFile('playerDetail.tmpl');
  $template->setShowRresource(false);

  // workaround, if no playerID is submitted! TODO
  if ($playerID == 0) $playerID = $_SESSION['player']->playerID;

  $playerDetails = Player::getPlayer($playerID, true);
  if (!$playerDetails) {
    $template->throwError('Da wollte irgendwie was nicht aus der Datenbank ausgelesen werden :(');
    return;
  }

  if ($playerDetails['avatar']) {
    $playerDetails['avatar'] = @unserialize($playerDetails['avatar']);

    $template->addVars(array(
      'player_avatar'        => $playerDetails['avatar']['path'],
      'player_avatar_width'  => $playerDetails['avatar']['width'],
      'player_avatar_height' => $playerDetails['avatar']['height'],
    ));
  }

  if (!empty($playerDetails['awards'])) {
    $tmp = explode('|', $playerDetails['awards']);
    $awards = array();
    foreach ($tmp AS $tag) $awards[] = $tag;
    $playerDetails['award'] = $awards;
  }
  unset($playerDetails['awards']);

  foreach($playerDetails as $k => $v)  {
    if (! $v ) {
      $playerDetails[$k] = _('k.A.');
    }
  }

  $playerDetails['mail_receiver'] = urlencode($playerDetails['name']);
  $playerDetails['caveID'] = $caveID;
  $playerTribe = $playerDetails['tribe'];

  $timediff = getUgaAggaTimeDiff(time_fromDatetime($playerDetails['created']), time());
  $playerDetails['age'] = 18 + $timediff['year'];

  // init messages class
  $parser = new parser;
  $playerDetails['description'] = $parser->p($playerDetails['description']);

  // show player's caves
  $caves = getCaves($playerID);
  if ($caves) {
    $template->addVar('player_caves',  $caves);
  }

  // show player's history
  $history = Player::getHistory($playerID);
  if (sizeof($history)) {
    $template->addVar('player_history',  $history);
  }

  //get player rank
  $sql = $db->prepare("SELECT rank FROM ". RANKING_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, pDo::PARAM_INT);
  if (!$sql->execute()) page_dberror();

  if ($row = $sql->fetch()) {
    $playerDetails['rank'] = $row['rank'];
  } else {
    $playerDetails['rank'] = '';
  }

  $template->addVars(array(
    'player_details' => $playerDetails
  ));
}