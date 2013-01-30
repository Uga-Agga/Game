<?php
/*
 * ranking.html.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function ranking_getContent() {
  global $template;

  // open template
  $template->setFile('rankingPlayer.tmpl');
  $template->setShowResource(false);

  $religions = ranking_getReligiousDistribution();

  if (!isset($religions['uga'])) $religions['uga'] = 0;
  if (!isset($religions['agga'])) $religions['agga'] = 0;

  if($religions['uga']+$religions['agga'] != 0) {
    $ugapercent = round($religions['uga']/($religions['uga'] + $religions['agga'])*100);
    $aggapercent = round($religions['agga']/($religions['uga'] + $religions['agga'])*100);
  } else {
    $ugapercent = 0;
    $aggapercent = 0;
  }

  $numRows = rankingPlayer_getMaxRows();
  $offset = 0; $row = array();
  if ($numRows > 0) {
    $search = Request::getVar('search', '');
    $page = Request::getVar('page', 0);
    if ($search !== '') {
      $offset = rankingPlayer_checkOffsetBySearch($search, $numRows);
      if ($offset < 0) {
        $offset = 0;
        $template->addVar('status_msg', array('type' => 'error', 'message' => 'Der gesuchte Spieler wurde nicht gefunden'));
      }
    } else {
      $offset = rankingPlayer_checkOffsetByPage($_SESSION['player']->playerID, $page, $numRows);
    }

    $row = rankingPlayer_getRowsByOffset($offset);
  }

  $template->addVars(array(
    'page'          => ceil($offset/RANKING_ROWS)+1,
    'max_pages'     => ceil($numRows/RANKING_ROWS),
    'rows_per_page' => RANKING_ROWS,
    'religious'     =>  array('ugapercent' => $ugapercent, 'aggapercent' => $aggapercent),
    'row'           => $row,
  ));
}

function rankingTribe_getContent(){
  global $template;

  // open template
  $template->setFile('rankingTribe.tmpl');
  $template->setShowRresource(false);

  $numRows = rankingTribe_getMaxRows();
  $offset = 0; $row = array();
  if ($numRows > 0) {
    $search = Request::getVar('search', '');
    $page = Request::getVar('page', 0);
    if ($search !== '') {
      $offset = rankingTribe_checkOffsetBySearch($search, $numRows);
      if ($offset < 0) {
        $offset = 0;
        $template->addVar('status_msg', array('type' => 'error', 'message' => 'Der gesuchte Stamm wurde nicht gefunden'));
      }
    } else {
      $offset = rankingTribe_checkOffsetByPage($_SESSION['player']->tribeID, $page, $numRows);
    }

    $row = rankingTribe_getRowsByOffset($offset);
  }

  $template->addVars(array(
    'page'          => ceil($offset/RANKING_ROWS)+1,
    'max_pages'     => ceil($numRows/RANKING_ROWS),
    'rows_per_page' => RANKING_ROWS,
    'row'           => $row,
  ));
}

?>