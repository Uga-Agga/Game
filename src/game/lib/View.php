<?php
/*
 * View.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

class View {

  var $template;

  function openTemplate($language, $skin, $templateFile){
    global $config, $template;

    $this->template = $template->setFile($templateFile);
  }
  
  function getTitle(){
    return $this->template ? tmpl_parse($this->template, '/TITLE') : __CLASS__;
  }
}

?>