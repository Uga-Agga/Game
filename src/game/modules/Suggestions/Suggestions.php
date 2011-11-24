<?php
/*
 * Suggestions.php - Show suggestions page.
 * Copyright (c) 2005  Marcus Lunzenauer/Johannes Roessel
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

function suggestions_main($caveID, $caves) {
  global $request;

  // initialize controller
  $controller = NULL;

  // get current task
  $task = $request->getVar('task', '');

  switch ($task) {

    default:
    case 'Index':
      require_once('modules/Suggestions/controller/Index.php');
      $controller = new Suggestions_Index_Controller();
      break;
    case 'Send':
      require_once('modules/Suggestions/controller/Send.php');
      $controller = new Suggestions_Send_Controller();
      break;
  }

  return $controller === NULL ? '' : $controller->execute($caveID, $caves);
}

?>