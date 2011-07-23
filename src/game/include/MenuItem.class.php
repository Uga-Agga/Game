<?php
/*
 * MenuItem.class.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

DEFINE('MENU_MAIN', 'main.php');
DEFINE('MENU_MAINFRAME', 'mainFrame');

class MenuItem {
  var $link;
  var $pic;
  var $alt;
  var $target;

  function getTmplData(){
    return array('link'   => $this->link,
                 'pic'    => $this->pic,
                 'alt'    => $this->alt,
                 'target' => $this->target);
  }
}

class InGameMenuItem extends MenuItem {

  function InGameMenuItem($modus, $alt, $task = NULL){
    $this->alt    = $alt;
    $this->target = MENU_MAINFRAME;

    if (!$task){
      $this->link = sprintf('%s?modus=%s', MENU_MAIN, $modus);
      $this->pic  = $modus;
    } else {
      $this->link = sprintf('%s?modus=%s&amp;task=%s', MENU_MAIN, $modus, $task);
      $this->pic  = sprintf('%s_%s', $modus, $task);
    }
  }
}

class OffGameMenuItem extends MenuItem {

  function OffGameMenuItem($link, $pic, $alt){
    $this->link   = $link;
    $this->pic    = $pic;
    $this->alt    = $alt;
    $this->target = '_blank';
  }
}

?>