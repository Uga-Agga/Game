<?php
/*
 * Index.php - Index view of the Donations module.
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/View.php');

class Donations_Index_View extends View {

  var $error;

  function Donations_Index_View($language, $skin) {

    // open template
    $this->openTemplate($language, $skin, 'Donations_Index.ihtml');
  }

  function setError($error) {
    $this->error = $error;
  }

  function getContent() {

    switch ($this->error) {
//      case CONTACTS_ERROR_NOSUCHPLAYER:
//        tmpl_set($this->template, '/CONTENT/ERROR_NOSUCHPLAYER/iterate', '');
//        break;

      default:
      case CONTACTS_NOERROR:
        break;
    }

    // set name and id
    tmpl_set($this->template, '/CONTENT',
             array('name' => urlencode($_SESSION['player']->name),
                   'id'   => $_SESSION['player']->playerID));

    // return parsed template
    return tmpl_parse($this->template, '/CONTENT');
  }
}

?>