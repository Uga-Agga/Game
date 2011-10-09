<?php
/*
 * Menu_Item.lib.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class Menu_Item {

  var $link   = "#";
  var $text   = "link";
  var $target = "";

  function Menu_Item($link, $text, $target = ""){
    $this->link   = $link;
    $this->text   = $text;
    $this->target = $target;
  }

  function getItem(){
    $result = array('link' => $this->link, 'text' => $this->text);
    if ($this->target)
      $result['target'] = array('target' => $this->target);
    return $result;
  }
}
?>