<?php
/*
 * CaveBookmarks.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 * Copyright (c) 2012  David Unger <unger.dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set Namespace **/
namespace Modules\CaveBookmarks\Model;

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################
class CaveBookmarks extends \Lib\Model {
  public static function getCaveBookmarks($extended=false) {
    global $db;

    // init return value
    $ret = $names = array();

    // prepare query
    $sql = $db->prepare("SELECT cb.*, c.name, c.xCoord, c.yCoord, p.playerID, p.name as playerName, p.tribe, r.name as region
                         FROM " . CAVE_BOOKMARKS_TABLE . " cb
                           LEFT JOIN " . CAVE_TABLE . " c ON cb.caveID = c.caveID
                           LEFT JOIN " .PLAYER_TABLE . " p ON c.playerID = p.playerID
                           LEFT JOIN " . REGIONS_TABLE . " r ON c.regionID = r.regionID
                         WHERE cb.playerID = :playerID
                         ORDER BY c.name");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, \PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    // collect rows
    if ($sql->execute()) {
      while($row = $sql->fetch(\PDO::FETCH_ASSOC)) {
        $row['raw_name'] = unhtmlentities($row['name']);
        $ret[] = $row;
        array_push($names, $row['name']);
      }
      $sql->closeCursor();
    }

    if ($extended) {
      $sql = $db->prepare("SELECT c.caveID, c.name, c.xCoord, c.yCoord, p.playerID, p.name as playerName, p.tribe, r.name as region
                           FROM " . CAVE_TABLE . " c
                             LEFT JOIN ". PLAYER_TABLE ." p ON c.playerID = p.playerID
                             LEFT JOIN ". REGIONS_TABLE ." r ON c.regionID = r.regionID
                           WHERE c.playerID = :playerID
                           ORDER BY c.name");
      $sql->bindValue('playerID', $_SESSION['player']->playerID, \PDO::PARAM_INT);

      // collect rows
      if ($sql->execute()) {
        while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
          if (!in_array($row['name'], $names)) {
            $row['raw_name'] = unhtmlentities($row['name']);
            $ret[] = $row;
          }
        }
        $sql->closeCursor();
      }
    }

    return $ret;
  }

  public static function getCaveBookmark($bookmarkID) {
    global $db;

    $ret = NULL;

    if (empty($bookmarkID)) {
      return $ret;
    }

    $sql = $db->prepare("SELECT cb.*, c.name, c.xCoord, c.yCoord
                         FROM " . CAVE_BOOKMARKS_TABLE . " cb
                           LEFT JOIN " . CAVE_TABLE . " c ON cb.caveID = c.playerID
                         WHERE cb.playerID = :playerID
                           AND cb.bookmarkID = :bookmarkID 
                         LIMIT 1");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->bindValue('bookmarkID', $bookmarkID, PDO::PARAM_INT);
    if (!$sql->execute()) return $ret;

    $ret = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $ret;
  }

  public static function addCaveBookmark($caveID){
    global $db;

    // no more than CAVESBOOKMARKS_MAX should be inserted
    if (sizeof(self::getCaveBookmarks()) >= CAVESBOOKMARKS_MAX) return CAVEBOOKMARKS_ERROR_MAXREACHED;

    // insert cave
    $sql = $db->prepare("INSERT INTO " . CAVE_BOOKMARKS_TABLE . "
                           (playerID, caveID)
                         VALUES
                           (:playerID, :caveID)");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, \PDO::PARAM_INT);
    $sql->bindValue('caveID', $caveID, \PDO::PARAM_INT);
    if (!$sql->execute() || !$sql->rowCount() == 1) {
      return CAVEBOOKMARKS_ERROR_INSERTFAILED;
    }

    return CAVEBOOKMARKS_NOERROR;
  }

  public static function addCaveBookmarkByName($name) {
    if (!empty($name)) return CAVEBOOKMARKS_ERROR_NOSUCHCAVE;

    // check cave name
    $cave = getCaveByName($name);

    // no such cave
    if (empty($cave)) return CAVEBOOKMARKS_ERROR_NOSUCHCAVE;

    return $this->addCaveBookmark($cave['caveID']);
  }

  public static function addCaveBookmarkByCoord($xCoord, $yCoord) {
    if (!intval($xCoord) || !intval($yCoord)) return CAVEBOOKMARKS_ERROR_NOSUCHCAVE;

    // check coords
    $cave = \Lib\Model\Cave::getCaveByCoords($xCoord, $yCoord);

    // no such cave
    if (empty($cave)) return CAVEBOOKMARKS_ERROR_NOSUCHCAVE;

    return self::addCaveBookmark($cave['caveID']);
  }

  public static function deleteCaveBookmark($bookmarkID) {
    global $db;

    if (empty($bookmarkID)) return CAVEBOOKMARKS_ERROR_INSERTFAILED;

    // prepare query
    $sql = $db->prepare("DELETE FROM ". CAVE_BOOKMARKS_TABLE ."
                         WHERE playerID = :playerID
                           AND bookmarkID = :bookmarkID");
    $sql->bindValue('playerID', $_SESSION['player']->playerID, \PDO::PARAM_INT);
    $sql->bindValue('bookmarkID', $bookmarkID, \PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return CAVEBOOKMARKS_ERROR_DELETEFAILED;
    }

    return CAVEBOOKMARKS_NOERROR;
  }
}

?>