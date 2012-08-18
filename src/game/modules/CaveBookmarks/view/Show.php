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

  function getContent() {
    global $template;

    $template->addVar('cave_bookmarks', $this->cavebookmarks);

    $message = array();
    switch ($this->error) {
      case CAVEBOOKMARKS_ERROR_NOSUCHCAVE:
        $message = array('type' => 'error', 'message' => 'Diese Höhle existiert nicht.');
        break;

      case CAVEBOOKMARKS_ERROR_MAXREACHED:
        $message = array('type' => 'error', 'message' => sprintf('Sie dürfen nicht mehr als %d Einträge in ihre Liste aufnehmen.', CAVESBOOKMARKS_MAX));
        break;

      case CAVEBOOKMARKS_ERROR_INSERTFAILED:
        $message = array('type' => 'error', 'message' => 'Die Höhle konnte nicht eingetragen werden oder war bereits vorhanden.');
        break;

      case CAVEBOOKMARKS_ERROR_DELETEFAILED:
        $message = array('type' => 'error', 'message' => 'Der Eintrag konnte nicht entfernt werden');
        break;

      default:
      case CAVEBOOKMARKS_NOERROR:
        break;
    }

    if (sizeof($message)) {
      $template->addVar('status_msg', $message);
    }
  }
}

?>