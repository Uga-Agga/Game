<?php
/*
 * ranking.inc.php -
 * Copyright (c) 2004  OGP-Team
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');


function rankingPlayer_checkOffsetBySearch($search, $numRows) {
  global $db;

  // Es gibt weniger Spieler als maximal angezeigt werden können? Ab Spieler 1 auflisten
  if ($numRows <= RANKING_ROWS) {
    return 0;
  }

  $sql = $db->prepare("SELECT rank
                       FROM ". RANKING_TABLE ." 
                       WHERE name LIKE :search");
  $sql->bindValue('search', $search, PDO::PARAM_STR);
  if (!$sql->execute()) return -1;

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (!$row) {
    return -1;
  } else {
    return (abs(ceil($row['rank']/RANKING_ROWS))-1) * RANKING_ROWS;
  }
}

function rankingPlayer_checkOffsetByPage($playerID, $page, $numRows) {
  global $db;

  // Es gibt weniger Spieler als maximal angezeigt werden können? Ab Spieler 1 auflisten
  if ($numRows <= RANKING_ROWS) {
    return 0;
  }

  if ($page == 0) {
    // $page is not set yet, show the actual player in the middle of the list
    $sql = $db->prepare("SELECT rank
                         FROM ". RANKING_TABLE." 
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) return 0;
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (!$row) {
      return 0;
    } else {
      return abs(floor($row['rank']/RANKING_ROWS)) * RANKING_ROWS;
    }
  } else {
    $maxPages = ceil($numRows/RANKING_ROWS);
    if ($page > $maxPages) {
      return (($maxPages-1) * RANKING_ROWS);
    } else {
      return (($page-1) * RANKING_ROWS);
    }
  }
}

function rankingPlayer_getRowsByOffset($offset) {
  global $db;

  if ($offset < 0) {
    return array();
  }

  $sql = $db->prepare("SELECT r.rank, r.playerID AS playerID, r.name, r.average AS points, r.religion, p.tribe, r.caves, p.awards, r.fame as kp, (IF(ISNULL(t.leaderID),0,r.playerID = t.leaderID)) AS is_leader
                       FROM ". RANKING_TABLE ." r
                         LEFT JOIN ". PLAYER_TABLE ." p ON r.playerID = p.playerID
                         LEFT JOIN ". TRIBE_TABLE ." t ON p.tribe = t.tag
                       ORDER BY rank ASC LIMIT :offset, :rankingRows");
  $sql->bindValue('offset', $offset, PDO::PARAM_INT);
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

function rankingPlayer_getMaxRows() {
  global $db;

  // get numRows of Ranking
  $sql = $db->prepare("SELECT COUNT(*) AS num_rows FROM " . RANKING_TABLE);
  if (!$sql->execute()) return 0;

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret['num_rows'];
}

function rankingTribe_checkOffsetBySearch($search, $numRows) {
  global $db;

  // Es gibt weniger ´Stämme als maximal angezeigt werden können? Ab Stamm 1 auflisten
  if ($numRows <= RANKING_ROWS) {
    return 0;
  }

  $sql = $db->prepare("SELECT rank
                       FROM ". RANKING_TRIBE_TABLE ." 
                       WHERE tribe LIKE :search");
  $sql->bindValue('search', $search, PDO::PARAM_STR);
  if (!$sql->execute()) return -1;

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (!$row) {
    return -1;
  } else {
    return (abs(ceil($row['rank']/RANKING_ROWS))-1) * RANKING_ROWS;
  }
}

function rankingTribe_checkOffsetByPage($tribeID, $page, $numRows) {
  global $db;

  // Es gibt weniger Spieler als maximal angezeigt werden können? Ab Spieler 1 auflisten
  if ($numRows <= RANKING_ROWS) {
    return 0;
  }

  if ($page == 0) {
    if ($tribeID == 0) {
      return 0;
    }

    // $page is not set yet, show the actual player in the middle of the list
    $sql = $db->prepare("SELECT rank
                         FROM ". RANKING_TRIBE_TABLE." 
                         WHERE tribeID = :tribeID");
    $sql->bindValue('tribeID', $tribeID, PDO::PARAM_INT);
    if (!$sql->execute()) return 0;
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (!$row) {
      return -1;
    } else {
      return abs(floor($row['rank']/RANKING_ROWS)) * RANKING_ROWS;
    }
  } else {
    $maxPages = ceil($numRows/RANKING_ROWS);
    if ($page > $maxPages) {
      return (($maxPages-1) * RANKING_ROWS);
    } else {
      return (($page-1) * RANKING_ROWS);
    }
  }
}

function rankingTribe_getRowsByOffset($offset) {
  global $db;

  $sql = $db->prepare("SELECT r.*, r.playerAverage AS average, t.awards, t.war_won, t.war_lost
                       FROM ". RANKING_TRIBE_TABLE ." r
                         LEFT JOIN ". TRIBE_TABLE ." t ON r.tribe = t.tag
                       ORDER BY r.rank ASC
                       LIMIT :offset, :rankingRows");
  $sql->bindValue('offset', $offset, PDO::PARAM_INT);
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

function rankingTribe_getMaxRows() {
  global $db;

  // get numRows of Ranking
  $sql = $db->prepare("SELECT COUNT(*) AS num_rows FROM " . RANKING_TRIBE_TABLE);
  if (!$sql->execute()) return 0;

  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return $ret['num_rows'];
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