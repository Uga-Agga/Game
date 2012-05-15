<?php
/*
 * Delete.php -
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
class Delete {
  public static function execute() {
    return \Modules\CaveBookmarks\Model\CaveBookmarks::deleteCaveBookmark(\Lib\Request::getVar('bookmarkID', 0));
  }
}

?>