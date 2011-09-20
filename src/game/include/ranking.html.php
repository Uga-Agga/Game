<?php
/*
 * ranking.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

define("RANKING_ROWS", 20);

function ranking_getContent($caveID, $offset) {
  global $template, $no_resource_flag;

  // open template
  $template->setFile('rankingPlayer.tmpl');

  $no_resource_flag = 1;

  $religions = ranking_getReligiousDistribution();
  
  if (!array_key_exists('uga', $religions)) $religions['uga'] = 0;
  if (!array_key_exists('agga', $religions)) $religions['agga'] = 0;
  
  if($religions['uga']+$religions['agga'] != 0) {
    $ugapercent = round($religions['uga']/($religions['uga'] + $religions['agga'])*100);
    $aggapercent = round($religions['agga']/($religions['uga'] + $religions['agga'])*100);
  } else {
    $ugapercent = 0;
    $aggapercent = 0;
  }
  
  $row = ranking_getRowsByOffset($caveID, $offset);

  $template->addVars(array(
    'offset_up'   => (($offset - RANKING_ROWS) > 0) ? ($offset - RANKING_ROWS) : 0,
    'offset_down' => ($offset + RANKING_ROWS),
    'religious' =>  array(
      'ugapercent' => $ugapercent,
      'aggapercent' => $aggapercent
    ),
    'row'       => $row,
  ));
}

function rankingTribe_getContent($caveID, $offset){
  global $template, $no_resource_flag;

  $no_resource_flag = 1;

  $row = rankingTribe_getRowsByOffset($caveID, $offset);

  // open template
  $template->setFile('rankingTribe.tmpl');

  $template->addVars(array(
    'offset_up'   => (($offset - RANKING_ROWS) > 0) ? ($offset - RANKING_ROWS) : 0,
    'offset_down' => ($offset + RANKING_ROWS),
    'row'       => $row,
  ));
}

?>