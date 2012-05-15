<?php
/*
 * Index.php - Show the donations page.
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Controller.php');
require_once('modules/Donations/view/Index.php');

class Donations_Index_Controller extends Controller {

  var $error;

  function Donations_Index_Controller($error = NULL) {
    $this->error = $error;
  }

  function execute($caveID, $caves) {

    // create View
    $view = new Donations_Index_View($_SESSION['player']->language,
                                     $_SESSION['player']->template);

    if ($this->error)
      $view->setError($this->error);

    return array($view->getTitle(), $view->getContent());
  }
}

?>