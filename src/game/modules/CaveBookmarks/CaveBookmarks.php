<?php
/*
 * CaveBookmarks.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################

require_once('lib/Controller.php');
require_once('modules/CaveBookmarks/model/CaveBookmarks.php');

define('ACTION_NO',     0x00);
define('ACTION_INSERT', 0x01);
define('ACTION_UPDATE', 0x02);
define('ACTION_DELETE', 0x03);

class CaveBookmarks extends Controller {
  public $templateFile = 'caveBookmarks.tmpl';
  private $error = CAVEBOOKMARKS_NOERROR;

  public function getContent() {
    $model = new CaveBookmarks_Model();
    $this->template->addVar('cave_bookmarks', $model->getCaveBookmarks());
  }

  public function submit() {
    $action = Request::getVar('action', ACTION_NO);

    $this->error = CAVEBOOKMARKS_NOERROR;

    switch ($action) {
      case ACTION_NO:
        return;
      break;

      case ACTION_INSERT:
        if (Request::getVar('name', '')) {
          $this->error = $model->addCaveBookmarkByName(Request::getVar('name', ''));
        } elseif (Request::getVar('xCoord', 0) && Request::getVar('yCoord', 0)) {
          $this->error = $model->addCaveBookmarkByCoord(Request::getVar('xCoord', 0), Request::getVar('yCoord', 0));
        } else {
          $this->error = CAVEBOOKMARKS_ERROR_INSERTFAILED;
        }
      break;

      case ACTION_UPDATE:
        // do something
      break;

      case ACTION_DELETE:
        $bookmarkID = Request::getVar('bookmarkID', 0);
        $this->error = $model->deleteCaveBookmark($bookmarkID);
      break;
    }
  }
}

?>