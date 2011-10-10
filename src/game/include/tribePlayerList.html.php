<?php
/*
 * tribePlayerList.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribePlayerList_getContent($caveID, $tribe) {
  global $db, $template;

  $template->setFile('tribePlayerList.tmpl');
  $template->setShowRresource(false);
  $template->addVars(array(
    'show_page'  => true,
    'tribe_name' => $tribe,
  ));

  if (empty($tribe)) {
    $template->addVars(array(
      'status_msg' => array('type' => 'error', 'message' => 'Dieser Stamm wurde nicht gefunden.'),
      'show_page'  => false,
    ));
  }

  $playerList = tribe_getPlayerList($tribe);
  foreach($playerList AS $id => $playerData) {
    if (!empty($playerData['awards'])) {
      $playerData['awards'] = explode('|', $playerData['awards']);

      $awards = array();
      foreach ($playerData['awards'] AS $tag) {
        $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
      }

      $playerData['award'] = $awards;
    }

    foreach($playerData as $k => $v) {
      if ($k == 'awards' || $k == 'religion') {
        continue;
      }

      if (!$v) {
        $playerData[$k] = _('k.A.');
      }
    }

    $playerList[$id] = $playerData;
  }

  $template->addVar('tribe_player_list', $playerList);
}

?>