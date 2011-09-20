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

require_once('lib/View.php');

class Contacts_Show_View extends View {

  var $contacts;
  var $error;

  function Contacts_Show_View($language, $skin) {

    // init contacts
    $this->contacts = array();

    // open template
    $this->openTemplate($language, $skin, 'contactBookmarks.tmpl');
  }

  function setContacts($contacts) {
    $this->contacts = $contacts;
  }

  function setError($error) {
    $this->error = $error;
  }

  function getContent() {
    global $template;

    $template->addVar('contacts', $this->contacts);

    $message = array();
    switch ($this->error) {
      case CONTACTS_ERROR_NOSUCHPLAYER:
        $message = array('type' => 'error', 'message' => _('Dieser Spieler existiert nicht.'));
        break;

      case CONTACTS_ERROR_MAXREACHED:
        $message = array('type' => 'error', 'message' => sprintf('Sie dürfen nicht mehr als {entries} Einträge in ihr Adressbuch aufnehmen.', CONTACTS_MAX));
        break;

      case CONTACTS_ERROR_INSERTFAILED:
        $message = array('type' => 'error', 'message' => _('Der Kontakt konnte nicht eingetragen werden oder war bereits vorhanden.'));
        break;

      case CONTACTS_ERROR_DELETEFAILED:
        $message = array('type' => 'error', 'message' => _('Der Eintrag konnte nicht entfernt werden.'));
        break;

      case CONTACTS_ERROR_DUPLICATE_ENTRY:
        $message = array('type' => 'error', 'message' => _('Der Eintrag ist schon in der Liste vorhanden.'));
        break;

      default:
      case CONTACTS_NOERROR:
        break;
    }

    if (sizeof($message)) {
      $template->addVar('status_msg', $message);
    }
  }
}

?>