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

/** Set Namespace **/
namespace Modules\CaveBookmarks;

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################
define('NO_ACTION',     0x00);
define('ACTION_ADD',    0x01);
define('ACTION_UPDATE', 0x02);
define('ACTION_DELETE', 0x03);

define('CAVEBOOKMARKS_NOERROR',             0x00);
define('CAVEBOOKMARKS_ERROR_NOSUCHCAVE',    0x01);
define('CAVEBOOKMARKS_ERROR_MAXREACHED',    0x02);
define('CAVEBOOKMARKS_ERROR_INSERTFAILED',  0x03);
define('CAVEBOOKMARKS_ERROR_DELETEFAILED',  0x04);

class CaveBookmarks extends \Lib\Controller {
  public $templateFile = 'caveBookmarks.tmpl';
  private $error = CAVEBOOKMARKS_NOERROR;

  public function getContent() {
    $this->template->addVars(array(
      'cave_bookmarks' => Model\CaveBookmarks::getCaveBookmarks(),

      'cave_bookmarks_action_add'    => ACTION_ADD,
      'cave_bookmarks_action_update' => ACTION_UPDATE,
      'cave_bookmarks_action_delete' => ACTION_DELETE,
    ));
  }

  public function submit() {
    $action = \Lib\Request::getVar('action', NO_ACTION);

    $this->error = CAVEBOOKMARKS_NOERROR;

    switch ($action) {
      case ACTION_ADD:
        $messageID = Controller\Add::execute();
      break;

      case ACTION_UPDATE:
        // do something
      break;

      case ACTION_DELETE:
        $messageID = Controller\Delete::execute();
      break;
    }
  }
}

?>