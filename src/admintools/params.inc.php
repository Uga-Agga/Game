<?php
/*
 * params.inc.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class Params{

  function Params(){

    $params = array_merge($_GET, $_POST);

    foreach ($params as $k=>$v){
      if (is_array($v)){
        $array = array();
        foreach ($v as $key => $value)
          $array[$key] = trim(htmlentities(strip_tags($value), ENT_QUOTES));
        $this->$k = $array;
      } else {
        $v = trim(htmlentities(strip_tags($v), ENT_QUOTES));
        $this->$k = $v;
      }
    }
  }
}
?>