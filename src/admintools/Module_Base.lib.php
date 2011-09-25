<?
/*
 * Module_Base.lib.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class Module_Base {

  var $modi    = array();
  var $isActive = true;
  var $weight   = 0;

  function Module_Base(){
  }

  function checkModus($modus){
    return in_array($modus, $this->modi);
  }

  function getModi(){
    return $this->modi;
  }

  function getContent($modus){
    return null;
  }

  function getMenu(){
    return null;
  }

  function isActive(){
    return $this->isActive;
  }

  function getWeight(){
    return $this->weight;
  }

  function getName(){
    return get_class($this);
  }
}
?>