<?php
/*
 * Show.php -
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
require_once('modules/CaveBookmarks/view/Show.php');

class CaveBookmarks_Controller_Show extends Controller {
  function execute($caveID, $caves) {
    // get model
    $model = new CaveBookmarks_Model();

    // create View
    $view = new CaveBookmarks_View_Show($_SESSION['player']->language, $_SESSION['player']->template);

    // set tmpl data
    $view->setCaveBookmarks($model->getCaveBookmarks());
    if ($this->error)
      $view->setError($this->error);

    // return view
    //return $view->toString();
    // FIXME
    return array($view->getTitle(), $view->getContent());
  }
}

?>