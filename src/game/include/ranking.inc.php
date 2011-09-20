<?php
/*
 * ranking.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function ranking_checkOffset($playerID, $offset) {

  global $db;

  // get numRows of Ranking
  $sql = $db->prepare("SELECT COUNT(*) AS num_rows FROM " . RANKING_TABLE);

  if (!$sql->execute() ) {
    return -1;
  }
  if ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $num_rows = $row['num_rows'];
  } else {
    // something is real wrong, just return '-1' as error message
    return -1;
  }

  if (strval(intval($offset)) != $offset) {
    // $offset is NOT a line number

    if (!isset($offset)) {
      // $offset is not set yet, show the actual player in the middle of the list
      $sql = $db->prepare("SELECT rank
                           FROM ". RANKING_TABLE." 
                           WHERE playerID = :playerID");
      $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

    } else {
      // $offset is a player name
      $sql = $db->prepare("SELECT rank
                           FROM ". RANKING_TABLE ." 
                           WHERE name LIKE :offset");
      $sql->bindValue('offset', $offset, PDO::PARAM_STR);
    }

    if (!$sql->execute()) {
      return -1;
    }

    // if at least one record exists, get 'rank'
    if ($row = $sql->fetch()){
      $offset = $row['rank'] - (!isset($offset) ? floor(RANKING_ROWS/2) : 0);
    } else {
      $offset = 1;
    }
  }

  // the $offset is possibly out of bounds so make it right
  if ($offset < 1)
    $offset = 1;
  if ($offset > $num_rows)
    $offset = $num_rows;

  return $offset;
}

function rankingTribe_checkOffset($offset) {

  global $db;

  // get numRows of Ranking
  $sql = $db->prepare("SELECT COUNT(*) AS num_rows FROM " . RANKING_TRIBE_TABLE);

  if (!$sql->execute()) {
    return -1;
  }
  if ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $num_rows = $row['num_rows'];
  } else {
    // something is real wrong, just return '-1' as error message
    return -1;
  }

  //if (!isset($offset))
  //    $offset = 1;

  if (strval(intval($offset)) != $offset) {

    if (!isset($offset) && $_SESSION['player']->tribe != '') {
      // $offset is not set yet, show the actual player's tribe in the middle of the list
      $sql = $db->prepare("SELECT rank
                           FROM ". RANKING_TRIBE_TABLE ." 
                           WHERE tribe = :tibe");
      $sql->bindValue('tribe', $_SESSION['player']->tribe, PDO::PARAM_STR);
      
    } else {
      // $offset is a tribe name
      $sql = $db->prepare("SELECT rank
                           FROM ". RANKING_TRIBE_TABLE ." 
                           WHERE tribe LIKE :offset");
      $sql->bindValue('offset', $offset, PDO::PARAM_STR);
    }

    if (!$sql->execute()) {
      return -1;
    }

    // if at least one record exists, get 'rank'
    if ($row = $sql->fetch()) {
      $offset = $row['rank'] - (!isset($offset) ? floor(RANKING_ROWS/2) : 0);
    } else {
      $offset = 1;
    }
  }

  // the $offset is possibly out of bounds so make it right
  if ($offset < 1)
    $offset = 1;
  if ($offset > $num_rows)
    $offset = $num_rows;

  return $offset;
}


function ranking_getRowsByOffset($caveID, $offset) {

  global $db;

  $sql = $db->prepare("SELECT r.rank, r.playerID AS playerID, r.name, r.average AS points, r.religion, p.tribe, r.caves, p.awards, r.fame as kp, (IF(ISNULL(t.leaderID),0,r.playerID = t.leaderID)) AS is_leader
                       FROM ". RANKING_TABLE ." r
                         LEFT JOIN ". PLAYER_TABLE ." p ON r.playerID = p.playerID
                         LEFT JOIN ". TRIBE_TABLE ." t ON p.tribe = t.tag
                       ORDER BY rank ASC LIMIT :offset, :rankingRows");
  $sql->bindValue('offset', ($offset - 1), PDO::PARAM_INT);
  $sql->bindValue('rankingRows', RANKING_ROWS, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return array();
  }

  $result = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['awards'])){
      $tmp = explode('|', $row['awards']);
      $awards = array();
      foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
      $row['award'] = $awards;
    }
    $result[] = $row;
  }

  return $result;
}

function rankingTribe_getRowsByOffset($caveID, $offset) {

  global $db;

  $sql = $db->prepare("SELECT r.*, r.playerAverage AS average, t.awards, t.war_won, t.war_lost
                       FROM ". RANKING_TRIBE_TABLE ." r
                         LEFT JOIN ". TRIBE_TABLE ." t ON r.tribe = t.tag
                       ORDER BY r.rank ASC
                       LIMIT :offset, :rankingRows");
  $sql->bindValue('offset', $offset - 1, PDO::PARAM_INT);
  $sql->bindValue('rankingRows', RANKING_ROWS, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return array();
  }

  $result = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['awards'])){
      $tmp = explode('|', $row['awards']);
      $awards = array();
      foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
      $row['award'] = $awards;
    }
    $result[] = $row;
  }

  return $result;
}

function ranking_getReligiousDistribution() {

  global $db;

  $sql = $db->prepare("SELECT religion, COUNT(religion) AS sum
                       FROM ". RANKING_TABLE ."
                       WHERE religion NOT LIKE 'none'
                       GROUP BY religion");

  if (!$sql->execute()) {
    return array();
  }

  $result = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $result[$row['religion']] = $row['sum'];
  }
  return $result;
}

?>