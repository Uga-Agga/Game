<?php
/*
 * CaveBookmarks.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Model.php');

DEFINE('CAVEBOOKMARKS_NOERROR',             0x00);
DEFINE('CAVEBOOKMARKS_DELETED',             0x01);

DEFINE('CAVEBOOKMARKS_ERROR_NOSUCHCAVE',    0x02);
DEFINE('CAVEBOOKMARKS_ERROR_MAXREACHED',    0x03);
DEFINE('CAVEBOOKMARKS_ERROR_INSERTFAILED',  0x04);

DEFINE('CAVEBOOKMARKS_ERROR_DELETEFAILED',  0x05);


class CaveBookmarks_Model extends Model {

  function CaveBookmarks_Model() {
  }

  function getCaveBookmarks($extended = false) {
    global $db;

    // init return value
    $result = array();
    $names = array();
    // prepare query
    $sql = $db->prepare("SELECT cb.*, c.name, c.xCoord, c.yCoord, ".
                   "p.playerID, p.name as playerName, p.tribeID, t.tag as tribe, ".
                   "r.name as region ".
                   "FROM ". CAVE_BOOKMARKS_TABLE ." cb ".
                   "LEFT JOIN " . CAVE_TABLE . " c ON cb.caveID = c.caveID ".
                   "LEFT JOIN " . PLAYER_TABLE . " p ON c.playerID = p.playerID ".
                   "LEFT JOIN " . REGIONS_TABLE . " r ON c.regionID = r.regionID ".
                  " LEFT JOIN " . TRIBE_TABLE . " t ON t.tribeID = p.tribeID ".
                   "WHERE cb.playerID = :playerID ".
                   "ORDER BY c.name");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

    // collect rows
    if ($sql->execute()) {
      while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
        $row['raw_name'] = unhtmlentities($row['name']);
        $result[] = $row;
        array_push($names,$row['name']);
      }
    }

    if ($extended) {
      $sql = $db->prepare("SELECT c.caveID, c.name, c.xCoord, c.yCoord, ".
               "p.playerID, p.name as playerName, p.tribeID, ".
               "r.name as region ".
               "FROM ". CAVE_TABLE ." c ".
               "LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = p.playerID ".
               "LEFT JOIN ". REGIONS_TABLE ." r ON c.regionID = r.regionID ".
               "WHERE c.playerID = :playerID ".
               "ORDER BY c.name");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

      // collect rows
      if ($sql->execute()) {
        while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
          if (!in_array($row['name'], $names)) {
            $row['raw_name'] = unhtmlentities($row['name']);
            $result[] = $row;
          }
        }
      }
    }

    return $result;
  }

  function getCaveBookmark($bookmarkID) {
    global $db;

    $retval = NULL;
    $sql = $db->prepare("SELECT cb.*, c.name, c.xCoord, c.yCoord ".
                   "FROM ". CAVE_BOOKMARKS_TABLE ." cb ".
                   "LEFT JOIN ". CAVE_TABLE ." c ON cb.caveID = c.playerID ".
                   "WHERE cb.playerID = :playerID AND cb.bookmarkID = :bookmarkID
                   LIMIT 1");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('bookmarkID', $bookmarkID, PDO::PARAM_INT);

    if ($sql->execute())
      $retval = $sql->fetch(PDO::FETCH_ASSOC);

    return $retval;
  }

  function addCaveBookmark($caveID){
    global $db;

    // no more than CAVESBOOKMARKS_MAX should be inserted
    if (sizeof($this->getCaveBookmarks()) >= CAVESBOOKMARKS_MAX)
      return CAVEBOOKMARKS_ERROR_MAXREACHED;

    // insert cave
    $sql = $db->prepare("INSERT INTO ". CAVE_BOOKMARKS_TABLE ." (playerID, caveID) ".
                   "VALUES (:playerID, :caveID)");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

    if (!$sql->execute())
      return CAVEBOOKMARKS_ERROR_INSERTFAILED;

    return CAVEBOOKMARKS_NOERROR;
  }

  function addCaveBookmarkByName($name) {

    // check cave name
    $cave = getCaveByName($name);

    // no such cave
    if (empty($cave))
      return CAVEBOOKMARKS_ERROR_NOSUCHCAVE;

    return $this->addCaveBookmark($cave['caveID']);
  }

  function addCaveBookmarkByCoord($xCoord, $yCoord){

    // check coords
    $cave = getCaveByCoords($xCoord, $yCoord);

    // no such cave
    if (!sizeof($cave))
      return CAVEBOOKMARKS_ERROR_NOSUCHCAVE;

    return $this->addCaveBookmark($cave['caveID']);
  }

  function deleteCaveBookmark($bookmarkID){
    global $db;

    // prepare query
    $sql = $db->prepare("DELETE FROM ". CAVE_BOOKMARKS_TABLE ." WHERE playerID = :playerID ".
                   "AND bookmarkID = :bookmarkID");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('bookmarkID', $bookmarkID, PDO::PARAM_INT);

    // send query
    $sql->execute();

    return ($sql->rowCount() == 1) ? CAVEBOOKMARKS_DELETED : CAVEBOOKMARKS_ERROR_DELETEFAILED;
  }
}

?>