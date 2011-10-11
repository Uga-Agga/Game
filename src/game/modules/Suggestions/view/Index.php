<?php
/*
 * Index.php - Index view of the Suggestions module.
 * Copyright (c) 2005  Marcus Lunzenauer/Johannes Roessel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/View.php');

class Suggestions_Index_View extends View {

  var $error;
  var $suggestions;

  function Suggestions_Index_View($language, $skin) {
    // open template
    $this->openTemplate($language, $skin, 'suggestions.tmpl');
  }

  function setError($error) {
    $this->error = $error;
  }

  function getContent($count) {
    global $template;
echo $this->error;
    switch ($this->error) {
      default:
      case SUGGESTIONS_NOERROR:
        break;
    }

    $template->addVars(array(
      'count'          => $count+1,
      'max_count'      => SUGGESTIONS_MAX,
      'no_suggestions' => ($count < SUGGESTIONS_MAX) ? false : true,
    ));
  }
}

?>