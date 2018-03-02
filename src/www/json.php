<?php
/*
 * json.php -
 * Copyright (c) 2012 - 2016 David Unger <david@edv-unger.com>
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

  case 'log':
    require_once("include/chat.inc.php");

    $room = Request::getVar('room', '');
    if (empty($room)) {
      echo 'no room';
      exit;
    }

    $year  = filter_var(Request::getVar('year', 0), FILTER_VALIDATE_INT, array('options' => array('min_range' => 2002, 'max_range' => 2050)));
    $month = filter_var(Request::getVar('month', 0), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 12)));
    $day   = filter_var(Request::getVar('day', 0), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 31)));

    if (!$year || !$month || !$day) {
      echo 'no date';
      exit;
    }

    if (!in_array($room, ['ugaagga', 'handel']) && !Chat::checkAccessLog($room, $_SESSION['player']->playerID)) {
      echo 'no access';
      exit;
    }

    $dir = '/opt/ejabberd/var/log/roomlogs/' . $room . '@' . Config::JABBER_MUC . '/' . $year . '/' . sprintf('%02d', $month) . '/' . sprintf('%02d', $day) . '.txt';
    if (!file_exists($dir)) {
      exit;
    }

    $search = [
      "/\[\d\d:\d\d:\d\d] (.*) betretet den Raum(.*)[\n\r]/",
      "/\[\d\d:\d\d:\d\d] (.*) verlässt den Raum(.*)[\n\r]/"
    ];
    $replace = ["", ""];
    //echo base64_encode(gzcompress(preg_replace($search, $replace, file_get_contents($dir))));
    echo nl2br(htmlentities(preg_replace($search, $replace, file_get_contents($dir))));
  break;

  default:
    echo json_encode(array('error' => 'Unbekannter Modus'));
  break;
}

?>