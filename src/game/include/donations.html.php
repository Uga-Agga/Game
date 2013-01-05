<?php
/*
 * donations.html.php -
 * Copyright (c) 2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function donations_getContent() {
  global $template;

  // open template
  $template->setFile('donations.tmpl');

  $template->addVars(array(
    'player_id'   => $_SESSION['player']->playerID,
    'player_name' => $_SESSION['player']->name,
  ));
}

?>