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
require_once('modules/Contacts/model/Contacts.php');
require_once('modules/Contacts/controller/Show.php');

class Contacts_Add_Controller extends Controller {

  function Contacts_Add_Controller() {
  }

  function execute($caveID, $caves) {
    global $request;

    // get model
    $model = new Contacts_Model($caveID, $caves);

    // init error
    $error = CONTACTS_NOERROR;

    // add contact
    $contact = $request->getVar('contact', '');
    $error = $model->addContact($contact);

    // return Show Controller
    $controller = new Contacts_Show_Controller($error);
    return $controller->execute($caveID, $caves);
  }
}

?>