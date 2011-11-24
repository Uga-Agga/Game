<?php
/*
 * Messages.php - Messages module.
 * Copyright (c) 2005  Marcus Lunzenauer
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

function messages_main($caveID, $caves) {
  global $request;

  // initialize controller
  $controller = NULL;

  // get current task
  $task = $request->getVar('task', '');

  switch ($task) {

    default:
    case 'New':
      require_once('modules/Messages/controller/New.php');
      $controller = new Messages_New_Controller();
      break;
  }

  return $controller === NULL ? '' : $controller->execute($caveID, $caves);
}

?>