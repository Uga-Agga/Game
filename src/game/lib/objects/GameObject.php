<?php
/*
 * GameObject.php - TODO
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('lib/exception/NoSuchObjectException.php');

/**
 * TODO
 *
 * @package    lib
 * @subpackage objects
 *
 * @author Marcus Lunzenauer
 */
abstract class GameObject {

  protected $id;

  public function __construct($id) {

    // 0 <= id < MAX_OF_THIS_TYPE
    if ($id < 0 || $id >= ua_get_length($this->getType()))
      throw new NoSuchObjectException();

    $this->id = $id;
  }

  public function getAmountInCave(Cave $cave) {
    $dbfield = $this->getDBField();
    return (double) $cave->$dbfield;
  }

  public function getDBField() {
    return ua_object_dbfieldname($this->getType(), $this->id);
  }

  public function getDescription() {
    return ua_object_description($this->getType(), $this->id);
  }

  public function getID() {
    return $this->id;
  }

  public function getMaxLevel() {
    return ua_object_maxlevel($this->getType(), $this->id);
  }

  public function getName() {
    return ua_object_name($this->getType(), $this->id);
  }

  abstract protected function getType();

  public function isHidden() {
    return (boolean) ua_object_nodocumentation($this->getType(), $this->id);
  }
}

?>