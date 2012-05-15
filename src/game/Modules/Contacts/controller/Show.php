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
require_once('modules/Contacts/model/Contacts.php');
require_once('modules/Contacts/view/Show.php');

class Contacts_Show_Controller extends Controller {

  var $error;

  function Contacts_Show_Controller($error = CONTACTS_NOERROR) {
    $this->error = $error;
  }

  function execute($caveID, $caves) {

    // get model
    $model = new Contacts_Model();

    // create View
    $view = new Contacts_Show_View($_SESSION['player']->language, $_SESSION['player']->template);

    // set tmpl data
    $view->setContacts($model->getContacts());
    if ($this->error)
      $view->setError($this->error);

    // return view
    //return $view->toString();
    // FIXME
    return array($view->getTitle(), $view->getContent());
  }
}

?>