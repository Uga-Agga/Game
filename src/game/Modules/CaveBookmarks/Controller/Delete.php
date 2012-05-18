<?php
/*
 * Delete.php -
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
class Delete {
  public static function execute() {
    $messageID = CaveBookmarksModel::deleteCaveBookmark(\Lib\Request::getVar('bookmarkID', 0));

    if ($messageID == CAVEBOOKMARKS_NOERROR) {
      return CAVEBOOKMARKS_SUCCESS_DELETE;
    }

    return $messageID;
  }
}

?>