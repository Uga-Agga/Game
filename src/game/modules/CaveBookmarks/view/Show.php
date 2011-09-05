<?php
/*
 * Show.php -
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

class CaveBookmarks_View_Show extends View {

  var $cavebookmarks;
  var $error;

  function CaveBookmarks_View_Show($language, $skin) {

    // init cavebookmarks
    $this->cavebookmarks = array();

    // open template
    $this->openTemplate($language, $skin, 'caveBookmarks.tmpl');
  }

  function setCaveBookmarks($cavebookmarks) {
    $this->cavebookmarks = $cavebookmarks;
  }

  function setError($error) {
    $this->error = $error;
  }

  function getContent(){

    if (sizeof($this->cavebookmarks))
      tmpl_set($this->template, '/CONTENT/CAVEBOOKMARKS/CAVEBOOKMARK', $this->cavebookmarks);
    else
      tmpl_set($this->template, '/CONTENT/NOCAVEBOOKMARKS/iterate', '');

    switch ($this->error){
      case CAVEBOOKMARKS_ERROR_NOSUCHCAVE:
        tmpl_set($this->template, '/CONTENT/ERROR_NOSUCHCAVE/iterate', '');
        break;

      case CAVEBOOKMARKS_ERROR_MAXREACHED:
        tmpl_set($this->template, '/CONTENT/ERROR_MAXREACHED/entries', CAVESBOOKMARKS_MAX);
        break;

      case CAVEBOOKMARKS_ERROR_INSERTFAILED:
        tmpl_set($this->template, '/CONTENT/ERROR_INSERTFAILED/iterate', '');
        break;

      case CAVEBOOKMARKS_ERROR_DELETEFAILED:
        tmpl_set($this->template, '/CONTENT/ERROR_DELETEFAILED/iterate', '');
        break;

      default:
      case CAVEBOOKMARKS_NOERROR:
        break;
    }

    // return parsed template
    return tmpl_parse($this->template, '/CONTENT');
  }
}

?>