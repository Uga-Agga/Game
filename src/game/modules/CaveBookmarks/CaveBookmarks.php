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

/**
 * This function dispatches the task at issue to the respective function.
 */

function cavebookmarks_main($caveID, $caves) {

  // initialize controller
  $controller = NULL;

  // get current task
  $task = request_var('task', '');

  switch ($task) {

    default:
    case 'Show':
      require_once('modules/CaveBookmarks/controller/Show.php');
      $controller = new CaveBookmarks_Controller_Show();
      break;

    case 'Delete':
      require_once('modules/CaveBookmarks/controller/Delete.php');
      $controller = new CaveBookmarks_Controller_Delete();
      break;

    case 'Add':
      require_once('modules/CaveBookmarks/controller/Add.php');
      $controller = new CaveBookmarks_Controller_Add();
      break;
  }

  return $controller === NULL ? '' : $controller->execute($caveID, $caves);
}

?>