<?php
/*
 * Movements.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/View.php');

class EventReports_Movements_View extends View {

  var $categories;

  function EventReports_Movements_View($language, $skin) {
    global $template;
    // init categories
    $this->categories  = NULL;

    // open template
    $template->setFile('eventReport_movement.tmpl');
  }

  function setCategories($data) {
    $this->categories = $data;
  }

  function getContent() {
    global $template;

    // set categories
    if ($this->categories)
      $template->addVars(array('categories' => $this->categories));
      
  }
}

?>