<?php
/*
 * module_goverments.php - 
 * Copyright (c) 2003  OGP-Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function governments_getMenu() {
  $result[] = array('link' => "?modus=governments", 'content' => "Regierungen");
  return $result;
}

function governments_getContent(){
  global $template;

  // open template
  $template->setFile('governments.tmpl');
  $template->addVar('leaderDeterminationList', $GLOBALS['leaderDeterminationList']);

  foreach($GLOBALS['governmentList'] as $governmentData) {
    $GLOBALS['governmentList'][$governmentData['leaderDeterminationID']]['leaderDetermination'] = $GLOBALS['leaderDeterminationList'][$governmentData['leaderDeterminationID']]['name'];
  }
  $template->addVar('government_data', $GLOBALS['governmentList']);
}
?>