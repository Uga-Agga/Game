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
    $this->openTemplate($language, $skin, 'Contacts_Show.ihtml');
  }

  function setContacts($contacts) {
    $this->contacts = $contacts;
  }

  function setError($error) {
    $this->error = $error;
  }

  function getContent() {
    
    if (sizeof($this->contacts))
      tmpl_set($this->template, '/CONTENT/CONTACTS/CONTACT', $this->contacts);
    else
      tmpl_set($this->template, '/CONTENT/NOCONTACTS/iterate', '');

    switch ($this->error) {
      case CONTACTS_ERROR_NOSUCHPLAYER:
        tmpl_set($this->template, '/CONTENT/ERROR_NOSUCHPLAYER/iterate', '');
        break;

      case CONTACTS_ERROR_MAXREACHED:
        tmpl_set($this->template, '/CONTENT/ERROR_MAXREACHED/entries', CONTACTS_MAX);
        break;

      case CONTACTS_ERROR_INSERTFAILED:
        tmpl_set($this->template, '/CONTENT/ERROR_INSERTFAILED/iterate', '');
        break;

      case CONTACTS_ERROR_DELETEFAILED:
        tmpl_set($this->template, '/CONTENT/ERROR_DELETEFAILED/iterate', '');
        break;
        
      case CONTACTS_ERROR_DUPLICATE_ENTRY:
        tmpl_set($this->template, '/CONTENT/ERROR_DUPLICATE_ENTRY/iterate', '');
        break;

      default:
      case CONTACTS_NOERROR:
        break;
    }

    // return parsed template
    return tmpl_parse($this->template, '/CONTENT');
  }
}

?>