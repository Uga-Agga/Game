<?php
/*
 * New.php - TODO
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

class Messages_New_View extends View {

  var $count;

  function Messages_New_View($language, $skin) {

    // open template
    // $this->openTemplate($language, $skin, 'Messages_New.tmpl');
  }
  
  function setCount($count) {
    $this->count = (int) $count;
  }

  function getContent() {
    return;

    // set count
    if ($this->count != 0)
      tmpl_set($this->template, '/CONTENT/YOUVEGOTMAIL/count', $this->count);

    // return parsed template
    return tmpl_parse($this->template, '/CONTENT');
  }
}

?>