<?
/*
 * Menu.lib.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class Menu {

  var $name  = "";
  var $items = array();

  function Menu($name){
    $this->name   = $name;
  }

  function addItem($item){
    $this->items[] = $item;
  }

  function getMenu(){
    $result = array('name' => $this->name);
    if (sizeof($this->items)){
      $result['ITEM'] = array();
      foreach ($this->items AS $item){
        $result['ITEM'][] = $item->getItem();
      }
    }
    return $result;
  }
}
?>