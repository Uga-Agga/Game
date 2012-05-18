<?php
/*
 * Add.php -
 * Copyright (c) 2012 David Unger <unger.dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set Namespace **/
namespace Modules\CaveBookmarks;

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################
class Messages {
  public static function getMessage($messageID) {
    if (empty($messageID)) {
      return array('type' => 'error', 'message' => 'Es wurde keine NachrichtenID übergeben!');
    }

    switch ($messageID) {
      case CAVEBOOKMARKS_SUCCESS_ADD:
        return array('type' => 'success', 'message' => 'Die Höhle wurde erfolgreich hinzugefügt.');
      break;

      case CAVEBOOKMARKS_SUCCESS_DELETE:
        return array('type' => 'success', 'message' => 'Die Höhle wurde erfolgreich gelöscht.');
      break;

      case CAVEBOOKMARKS_ERROR_NOSUCHCAVE:
        return array('type' => 'error', 'message' => 'Die gewünschte Höhle wurde nicht gefunden.');
      break;

      case CAVEBOOKMARKS_ERROR_MAXREACHED:
        return array('type' => 'error', 'message' => 'Du hast bereits die maximale Anzahl der Höhlen erreicht.');
      break;

      case CAVEBOOKMARKS_ERROR_INSERTFAILED:
        return array('type' => 'error', 'message' => 'Beim einfügen der Höhle ist ein Fehler aufgetreten.');
      break;

      case CAVEBOOKMARKS_ERROR_DELETEFAILED:
        return array('type' => 'error', 'message' => 'Die wünschte Höhle konnte wegen eines Fehlers nicht aus der Liste gelöscht werden.');
      break;
    }
  }
}