<?php
/*
 * Index.php - Index view of the Donations module.
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/View.php');

class Donations_Index_View extends View {

  var $error;

  function Donations_Index_View($language, $skin) {

    // open template
    $this->openTemplate($language, $skin, 'donations.tmpl');
  }

  function setError($error) {
    $this->error = $error;
  }

  function getContent() {
    global $template;

    // set name and id
    $template->addVar('player_name', urlencode($_SESSION['player']->name));
    $template->addVar('player_id', $_SESSION['player']->playerID);
  }
}

?>