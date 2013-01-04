<?php
/*
 * json.php -
 * Copyright (c) 2012 David Unger <unger.dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1); 

require_once("config.inc.php");

require_once("include/page.inc.php");
require_once("include/db.functions.php");

page_start();
global $db;

$modus = Request::getVar('modus', '');
$term = Request::getVar('term', '').'%';

switch ($modus) {
  case 'caves':
    $caves = array();
    $sql = $db->prepare("SELECT name
                         FROM " . CAVE_TABLE . "
                         WHERE name LIKE :term
                         ORDER BY name ASC
                         LIMIT 0,5");
    $sql->bindValue('term', $term, PDO::PARAM_STR);
    if (!$sql->execute()) return '';
    while($row = $sql->fetch(PDO::FETCH_ASSOC)){
      $caves[] = $row['name'];
    }
    $sql->closeCursor();

    echo json_encode($caves);
  break;

  case 'player':
    $player = array();
    $sql = $db->prepare("SELECT name
                         FROM " . PLAYER_TABLE . "
                         WHERE name LIKE :term
                         ORDER BY name ASC
                         LIMIT 0,5");
    $sql->bindValue('term', $term, PDO::PARAM_STR);
    if (!$sql->execute()) return '';
    while($row = $sql->fetch(PDO::FETCH_ASSOC)){
      $player[] = $row['name'];
    }
    $sql->closeCursor();

    echo json_encode($player);
  break;

  case 'tribe':
    $tribes = array();
    $sql = $db->prepare("SELECT tag
                         FROM " . TRIBE_TABLE . "
                         WHERE tag LIKE :term
                         ORDER BY tag ASC
                         LIMIT 0,5");
    $sql->bindValue('term', $term, PDO::PARAM_STR);
    if (!$sql->execute()) return '';
    while($row = $sql->fetch(PDO::FETCH_ASSOC)){
      $tribes[] = $row['tag'];
    }
    $sql->closeCursor();

    echo json_encode($tribes);
  break;

  default:
    echo json_encode(array('error' => 'Unbekannter Modus'));
  break;
}

?>