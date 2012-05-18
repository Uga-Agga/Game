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
namespace Modules\CaveBookmarks\Controller;

use \Modules\CaveBookmarks\Model\CaveBookmarks as CaveBookmarksModel;

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################
class Add {
  public static function execute() {
    if (\Lib\Request::getVar('name', '')) {
      $messageID = CaveBookmarksModel::addCaveBookmarkByName(\Lib\Request::getVar('name', ''));
    } elseif (\Lib\Request::getVar('xCoord', 0) && \Lib\Request::getVar('yCoord', 0)) {
      $messageID = CaveBookmarksModel::addCaveBookmarkByCoord(\Lib\Request::getVar('xCoord', 0), \Lib\Request::getVar('yCoord', 0));
    } else {
      $messageID = CAVEBOOKMARKS_ERROR_INSERTFAILED;
    }

    if ($messageID == CAVEBOOKMARKS_NOERROR) {
      return CAVEBOOKMARKS_SUCCESS_ADD;
    }

    return $messageID;
  }
}

?>