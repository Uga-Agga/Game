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
    $this->openTemplate($language, $skin, 'Suggestions_Index.ihtml');
  }

  function setError($error) {
    $this->error = $error;
  }

  function getContent($count) {

    switch ($this->error) {
      default:
      case SUGGESTIONS_NOERROR:
        break;
    }

    // insert maximum number of suggestions
    tmpl_set($this->template, '/CONTENT',
             array('max_count' => SUGGESTIONS_MAX));

    // output suggestion box
    if ($count < SUGGESTIONS_MAX) {
      tmpl_set($this->template, '/CONTENT/SUGGESTIONS/SUGGESTION',
               array('num' => $count+1, 'max_count' => SUGGESTIONS_MAX));
    } else {
      tmpl_set($this->template, '/CONTENT/MAX_SUGGESTIONS_REACHED', array('iterate' => ''));
    }

    // return parsed template
    return tmpl_parse($this->template, '/CONTENT');
  }
}

?>