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

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Controller.php');
require_once('modules/CaveBookmarks/model/CaveBookmarks.php');
require_once('modules/CaveBookmarks/controller/Show.php');

class CaveBookmarks_Controller_Add extends Controller {

  function CaveBookmarks_Controller_Add() {
  }

  function execute($caveID, $caves) {
    global $request;

    // get model
    $model = new CaveBookmarks_Model($caveID, $caves);

    // init error
    $error = CAVEBOOKMARKS_NOERROR;

    // add CaveBookmark
    if ($request->getVar('name', '')) {
      $error = $model->addCaveBookmarkByName($request->getVar('name', ''));
    } else {
      $error = $model->addCaveBookmarkByCoord($request->getVar('xCoord', 0), $request->getVar('yCoord', 0));
    }

    // return Show Controller
    $controller = new CaveBookmarks_Controller_Show($error);
    return $controller->execute($caveID, $caves);
  }
}

?>