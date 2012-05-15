<?php
/*
 * Add.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set Namespace **/
namespace Modules\CaveBookmarks\Controller;

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################
class Add {
  public static function execute() {
    if (\Lib\Request::getVar('name', '')) {
      return \Modules\CaveBookmarks\Model\CaveBookmarks::addCaveBookmarkByName(\Lib\Request::getVar('name', ''));
    } elseif (\Lib\Request::getVar('xCoord', 0) && \Lib\Request::getVar('yCoord', 0)) {
      return \Modules\CaveBookmarks\Model\CaveBookmarks::addCaveBookmarkByCoord(\Lib\Request::getVar('xCoord', 0), \Lib\Request::getVar('yCoord', 0));
    } else {
      return CAVEBOOKMARKS_ERROR_INSERTFAILED;
    }
  }
}

?>