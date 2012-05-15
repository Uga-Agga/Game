<?php
/*
 * EventReports.php -
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

function eventReports_main($caveID, $caves) {

  // initialize controller
  $controller = NULL;

  // get current task
  $task = Request::getVar('task', '');

  switch ($task) {

    // show main page
    case 'Schedule':
      //require_once('modules/EventReports/controller/Schedule.php');
      //$controller = new EventReports_Schedule_Controller();
      break;

    // show change confirmation page
    default:
    case 'Movements':
      require_once('modules/EventReports/controller/Movements.php');
      $controller = new EventReports_Movements_Controller();
      break;
  }

  return $controller === NULL ? '' : $controller->execute($caveID, $caves);
}

?>
