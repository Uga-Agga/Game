<?php
/*
 * playerDetail.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function player_getContent($caveID, $playerID) {
  global $db, $no_resource_flag;

  $no_resource_flag = 1;

  // workaround, if no playerID is submitted! TODO
  if ($playerID == 0) $playerID = $_SESSION['player']->playerID;
  
  $sql = $db->prepare("SELECT * FROM ". PLAYER_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  
  if (!$sql->execute()) page_dberror();

  if (!$row = $sql->fetch(PDO::FETCH_ASSOC)) page_dberror();
  $sql->closeCursor();

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'playerDetail.ihtml');

  if ($row['avatar']) {
    $size = getimagesize($row['avatar']);
     
    tmpl_set($template, 'DETAILS/AVATAR_IMG/avatar', $row['avatar']);
    tmpl_set($template, 'DETAILS/AVATAR_IMG/width',  ($size[0] <= MAX_AVATAR_WIDTH) ? $size[0] : MAX_AVATAR_WIDTH);
    tmpl_set($template, 'DETAILS/AVATAR_IMG/height', ($size[1] <= MAX_AVATAR_HEIGHT) ? $size[1] : MAX_AVATAR_HEIGHT);
  }

  if (!empty($row['awards'])) {
    $tmp = explode('|', $row['awards']);
    $awards = array();
    foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
    $row['award'] = $awards;
  }
  unset($row['awards']);

  foreach($row as $k => $v)  {
    if (! $v ) {
      $row[$k] = _('k.A.');
    }
  }

  $row['mail_modus']    = NEW_MESSAGE;
  $row['mail_receiver'] = urlencode($row['name']);
  $row['caveID']        = $caveID;
  $playerTribe          = $row['tribe'];

  $timediff = getUgaAggaTimeDiff(time_fromDatetime($row['created']), time());
  $row['age'] = 18 + $timediff['year'];
  
    // init messages class
  $parser = new parser;
  $row['description'] = $parser->p($row['description']);

  tmpl_set($template, 'DETAILS', $row);

  // show player's caves
  $caves = getCaves($playerID);
  if ($caves) {
    tmpl_set($template, '/DETAILS/CAVES', $caves);
  }

  //show bodycount
  // Keinen Bodycount fuers erste.... Nebrot
  //$body_count = $row['body_count'];
  //tmpl_set($template, '/DETAILS/BODYCOUNT/body_count', $body_count);


  // show player's history
  $history = Player::getHistory($playerID);
  if (sizeof($history)) {
    tmpl_set($template, '/DETAILS/HISTORY/ENTRY', $history);
  } else {
    tmpl_set($template, '/DETAILS/HISTORY/NOENTRIES/iterate', '');
  }

  //get player rank
  $sql = $db->prepare("SELECT rank FROM ". RANKING_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, pDo::PARAM_INT);
  if (!$sql->execute()) page_dberror();

  if ($row = $sql->fetch()) {
    $rank = $row['rank'];
  } else {
    $rank = '';
  }

  // create player ranking link
  tmpl_set($template, array('/DETAILS/playerRank' => $rank,
                             '/DETAILS/playerRankOffset' => $rank, //ranking_checkOffset($playerID, FALSE),
                             '/DETAILS/tribeRankOffset' => rankingTribe_checkOffset($playerTribe)));

  return tmpl_parse($template);
}