<?php
/*
 * chatlog.html.php -
 * Copyright (c) 2016 David Unger <david@edv-unger.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/** This function returns basic award details
 *
 *  @param tag       the current award's tag
 */
function chatlog_getDetail() {
  global $db, $template;

  // open template
  $template->setFile('chatLog.tmpl');

  $month = [
     1 => 'Januar',
     2 => 'Februar',
     3 => 'März',
     4 => 'April',
     5 => 'Mai',
     6 => 'Juni',
     7 => 'Juli',
     8 => 'August',
     9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Dezember',
  ];

  $playerRooms = Chat::getPossibleAccessLog($_SESSION['player']->playerID);
  $playerRooms['ugaagga'] = true;
  $playerRooms['handel'] = true;

  $chatRoomLogs = array();
  foreach ($playerRooms as $room => $tmp) {
    $dir = '/opt/ejabberd/var/log/roomlogs/' . $room . '@' . Config::JABBER_MUC;
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir)
    );
    $php_files = new RegexIterator($iterator, '/\.txt$/'); // Dateiendung ".php"

    $logPath = array();
    foreach ($php_files as $file) {
      if (!$file->isFile()) {
        continue;
      }

      $logPath[filemtime($file->getPathname())] = array(
        'time' => filemtime($file->getPathname()),
        'path' => $file->getPathname()
      );
    }
    arsort($logPath);

    foreach ($logPath as $log) {
      $path = array_slice(explode('/', $log['path']), -3, 3, false);
      $chatRoomLogs[$room ][(int)$path[0]][(int)$path[1]][(int)str_replace('.txt', '', $path[2])] = true;
    }
  }
  
  $template->addVars(array(
    'monthName' => $month,
    'logs'      => $chatRoomLogs
  ));
}

?>