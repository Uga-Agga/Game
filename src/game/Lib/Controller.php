<?php
/*
 * Controller.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set Namespace **/
namespace lib;

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################
abstract class Controller {
  private $message = array();
  public $template;

  abstract protected function getContent();
  abstract protected function submit();

  public function __construct() {
    global $template;

    // init template and set file
    if (empty($template)) {
      $this->template = new Template;
    } else {
      $this->template = &$template;
    }

    $this->template->setFile($this->templateFile);
  }

  public function getMessage() {
    return self::$message;
  }

  function setMessage($message) {
    self::$message[] = $message;
  }

  function execute() {
    $this->submit();
    $this->getContent();

    //$this->template->render();
  }
}

?>